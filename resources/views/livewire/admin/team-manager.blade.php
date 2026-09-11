<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            @if (session()->has('message'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    {{ session('message') }}
                </div>
            @endif

            <div class="flex justify-between items-center mb-6">
                <input wire:model.live="search" type="text" placeholder="Buscar equipes..." class="border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-1/3">
                <button wire:click="create()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition">
                    Nova Equipe
                </button>
            </div>

            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="p-3 border-b">Nome</th>
                        <th class="p-3 border-b">Categorias</th>
                        <th class="p-3 border-b text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teams as $team)
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 border-b">
                                <div class="font-semibold text-gray-900">{{ $team->name }}</div>
                                <div class="text-sm text-gray-500">{{ $team->description ?: 'Sem descricao informada.' }}</div>
                            </td>
                            <td class="p-3 border-b">
                                <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    {{ $team->categories_count }}
                                </span>
                            </td>
                            <td class="p-3 border-b text-center">
                                <button wire:click="edit({{ $team->id }})" class="text-blue-600 hover:underline mr-3">Editar</button>
                                <button wire:click="delete({{ $team->id }})" wire:confirm="Tem certeza que deseja mover para a lixeira?" class="text-red-600 hover:underline">Excluir</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="mt-4">
                {{ $teams->links() }}
            </div>
        </div>
    </div>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center overflow-x-hidden overflow-y-auto outline-none focus:outline-none">
            <div class="fixed inset-0 bg-black opacity-50"></div>
            <div class="relative w-full max-w-lg mx-auto my-6 z-50">
                <div class="bg-white rounded-md shadow-sm relative flex flex-col w-full outline-none focus:outline-none">
                    <div class="p-6 border-b border-solid border-gray-200 rounded-t">
                        <h3 class="text-xl font-semibold">{{ $form->selected_id ? 'Editar Equipe' : 'Nova Equipe' }}</h3>
                    </div>

                    <div class="p-6 flex-auto">
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Nome da Equipe</label>
                            <input type="text" wire:model="form.name" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('form.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">Descrição (Opcional)</label>
                            <textarea wire:model="form.description" class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                            @error('form.description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="p-6 border-t border-solid border-gray-200 rounded-b flex justify-end">
                        <button wire:click="closeModal()" class="text-gray-500 font-bold py-2 px-4 mr-2">Cancelar</button>
                        <button wire:click="store()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
