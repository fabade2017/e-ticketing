<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('supply_items')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('store')->default('main');
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('quantity_reserved', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['item_id', 'hospital_id', 'store'], 'supply_stock_balances_unique');
            $table->index(['hospital_id', 'store']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_stock_balances');
    }
};
