<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Tasks') }}</title>

        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="flex min-h-[100dvh] items-center justify-center bg-muted/40 px-3 py-6 text-foreground sm:px-4 sm:py-10">
            <div class="w-full max-w-sm">
                <a href="/" wire:navigate class="mb-6 block text-center">
                    <span class="text-xl font-semibold tracking-tight text-foreground">{{ config('app.name') }}</span>
                    <span class="mt-1 block text-sm text-muted-foreground">Gestão de equipes e tarefas</span>
                </a>

                <main class="overflow-hidden rounded-shadcn border border-border bg-card p-5 text-card-foreground shadow-sm sm:p-7">
                    {{ $slot }}
                </main>

                <p class="mt-5 text-center text-xs text-muted-foreground">Acesso restrito a usuários autorizados.</p>
            </div>
        </div>
    </body>
</html>
