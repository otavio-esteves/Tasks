<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\UserManager;
use App\Models\SystemSetting;
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
}
