<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_number')->unique();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('care_registrations')->nullOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained('care_appointments')->nullOnDelete();
            $table->string('visit_type')->default('consultation');
            $table->string('status')->default('open');
            $table->text('published_summary')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('clinician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index(['patient_id', 'status']);
            $table->index(['status', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_visits');
    }
};
