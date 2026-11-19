<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_patient_sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('sponsor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sponsor_patient_id')->nullable()->constrained('care_patients')->cascadeOnDelete();
            $table->string('relationship')->default('sponsor');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['patient_id', 'sponsor_user_id'], 'care_patient_sponsors_user_unique');
            $table->unique(['patient_id', 'sponsor_patient_id'], 'care_patient_sponsors_patient_unique');
            $table->index(['patient_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_patient_sponsors');
    }
};
