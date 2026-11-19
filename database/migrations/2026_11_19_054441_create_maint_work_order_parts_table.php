<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maint_work_order_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('maint_work_orders')->cascadeOnDelete();
            $table->foreignId('supply_issue_id')->nullable()->constrained('supply_goods_issued')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['work_order_id', 'supply_issue_id'], 'maint_work_order_parts_issue_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_work_order_parts');
    }
};
