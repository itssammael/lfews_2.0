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
        Schema::create('sitios', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('barangay_name')->nullable();
            $table->unsignedBigInteger('barangay_id')->nullable();
            $table->json('properties')->nullable();
            $table->longText('geometry'); // Storing GeoJSON geometry as text
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sitios');
    }
};
