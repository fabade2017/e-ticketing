<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('care_visits')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('prescribed_by')->constrained('users')->cascadeOnDelete();
            $table->string('medication');
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('instructions')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_prescriptions');
    }
};
