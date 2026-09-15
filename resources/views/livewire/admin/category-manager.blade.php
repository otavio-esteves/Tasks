<div class="min-h-0 bg-background text-foreground">
    <main class="mx-auto w-full max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="mb-1 text-xs font-medium uppercase tracking-wider text-muted-foreground">Configurações</p>
                <h1 class="text-2xl font-semibold tracking-tight">Categorias</h1>
                <p class="mt-1 text-sm text-muted-foreground">Organize as categorias disponíveis para cada equipe.</p>
            </div>

            <button type="button" wire:click="create"
                class="inline-flex h-9 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background sm:w-auto">
                <i class="ph ph-plus text-base" aria-hidden="true"></i>
                Nova categoria
            </button>
        </div>

        @if (session()->has('message'))
            <div class="mb-4 flex items-center gap-2 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2.5 text-sm text-emerald-700 dark:text-emerald-300" role="status">
                <i class="ph ph-check-circle text-base" aria-hidden="true"></i>
                <span>{{ session('message') }}</span>
            </div>
        @endif

        <section class="overflow-hidden rounded-shadcn border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <i class="ph ph-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground" aria-hidden="true"></i>
                    <label for="category-search" class="sr-only">Buscar categorias</label>
                    <input id="category-search" wire:model.live.debounce.300ms="search" type="search" placeholder="Buscar categorias..."
                        class="h-9 w-full rounded-md border border-input bg-muted/50 py-2 pl-9 pr-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:bg-background focus:ring-1 focus:ring-ring">
                </div>

                <span class="text-xs text-muted-foreground">{{ $categories->total() }} {{ $categories->total() === 1 ? 'categoria cadastrada' : 'categorias cadastradas' }}</span>
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="w-full min-w-[38rem] text-left text-sm">
                    <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground">
                        <tr>
                            <th scope="col" class="h-10 px-4 font-medium">Categoria</th>
                            <th scope="col" class="h-10 px-4 font-medium">Equipe responsável</th>
                            <th scope="col" class="h-10 px-4 text-right font-medium">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        @forelse($categories as $category)
                            <tr wire:key="category-{{ $category->id }}" class="transition-colors hover:bg-muted/40">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-foreground">{{ $category->name }}</div>
                                    <div class="mt-0.5 max-w-2xl text-xs text-muted-foreground">{{ $category->description ?: 'Sem descrição informada.' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 rounded-md border border-border bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">
                                        <i class="ph ph-users-three" aria-hidden="true"></i>
                                        {{ $category->team->name }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" wire:click="edit({{ $category->id }})"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            aria-label="Editar categoria {{ $category->name }}">
                                            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                                            Editar
                                        </button>
                                        <button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Tem certeza que deseja mover para a lixeira?"
                                            class="inline-flex h-8 items-center gap-1.5 rounded-md px-2.5 text-xs font-medium text-destructive transition-colors hover:bg-destructive/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                            aria-label="Excluir categoria {{ $category->name }}">
                                            <i class="ph ph-trash" aria-hidden="true"></i>
                                            Excluir
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-md border border-border bg-muted text-muted-foreground">
                                        <i class="ph ph-tag text-xl" aria-hidden="true"></i>
                                    </div>
                                    <p class="mt-3 text-sm font-medium">Nenhuma categoria encontrada</p>
                                    <p class="mt-1 text-xs text-muted-foreground">Ajuste a busca ou cadastre uma nova categoria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="divide-y divide-border sm:hidden">
                @forelse($categories as $category)
                    <article wire:key="category-card-{{ $category->id }}" class="space-y-3 px-4 py-3">
                        <div class="min-w-0"><h2 class="truncate text-sm font-medium">{{ $category->name }}</h2><p class="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{{ $category->description ?: 'Sem descrição informada.' }}</p></div>
                        <span class="inline-flex max-w-full items-center gap-1.5 truncate rounded-md border border-border bg-secondary px-2 py-1 text-xs font-medium text-secondary-foreground"><i class="ph ph-users-three shrink-0" aria-hidden="true"></i><span class="truncate">{{ $category->team->name }}</span></span>
                        <div class="grid grid-cols-2 gap-2"><button type="button" wire:click="edit({{ $category->id }})" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-input bg-background text-xs font-medium text-foreground shadow-sm"><i class="ph ph-pencil-simple" aria-hidden="true"></i>Editar</button><button type="button" wire:click="delete({{ $category->id }})" wire:confirm="Tem certeza que deseja mover para a lixeira?" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md border border-destructive/30 text-xs font-medium text-destructive"><i class="ph ph-trash" aria-hidden="true"></i>Excluir</button></div>
                    </article>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-muted-foreground">Nenhuma categoria encontrada.</p>
                @endforelse
            </div>

            @if($categories->hasPages())
                <div class="border-t border-border px-4 py-3">
                    {{ $categories->links() }}
                </div>
            @endif
        </section>
    </main>

    @if($isModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-2 backdrop-blur-sm sm:p-4" role="dialog" aria-modal="true" aria-labelledby="category-modal-title">
            <div class="max-h-[calc(100dvh-1rem)] w-full max-w-lg overflow-y-auto rounded-shadcn border border-border bg-background text-foreground shadow-lg custom-scrollbar">
                <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5 sm:py-4">
                    <div class="min-w-0">
                        <h2 id="category-modal-title" class="text-base font-semibold tracking-tight">{{ $form->selected_id ? 'Editar categoria' : 'Nova categoria' }}</h2>
                        <p class="mt-0.5 truncate text-xs text-muted-foreground">{{ $form->selected_id ? 'Atualize os dados da categoria selecionada.' : 'Defina a equipe responsável e os dados da categoria.' }}</p>
                    </div>
                    <button type="button" wire:click="closeModal" aria-label="Fechar formulário"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                        <i class="ph ph-x text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form wire:submit="store">
                    <div class="space-y-4 p-4 sm:px-5 sm:py-5">
                        <div class="space-y-1.5">
                            <label for="category-team" class="text-xs font-medium text-foreground">Equipe responsável</label>
                            <x-system-select id="category-team" model="form.team_id" :value="$form->team_id" :options="$teams->pluck('name', 'id')->all()" placeholder="Selecione uma equipe..." icon="users-three" />
                            @error('form.team_id') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="category-name" class="text-xs font-medium text-foreground">Nome da categoria</label>
                            <input id="category-name" type="text" wire:model="form.name" autocomplete="off"
                                class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:bg-background focus:ring-1 focus:ring-ring">
                            @error('form.name') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>

                        <div class="space-y-1.5">
                            <label for="category-description" class="text-xs font-medium text-foreground">Descrição <span class="font-normal text-muted-foreground">(opcional)</span></label>
                            <textarea id="category-description" wire:model="form.description" rows="4"
                                class="w-full resize-none rounded-md border border-input bg-muted/50 px-3 py-2 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:bg-background focus:ring-1 focus:ring-ring"></textarea>
                            @error('form.description') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 border-t border-border bg-muted/30 px-4 py-3 sm:flex sm:justify-end sm:px-5">
                        <button type="button" wire:click="closeModal"
                            class="inline-flex h-9 items-center justify-center rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <i class="ph ph-floppy-disk text-base" aria-hidden="true"></i>
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
