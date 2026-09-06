<?php

namespace Tests\Unit\Auth;

use App\Application\Auth\ResolveUserHomeRoute;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveUserHomeRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_is_resolved_to_dashboard_route(): void
    {
        $user = User::factory()->admin()->create();

        $target = app(ResolveUserHomeRoute::class)->handle($user);

        $this->assertSame('dashboard', $target->routeName);
        $this->assertSame([], $target->parameters);
    }

    public function test_team_user_is_resolved_to_own_task_route(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $target = app(ResolveUserHomeRoute::class)->handle($user);

        $this->assertSame('teams.tasks', $target->routeName);
        $this->assertSame(['team' => $team->id], $target->parameters);
    }

    public function test_user_without_team_or_admin_privilege_is_pending(): void
    {
        $user = User::factory()->create(['team_id' => null]);

        $target = app(ResolveUserHomeRoute::class)->handle($user);

        $this->assertSame('access.pending', $target->routeName);
        $this->assertSame([], $target->parameters);
    }
}
