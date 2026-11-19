<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_frontline_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('care_visits')->nullOnDelete();
            $table->foreignId('evidence_document_id')->nullable()->constrained('care_documents')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('deployment_unit')->nullable();
            $table->string('theatre')->nullable();
            $table->string('duty_type')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'patient_id']);
            $table->index(['patient_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_frontline_records');
    }
};
