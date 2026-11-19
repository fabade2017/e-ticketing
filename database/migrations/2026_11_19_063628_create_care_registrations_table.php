<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('sponsor_id')->nullable()->constrained('care_patient_sponsors')->nullOnDelete();
            $table->string('category');
            $table->string('status')->default('pending');
            $table->boolean('identity_verified')->default(false);
            $table->boolean('evidence_verified')->default(false);
            $table->boolean('sponsor_approved')->default(false);
            $table->decimal('fee_amount', 12, 2)->default(0);
            $table->decimal('waiver_amount', 12, 2)->default(0);
            $table->decimal('payable_amount', 12, 2)->default(0);
            $table->string('waiver_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'status']);
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_registrations');
    }
};
