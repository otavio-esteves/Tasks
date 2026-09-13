<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_explicitly_granted_and_revoked_admin_access(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->artisan('users:admin', ['email' => $user->email])
            ->expectsOutput('Administrative access granted.')
            ->assertSuccessful();

        $this->assertTrue($user->fresh()->isAdmin());
        $this->actingAs($user->fresh())->get(route('admin.teams'))->assertOk();

        $this->artisan('users:admin', ['email' => $user->email, '--revoke' => true])
            ->expectsOutput('Administrative access revoked.')
            ->assertSuccessful();

        $this->assertFalse($user->fresh()->isAdmin());
        $this->actingAs($user->fresh())->get(route('admin.teams'))->assertForbidden();
    }

    public function test_missing_account_fails_and_unverified_account_can_be_promoted(): void
    {
        User::factory()->admin()->create();
        $this->artisan('users:admin', ['email' => 'missing@example.com'])->assertFailed();

        $user = User::factory()->unverified()->create();
        $this->artisan('users:admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_access_can_be_revoked_even_if_the_account_is_no_longer_verified(): void
    {
        User::factory()->admin()->create();
        $user = User::factory()->admin()->unverified()->create();

        $this->artisan('users:admin', ['email' => $user->email, '--revoke' => true])->assertSuccessful();

        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_last_administrator_cannot_be_revoked_from_console(): void
    {
        $admin = User::factory()->admin()->create();

        $this->artisan('users:admin', ['email' => $admin->email, '--revoke' => true])
            ->expectsOutput('O sistema precisa manter pelo menos um administrador.')
            ->assertFailed();

        $this->assertTrue($admin->fresh()->isAdmin());
    }
}
