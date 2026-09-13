<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="border-b border-border bg-background text-foreground">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ url('/') }}" wire:navigate class="flex items-center gap-3">
                <div class="hidden sm:block">
                    <div class="text-sm font-semibold text-foreground">{{ config('app.name') }}</div>
                    <div class="text-xs text-muted-foreground">Configurações do sistema</div>
                </div>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.teams') }}"
                        wire:navigate
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.teams') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                        Equipes
                    </a>

                    <a href="{{ route('admin.categories') }}"
                        wire:navigate
                        class="rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('admin.categories') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                        Categorias
                    </a>
                @endif
            </div>
        </div>

        <div class="hidden items-center gap-3 sm:flex">
            @unless (auth()->user()->isAdmin())
                <a href="{{ route('profile') }}"
                    wire:navigate
                    class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                    Perfil
                </a>
            @endunless

            <div class="flex items-center gap-3 rounded-md border border-border bg-muted/50 px-3 py-2">
                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="text-sm font-medium text-foreground"></div>
                <button wire:click="logout"
                    class="rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90">
                    Sair
                </button>
            </div>
        </div>

        <div class="flex items-center sm:hidden">
            <button @click="open = ! open" aria-label="Alternar menu" class="inline-flex items-center justify-center rounded-md border border-input p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-border px-4 pb-4 pt-3 sm:hidden">
        <div class="space-y-2">
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.teams') }}"
                    wire:navigate
                    class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.teams') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                    Equipes
                </a>

                <a href="{{ route('admin.categories') }}"
                    wire:navigate
                    class="block rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.categories') ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                    Categorias
                </a>
            @endif

            @unless (auth()->user()->isAdmin())
                <a href="{{ route('profile') }}"
                    wire:navigate
                    class="block rounded-md px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground">
                    Perfil
                </a>
            @endunless
        </div>

        <div class="mt-4 rounded-md border border-border bg-muted/50 p-3">
            <div class="text-sm font-medium text-foreground" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
            <div class="text-xs text-muted-foreground">{{ auth()->user()->email }}</div>
            <div class="mt-3">
                <button wire:click="logout"
                    class="w-full rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90">
                    Sair
                </button>
            </div>
        </div>
    </div>
</nav>
