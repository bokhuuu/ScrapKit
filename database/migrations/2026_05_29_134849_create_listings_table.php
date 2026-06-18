<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the listings table.
 *
 * Stores normalized real estate listing data scraped from external sources.
 * Columns match ListingDTO::toArray() exactly - never add a column here
 * without adding the corresponding property to the DTO.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('scraper_run_id')->nullable();

            // Identity
            $table->string('external_id');
            $table->string('source_profile_name');
            $table->string('url')->nullable();
            $table->string('listing_type')->nullable();
            $table->string('property_type')->nullable();

            // Price
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('price_per_sqm', 10, 2)->nullable();

            // Size
            $table->decimal('area', 8, 2)->nullable();

            // Property details
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->unsignedTinyInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->decimal('ceiling_height', 4, 2)->nullable();
            $table->string('building_type')->nullable();
            $table->string('condition')->nullable();
            $table->boolean('is_new_building')->nullable();

            // Location
            $table->string('district')->nullable();
            $table->string('address')->nullable();

            // Contact
            $table->string('phone')->nullable();
            $table->string('agency_name')->nullable();

            // Content
            $table->json('images')->nullable();
            $table->json('extras')->nullable();

            // Timestamps
            $table->timestamp('listing_date')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['external_id', 'source_profile_name']);
            $table->index('source_profile_name');
            $table->index('district');
            $table->index('listing_type');
            $table->index('property_type');
            $table->index('scraped_at');
            $table->index('scraper_run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
