<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('code');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['hospital_id', 'status']);
            $table->index(['equipment_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faults');
    }
};
