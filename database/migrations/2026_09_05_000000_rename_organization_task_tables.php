<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('secretariats', 'teams');
        Schema::rename('service_orders', 'tasks');
        Schema::rename('ods_checklists', 'task_checklists');
        Schema::rename('ods_histories', 'task_histories');

        foreach (['users', 'categories', 'tasks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('secretariat_id', 'team_id');
            });
        }

        foreach (['task_checklists', 'task_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('service_order_id', 'task_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['task_checklists', 'task_histories'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('task_id', 'service_order_id');
            });
        }

        foreach (['users', 'categories', 'tasks'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('team_id', 'secretariat_id');
            });
        }

        Schema::rename('task_histories', 'ods_histories');
        Schema::rename('task_checklists', 'ods_checklists');
        Schema::rename('tasks', 'service_orders');
        Schema::rename('teams', 'secretariats');
    }
};
