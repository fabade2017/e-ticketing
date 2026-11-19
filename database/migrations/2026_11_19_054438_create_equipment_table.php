<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('asset_id')->unique();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->string('model')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['hospital_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
