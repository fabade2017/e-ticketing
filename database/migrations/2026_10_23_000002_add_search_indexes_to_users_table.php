<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('name');
            $table->index('rank');
            $table->index('unit');
            $table->index('is_active');
            $table->index('face_enrolled_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['rank']);
            $table->dropIndex(['unit']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['face_enrolled_at']);
            $table->dropIndex(['created_at']);
        });
    }
};
