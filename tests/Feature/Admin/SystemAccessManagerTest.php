<?php

namespace Tests\Feature\Admin;

use App\Application\System\Queries\GetPublicAccessUser;
use App\Livewire\Admin\SystemAccessManager;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SystemAccessManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_enable_public_access_through_a_common_team_user(): void
    {
        $admin = User::factory()->admin()->create();
        $publicUser = User::factory()->forTeam(Team::factory()->create())->unverified()->create();
        $otherTeam = Team::factory()->create();

        Livewire::actingAs($admin)
            ->test(SystemAccessManager::class)
            ->set('loginRequired', false)
            ->set('publicUserId', (string) $publicUser->id)
            ->call('save')
            ->assertSee('Configuração de acesso atualizada.');

        $this->assertDatabaseHas('system_settings', ['login_required' => false, 'public_user_id' => $publicUser->id]);
        $this->assertSame($publicUser->id, app(GetPublicAccessUser::class)->handle()?->id);

        auth()->logout();

        $this->get('/')
            ->assertRedirect(route('teams.tasks', $publicUser->team));
        $this->assertAuthenticatedAs($publicUser);
        $this->get(route('teams.tasks', $otherTeam))->assertForbidden();
    }

    public function test_public_access_cannot_use_an_administrator_account(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)
            ->test(SystemAccessManager::class)
            ->set('loginRequired', false)
            ->set('publicUserId', (string) $admin->id)
            ->call('save')
            ->assertHasErrors('publicUserId');
    }

    public function test_admin_login_bypass_remains_available_during_public_access(): void
    {
        $admin = User::factory()->admin()->create();
        $publicUser = User::factory()->forTeam(Team::factory()->create())->create();

        Livewire::actingAs($admin)
            ->test(SystemAccessManager::class)
            ->set('loginRequired', false)
            ->set('publicUserId', (string) $publicUser->id)
            ->call('save');

        auth()->logout();

        $this->get('/login?admin=1')->assertOk();
        $this->assertGuest();
    }
}
