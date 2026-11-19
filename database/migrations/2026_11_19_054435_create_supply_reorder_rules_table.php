<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_reorder_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('supply_items')->cascadeOnDelete();
            $table->decimal('minimum_quantity', 14, 3);
            $table->decimal('target_quantity', 14, 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['hospital_id', 'item_id'], 'supply_reorder_rules_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_reorder_rules');
    }
};
