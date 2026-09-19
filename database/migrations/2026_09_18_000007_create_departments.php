<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique()->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('unit');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('category');
        });
    }
    public function down(): void
    {
        Schema::table('tickets', fn($t)=>$t->dropConstrainedForeignId('department_id'));
        Schema::table('users', fn($t)=>$t->dropConstrainedForeignId('department_id'));
        Schema::dropIfExists('departments');
    }
};
