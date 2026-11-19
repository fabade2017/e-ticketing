<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_lab_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('care_lab_results')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->index(['lab_result_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_lab_images');
    }
};
