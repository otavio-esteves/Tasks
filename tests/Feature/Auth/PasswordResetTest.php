<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Livewire\Volt\Volt;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_directs_users_to_the_administrator(): void
    {
        $response = $this->get('/forgot-password');

        $response
            ->assertSeeVolt('pages.auth.forgot-password')
            ->assertOk()
            ->assertSee('entre em contato com o administrador do sistema')
            ->assertSee('Voltar ao login')
            ->assertDontSee('wire:submit="sendPasswordResetLink"', false);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get('/reset-password/'.$token)
            ->assertSeeVolt('pages.auth.reset-password')
            ->assertOk();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        Volt::test('pages.auth.reset-password', ['token' => $token])
            ->set('email', $user->email)
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('resetPassword')
            ->assertRedirect('/login')
            ->assertHasNoErrors();
    }
}
