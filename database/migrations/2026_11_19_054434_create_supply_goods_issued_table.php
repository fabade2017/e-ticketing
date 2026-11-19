<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_goods_issued', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('supply_items')->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('supply_batches')->nullOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->text('purpose')->nullable();
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->unsignedBigInteger('care_visit_id')->nullable();
            $table->string('idempotency_key');
            $table->string('status')->default('pending');
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['hospital_id', 'idempotency_key'], 'supply_goods_issued_idempotency');
            $table->index(['hospital_id', 'status']);
            $table->index('work_order_id');
            $table->index('care_visit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_goods_issued');
    }
};
