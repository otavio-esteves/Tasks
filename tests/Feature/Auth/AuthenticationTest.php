<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.login');
    }

    public function test_root_route_redirects_guest_users_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_root_route_redirects_admin_without_team_to_first_team_tasks(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('teams.tasks', $team));
    }

    public function test_root_route_redirects_admin_linked_to_team_to_own_tasks(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('teams.tasks', $team));

        $this->assertSame($team->id, $user->fresh()->team_id);
    }

    public function test_root_route_redirects_team_users_to_their_task_panel(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('teams.tasks', ['team' => $team->id]));
    }

    public function test_admin_users_are_redirected_to_tasks_after_login(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('teams.tasks', $team, absolute: false));

        $this->assertAuthenticated();
    }

    public function test_admin_linked_to_team_is_redirected_to_own_tasks_after_login(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('teams.tasks', $team, absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($team->id, $user->fresh()->team_id);
    }

    public function test_team_users_are_redirected_to_their_own_tasks_after_login(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('teams.tasks', ['team' => $team->id], absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'wrong-password');

        $component->call('login');

        $component
            ->assertHasErrors()
            ->assertNoRedirect();

        $this->assertGuest();
    }

    public function test_removed_dashboard_route_is_not_available(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertNotFound();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $component = Volt::test('layout.navigation');

        $component->call('logout');

        $component
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
