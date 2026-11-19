<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('care_patients')->cascadeOnDelete();
            $table->foreignId('registration_id')->nullable()->constrained('care_registrations')->cascadeOnDelete();
            $table->string('document_type');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'document_type']);
            $table->index(['registration_id', 'is_verified']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_documents');
    }
};
