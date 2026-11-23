<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supply_items', function (Blueprint $table): void {
            $table->foreignId('supply_item_category_id')
                ->nullable()
                ->after('category')
                ->constrained('supply_item_categories')
                ->nullOnDelete();
            $table->index(['is_active', 'supply_item_category_id']);
        });

        $now = now();
        DB::table('supply_items')
            ->selectRaw('distinct lower(trim(category)) as name')
            ->whereNotNull('category')
            ->whereRaw("trim(category) <> ''")
            ->pluck('name')
            ->filter()
            ->each(function (string $name) use ($now): void {
                DB::table('supply_item_categories')->insertOrIgnore([
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::statement("update supply_items
            set supply_item_category_id = (
                select id from supply_item_categories
                where lower(supply_item_categories.name) = lower(trim(supply_items.category))
                limit 1
            )
            where supply_item_category_id is null
              and category is not null
              and trim(category) <> ''");
    }

    public function down(): void
    {
        Schema::table('supply_items', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'supply_item_category_id']);
            $table->dropConstrainedForeignId('supply_item_category_id');
        });
    }
};
