<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the listings table.
 *
 * Stores normalized real estate listing data scraped from external sources.
 * Compound unique index on external_id + source_profile_name prevents duplicates.
 * Column names match ListingDTO::toArray() exactly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

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
            $table->decimal('living_area', 8, 2)->nullable();
            $table->decimal('kitchen_area', 8, 2)->nullable();

            // Property details
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedTinyInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();
            $table->decimal('ceiling_height', 4, 2)->nullable();
            $table->string('building_type')->nullable();
            $table->string('condition')->nullable();
            $table->boolean('is_new_building')->nullable();

            // Features
            $table->boolean('has_balcony')->nullable();
            $table->boolean('has_furniture')->nullable();
            $table->boolean('has_elevator')->nullable();
            $table->boolean('has_parking')->nullable();
            $table->boolean('has_garage')->nullable();

            // Location
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Contact
            $table->string('phone')->nullable();

            // Content
            $table->text('description')->nullable();
            $table->json('images')->nullable();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
