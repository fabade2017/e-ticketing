<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('sla_due_at')->nullable()->after('priority');
            $table->timestamp('escalated_at')->nullable()->after('sla_due_at');
        });

        Schema::create('ticket_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // created, status_changed, assigned, escalated, priority_changed
            $table->json('from')->nullable();
            $table->json('to')->nullable();
            $table->string('ip')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ticket_histories');
        Schema::table('tickets', fn($t)=>$t->dropColumn(['sla_due_at','escalated_at']));
    }
};
