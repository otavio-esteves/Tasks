<?php

namespace Tests\Feature\Authorization;

use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\TeamManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $team = Team::factory()->create();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.teams'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.categories'))
            ->assertRedirect(route('login'));

        $this->get(route('teams.tasks', $team))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create(['team_id' => null]);

        $this->actingAs($admin)
            ->get(route('admin.teams'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('admin.categories'))
            ->assertOk();
    }

    public function test_team_user_cannot_access_admin_routes(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->actingAs($user)
            ->get(route('admin.teams'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.categories'))
            ->assertForbidden();
    }

    public function test_team_user_cannot_mount_admin_livewire_components(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($user)
            ->test(TeamManager::class)
            ->assertForbidden();

        Livewire::actingAs($user)
            ->test(CategoryManager::class)
            ->assertForbidden();
    }

    public function test_team_user_can_access_only_own_task_panel(): void
    {
        $ownTeam = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $ownTeam->id]);

        $this->actingAs($user)
            ->get(route('teams.tasks', $ownTeam))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('teams.tasks', $otherTeam))
            ->assertForbidden();
    }

    public function test_admin_can_access_any_task_panel(): void
    {
        $admin = User::factory()->create(['team_id' => null]);
        $team = Team::factory()->create();

        $this->actingAs($admin)
            ->get(route('teams.tasks', $team))
            ->assertOk();
    }
}
