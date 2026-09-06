<?php

namespace Tests\Feature\Tasks;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasksBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_name_is_configurable_on_login_and_task_panel(): void
    {
        config(['app.name' => 'Tasks Example']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Tasks Example');

        $team = Team::factory()->create(['name' => 'Atendimento']);
        $user = User::factory()->create(['team_id' => $team->id]);

        $this->assertSame('/equipes/'.$team->id.'/tarefas', route('teams.tasks', $team, false));

        $this->actingAs($user)->get(route('teams.tasks', $team))
            ->assertOk()
            ->assertSee('Tasks Example')
            ->assertSee('Atendimento')
            ->assertSee('Nova Tarefa')
            ->assertDontSee('Prefeitura');
    }

    public function test_legacy_urls_redirect_with_the_same_access_control(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();
        $user = User::factory()->create(['team_id' => $team->id]);
        $legacyUrl = '/secretarias/'.$team->id.'/ods';

        $this->get($legacyUrl)->assertRedirect(route('login'));

        $this->actingAs($user)->get($legacyUrl)
            ->assertRedirect(route('teams.tasks', $team));
        $this->get('/secretarias/'.$otherTeam->id.'/ods')->assertForbidden();
        $this->get('/admin/secretarias')->assertForbidden();

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/secretarias')
            ->assertRedirect(route('admin.teams'));
    }
}
