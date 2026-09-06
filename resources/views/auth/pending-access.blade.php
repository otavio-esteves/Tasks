<x-app-layout>
    <x-slot name="header">
        <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Aguardando liberação de acesso</h1>
    </x-slot>

    <div class="mx-auto max-w-3xl px-4 py-6">
        <p class="text-sm text-gray-700 dark:text-gray-300">
            Sua conta foi criada. Entre em contato com o responsável pela organização para receber acesso à sua equipe.
        </p>
        <a href="{{ route('profile') }}" wire:navigate class="mt-4 inline-block text-sm font-medium underline focus:outline-none focus:ring-2 focus:ring-indigo-500">
            Gerenciar meu perfil
        </a>
        <a href="/" wire:navigate class="ml-4 mt-4 inline-block text-sm font-medium underline focus:outline-none focus:ring-2 focus:ring-indigo-500">
            Verificar acesso
        </a>
    </div>
</x-app-layout>
