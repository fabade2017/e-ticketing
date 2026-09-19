<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('country')->default('Nigeria')->after('location');
            $table->string('state')->nullable()->after('country');
            $table->string('lga')->nullable()->after('state');
        });
    }
    public function down(): void
    {
        Schema::table('tickets', fn($t)=>$t->dropColumn(['country','state','lga']));
    }
};
