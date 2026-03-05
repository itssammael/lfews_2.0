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
        Schema::create('tides', function (Blueprint $table) {
            $table->id();
            $table->integer('dt');
            $table->dateTime('date');
            $table->float('height');
            $table->string('type'); // High, Low
            $table->float('latitude');
            $table->float('longitude');
            $table->string('station')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tides');
    }
};
