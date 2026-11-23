<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_item_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 20)->nullable()->unique();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('is_active');
        });

        $now = now();
        DB::table('supply_item_categories')->insertOrIgnore([
            ['name' => 'medical', 'code' => 'MED', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'logistics', 'code' => 'LOG', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'uniform', 'code' => 'UNI', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'fuel', 'code' => 'FUE', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'equipment', 'code' => 'EQP', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'consumables', 'code' => 'CON', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_item_categories');
    }
};
