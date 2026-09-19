<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('friend_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->cascadeOnDelete(); // for colleague friend
            $table->foreignId('group_id')->nullable()->constrained('chat_groups')->cascadeOnDelete(); // for group join
            $table->enum('type', ['colleague','group_join'])->default('colleague');
            $table->enum('status', ['pending','accepted','rejected'])->default('pending');
            $table->string('message')->nullable();
            $table->timestamps();
            $table->index(['receiver_id','status']);
            $table->index(['group_id','status']);
        });

        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id','friend_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('friendships');
        Schema::dropIfExists('friend_requests');
    }
};
