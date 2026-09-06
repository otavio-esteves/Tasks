<?php

namespace Tests\Feature\Authorization;

use App\Livewire\Admin\CategoryManager;
use App\Livewire\Admin\TeamManager;
use App\Livewire\Team\TaskManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AdministratorAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_cannot_access_organization_data_even_after_email_verification(): void
    {
        $team = Team::factory()->create(['name' => 'Private team']);

        Volt::test('pages.auth.register')
            ->set('name', 'New User')
            ->set('email', 'new@example.com')
            ->set('password', 'secure-password')
            ->set('password_confirmation', 'secure-password')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('access.pending', absolute: false));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->team_id);
        $this->actingAs($user)->get(route('access.pending'))->assertRedirect(route('verification.notice'));
        $user->markEmailAsVerified();

        $this->actingAs($user)->get('/')->assertRedirect(route('access.pending'));
        $this->get(route('dashboard'))->assertRedirect(route('access.pending'));
        $this->get(route('access.pending'))->assertOk()->assertDontSee('Private team');
        $this->get(route('profile'))->assertOk();
        $this->get(route('admin.teams'))->assertForbidden();
        $this->get(route('admin.categories'))->assertForbidden();
        $this->get(route('teams.tasks', $team))->assertForbidden();
        $this->get('/admin/secretarias')->assertForbidden();
        $this->get('/secretarias/'.$team->id.'/ods')->assertForbidden();

        Livewire::actingAs($user)->test(TeamManager::class)->assertForbidden();
        Livewire::actingAs($user)->test(CategoryManager::class)->assertForbidden();
        Livewire::actingAs($user)->test(TaskManager::class, ['team' => $team])->assertForbidden();
    }

    public function test_admin_privilege_cannot_be_mass_assigned(): void
    {
        $user = User::factory()->create();
        $user->fill(['is_admin' => true])->save();

        $this->assertFalse($user->fresh()->isAdmin());
        $this->assertFalse($user->isFillable('is_admin'));
    }

    public function test_removing_team_membership_does_not_grant_admin_access(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $team->forceDelete();

        $user->refresh();
        $this->assertNull($user->team_id);
        $this->assertFalse($user->isAdmin());
        $this->actingAs($user)->get(route('admin.teams'))->assertForbidden();
        $this->get(route('dashboard'))->assertRedirect(route('access.pending'));
    }

    public function test_migration_does_not_trust_legacy_accounts_without_a_team(): void
    {
        $legacyUser = User::factory()->create(['team_id' => null]);
        $migration = require database_path('migrations/2026_09_06_000000_add_explicit_admin_privilege_to_users.php');
        $migration->down();

        $this->assertDatabaseHas('users', ['id' => $legacyUser->id, 'team_id' => null]);
        $migration->up();

        $this->assertFalse($legacyUser->fresh()->isAdmin());
        $this->assertFalse((bool) DB::table('users')->where('id', $legacyUser->id)->value('is_admin'));
    }

    public function test_pending_user_gains_only_assigned_team_access(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create();
        $user->update(['team_id' => $team->id]);

        $this->actingAs($user)->get('/')->assertRedirect(route('teams.tasks', $team));
        $this->get(route('teams.tasks', $team))->assertOk();
        $this->get(route('teams.tasks', $otherTeam))->assertForbidden();
        $this->get(route('admin.teams'))->assertForbidden();
    }
}
