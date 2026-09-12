<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PerformanceIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_query_indexes_are_available_with_expected_prefixes(): void
    {
        $this->assertTrue(Schema::hasIndex(
            'tasks',
            ['team_id', 'status', 'deleted_at', 'created_at', 'id'],
        ));
        $this->assertTrue(Schema::hasIndex('tasks', ['category_id']));
        $this->assertTrue(Schema::hasIndex(
            'task_checklists',
            ['task_id', 'sort_order', 'id'],
        ));
        $this->assertTrue(Schema::hasIndex(
            'task_histories',
            ['task_id', 'created_at', 'id'],
        ));
        $this->assertTrue(Schema::hasIndex('task_histories', ['user_id']));
        $this->assertTrue(Schema::hasIndex('users', ['team_id']));
    }
}
