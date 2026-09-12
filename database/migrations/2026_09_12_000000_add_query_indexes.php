<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->index(
                ['team_id', 'status', 'deleted_at', 'created_at', 'id'],
                'tasks_team_status_active_order_index',
            );
            $table->index('category_id', 'tasks_category_id_index');
        });

        Schema::table('task_checklists', function (Blueprint $table): void {
            $table->index(
                ['task_id', 'sort_order', 'id'],
                'task_checklists_task_order_index',
            );
        });

        Schema::table('task_histories', function (Blueprint $table): void {
            $table->index(
                ['task_id', 'created_at', 'id'],
                'task_histories_task_order_index',
            );
            $table->index('user_id', 'task_histories_user_id_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('team_id', 'users_team_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_team_id_index');
        });

        Schema::table('task_histories', function (Blueprint $table): void {
            $table->dropIndex('task_histories_user_id_index');
            $table->dropIndex('task_histories_task_order_index');
        });

        Schema::table('task_checklists', function (Blueprint $table): void {
            $table->dropIndex('task_checklists_task_order_index');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_category_id_index');
            $table->dropIndex('tasks_team_status_active_order_index');
        });
    }
};
