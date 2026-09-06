<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdministratorCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_be_explicitly_granted_and_revoked_admin_access(): void
    {
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

    public function test_missing_or_unverified_accounts_cannot_be_promoted(): void
    {
        $this->artisan('users:admin', ['email' => 'missing@example.com'])->assertFailed();
        $this->assertDatabaseCount('users', 0);

        $user = User::factory()->unverified()->create();
        $this->artisan('users:admin', ['email' => $user->email])->assertFailed();
        $this->assertFalse($user->fresh()->isAdmin());
    }

    public function test_access_can_be_revoked_even_if_the_account_is_no_longer_verified(): void
    {
        $user = User::factory()->admin()->unverified()->create();

        $this->artisan('users:admin', ['email' => $user->email, '--revoke' => true])->assertSuccessful();

        $this->assertFalse($user->fresh()->isAdmin());
    }
}
