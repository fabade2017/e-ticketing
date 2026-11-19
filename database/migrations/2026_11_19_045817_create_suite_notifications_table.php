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
        Schema::create('suite_notifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('module', 50);
            $table->string('type', 100);
            $table->string('title');
            $table->string('url')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['module', 'read_at']);
            $table->index(['hospital_id', 'read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suite_notifications');
    }
};
