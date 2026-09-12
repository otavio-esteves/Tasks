<?php

namespace Tests\Feature\Auth;

use App\Application\Dashboard\Data\DashboardCountersData;
use App\Domain\Tasks\TaskStatus;
use App\Models\Category;
use App\Models\Task;
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

    public function test_root_route_redirects_admin_users_to_dashboard(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_root_route_redirects_admin_linked_to_team_to_dashboard(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));

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

    public function test_admin_users_are_redirected_to_dashboard_after_login(): void
    {
        $user = User::factory()->admin()->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_admin_linked_to_team_is_redirected_to_dashboard_after_login(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $component
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($team->id, $user->fresh()->team_id);
    }

    public function test_team_users_are_redirected_to_their_own_dashboard_after_login(): void
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

    public function test_dashboard_route_renders_for_admin_users(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user);

        $response = $this->get('/dashboard');

        $response
            ->assertOk()
            ->assertSeeVolt('layout.navigation');
    }

    public function test_dashboard_route_renders_for_admin_linked_to_team(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->admin()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeVolt('layout.navigation');

        $this->assertSame($team->id, $user->fresh()->team_id);
    }

    public function test_admin_dashboard_displays_real_active_counters(): void
    {
        $admin = User::factory()->admin()->create();
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();
        Team::factory()->create()->delete();
        $firstCategory = Category::factory()->for($firstTeam)->create();
        $secondCategory = Category::factory()->for($secondTeam)->create();
        Category::factory()->for($firstTeam)->create()->delete();
        Task::factory()->forCategory($firstCategory)->withStatus(TaskStatus::Pending)->count(2)->create();
        Task::factory()->forCategory($firstCategory)->withStatus(TaskStatus::InProgress)->create();
        Task::factory()->forCategory($secondCategory)->withStatus(TaskStatus::Completed)->create();
        Task::factory()->forCategory($secondCategory)->withStatus(TaskStatus::Pending)->create()->delete();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response
            ->assertOk()
            ->assertViewHas('counters', function (DashboardCountersData $counters): bool {
                return $counters->activeTeams === 2
                    && $counters->activeCategories === 2
                    && $counters->activePendingTasks === 2;
            })
            ->assertSeeInOrder([
                'Total de Equipes',
                '>2<',
                'Categorias Ativas',
                '>2<',
                'Tarefas pendentes',
                '>2<',
            ], false);
    }

    public function test_dashboard_route_redirects_team_users_to_their_own_task_panel(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('teams.tasks', ['team' => $team->id]));
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
