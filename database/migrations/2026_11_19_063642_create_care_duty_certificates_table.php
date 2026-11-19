<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_duty_certificates', function (Blueprint $table) {
            $table->id();
            $table->string('certificate_number')->unique();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('care_visits')->nullOnDelete();
            $table->string('status');
            $table->text('statement');
            $table->date('review_date')->nullable();
            $table->text('administrative_note')->nullable();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->boolean('is_current')->default(true);
            $table->foreignId('superseded_by')->nullable()->constrained('care_duty_certificates')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'is_current']);
            $table->index(['hospital_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_duty_certificates');
    }
};
