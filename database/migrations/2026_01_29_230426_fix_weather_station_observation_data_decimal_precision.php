<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('weather_station_observation_data', function (Blueprint $table) {
            $table->decimal('temperature', 12, 7)->change();
            $table->decimal('heat_index', 12, 7)->change();
            $table->decimal('dewpoint', 12, 7)->change();
            $table->decimal('humidity', 12, 7)->change();
            $table->decimal('wind_speed', 12, 7)->change();
            $table->decimal('wind_direction', 12, 7)->change();
            $table->decimal('wind_gust', 12, 7)->change();
            $table->decimal('pressure', 12, 7)->change();
            $table->decimal('precipitation_rate', 12, 7)->change();
            $table->decimal('precipitation_total', 12, 7)->change();
            $table->decimal('uv', 12, 7)->nullable()->change();
            $table->decimal('solar_radiation', 12, 7)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('weather_station_observation_data', function (Blueprint $table) {
            $table->decimal('temperature', 10, 7)->change();
            $table->decimal('heat_index', 10, 7)->change();
            $table->decimal('dewpoint', 10, 7)->change();
            $table->decimal('humidity', 10, 7)->change();
            $table->decimal('wind_speed', 10, 7)->change();
            $table->decimal('wind_direction', 10, 7)->change();
            $table->decimal('wind_gust', 10, 7)->change();
            $table->decimal('pressure', 10, 7)->change();
            $table->decimal('precipitation_rate', 10, 7)->change();
            $table->decimal('precipitation_total', 10, 7)->change();
            $table->decimal('uv', 10, 7)->nullable()->change();
            $table->decimal('solar_radiation', 10, 7)->nullable()->change();
        });
    }
};
