<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('care_visits')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('diagnosed_by')->constrained('users')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('description');
            $table->string('type')->default('provisional');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hospital_id', 'is_active']);
            $table->index(['patient_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_diagnoses');
    }
};
