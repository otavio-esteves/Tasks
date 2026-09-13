<?php

use App\Application\Auth\ResolveUserHomeRoute;
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(ResolveUserHomeRoute $resolveUserHomeRoute): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $target = $resolveUserHomeRoute->handle($user);

        $this->redirectIntended(default: $target->toUrl(), navigate: true);
    }
}; ?>

<div data-testid="login-card">
    <div class="mb-6 space-y-1">
        <h1 class="text-xl font-semibold tracking-tight text-foreground">Acessar sua conta</h1>
        <p class="text-sm text-muted-foreground">Informe suas credenciais para continuar.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <div class="space-y-1.5">
            <x-input-label for="email" value="E-mail" />
            <x-text-input wire:model="form.email" id="email" class="block w-full bg-muted/50 focus:bg-background" type="email" name="email" required autofocus autocomplete="username" placeholder="nome@empresa.com" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <div class="mt-4 space-y-1.5">
            <x-input-label for="password" value="Senha" />

            <x-text-input wire:model="form.password" id="password" class="block w-full bg-muted/50 focus:bg-background"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <label for="remember" class="inline-flex items-center gap-2 text-sm text-muted-foreground">
                <input wire:model="form.remember" id="remember" type="checkbox" class="system-checkbox" name="remember">
                <span>Lembrar de mim</span>
            </label>
        </div>

        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm text-muted-foreground transition-colors hover:text-foreground focus:outline-none focus:ring-2 focus:ring-ring" href="{{ route('password.request') }}" wire:navigate>
                    Esqueceu a senha?
                </a>
            @endif

            <x-primary-button class="w-full sm:w-auto">
                Entrar
            </x-primary-button>
        </div>
    </form>
</div>
