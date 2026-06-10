<?php

declare(strict_types=1);

namespace App\Scrapers\Profiles\Reports;

use App\Scrapers\Contracts\ScraperProfileInterface;
use App\Scrapers\Exports\Contracts\ExporterInterface;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Produces an eight-sheet workbook from scraped apartment data.
 * Each sheet answers a specific question a market entry team needs:
 *
 *   Sheet 1 - All Listings       : full raw dataset for analyst use
 *   Sheet 2 - Market Overview    : executive headline statistics
 *   Sheet 3 - District Analysis  : pricing and supply by district
 *   Sheet 4 - Room Type Analysis : product mix and pricing by bedroom count
 *   Sheet 5 - Building Stock     : construction type and condition breakdown
 *   Sheet 6 - Price Distribution : market segmentation by price bracket
 *   Sheet 7 - Agency Intelligence: market participant map, owner vs agency split
 *   Sheet 8 - Floor & Size       : granular pricing factors for valuation
 *
 * Registered in ListAmProfile::getExports() as 'colliers_report'.
 * ExportManager resolves it via config/scraper.php exporters map.
 */
class ColliersExcelReport implements ExporterInterface
{
    /**
     * Generate the eight-sheet Colliers report.
     */
    public function export(array $data, ScraperProfileInterface $profile): string
    {
        $path = $this->buildPath();

        Excel::store(new class($data) implements WithMultipleSheets
        {
            public function __construct(private readonly array $data) {}

            public function sheets(): array
            {
                return [
                    new AllListingsSheet($this->data),
                    new MarketOverviewSheet($this->data),
                    new DistrictAnalysisSheet($this->data),
                    new RoomTypeAnalysisSheet($this->data),
                    new BuildingStockSheet($this->data),
                    new PriceDistributionSheet($this->data),
                    new AgencyIntelligenceSheet($this->data),
                    new FloorSizeAnalysisSheet($this->data),
                ];
            }
        }, $path, 'local');

        return storage_path('app/'.$path);
    }

    public function extension(): string
    {
        return 'xlsx';
    }

    /**
     * Fixed filename - this is a client deliverable, not a rolling export.
     * Date suffix ensures each run produces a distinct versioned file.
     */
    private function buildPath(): string
    {
        $dir = config('scraper.export_path');
        $date = now()->format('d_m_Y');

        return "{$dir}/colliers_yerevan_report_{$date}.xlsx";
    }
}

/**
 * Sheet 1 — Client-facing raw dataset.
 * Contains only fields relevant to Colliers — internal system columns excluded.
 * Ordered for readability: location → price → property details → contact.
 */
class AllListingsSheet implements FromArray, WithHeadings, WithTitle
{
    private array $clientColumns = [
        'url',
        'listing_type',
        'property_type',
        'district',
        'address',
        'price',
        'currency',
        'price_per_sqm',
        'area',
        'rooms',
        'bathrooms',
        'floor',
        'total_floors',
        'ceiling_height',
        'building_type',
        'condition',
        'is_new_building',
        'agency_name',
        'listing_date',
    ];

    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'All Listings';
    }

    public function headings(): array
    {
        return $this->clientColumns;
    }

    public function array(): array
    {
        return array_map(function ($row) {
            return array_map(fn ($col) => $row[$col] ?? null, $this->clientColumns);
        }, $this->data);
    }
}

/**
 * Sheet 2 - Executive headline statistics.
 * Single-page summary for decision makers.
 * Covers total supply, pricing averages, market composition.
 */
class MarketOverviewSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Market Overview';
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function array(): array
    {
        $prices = array_filter(array_column($this->data, 'price'));
        $sqmPrices = array_filter(array_column($this->data, 'price_per_sqm'));
        $areas = array_filter(array_column($this->data, 'area'));
        $districts = array_filter(array_column($this->data, 'district'));
        $agencies = array_filter(array_column($this->data, 'agency_name'));

        $avgPrice = count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A';
        $avgSqm = count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A';
        $avgArea = count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A';
        $medianPrice = count($prices) ? $this->median(array_values($prices)) : 'N/A';

        $newBuildingCount = count(array_filter(
            array_column($this->data, 'is_new_building'),
            fn ($v) => $v === true || $v === 1
        ));

        return [
            ['Report Date',                now()->format('d/m/Y H:i')],
            ['Data Source',                'list.am - Yerevan Apartments for Sale'],
            ['', ''],
            ['--- SUPPLY ---',             ''],
            ['Total Active Listings',      count($this->data)],
            ['Districts Covered',          count(array_unique($districts))],
            ['', ''],
            ['--- PRICING ---',            ''],
            ['Average Price (USD)',        $avgPrice],
            ['Median Price (USD)',         $medianPrice],
            ['Average Price/sqm (USD)',    $avgSqm],
            ['Average Area (sqm)',         $avgArea],
            ['Min Price (USD)',            count($prices) ? min($prices) : 'N/A'],
            ['Max Price (USD)',            count($prices) ? max($prices) : 'N/A'],
            ['', ''],
            ['--- MARKET COMPOSITION ---', ''],
            ['New Construction Listings',  $newBuildingCount],
            ['New Construction %',         count($this->data) > 0 ? round($newBuildingCount / count($this->data) * 100, 1).'%' : 'N/A'],
            ['Agency Listings',            count($agencies)],
            ['Owner Listings',             count($this->data) - count($agencies)],
        ];
    }

    private function median(array $values): float
    {
        sort($values);
        $count = count($values);
        $mid = (int) floor($count / 2);

        return $count % 2 === 0
            ? round(($values[$mid - 1] + $values[$mid]) / 2, 2)
            : round($values[$mid], 2);
    }
}

/**
 * Sheet 3 - District-level supply and pricing analysis.
 * Sorted by listing count descending - most active districts first.
 * Includes new construction split per district for development potential assessment.
 */
class DistrictAnalysisSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'District Analysis';
    }

    public function headings(): array
    {
        return [
            'District',
            'Listings',
            '% of Market',
            'Avg Price (USD)',
            'Median Price (USD)',
            'Avg Price/sqm (USD)',
            'Avg Area (sqm)',
            'New Construction',
            'New Construction %',
        ];
    }

    public function array(): array
    {
        $grouped = [];
        $total = count($this->data);

        foreach ($this->data as $row) {
            $district = $row['district'] ?? 'Unknown';
            $grouped[$district][] = $row;
        }

        $rows = [];

        foreach ($grouped as $district => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $areas = array_filter(array_column($listings, 'area'));
            $newCount = count(array_filter(
                array_column($listings, 'is_new_building'),
                fn ($v) => $v === true || $v === 1
            ));

            $sortedPrices = array_values($prices);
            sort($sortedPrices);
            $mid = (int) floor(count($sortedPrices) / 2);
            $median = count($sortedPrices) > 0
                ? (count($sortedPrices) % 2 === 0
                    ? round(($sortedPrices[$mid - 1] + $sortedPrices[$mid]) / 2, 2)
                    : round($sortedPrices[$mid], 2))
                : null;

            $count = count($listings);

            $rows[] = [
                $district,
                $count,
                round($count / $total * 100, 1).'%',
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                $median ?? 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
                $newCount,
                $count > 0 ? round($newCount / $count * 100, 1).'%' : 'N/A',
            ];
        }

        usort($rows, fn ($a, $b) => $b[1] <=> $a[1]);

        return $rows;
    }
}

/**
 * Sheet 4 - Product mix and pricing by bedroom count.
 * Answers: what is the dominant product type and what does each command?
 * Critical for Colliers to understand demand segments.
 */
class RoomTypeAnalysisSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Room Type Analysis';
    }

    public function headings(): array
    {
        return [
            'Room Type',
            'Listings',
            '% of Market',
            'Avg Price (USD)',
            'Avg Price/sqm (USD)',
            'Avg Area (sqm)',
            'New Construction %',
        ];
    }

    public function array(): array
    {
        $grouped = [];
        $total = count($this->data);

        foreach ($this->data as $row) {
            $rooms = $row['rooms'] ?? null;
            $label = $rooms !== null ? "{$rooms} Room".($rooms > 1 ? 's' : '') : 'Unknown';
            $grouped[$label][] = $row;
        }

        ksort($grouped);

        $rows = [];

        foreach ($grouped as $label => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $areas = array_filter(array_column($listings, 'area'));
            $newCount = count(array_filter(
                array_column($listings, 'is_new_building'),
                fn ($v) => $v === true || $v === 1
            ));
            $count = count($listings);

            $rows[] = [
                $label,
                $count,
                round($count / $total * 100, 1).'%',
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
                $count > 0 ? round($newCount / $count * 100, 1).'%' : 'N/A',
            ];
        }

        return $rows;
    }
}

/**
 * Sheet 5 - Building stock composition.
 * Construction type and condition breakdown.
 * Informs Colliers on asset quality distribution across the market.
 */
class BuildingStockSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Building Stock';
    }

    public function headings(): array
    {
        return ['Category', 'Value', 'Listings', '% of Market', 'Avg Price/sqm (USD)'];
    }

    public function array(): array
    {
        $total = count($this->data);
        $rows = [];

        // Construction type breakdown
        $rows[] = ['CONSTRUCTION TYPE', '', '', '', ''];
        $byType = [];

        foreach ($this->data as $row) {
            $type = $row['building_type'] ?? 'Unknown';
            $byType[$type][] = $row;
        }

        arsort($byType);

        foreach ($byType as $type => $listings) {
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $count = count($listings);

            $rows[] = [
                '',
                ucfirst($type),
                $count,
                round($count / $total * 100, 1).'%',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
            ];
        }

        // Condition breakdown
        $rows[] = ['', '', '', '', ''];
        $rows[] = ['CONDITION / RENOVATION', '', '', '', ''];
        $byCond = [];

        foreach ($this->data as $row) {
            $cond = $row['condition'] ?? 'Unknown';
            $byCond[$cond][] = $row;
        }

        arsort($byCond);

        foreach ($byCond as $cond => $listings) {
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $count = count($listings);

            $rows[] = [
                '',
                $cond,
                $count,
                round($count / $total * 100, 1).'%',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
            ];
        }

        return $rows;
    }
}

/**
 * Sheet 6 - Price distribution by bracket.
 * Shows market segmentation - affordable, mid-market, premium, luxury.
 * USD brackets calibrated to Yerevan market range.
 */
class PriceDistributionSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Price Distribution';
    }

    public function headings(): array
    {
        return ['Price Bracket (USD)', 'Listings', '% of Market', 'Avg Area (sqm)', 'Avg Price/sqm (USD)'];
    }

    public function array(): array
    {
        $brackets = [
            'Under $50,000' => [0, 50000],
            '$50,000 – $100,000' => [50000, 100000],
            '$100,000 – $150,000' => [100000, 150000],
            '$150,000 – $200,000' => [150000, 200000],
            '$200,000 – $300,000' => [200000, 300000],
            '$300,000 – $500,000' => [300000, 500000],
            'Above $500,000' => [500000, PHP_INT_MAX],
        ];

        $buckets = array_fill_keys(array_keys($brackets), []);

        foreach ($this->data as $row) {
            $price = $row['price'] ?? null;
            if (! $price) {
                continue;
            }

            foreach ($brackets as $label => [$min, $max]) {
                if ($price >= $min && $price < $max) {
                    $buckets[$label][] = $row;
                    break;
                }
            }
        }

        $total = count($this->data);
        $rows = [];

        foreach ($buckets as $label => $listings) {
            $count = count($listings);
            $areas = array_filter(array_column($listings, 'area'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));

            $rows[] = [
                $label,
                $count,
                $total > 0 ? round($count / $total * 100, 1).'%' : '0%',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
            ];
        }

        return $rows;
    }
}

/**
 * Sheet 7 - Market participant intelligence.
 * Top agencies by listing volume, owner vs agency split.
 * Tells Colliers who controls supply and potential partnership targets.
 */
class AgencyIntelligenceSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Agency Intelligence';
    }

    public function headings(): array
    {
        return ['Participant', 'Listings', '% of Market', 'Avg Price (USD)', 'Avg Price/sqm (USD)'];
    }

    public function array(): array
    {
        $total = count($this->data);
        $agencyData = [];
        $ownerCount = 0;

        foreach ($this->data as $row) {
            $agency = $row['agency_name'] ?? null;

            if ($agency) {
                $agencyData[$agency][] = $row;
            } else {
                $ownerCount++;
            }
        }

        uasort($agencyData, fn ($a, $b) => count($b) <=> count($a));

        $rows = [];
        $rows[] = ['MARKET SPLIT', '', '', '', ''];

        $agencyTotal = array_sum(array_map('count', $agencyData));

        $rows[] = [
            'Agency Listings',
            $agencyTotal,
            round($agencyTotal / $total * 100, 1).'%',
            '',
            '',
        ];

        $rows[] = [
            'Owner Listings',
            $ownerCount,
            round($ownerCount / $total * 100, 1).'%',
            '',
            '',
        ];

        $rows[] = ['', '', '', '', ''];
        $rows[] = ['TOP AGENCIES', '', '', '', ''];

        foreach (array_slice($agencyData, 0, 20, true) as $agency => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $count = count($listings);

            $rows[] = [
                $agency,
                $count,
                round($count / $total * 100, 1).'%',
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
            ];
        }

        return $rows;
    }
}

