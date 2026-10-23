<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('user_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('tickets')->whereNull('user_id')->delete();

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
        });
    }
};
