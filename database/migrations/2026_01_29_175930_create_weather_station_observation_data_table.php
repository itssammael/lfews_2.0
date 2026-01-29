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
        Schema::create('weather_station_observation_data', function (Blueprint $table) {
            $table->id();
            $table->dateTime('date_time');
            $table->decimal('temperature', 10, 7);
            $table->decimal('heat_index', 10, 7);
            $table->decimal('dewpoint', 10, 7);
            $table->decimal('humidity', 10, 7);
            $table->decimal('wind_speed', 10, 7);
            $table->decimal('wind_direction', 10, 7);
            $table->decimal('wind_gust', 10, 7);
            $table->decimal('pressure', 10, 7);
            $table->decimal('precipitation_rate', 10, 7);
            $table->decimal('precipitation_total', 10, 7);
            $table->decimal('uv', 10, 7)->nullable();
            $table->decimal('solar_radiation', 10, 7)->nullable();
            $table->foreignId('weather_station_id')->constrained('weather_stations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_station_observation_data');
    }
};
