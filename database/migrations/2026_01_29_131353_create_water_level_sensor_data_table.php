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
        Schema::create('water_level_sensor_data', function (Blueprint $table) {
            $table->id();
            $table->datetime('date');
            $table->decimal('sensor_data', 8, 2);
            $table->foreignId('water_level_sensor_id')->constrained('water_level_sensors')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_level_sensor_data');
    }
};
