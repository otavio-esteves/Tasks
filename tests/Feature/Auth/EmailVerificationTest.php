<?php

namespace Tests\Feature\Auth;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_team_user_can_access_the_application(): void
    {
        $team = Team::factory()->create();
        $user = User::factory()->forTeam($team)->unverified()->create();

        $this->actingAs($user)
            ->get(route('teams.tasks', $team))
            ->assertOk();

        $this->get('/')->assertRedirect(route('teams.tasks', $team));
    }

    public function test_email_verification_routes_are_not_available(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get('/verify-email')->assertNotFound();
        $this->get('/verify-email/1/invalid')->assertNotFound();
    }
}
