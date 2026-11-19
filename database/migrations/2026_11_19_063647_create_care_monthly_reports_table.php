<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->cascadeOnDelete();
            $table->string('scope');
            $table->string('scope_key')->unique();
            $table->date('report_month');
            $table->unsignedInteger('distinct_patients')->default(0);
            $table->unsignedInteger('completed_visits')->default(0);
            $table->unsignedInteger('patients_tested')->default(0);
            $table->unsignedInteger('tests_performed')->default(0);
            $table->unsignedInteger('disease_encounters')->default(0);
            $table->foreignId('generated_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['hospital_id', 'report_month']);
            $table->index('scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_monthly_reports');
    }
};
