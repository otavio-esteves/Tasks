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

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-md bg-slate-900 text-sm font-black tracking-widest text-white dark:bg-white dark:text-slate-900">
                    <x-application-logo class="h-6 w-6" />
                </div>
                <div class="hidden sm:block">
                    <div class="text-sm font-semibold text-slate-900 dark:text-white">{{ config('app.name') }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">Painel administrativo</div>
                </div>
            </a>

            <div class="hidden items-center gap-2 md:flex">
                <a href="{{ route('dashboard') }}"
                    wire:navigate
                    class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Dashboard
                </a>

                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.teams') }}"
                        wire:navigate
                        class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.teams') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        Equipes
                    </a>

                    <a href="{{ route('admin.categories') }}"
                        wire:navigate
                        class="rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('admin.categories') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                        Categorias
                    </a>
                @endif
            </div>
        </div>

        <div class="hidden items-center gap-3 sm:flex">
            <a href="{{ route('profile') }}"
                wire:navigate
                class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">
                Perfil
            </a>

            <div class="flex items-center gap-3 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-900">
                <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name" class="text-sm font-medium text-slate-700 dark:text-slate-200"></div>
                <button wire:click="logout"
                    class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Sair
                </button>
            </div>
        </div>

        <div class="flex items-center sm:hidden">
            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-lg border border-slate-200 p-2 text-slate-500 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-400 dark:hover:bg-slate-700">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 px-4 pb-4 pt-3 sm:hidden dark:border-slate-700">
        <div class="space-y-2">
            <a href="{{ route('dashboard') }}"
                wire:navigate
                class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                Dashboard
            </a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.teams') }}"
                    wire:navigate
                    class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.teams') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Equipes
                </a>

                <a href="{{ route('admin.categories') }}"
                    wire:navigate
                    class="block rounded-lg px-3 py-2 text-sm font-medium {{ request()->routeIs('admin.categories') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' }}">
                    Categorias
                </a>
            @endif

            <a href="{{ route('profile') }}"
                wire:navigate
                class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700">
                Perfil
            </a>
        </div>

        <div class="mt-4 rounded-md border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900">
            <div class="text-sm font-medium text-slate-800 dark:text-slate-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</div>
            <div class="mt-3">
                <button wire:click="logout"
                    class="w-full rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white transition hover:bg-slate-700 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200">
                    Sair
                </button>
            </div>
        </div>
    </div>
</nav>
