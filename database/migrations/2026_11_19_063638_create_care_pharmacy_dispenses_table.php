<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_pharmacy_dispenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('care_prescriptions')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('dispensed_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->text('notes')->nullable();
            $table->timestamp('dispensed_at')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'dispensed_at']);
            $table->index(['patient_id', 'dispensed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_pharmacy_dispenses');
    }
};
