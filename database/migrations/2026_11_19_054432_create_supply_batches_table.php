<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('supply_items')->cascadeOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('batch_code');
            $table->date('expires_on')->nullable();
            $table->boolean('is_damaged')->default(false);
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();
            $table->unique(['item_id', 'hospital_id', 'batch_code'], 'supply_batches_unique');
            $table->index(['hospital_id', 'expires_on']);
            $table->index(['hospital_id', 'is_damaged']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_batches');
    }
};
