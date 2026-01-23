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
        Schema::create('weather_stations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->string('location')->nullable();
            $table->decimal('level_2', 8, 2)->nullable();
            $table->decimal('level_3', 8, 2)->nullable();
            $table->decimal('level_4', 8, 2)->nullable();
            $table->string('status')->nullable();
            $table->string('state')->nullable();
            $table->string('ip')->nullable();
            $table->integer('port')->nullable();
            $table->integer('slave_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weather_stations');
    }
};
