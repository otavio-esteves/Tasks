<?php

namespace Tests\Feature\Admin;

use App\Application\Tasks\Validators\EnsureAssigneesBelongToTeam;
use App\Application\Users\Queries\ListTeamUsers;
use App\Livewire\Admin\UserManager;
use App\Models\SystemSetting;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_promote_and_demote_users_while_one_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->forTeam(Team::factory()->create())->create();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('setRole', $user->id, 'admin')
            ->assertSee('Cargo atualizado com sucesso.');

        $this->assertTrue($user->refresh()->isAdmin());

        Livewire::actingAs($user)
            ->test(UserManager::class)
            ->call('setRole', $admin->id, 'common')
            ->assertSee('Cargo atualizado com sucesso.');

        $this->assertFalse($admin->refresh()->isAdmin());
        $this->assertTrue($user->refresh()->isAdmin());
    }

    public function test_last_administrator_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('setRole', $admin->id, 'common')
            ->assertSee('O sistema precisa manter pelo menos um administrador.');

        $this->assertTrue($admin->refresh()->isAdmin());
    }

    public function test_common_user_cannot_manage_roles(): void
    {
        $user = User::factory()->forTeam(Team::factory()->create())->create();

        Livewire::actingAs($user)->test(UserManager::class)->assertForbidden();
    }

    public function test_public_access_account_cannot_be_promoted_to_administrator(): void
    {
        $admin = User::factory()->admin()->create();
        $publicUser = User::factory()->forTeam(Team::factory()->create())->create();
        SystemSetting::query()->create(['login_required' => false, 'public_user_id' => $publicUser->id]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('setRole', $publicUser->id, 'admin')
            ->assertSee('A conta usada no acesso sem login deve permanecer como usuário comum.');

        $this->assertFalse($publicUser->refresh()->isAdmin());
    }

    public function test_admin_can_create_a_team_user_without_email_verification(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openCreate')
            ->set('name', 'Nova Usuária')
            ->set('email', 'nova@example.com')
            ->set('password', 'password-123')
            ->set('passwordConfirmation', 'password-123')
            ->set('teamId', (string) $team->id)
            ->call('create')
            ->assertHasNoErrors()
            ->assertSee('Usuário criado com sucesso.');

        $user = User::query()->where('email', 'nova@example.com')->firstOrFail();
        $this->assertSame($team->id, $user->team_id);
        $this->assertFalse($user->isAdmin());
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('password-123', $user->password));
    }

    public function test_common_user_requires_a_team_and_duplicate_email_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existente@example.com']);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->set('name', 'Usuário sem equipe')
            ->set('email', 'novo@example.com')
            ->set('password', 'password-123')
            ->set('passwordConfirmation', 'password-123')
            ->call('create')
            ->assertHasErrors('teamId')
            ->set('teamId', (string) Team::factory()->create()->id)
            ->set('email', 'EXISTENTE@example.com')
            ->call('create')
            ->assertHasErrors('email');
    }

    public function test_admin_can_move_a_user_to_another_team_and_access_follows_the_change(): void
    {
        $admin = User::factory()->admin()->create();
        $oldTeam = Team::factory()->create();
        $newTeam = Team::factory()->create();
        $user = User::factory()->forTeam($oldTeam)->create();
        $colleague = User::factory()->forTeam($oldTeam)->create();
        $task = Task::factory()->forTeam($oldTeam)->create();
        $task->assignees()->attach([$user->id, $colleague->id]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $user->id)
            ->assertSet('editTeamIds', [(string) $oldTeam->id])
            ->set('editTeamIds', [(string) $newTeam->id])
            ->call('saveTeams')
            ->assertHasNoErrors()
            ->assertSee('Equipes do usuário atualizadas com sucesso.');

        $this->assertSame($newTeam->id, $user->refresh()->team_id);
        $this->assertEqualsCanonicalizing([$colleague->id], $task->assignees()->pluck('users.id')->all());
        $this->assertDatabaseHas('task_histories', [
            'task_id' => $task->id,
            'user_id' => $admin->id,
            'description' => 'Responsável removido após mudança de equipe.',
        ]);

        $this->actingAs($user)
            ->get(route('teams.tasks', $oldTeam))
            ->assertForbidden();

        $this->get(route('teams.tasks', $newTeam))->assertOk();
    }

    public function test_common_user_cannot_be_left_without_a_team_or_moved_to_an_inactive_team(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $inactiveTeam = Team::factory()->create();
        $inactiveTeam->delete();
        $user = User::factory()->forTeam($team)->create();

        $component = Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $user->id)
            ->set('editTeamIds', [])
            ->call('saveTeams')
            ->assertHasErrors('editTeamIds');

        $component->set('editTeamIds', [(string) $inactiveTeam->id])
            ->call('saveTeams')
            ->assertHasErrors('editTeamIds');

        $this->assertSame($team->id, $user->refresh()->team_id);
    }

    public function test_admin_can_remove_another_administrators_team(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->forTeam(Team::factory()->create())->create();

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $target->id)
            ->set('editTeamIds', [])
            ->call('saveTeams')
            ->assertHasNoErrors();

        $this->assertNull($target->refresh()->team_id);
        $this->assertTrue($target->isAdmin());
    }

    public function test_admin_can_assign_a_team_to_a_user_waiting_for_access(): void
    {
        $admin = User::factory()->admin()->create();
        $team = Team::factory()->create();
        $user = User::factory()->create(['team_id' => null]);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $user->id)
            ->set('editTeamIds', [(string) $team->id])
            ->call('saveTeams')
            ->assertHasNoErrors();

        $this->assertSame($team->id, $user->refresh()->team_id);
        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk();
    }

    public function test_admin_can_add_a_second_team_without_removing_the_first(): void
    {
        $admin = User::factory()->admin()->create();
        $firstTeam = Team::factory()->create();
        $secondTeam = Team::factory()->create();
        $user = User::factory()->forTeam($firstTeam)->create();
        $task = Task::factory()->forTeam($firstTeam)->create();
        $task->assignees()->attach($user);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $user->id)
            ->set('editTeamIds', [(string) $firstTeam->id, (string) $secondTeam->id])
            ->call('saveTeams')
            ->assertHasNoErrors();

        $this->assertSame($firstTeam->id, $user->refresh()->team_id);
        $this->assertTrue($user->belongsToTeam($secondTeam->id));
        $this->assertTrue(app(ListTeamUsers::class)->handle($secondTeam->id)->contains('id', $user->id));
        app(EnsureAssigneesBelongToTeam::class)->handle($secondTeam->id, [$user->id]);
        $this->assertDatabaseHas('task_user', ['task_id' => $task->id, 'user_id' => $user->id]);

        $this->actingAs($user)->get(route('teams.tasks', $firstTeam))->assertOk();
        $this->get(route('teams.tasks', $secondTeam))->assertOk();
    }

    public function test_removing_a_secondary_team_releases_only_its_task_assignments(): void
    {
        $admin = User::factory()->admin()->create();
        $primaryTeam = Team::factory()->create();
        $secondaryTeam = Team::factory()->create();
        $user = User::factory()->forTeam($primaryTeam)->create();
        $user->additionalTeams()->attach($secondaryTeam->id);
        $primaryTask = Task::factory()->forTeam($primaryTeam)->create();
        $secondaryTask = Task::factory()->forTeam($secondaryTeam)->create();
        $primaryTask->assignees()->attach($user->id);
        $secondaryTask->assignees()->attach($user->id);

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openTeamEdit', $user->id)
            ->assertSet('editTeamIds', [(string) $primaryTeam->id, (string) $secondaryTeam->id])
            ->set('editTeamIds', [(string) $primaryTeam->id])
            ->call('saveTeams')
            ->assertHasNoErrors();

        $this->assertTrue($user->refresh()->belongsToTeam($primaryTeam->id));
        $this->assertFalse($user->belongsToTeam($secondaryTeam->id));
        $this->assertDatabaseHas('task_user', ['task_id' => $primaryTask->id, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('task_user', ['task_id' => $secondaryTask->id, 'user_id' => $user->id]);
    }
}
