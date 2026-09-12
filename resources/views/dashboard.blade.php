<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel da organização') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-blue-500">
                    <div class="text-sm font-medium text-gray-500 truncate">Total de Equipes</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $counters->activeTeams }}</div>
                </div>
                
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-green-500">
                    <div class="text-sm font-medium text-gray-500 truncate">Categorias Ativas</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $counters->activeCategories }}</div>
                </div>

                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 border-l-4 border-yellow-500">
                    <div class="text-sm font-medium text-gray-500 truncate">Tarefas pendentes</div>
                    <div class="mt-1 text-3xl font-semibold text-gray-900">{{ $counters->activePendingTasks }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-bold mb-4">Gestão de Entidades</h3>
                <div class="flex gap-4">
                    <a href="{{ route('admin.teams') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                        Gerenciar Equipes
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
