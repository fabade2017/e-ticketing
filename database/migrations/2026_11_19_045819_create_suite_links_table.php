<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('suite_links', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 100);
            $table->unsignedBigInteger('source_id');
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id');
            $table->string('type', 100);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['source_type', 'source_id', 'target_type', 'target_id', 'type'], 'suite_links_unique');
            $table->index(['target_type', 'target_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suite_links');
    }
};
