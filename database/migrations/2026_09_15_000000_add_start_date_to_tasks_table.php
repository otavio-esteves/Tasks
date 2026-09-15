<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->date('start_date')->nullable()->after('observation');
            $table->index(['team_id', 'start_date'], 'tasks_team_start_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropIndex('tasks_team_start_date_index');
            $table->dropColumn('start_date');
        });
    }
};
