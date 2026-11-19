<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maint_preventive_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('interval_days');
            $table->timestamp('next_due_at');
            $table->foreignId('last_work_order_id')->nullable()->constrained('maint_work_orders')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'next_due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_preventive_schedules');
    }
};
