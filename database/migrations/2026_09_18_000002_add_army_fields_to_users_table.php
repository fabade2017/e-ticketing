<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('service_number')->unique()->nullable()->after('email');
            $table->string('rank')->nullable()->after('service_number');
            $table->string('unit')->nullable()->after('rank');
            $table->string('phone')->nullable()->after('unit');
            $table->json('face_embedding')->nullable()->after('phone');
            $table->timestamp('face_enrolled_at')->nullable()->after('face_embedding');
            $table->boolean('is_active')->default(true)->after('face_enrolled_at');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['service_number', 'rank', 'unit', 'phone', 'face_embedding', 'face_enrolled_at', 'is_active']);
        });
    }
};
