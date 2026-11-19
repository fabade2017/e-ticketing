<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('care_visits')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('height_cm', 7, 2)->nullable();
            $table->decimal('weight_kg', 7, 2)->nullable();
            $table->decimal('temperature_c', 5, 2)->nullable();
            $table->unsignedSmallInteger('pulse')->nullable();
            $table->unsignedSmallInteger('bp_systolic')->nullable();
            $table->unsignedSmallInteger('bp_diastolic')->nullable();
            $table->unsignedSmallInteger('respiratory_rate')->nullable();
            $table->unsignedSmallInteger('oxygen_saturation')->nullable();
            $table->timestamp('recorded_at')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_vitals');
    }
};
