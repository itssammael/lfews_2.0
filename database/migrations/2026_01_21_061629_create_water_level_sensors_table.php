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
        Schema::create('water_level_sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand');
            $table->string('mode');
            $table->decimal('level_2', 8, 2);
            $table->decimal('level_3', 8, 2);
            $table->decimal('level_4', 8, 2);
            $table->tinyInteger('state')->default(1);;
            $table->string('ip');
            $table->integer('port');
            $table->integer('slave_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('water_level_sensors');
    }
};
