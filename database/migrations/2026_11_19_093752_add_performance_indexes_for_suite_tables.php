<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_group_members', function (Blueprint $table): void {
            $table->index(['user_id', 'chat_group_id'], 'chat_members_user_group_idx');
        });

        Schema::table('suite_notifications', function (Blueprint $table): void {
            $table->index(
                ['notifiable_type', 'notifiable_id', 'read_at', 'created_at'],
                'suite_notifs_notifiable_read_idx'
            );
        });

        Schema::table('maint_work_orders', function (Blueprint $table): void {
            $table->dropIndex('maint_work_orders_hospital_id_status_index');
            $table->index(['hospital_id', 'status', 'created_at'], 'maint_orders_hospital_status_created_idx');
        });

        Schema::table('maint_service_logs', function (Blueprint $table): void {
            $table->index(['work_order_id', 'created_at'], 'maint_logs_order_created_idx');
        });

        Schema::table('supply_stock_balances', function (Blueprint $table): void {
            $table->index(['hospital_id', 'item_id'], 'supply_balances_hospital_item_idx');
        });

        Schema::table('supply_batches', function (Blueprint $table): void {
            $table->dropIndex('supply_batches_hospital_id_is_damaged_index');
            $table->index(['hospital_id', 'is_damaged', 'expires_on'], 'supply_batches_hospital_damage_expiry_idx');
        });

        Schema::table('friend_requests', function (Blueprint $table): void {
            $table->index(['sender_id', 'status'], 'friend_requests_sender_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('friend_requests', function (Blueprint $table): void {
            $table->dropIndex('friend_requests_sender_status_idx');
        });

        Schema::table('supply_batches', function (Blueprint $table): void {
            $table->dropIndex('supply_batches_hospital_damage_expiry_idx');
            $table->index(['hospital_id', 'is_damaged'], 'supply_batches_hospital_id_is_damaged_index');
        });

        Schema::table('supply_stock_balances', function (Blueprint $table): void {
            $table->dropIndex('supply_balances_hospital_item_idx');
        });

        Schema::table('maint_service_logs', function (Blueprint $table): void {
            $table->dropIndex('maint_logs_order_created_idx');
        });

        Schema::table('maint_work_orders', function (Blueprint $table): void {
            $table->dropIndex('maint_orders_hospital_status_created_idx');
            $table->index(['hospital_id', 'status'], 'maint_work_orders_hospital_id_status_index');
        });

        Schema::table('suite_notifications', function (Blueprint $table): void {
            $table->dropIndex('suite_notifs_notifiable_read_idx');
        });

        Schema::table('chat_group_members', function (Blueprint $table): void {
            $table->dropIndex('chat_members_user_group_idx');
        });
    }
};
