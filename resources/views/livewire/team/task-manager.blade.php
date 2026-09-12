<div class="task-reference-font flex h-screen flex-col overflow-hidden bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
     wire:ignore.self
     x-data="{ 
        sidebarOpen: false,
        adminOpen: {{ request()->routeIs('admin.*') ? 'true' : 'false' }},
        filterOpen: false,
        modalOpen: false, 
        settingsOpen: false,
        settingsTab: 'profile',
        modalView: 'details', 
        mode: 'create',
        viewMode: localStorage.getItem('taskViewMode') === 'list' ? 'list' : 'grid'
     }"
     x-init="
        document.documentElement.classList.add('dark');
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
     "
     x-on:open-task-modal.window="modalOpen = true; modalView = $event.detail.view || 'details'; mode = $event.detail.mode"
     x-on:task-saved.window="if (!$wire.showCategoryModal) modalOpen = false"
     x-on:task-modal-closed.window="if (!$wire.showCategoryModal) modalOpen = false"
     x-on:keydown.escape.window="
        if ($wire.showCategoryModal) {
            $wire.closeCategoryModal();
            return;
        }
        if (modalOpen) {
            $wire.closeModal();
        } else if (settingsOpen) {
            settingsOpen = false;
        } else if (sidebarOpen) {
            sidebarOpen = false;
        } else if (filterOpen) {
            filterOpen = false;
        }
     ">

    <div id="sidebarOverlay" 
        x-show="sidebarOpen"
        x-on:click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        class="fixed inset-0 bg-slate-900/20 dark:bg-black/40 backdrop-blur-sm z-40"></div>

    <aside id="sidebar"
        class="fixed left-0 top-0 z-50 flex h-full w-[23.4rem] max-w-[90vw] transform flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-lg transition-transform duration-300 ease-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex shrink-0 items-center justify-between border-b border-sidebar-border bg-sidebar p-2.5">
            <div class="flex items-center gap-1">
                <div class="text-black p-1.5 rounded-md shadow-slate-900/20 dark:text-white">
                    <x-application-logo class="h-6 w-6" />
                </div>
                <span class="font-semibold tracking-tight text-sidebar-accent-foreground">
                    {{ config('app.name') }}
                </span>
            </div>
            <button x-on:click="sidebarOpen = false" aria-label="Fechar menu lateral" class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-3 space-y-6 custom-scrollbar">
            <div>
                <h3 class="mb-2 px-3 text-[10px] font-medium text-muted-foreground">Geral</h3>
                <nav class="space-y-0.5">
                    <a href="{{ route('teams.tasks', $team) }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('teams.tasks') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                        <i class="ph-duotone ph-clipboard-text text-lg"></i>
                        Tarefas
                    </a>

                    <a href="{{ route('dashboard') }}"
                        wire:navigate
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                        <i class="ph-duotone ph-chart-pie-slice text-lg"></i>
                        Dashboard
                    </a>

                    <a href="#"
                        class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                        <i class="ph-duotone ph-scroll text-lg"></i>
                        Relatórios
                    </a>
                </nav>
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <h3 class="mb-2 px-3 text-[10px] font-medium text-muted-foreground">Administração</h3>
                    <nav class="space-y-0.5">
                        <button type="button"
                            x-on:click="adminOpen = !adminOpen"
                            class="group flex w-full items-center justify-between rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <div class="flex items-center gap-3">
                                <i class="ph-duotone ph-bank text-lg"></i>
                                <span>Equipes</span>
                            </div>
                            <i class="ph ph-caret-down text-muted-foreground transition-transform duration-200" :class="adminOpen ? 'rotate-180' : ''"></i>
                        </button>

                        <div x-show="adminOpen"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            x-cloak
                            class="space-y-0.5 pt-1">
                            
                            <a href="{{ route('admin.teams') }}"
                                wire:navigate
                                class="flex items-center gap-3 rounded-md py-2 pl-10 pr-3 text-sm font-medium transition-colors {{ request()->routeIs('admin.teams') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                                <i class="ph-duotone ph-buildings text-lg opacity-70"></i>
                                Listagem
                            </a>

                            <a href="{{ route('admin.categories') }}"
                                wire:navigate
                                class="flex items-center gap-3 rounded-md py-2 pl-10 pr-3 text-sm font-medium transition-colors {{ request()->routeIs('admin.categories') ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                                <i class="ph-duotone ph-tag text-lg opacity-70"></i>
                                Categorias
                            </a>
                        </div>
                    </nav>
                </div>
            @endif
        </div>

        <div class="shrink-0 border-t border-sidebar-border bg-sidebar px-3 py-2">
            <button x-on:click="settingsOpen = true; sidebarOpen = false"
                class="group flex w-full items-center gap-3 rounded-md px-2.5 py-2 text-left transition-colors hover:bg-sidebar-accent">
                <div class="flex h-8 w-8 items-center justify-center rounded-md bg-sidebar-accent text-xs font-medium text-sidebar-accent-foreground">
                    {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="truncate text-xs font-medium text-sidebar-accent-foreground">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] text-muted-foreground">Configurações</span>
                </div>
                <i class="ph ph-gear ml-auto mt-0.5 text-muted-foreground group-hover:text-sidebar-accent-foreground"></i>
            </button>
        </div>
    </aside>

    <div id="modalSettings" 
         x-show="settingsOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4">
        
        <div class="flex h-[600px] max-h-[90vh] w-full max-w-4xl transform overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg transition-[opacity,transform]"
             x-show="settingsOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-on:click.away="settingsOpen = false">
            
            <aside class="hidden w-56 shrink-0 flex-col gap-4 border-r border-border bg-muted/40 p-4 md:flex">
                <div class="flex items-center justify-between px-2 py-1">
                    <h2 class="text-base font-semibold tracking-tight">Configurações</h2>
                </div>

                <nav class="space-y-1">
                    <button x-on:click="settingsTab = 'profile'"
                        class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors"
                        :class="settingsTab === 'profile' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <i class="ph-duotone ph-user text-lg"></i>
                        Perfil
                    </button>
                    <button x-on:click="settingsTab = 'security'"
                        class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors"
                        :class="settingsTab === 'security' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <i class="ph-duotone ph-shield text-lg"></i>
                        Segurança
                    </button>
                    <button x-on:click="settingsTab = 'theme'"
                        class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors"
                        :class="settingsTab === 'theme' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <i class="ph-duotone ph-paint-brush text-lg"></i>
                        Tema
                    </button>
                    <button x-on:click="settingsTab = 'about'"
                        class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors"
                        :class="settingsTab === 'about' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'">
                        <i class="ph-duotone ph-info text-lg"></i>
                        Sobre
                    </button>
                    <div class="mt-4 border-t border-border pt-4">
                        <button x-on:click="$wire.logout()"
                            class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium text-destructive transition-colors hover:bg-destructive/10">
                            <i class="ph-duotone ph-sign-out text-lg"></i>
                            Encerrar Sessão
                        </button>
                    </div>
                </nav>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col bg-background">
                <div class="flex shrink-0 items-center justify-between border-b border-border px-6 py-4 md:justify-end">
                    <h2 class="text-base font-semibold tracking-tight md:hidden">Configurações</h2>
                    <button type="button" x-on:click="settingsOpen = false" aria-label="Fechar configurações"
                        class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto p-6 md:p-8 custom-scrollbar">
                    <div class="mx-auto max-w-2xl">
                        
                        <div x-show="settingsTab === 'profile'" class="flex flex-col-reverse gap-6 md:flex-row md:gap-8">
                            <div class="flex-1">
                                <livewire:profile.update-profile-information-form />
                            </div>
                            <div class="flex flex-col items-center md:w-40">
                                <label class="mb-3 block w-full text-center text-xs font-medium text-muted-foreground">Iniciais</label>
                                <div class="flex h-28 w-28 items-center justify-center rounded-shadcn border border-border bg-muted shadow-sm md:h-32 md:w-32">
                                    <span class="text-3xl font-semibold text-muted-foreground">
                                        {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div x-show="settingsTab === 'security'" class="space-y-6">
                            <livewire:profile.update-password-form />
                        </div>

                        <div x-show="settingsTab === 'theme'" class="space-y-6">
                            <div>
                                <h3 class="mb-1 text-lg font-semibold tracking-tight text-foreground">Aparência e tema</h3>
                                <p class="text-sm text-muted-foreground">Personalize como a interface é exibida no seu dispositivo.</p>
                            </div>
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                <button x-on:click="localStorage.theme = 'light'; document.documentElement.classList.remove('dark')"
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-card p-3 text-left text-card-foreground shadow-sm transition-colors hover:bg-accent/40"
                                    :class="localStorage.theme === 'light' ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-md border border-border bg-muted">
                                        <div class="absolute left-2 right-2 top-2 h-2 rounded-sm bg-background shadow-sm"></div>
                                        <div class="absolute bottom-2 left-2 top-6 w-6 rounded-sm bg-background shadow-sm"></div>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-sm font-medium">Modo claro</span>
                                        <span class="block text-xs text-muted-foreground">Visual limpo para ambientes iluminados.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.theme = 'dark'; document.documentElement.classList.add('dark')"
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-slate-950 p-3 text-left text-slate-50 shadow-sm transition-colors hover:bg-slate-900"
                                    :class="localStorage.theme === 'dark' ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-md border border-slate-800 bg-slate-950">
                                        <div class="absolute left-2 right-2 top-2 h-2 rounded-sm bg-slate-800"></div>
                                        <div class="absolute bottom-2 left-2 top-6 w-6 rounded-sm bg-slate-800"></div>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-sm font-medium">Modo escuro</span>
                                        <span class="block text-xs text-slate-400">Confortável para ambientes com pouca luz.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.removeItem('theme'); if(window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }"
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-card p-3 text-left text-card-foreground shadow-sm transition-colors hover:bg-accent/40"
                                    :class="!('theme' in localStorage) ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative flex aspect-[4/3] w-full overflow-hidden rounded-md border border-border">
                                        <div class="h-full w-1/2 bg-muted"></div>
                                        <div class="h-full w-1/2 bg-slate-900"></div>
                                        <div class="absolute left-2 right-2 top-2 h-2 rounded-sm bg-muted-foreground/40"></div>
                                        <div class="absolute bottom-2 left-2 top-6 w-6 rounded-sm bg-muted-foreground/40"></div>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-sm font-medium">Sistema</span>
                                        <span class="block text-xs text-muted-foreground">Acompanha o tema do sistema.</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div x-show="settingsTab === 'about'" class="space-y-6">
                            <div class="flex flex-col items-center gap-5 rounded-shadcn border border-border bg-card p-6 text-card-foreground shadow-sm sm:flex-row sm:items-start">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-shadcn border border-border bg-muted text-foreground">
                                    <x-application-logo class="h-8 w-8" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold tracking-tight text-foreground">{{ config('app.name') }}</h4>
                                    <span class="inline-flex rounded-sm border border-border bg-secondary px-2 py-0.5 text-xs font-medium text-secondary-foreground">Alpha 1.0</span>
                                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        Organize o trabalho da sua organização com equipes, tarefas, prioridades e prazos.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <header class="z-20 flex shrink-0 flex-col items-center justify-between gap-3 border-b border-border bg-background px-6 py-4 md:flex-row">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <button x-on:click="sidebarOpen = true" aria-label="Abrir menu lateral" class="group flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <i class="ph ph-list text-lg"></i>
            </button>
            <div class="flex items-center gap-3">
                <span class="font-semibold tracking-tight text-foreground">
                    {{ $team->name }} <span class="font-normal text-muted-foreground">/ {{ auth()->user()->name }}</span>
                </span>
            </div>
        </div>

        <div class="flex h-8 max-w-full items-center overflow-x-auto rounded-md border border-border bg-muted p-0.5">
            <button type="button" wire:click="applyQuickFilter('total')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'total' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <i class="ph ph-files text-sm"></i>
                <span class="text-xs font-medium">Total <span class="font-bold">{{ $summary['total'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('urgent')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'urgent' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <div class="h-1.5 w-1.5 rounded-full bg-destructive"></div>
                <span class="text-xs font-medium">Urgentes <span class="font-bold">{{ $summary['urgent'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('overdue')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'overdue' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
                <span class="text-xs font-medium">Vencidas <span class="font-bold">{{ $summary['overdue'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('in_progress')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'in_progress' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <div class="h-1.5 w-1.5 rounded-full bg-blue-500"></div>
                <span class="text-xs font-medium">Em And. <span class="font-bold">{{ $summary['in_progress'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('completed')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'completed' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <div class="h-1.5 w-1.5 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-medium">Concluídas <span class="font-bold">{{ $summary['completed'] }}</span></span>
            </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <div class="flex h-8 items-center rounded-md border border-input bg-background p-0.5" role="group" aria-label="Modo de visualização">
                <button type="button"
                    x-on:click="viewMode = 'grid'; localStorage.setItem('taskViewMode', 'grid')"
                    aria-label="Visualização em grade"
                    class="flex h-6 w-7 items-center justify-center rounded-sm transition-colors"
                    :class="viewMode === 'grid' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                    <i class="ph ph-squares-four text-sm"></i>
                </button>
                <button type="button"
                    x-on:click="viewMode = 'list'; localStorage.setItem('taskViewMode', 'list')"
                    aria-label="Visualização em lista"
                    class="flex h-6 w-7 items-center justify-center rounded-sm transition-colors"
                    :class="viewMode === 'list' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                    <i class="ph ph-list text-sm"></i>
                </button>
            </div>
            <div class="relative">
                <button type="button" x-on:click="filterOpen = !filterOpen" aria-label="Abrir filtros"
                    class="flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="({{ $filterCategoryId !== '' ? 'true' : 'false' }} || {{ $filterStatus !== '' ? 'true' : 'false' }} || {{ $filterUrgent !== '' ? 'true' : 'false' }} || {{ $quickFilter !== '' ? 'true' : 'false' }}) ? 'bg-accent text-accent-foreground' : ''">
                    <i class="ph ph-funnel text-base"></i>
                </button>

                <div x-show="filterOpen"
                    x-transition:enter="ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    x-on:click.away="filterOpen = false"
                    x-cloak
                    class="absolute right-0 top-10 z-30 w-72 rounded-shadcn border border-border bg-popover p-4 text-popover-foreground shadow-md">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-semibold">Filtros</p>
                            <p class="text-xs text-muted-foreground">Refine as tarefas exibidas no painel.</p>
                        </div>
                        <button type="button" wire:click="clearFilters" x-on:click="filterOpen = false"
                            class="text-xs font-medium text-muted-foreground transition-colors hover:text-foreground">
                            Limpar
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Categoria</label>
                            <select wire:model.live="filterCategoryId"
                                class="h-9 w-full cursor-pointer appearance-none rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                <option value="">Todas</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Status</label>
                            <select wire:model.live="filterStatus"
                                class="h-9 w-full cursor-pointer appearance-none rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                <option value="">Todos</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Urgência</label>
                            <select wire:model.live="filterUrgent"
                                class="h-9 w-full cursor-pointer appearance-none rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                <option value="">Todas</option>
                                <option value="1">Somente urgentes</option>
                                <option value="0">Somente não urgentes</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative flex-1 md:w-56 h-8">
                <i class="ph ph-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..." 
                    class="h-full w-full rounded-md border border-input bg-background pl-8 pr-3 text-xs shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring">
            </div>
            <button x-on:click="$wire.resetForm(); modalOpen = true; mode = 'create'; modalView = 'details'" 
                class="flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <i class="ph ph-plus text-sm"></i>
                <span>Nova tarefa</span>
            </button>
        </div>
    </header>

    <div x-data="{ show: false, message: '' }"
         x-on:task-saved.window="show = true; message = 'Tarefa salva com sucesso!'; setTimeout(() => show = false, 2500)"
         x-on:task-status-updated.window="show = true; message = 'Status atualizado!'; setTimeout(() => show = false, 2500)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         x-cloak
         class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 rounded-shadcn border border-border bg-background px-4 py-3 text-foreground shadow-lg">
        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
            <i class="ph-bold ph-check text-xs"></i>
        </div>
        <span class="text-xs font-bold tracking-tight" x-text="message"></span>
    </div>

    @if (session()->has('error'))
        <div class="m-6 flex items-center gap-3 rounded-md bg-destructive p-4 text-destructive-foreground shadow-sm">
            <i class="ph-fill ph-warning-circle text-2xl"></i>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if ($filterCategoryId !== '' || $filterStatus !== '' || $filterUrgent !== '' || $quickFilter !== '')
        <div class="mb-4 flex flex-wrap items-center gap-2 px-6 pt-4">
            @if ($quickFilter !== '')
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-faders text-sm"></i>
                    {{ match($quickFilter) {
                        'total' => 'Total',
                        'urgent' => 'Urgentes',
                        'overdue' => 'Vencidas',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluidas',
                        default => 'Filtro rapido',
                    } }}
                </span>
            @endif

            @if ($filterCategoryId !== '')
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-tag text-sm"></i>
                    {{ optional($categories->firstWhere('id', (int) $filterCategoryId))->name ?? 'Categoria' }}
                </span>
            @endif

            @if ($filterStatus !== '')
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-clock text-sm"></i>
                    {{ collect($statusOptions)->firstWhere('value', $filterStatus)?->label() ?? 'Status' }}
                </span>
            @endif

            @if ($filterUrgent !== '')
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-warning-circle text-sm"></i>
                    {{ $filterUrgent === '1' ? 'Urgentes' : 'Nao urgentes' }}
                </span>
            @endif

            <button type="button" wire:click="clearFilters"
                class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1 text-xs font-medium text-primary-foreground">
                Limpar filtros
            </button>
        </div>
    @endif

    <main class="flex-1 overflow-y-auto bg-muted/40 p-6 pb-32 custom-scrollbar">
        <div x-show="viewMode === 'list'" x-cloak
            class="mb-2 hidden grid-cols-[6.5rem_minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_6rem_8rem] gap-4 px-4 text-[10px] font-medium text-muted-foreground lg:grid">
            <span>Código</span>
            <span>Tarefa</span>
            <span>Local</span>
            <span>Categoria</span>
            <span>Prazo</span>
            <span>Prioridade</span>
            <span>Situação</span>
        </div>

        <div :class="viewMode === 'grid'
                ? 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5'
                : 'flex flex-col gap-2'"
            wire:loading.class="opacity-60">
            @forelse($tasks as $task)
                @php
                    $status = $task->status;
                    $isCompleted = $status === \App\Domain\Tasks\TaskStatus::Completed;
                    $isUrgent = (bool) $task->is_urgent;

                    $statusDotClass = match ($status) {
                        \App\Domain\Tasks\TaskStatus::Completed => 'bg-emerald-500',
                        \App\Domain\Tasks\TaskStatus::InProgress => 'bg-blue-500',
                        default => 'bg-amber-500',
                    };

                    $showUrgentBadge = $isUrgent && !$isCompleted;
                    $statusBadgeClass = 'border border-border bg-secondary text-secondary-foreground';
                    $urgentBadgeClass = 'border border-destructive/30 bg-destructive/10 text-destructive';
                @endphp
                <article
                    wire:key="task-{{ $task->id }}"
                    x-data="{
                        rowClickTimer: null,
                        openTask() {
                            $wire.edit({{ $task->id }}, 'details');
                            modalView = 'details';
                            mode = 'edit';
                            modalOpen = true;
                        },
                        handleRowClick() {
                            if (viewMode === 'grid') {
                                this.openTask();
                                return;
                            }

                            clearTimeout(this.rowClickTimer);
                            this.rowClickTimer = setTimeout(() => this.openTask(), 250);
                        },
                        cancelRowClick() {
                            clearTimeout(this.rowClickTimer);
                            this.rowClickTimer = null;
                        }
                    }"
                    role="button"
                    tabindex="0"
                    x-on:click="handleRowClick()"
                    x-on:dblclick="if (viewMode === 'list') cancelRowClick()"
                    x-on:keydown.enter.prevent="openTask()"
                    :class="viewMode === 'grid' ? 'h-[180px]' : 'min-h-[72px]'"
                    class="group relative cursor-pointer overflow-hidden rounded-shadcn border border-border bg-card text-card-foreground shadow-sm transition-[box-shadow,border-color,background-color] duration-150 hover:bg-accent/30 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 focus:ring-offset-background">

                    <div x-show="viewMode === 'grid'" x-cloak class="flex h-full flex-col p-4">
                        <div class="flex items-start justify-between gap-3">
                            <span class="text-sm font-semibold tracking-tight text-foreground">{{ $task->code }}</span>
                            <span class="max-w-[45%] truncate rounded-sm border border-border bg-secondary px-2 py-0.5 text-[10px] font-medium text-secondary-foreground">
                                {{ $task->category->name ?? 'Sem categoria' }}
                            </span>
                        </div>

                        <div class="mt-4 min-h-0 flex-1">
                            <h3 class="line-clamp-2 text-sm font-medium leading-5 text-foreground">{{ $task->title }}</h3>
                            <p class="mt-1 line-clamp-1 text-xs text-muted-foreground">{{ $task->location ?: 'Local não informado' }}</p>
                        </div>

                        <div class="mt-3 flex items-end justify-between gap-2 border-t border-border pt-3">
                            <span class="text-[11px] text-muted-foreground">
                                {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Sem prazo' }}
                            </span>
                            <div class="flex min-w-0 justify-end gap-1.5">
                                @if($showUrgentBadge)
                                    <span class="flex items-center gap-1 rounded-sm px-2 py-1 text-[10px] font-semibold {{ $urgentBadgeClass }}">
                                        <i class="ph ph-warning-circle"></i>
                                        Urgente
                                    </span>
                                @endif

                                <span class="flex items-center gap-1.5 rounded-sm px-2 py-1 text-[10px] font-medium {{ $statusBadgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ $status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div x-show="viewMode === 'list'" x-cloak
                        class="grid min-h-[72px] grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2 px-4 py-3 lg:grid-cols-[6.5rem_minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_6rem_8rem] lg:gap-4">
                        <span class="text-xs font-semibold text-foreground">{{ $task->code }}</span>
                        <div class="min-w-0" wire:dblclick="startInlineEdit({{ $task->id }}, 'title')" title="Clique duas vezes para editar o título">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'title')
                                <input wire:model="inlineEditValue" wire:keydown.enter.prevent="saveInlineEdit"
                                    wire:keydown.escape.prevent="cancelInlineEdit" wire:blur="saveInlineEdit"
                                    x-on:click.stop x-on:dblclick.stop
                                    x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <h3 class="truncate text-sm font-medium text-foreground">{{ $task->title }}</h3>
                                <span class="mt-0.5 block text-[10px] text-muted-foreground lg:hidden">Duplo clique para editar</span>
                            @endif
                        </div>

                        <div class="col-span-2 min-w-0 lg:col-span-1" wire:dblclick="startInlineEdit({{ $task->id }}, 'location')" title="Clique duas vezes para editar o local">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'location')
                                <input wire:model="inlineEditValue" wire:keydown.enter.prevent="saveInlineEdit"
                                    wire:keydown.escape.prevent="cancelInlineEdit" wire:blur="saveInlineEdit"
                                    x-on:click.stop x-on:dblclick.stop
                                    x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                    placeholder="Local não informado"
                                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-xs shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <p class="truncate text-xs text-muted-foreground">{{ $task->location ?: 'Local não informado' }}</p>
                            @endif
                        </div>

                        <div class="min-w-0" wire:dblclick="startInlineEdit({{ $task->id }}, 'category_id')" title="Clique duas vezes para editar a categoria">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'category_id')
                                <select wire:model="inlineEditValue" wire:change="saveInlineEdit"
                                    wire:keydown.escape.prevent="cancelInlineEdit" x-init="$nextTick(() => $el.focus())"
                                    x-on:click.stop x-on:dblclick.stop
                                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-xs shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <span class="block truncate text-xs text-muted-foreground">{{ $task->category->name ?? 'Sem categoria' }}</span>
                            @endif
                        </div>

                        <div wire:dblclick="startInlineEdit({{ $task->id }}, 'due_date')" title="Clique duas vezes para editar o prazo">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'due_date')
                                <input type="date" wire:model="inlineEditValue" wire:change="saveInlineEdit"
                                    wire:keydown.enter.prevent="saveInlineEdit" wire:keydown.escape.prevent="cancelInlineEdit"
                                    x-on:click.stop x-on:dblclick.stop
                                    x-init="$nextTick(() => $el.focus())"
                                    class="h-8 w-full rounded-md border border-input bg-background px-2 text-xs shadow-sm outline-none focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark]">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <span class="text-xs text-muted-foreground">
                                    {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Sem prazo' }}
                                </span>
                            @endif
                        </div>

                        <button type="button" wire:click.stop="toggleInlineUrgency({{ $task->id }})"
                            class="flex items-center justify-center gap-1 rounded-sm px-2 py-1 text-[10px] font-semibold {{ $showUrgentBadge ? $urgentBadgeClass : 'border border-border bg-background text-muted-foreground' }}"
                            title="Clique para alternar a prioridade">
                            <i class="ph {{ $isUrgent ? 'ph-warning-circle' : 'ph-minus-circle' }}"></i>
                            {{ $isUrgent ? 'Urgente' : 'Normal' }}
                        </button>

                        <button type="button" wire:click.stop="cycleStatus({{ $task->id }})"
                            class="flex items-center justify-center gap-1.5 rounded-sm px-2 py-1 text-[10px] font-medium {{ $statusBadgeClass }}"
                            title="Clique para avançar o status">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                {{ $status->label() }}
                        </button>
                    </div>
                </article>
            @empty
                <div class="col-span-full py-12 flex flex-col items-center justify-center opacity-50">
                    <i class="ph ph-files text-4xl mb-2"></i>
                    <p class="text-sm font-medium">Nenhuma tarefa encontrada.</p>
                </div>
            @endforelse
        </div>

        @if ($tasks->hasPages())
            <div class="mt-6">
                {{ $tasks->links() }}
            </div>
        @endif
    </main>

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 transition-opacity duration-200"
         x-show="modalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div class="flex h-[96.8vh] max-h-[726px] w-full max-w-6xl transform flex-col overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg transition-[opacity,transform]"
         x-show="modalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-on:click.away="if (!$wire.showCategoryModal) $wire.closeModal()">            
            <div class="flex shrink-0 items-center justify-between border-b border-border px-6 py-4">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-semibold tracking-tight text-foreground"
                        x-text="mode === 'create' ? 'Nova Tarefa' : 'Editar Tarefa - ' + '{{ $form->taskId }}'"></h2>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="modalView = 'details'" aria-label="Exibir detalhes da tarefa"
                        :class="modalView === 'details' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                        class="flex h-8 w-8 items-center justify-center rounded-md transition-colors"
                        title="Detalhes da Tarefa">
                        <i class="ph ph-info text-2xl"></i>
                    </button>
                    <button type="button" x-on:click="modalView = 'history'" aria-label="Exibir histórico da tarefa"
                        class="flex h-8 w-8 items-center justify-center rounded-md transition-colors"
                        :class="modalView === 'history' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                        title="Histórico de Alterações">
                        <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                    </button>
                    <div class="mx-1 h-4 w-px bg-border"></div>
                    <button type="button" x-on:click="$wire.closeModal()" aria-label="Fechar tarefa" class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
            </div>

            <form wire:submit="save" class="flex flex-1 flex-col overflow-hidden">
                <div class="min-h-0 flex-1 overflow-y-auto lg:grid lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)] lg:overflow-hidden" x-show="modalView === 'details'">
                <div class="flex min-h-0 flex-col">
                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto p-5 lg:p-6 custom-scrollbar">
                    
                    @if ($errors->any())
                        <div class="rounded-md border border-red-100 bg-red-50 p-3 text-red-600 dark:border-red-800 dark:bg-red-900/30 dark:text-red-400">
                            <ul class="list-disc list-inside text-xs font-bold uppercase">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">Título da tarefa</label>
                        <input wire:model="form.title" type="text" placeholder="Ex: Reparo de calçada"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring">
                        @error('form.title') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">Localização</label>
                        <input wire:model="form.location" type="text" placeholder="Nome da rua, bairro ou praça"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring">
                        @error('form.location') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-foreground">Categoria</label>
                            <div class="space-y-2">
                                <select wire:model.live="form.categoryId" class="h-9 w-full cursor-pointer appearance-none rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                    <option value="">Selecione...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                    @can('create', \App\Models\Category::class)
                                        <option value="new" class="border-t font-medium">+ Criar nova categoria...</option>
                                    @endcan
                                </select>
                                @error('form.categoryId') <span class="block text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-foreground">Prazo</label>
                            <input wire:model="form.dueDate" type="date" class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark]">
                            @error('form.dueDate') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <label class="relative block cursor-pointer group touch-manipulation select-none">
                        <input wire:model="form.isUrgent" type="checkbox" class="sr-only">
                        <div class="flex items-center justify-between rounded-md border border-border bg-card p-4 transition-colors"
                             :class="$wire.form.isUrgent ? 'border-destructive/40 bg-destructive/5' : ''">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-md border border-border bg-secondary text-secondary-foreground transition-colors"
                                     :class="$wire.form.isUrgent ? 'border-destructive/30 bg-destructive/10 text-destructive' : ''">
                                    <i class="ph ph-warning text-lg"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="mb-1 text-sm font-medium leading-none text-foreground">Prioridade urgente</span>
                                    <span class="text-xs text-muted-foreground">Destaca esta tarefa no painel.</span>
                                </div>
                            </div>
                            <div class="flex h-4 w-4 items-center justify-center rounded-sm border border-primary transition-colors"
                                 :class="$wire.form.isUrgent ? 'bg-primary text-primary-foreground' : 'bg-background'">
                                <i class="ph ph-check text-[10px] font-bold" x-show="$wire.form.isUrgent"></i>
                            </div>
                        </div>
                    </label>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">Observação</label>
                        <textarea wire:model="form.observation" rows="3" placeholder="Informações extras..."
                            class="block w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring"></textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">
                            Status
                        </label>

                        <div class="inline-flex w-full rounded-md border border-border bg-muted p-0.5" role="group">
                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Pending->value }}')"
                                x-bind:disabled="mode === 'create'"
                                class="flex h-8 flex-1 items-center justify-center gap-1.5 rounded-sm px-2 text-[10px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Pending->value ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Pendente
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::InProgress->value }}')"
                                x-bind:disabled="mode === 'create'"
                                class="flex h-8 flex-1 items-center justify-center gap-1.5 rounded-sm px-2 text-[10px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::InProgress->value ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Em andamento
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Completed->value }}')"
                                x-bind:disabled="mode === 'create'"
                                class="flex h-8 flex-1 items-center justify-center gap-1.5 rounded-sm px-2 text-[10px] font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Completed->value ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Concluído
                            </button>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-border bg-background px-5 py-4 lg:px-6">
                    <div class="flex gap-3">
                        <button type="button" x-on:click="$wire.closeModal()"
                            class="h-9 flex-1 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="h-9 flex-1 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                            <span x-text="mode === 'create' ? 'Criar Tarefa' : 'Salvar Alterações'"></span>
                        </button>
                    </div>
                </div>
                </div>

                <aside class="flex min-h-[380px] flex-col border-t border-border bg-muted/40 p-5 lg:min-h-0 lg:overflow-hidden lg:border-l lg:border-t-0 lg:p-6" aria-label="Checklist da tarefa">
                    <div class="flex items-center justify-between gap-3 mb-2 shrink-0">
                        <div>
                            <label class="block text-sm font-semibold tracking-tight text-foreground">Checklist</label>
                            <p class="mt-0.5 text-xs text-muted-foreground">Etapas de execução da tarefa</p>
                        </div>
                        <span class="text-[11px] font-medium text-muted-foreground">
                            {{ count($form->checklistItems) }} {{ count($form->checklistItems) === 1 ? 'item' : 'itens' }}
                        </span>
                    </div>

                    @php
                        $totalItems = count($form->checklistItems);
                        $completedItems = collect($form->checklistItems)->filter(fn($item) => !empty($item['is_completed']))->count();
                        $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;
                    @endphp

                    @if($totalItems > 0)
                        <div class="mb-5 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-secondary">
                                    <div class="h-full rounded-full bg-primary transition-[width] duration-300 ease-out" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="shrink-0 text-[10px] font-medium text-muted-foreground">{{ $percentage }}%</span>
                            </div>
                        </div>
                    @endif

                    <ul class="space-y-2 flex-1 overflow-y-auto custom-scrollbar mb-4 min-h-0">
                        @forelse ($form->checklistItems as $index => $item)
                            <li wire:key="checklist-item-{{ $index }}"
                                class="group flex items-center gap-3 rounded-md border border-border bg-card p-2.5 text-card-foreground shadow-sm transition-colors hover:bg-accent/50">
                                <button type="button"
                                    wire:click="toggleChecklistItem({{ $index }})"
                                    aria-label="Alternar conclusão de {{ $item['label'] }}"
                                    class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-sm border border-primary transition-colors {{ !empty($item['is_completed']) ? 'bg-primary text-primary-foreground' : 'bg-background' }}">
                                    <i class="ph ph-check text-[10px] {{ !empty($item['is_completed']) ? '' : 'hidden' }}"></i>
                                </button>

                                <span class="flex-1 truncate text-sm {{ !empty($item['is_completed']) ? 'text-muted-foreground line-through' : 'text-foreground' }}">
                                    {{ $item['label'] }}
                                </span>

                                <button type="button"
                                    wire:click="removeChecklistItem({{ $index }})"
                                    aria-label="Remover {{ $item['label'] }} do checklist"
                                    class="text-muted-foreground opacity-0 transition-colors hover:text-destructive focus:opacity-100 group-hover:opacity-100">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </li>
                        @empty
                            <li class="flex flex-col items-center justify-center rounded-md border border-dashed border-border bg-background/50 px-6 py-10 text-center">
                                <i class="ph ph-list-plus mb-2 text-3xl text-muted-foreground"></i>
                                <p class="text-xs font-medium text-foreground">Nenhum item adicionado.</p>
                                <p class="text-xs text-muted-foreground">Cadastre as etapas da execução para acompanhar o andamento.</p>
                            </li>
                        @endforelse
                    </ul>

                    <div class="flex gap-2 shrink-0 pt-2 border-t border-transparent">
                        <input wire:model.live="form.newChecklistItem" type="text" placeholder="Adicionar etapa..."
                            wire:keydown.enter.prevent="addChecklistItem"
                            class="h-9 flex-1 rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring">
                        <button type="button" wire:click="addChecklistItem"
                            aria-label="Adicionar item ao checklist"
                            class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <i class="ph ph-plus font-bold"></i>
                        </button>
                    </div>
                    @error('form.newChecklistItem') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </aside>
                </div>

                <div class="flex flex-col h-full overflow-hidden p-6" x-show="modalView === 'history'">
                    <div class="flex items-center justify-between gap-3 mb-2 shrink-0">
                        <label class="block text-sm font-semibold tracking-tight text-foreground">Histórico de alterações</label>
                        <span class="text-[11px] font-medium text-muted-foreground">
                            {{ count($form->historyItems) }} {{ count($form->historyItems) === 1 ? 'registro' : 'registros' }}
                        </span>
                    </div>

                    <ul class="space-y-3 flex-1 overflow-y-auto custom-scrollbar mb-4 min-h-0 pr-2">
                        @forelse ($form->historyItems as $index => $item)
                            <li wire:key="history-item-{{ $index }}" x-data="{ expanded: false }"
                                class="flex flex-col gap-2 rounded-md border border-border bg-card p-4 text-card-foreground shadow-sm">
                                
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <p class="break-words text-sm font-medium leading-relaxed text-foreground">
                                            {!! nl2br(e($item['description'])) !!}
                                        </p>
                                    </div>
                                    @if(!empty($item['metadata']))
                                        <button type="button" @click="expanded = !expanded" aria-label="Exibir detalhes do histórico" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                                            <i class="ph text-sm transition-transform" :class="expanded ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="mt-1 flex items-center gap-3 border-t border-border pt-3">
                                    <span class="flex items-center gap-1.5 text-[10px] font-medium text-muted-foreground">
                                        <div class="flex h-4 w-4 items-center justify-center rounded-full bg-secondary">
                                            <i class="ph ph-user text-[10px]"></i>
                                        </div>
                                        {{ $item['user_name'] ?? 'Sistema' }}
                                    </span>
                                    <div class="h-1 w-1 rounded-full bg-border"></div>
                                    <span class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground">
                                        <i class="ph ph-clock"></i> {{ $item['created_at'] }}
                                    </span>
                                </div>

                                @if(!empty($item['metadata']))
                                    <div x-show="expanded" x-collapse class="mt-2 text-xs">
                                        <div class="space-y-3 rounded-md border border-border bg-muted/50 p-3">
                                            @foreach($item['metadata'] as $key => $diff)
                                                @if($key !== 'checklist')
                                                    <div>
                                                        <span class="mb-1 block text-[10px] font-medium text-muted-foreground">Alteração em {{ config('app.locale') === 'pt_BR' ? trans("fields.{$key}") : $key }}</span>
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                            <div class="flex-1 px-2 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded border border-red-100 dark:border-red-900/30 line-through decoration-red-300 dark:decoration-red-800 break-all">
                                                                {{ $diff['from'] }}
                                                            </div>
                                                            <i class="ph ph-arrow-right hidden shrink-0 text-muted-foreground sm:block"></i>
                                                            <i class="ph ph-arrow-down shrink-0 self-center text-muted-foreground sm:hidden"></i>
                                                            <div class="flex-1 px-2 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded border border-emerald-100 dark:border-emerald-900/30 break-all">
                                                                {{ $diff['to'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div>
                                                        <span class="mb-2 block text-[10px] font-medium text-muted-foreground">Alterações no checklist</span>
                                                        <div class="space-y-1.5">
                                                            @php
                                                                $fromItems = $diff['from'] ?? [];
                                                                $toItems = $diff['to'] ?? [];

                                                                // Simple logic to show a compact summary of changes in the metadata view
                                                                $fromLabels = collect($fromItems)->map(fn($i) => $i['label'])->toArray();
                                                                $toLabels = collect($toItems)->map(fn($i) => $i['label'])->toArray();

                                                                $added = array_diff($toLabels, $fromLabels);
                                                                $removed = array_diff($fromLabels, $toLabels);
                                                            @endphp

                                                            @foreach($removed as $label)
                                                                <div class="flex items-center gap-2 text-red-600 dark:text-red-400 font-medium">
                                                                    <i class="ph ph-minus-circle"></i>
                                                                    <span class="line-through">{{ $label }}</span>
                                                                </div>
                                                            @endforeach

                                                            @foreach($added as $label)
                                                                <div class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400 font-medium">
                                                                    <i class="ph ph-plus-circle"></i>
                                                                    <span>{{ $label }}</span>
                                                                </div>
                                                            @endforeach

                                                            {{-- Status changes for existing items --}}
                                                            @foreach($toItems as $item)
                                                                @php
                                                                    $oldItem = collect($fromItems)->first(fn($i) => $i['label'] === $item['label']);
                                                                @endphp
                                                                @if($oldItem && $oldItem['is_completed'] !== $item['is_completed'])
                                                                    <div class="flex items-center gap-2 font-medium text-foreground">
                                                                        <i class="ph {{ $item['is_completed'] ? 'ph-check-circle text-emerald-500' : 'ph-circle text-amber-500' }}"></i>
                                                                        <span>{{ $item['label'] }}: {{ $item['is_completed'] ? 'Concluído' : 'Pendente' }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </li>
                        @empty
                            <li class="flex flex-col items-center justify-center rounded-md border border-dashed border-border bg-muted/30 px-6 py-10 text-center">
                                <i class="ph ph-clock-counter-clockwise mb-2 text-3xl text-muted-foreground"></i>
                                <p class="text-xs font-medium text-foreground">Nenhum registro no histórico.</p>
                                <p class="text-xs text-muted-foreground">As alterações da tarefa aparecerão aqui.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>

            </form>
        </div>
    </div>
    
    {{-- Modal de Criacao de Categoria --}}
    @can('create', \App\Models\Category::class)
    @if($showCategoryModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80 p-4">
            <div class="w-full max-w-sm overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg">
                <div class="flex items-center justify-between border-b border-border px-6 py-4">
                    <h3 class="text-sm font-semibold tracking-tight">Nova categoria</h3>
                    <button type="button" wire:click="closeCategoryModal" aria-label="Fechar criação de categoria" class="rounded-md p-1 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-medium text-foreground">Nome da categoria</label>
                        <input wire:model="newCategoryName" type="text" placeholder="Ex: Manutencao Eletrica"
                            wire:keydown.enter.prevent="createNewCategory"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:ring-2 focus:ring-ring">
                        @error('newCategoryName')
                            <span class="text-[10px] font-bold text-red-500 uppercase tracking-tight">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="closeCategoryModal"
                            class="h-9 flex-1 rounded-md border border-input bg-background px-4 text-xs font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                            Cancelar
                        </button>
                        <button type="button" wire:click="createNewCategory"
                            class="h-9 flex-1 rounded-md bg-primary px-4 text-xs font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
                            Criar Categoria
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @endcan

    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { 
            background-color: rgba(148, 163, 184, 0.4); 
            border-radius: 9999px; 
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { 
            background-color: rgba(71, 85, 105, 0.5); 
        }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #cbd5e1; }
        .dark .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #475569; }
        [x-cloak] { display: none !important; }
    </style>
</div>
