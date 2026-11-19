<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maint_overdue_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('maint_work_orders')->cascadeOnDelete();
            $table->timestamp('flagged_at');
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->unique('work_order_id', 'maint_overdue_flags_work_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_overdue_flags');
    }
};
