<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
}; ?>

<div class="space-y-5 text-center">
    <div class="space-y-1">
        <h1 class="text-xl font-semibold tracking-tight text-foreground">Esqueceu sua senha?</h1>
        <p class="text-sm text-muted-foreground">Para recuperar o acesso, entre em contato com o administrador do sistema.</p>
    </div>

    <a href="{{ route('login') }}" wire:navigate
        class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
        Voltar ao login
    </a>
</div>
