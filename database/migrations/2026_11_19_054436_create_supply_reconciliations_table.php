<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('supply_items')->cascadeOnDelete();
            $table->decimal('expected_quantity', 14, 3);
            $table->decimal('counted_quantity', 14, 3);
            $table->decimal('difference', 14, 3);
            $table->string('idempotency_key');
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['hospital_id', 'idempotency_key'], 'supply_reconciliations_idempotency');
            $table->index(['hospital_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_reconciliations');
    }
};
