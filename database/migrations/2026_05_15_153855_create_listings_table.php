<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Creates the listings table.
 *
 * Stores normalized real estate listing data scraped from external sources.
 * Compound unique index on external_id + source prevents cross-site duplicates.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            $table->string('external_id');
            $table->string('source');

            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();

            $table->string('category')->nullable();
            $table->string('listing_type')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();
            $table->decimal('area', 8, 2)->nullable();
            $table->unsignedTinyInteger('rooms')->nullable();
            $table->unsignedTinyInteger('floor')->nullable();
            $table->unsignedTinyInteger('total_floors')->nullable();
            $table->string('building_type')->nullable();
            $table->boolean('new_construction')->nullable();
            $table->string('renovation')->nullable();
            $table->json('images')->nullable();
            $table->year('build_year')->nullable();

            $table->string('phone')->nullable();

            $table->text('description')->nullable();
            $table->string('url')->nullable();

            $table->string('status', 50)->default('active');

            $table->timestamp('listed_at')->nullable();
            $table->timestamp('scraped_at')->nullable();
            $table->timestamps();

            $table->unique(['external_id', 'source']);
            $table->index('source');
            $table->index('district');
            $table->index('scraped_at');
            $table->index('status');
            $table->index('category');
            $table->index('listing_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