/**
 * Sheet 8 - Floor and size analysis.
 * Price/sqm by floor range and size bracket.
 * Supports granular valuation - do higher floors or larger units command premium?
 */
class FloorSizeAnalysisSheet implements FromArray, WithHeadings, WithTitle
{
    public function __construct(private readonly array $data) {}

    public function title(): string
    {
        return 'Floor & Size Analysis';
    }

    public function headings(): array
    {
        return ['Category', 'Value', 'Listings', 'Avg Price (USD)', 'Avg Price/sqm (USD)', 'Avg Area (sqm)'];
    }

    public function array(): array
    {
        $rows = [];

        // Floor range breakdown
        $floorBrackets = [
            'Ground Floor (1)' => [1, 1],
            'Low Floors (2–3)' => [2, 3],
            'Mid Floors (4–7)' => [4, 7],
            'High Floors (8–12)' => [8, 12],
            'Top Floors (13+)' => [13, PHP_INT_MAX],
        ];

        $rows[] = ['FLOOR ANALYSIS', '', '', '', '', ''];
        $floorBuckets = array_fill_keys(array_keys($floorBrackets), []);

        foreach ($this->data as $row) {
            $floor = $row['floor'] ?? null;
            if ($floor === null) {
                continue;
            }

            foreach ($floorBrackets as $label => [$min, $max]) {
                if ($floor >= $min && $floor <= $max) {
                    $floorBuckets[$label][] = $row;
                    break;
                }
            }
        }

        foreach ($floorBuckets as $label => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $areas = array_filter(array_column($listings, 'area'));
            $count = count($listings);

            $rows[] = [
                '',
                $label,
                $count,
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
            ];
        }

        // Size bracket breakdown
        $sizeBrackets = [
            'Studio / Micro (under 40sqm)' => [0, 40],
            'Small (40–60 sqm)' => [40, 60],
            'Medium (60–80 sqm)' => [60, 80],
            'Large (80–120 sqm)' => [80, 120],
            'Extra Large (120sqm+)' => [120, PHP_INT_MAX],
        ];

        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['SIZE ANALYSIS', '', '', '', '', ''];
        $sizeBuckets = array_fill_keys(array_keys($sizeBrackets), []);

        foreach ($this->data as $row) {
            $area = $row['area'] ?? null;
            if ($area === null) {
                continue;
            }

            foreach ($sizeBrackets as $label => [$min, $max]) {
                if ($area >= $min && $area < $max) {
                    $sizeBuckets[$label][] = $row;
                    break;
                }
            }
        }

        foreach ($sizeBuckets as $label => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $areas = array_filter(array_column($listings, 'area'));
            $count = count($listings);

            $rows[] = [
                '',
                $label,
                $count,
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
            ];
        }

        // Ceiling height premium
        $rows[] = ['', '', '', '', '', ''];
        $rows[] = ['CEILING HEIGHT PREMIUM', '', '', '', '', ''];

        $heightBrackets = [
            'Standard (under 2.8m)' => [0, 2.8],
            'Comfortable (2.8–3.0m)' => [2.8, 3.0],
            'High (3.0–3.5m)' => [3.0, 3.5],
            'Loft / Premium (3.5m+)' => [3.5, PHP_INT_MAX],
        ];

        $heightBuckets = array_fill_keys(array_keys($heightBrackets), []);

        foreach ($this->data as $row) {
            $height = $row['ceiling_height'] ?? null;
            if ($height === null) {
                continue;
            }

            foreach ($heightBrackets as $label => [$min, $max]) {
                if ($height >= $min && $height < $max) {
                    $heightBuckets[$label][] = $row;
                    break;
                }
            }
        }

        foreach ($heightBuckets as $label => $listings) {
            $prices = array_filter(array_column($listings, 'price'));
            $sqmPrices = array_filter(array_column($listings, 'price_per_sqm'));
            $areas = array_filter(array_column($listings, 'area'));
            $count = count($listings);

            $rows[] = [
                '',
                $label,
                $count,
                count($prices) ? round(array_sum($prices) / count($prices), 2) : 'N/A',
                count($sqmPrices) ? round(array_sum($sqmPrices) / count($sqmPrices), 2) : 'N/A',
                count($areas) ? round(array_sum($areas) / count($areas), 2) : 'N/A',
            ];
        }

        return $rows;
    }
}
