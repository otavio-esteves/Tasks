<div class="task-reference-font flex h-[100dvh] min-h-0 flex-col overflow-hidden bg-background text-foreground selection:bg-primary selection:text-primary-foreground"
     wire:ignore.self
     x-data="{ 
        sidebarOpen: false,
        panelView: 'tasks',
        indicatorChart: 'pie',
        setPanelView(view) {
            if (!['tasks', 'indicators', 'reports'].includes(view)) {
                return;
            }

            this.panelView = view;

            const url = new URL(window.location.href);

            if (view === 'tasks') {
                url.searchParams.delete('view');
            } else {
                url.searchParams.set('view', view);
            }

            window.history.pushState({ panelView: view }, '', url);
        },
        teamsOpen: true,
        systemSettingsOpen: false,
        filterOpen: false,
        reportConfigOpen: false,
        modalOpen: false, 
        settingsOpen: false,
        settingsTab: 'profile',
        modalView: 'details', 
        mode: 'create',
        viewMode: localStorage.getItem('taskViewMode') === 'list' ? 'list' : 'grid'
     }"
     x-init="
        const mobileView = window.matchMedia('(max-width: 767px)');
        const restorePanelView = () => {
            const view = new URLSearchParams(window.location.search).get('view');
            panelView = ['indicators', 'reports'].includes(view) ? view : 'tasks';
        };
        const enforceCardView = () => {
            if (mobileView.matches) {
                viewMode = 'grid';
                localStorage.setItem('taskViewMode', 'grid');
            }
        };
        restorePanelView();
        enforceCardView();
        mobileView.addEventListener('change', enforceCardView);
        window.addEventListener('popstate', restorePanelView);
        document.documentElement.classList.add('dark');
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
     "
     x-on:open-task-modal.window="modalOpen = true; modalView = $event.detail.view || 'details'; mode = $event.detail.mode"
     x-on:task-saved.window="modalOpen = false"
     x-on:task-modal-closed.window="modalOpen = false"
     x-on:keydown.escape.window="
        if (reportConfigOpen) {
            reportConfigOpen = false;
        } else if (modalOpen) {
            $wire.closeModal();
        } else if (systemSettingsOpen) {
            systemSettingsOpen = false;
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
        class="fixed inset-0 z-40 bg-zinc-950/20 transition-opacity motion-reduce:transition-none dark:bg-black/50"></div>

    <aside id="sidebar"
        class="fixed left-0 top-0 z-50 flex h-[100dvh] w-[calc(100vw-1rem)] max-w-[23.4rem] transform-gpu flex-col border-r border-sidebar-border bg-sidebar text-sidebar-foreground shadow-lg transition-transform duration-300 ease-out motion-reduce:transition-none sm:w-[23.4rem]"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="flex shrink-0 items-center justify-between border-b border-sidebar-border bg-sidebar p-2.5">
            <div class="flex items-center px-1.5">
                <span class="font-semibold tracking-tight text-sidebar-accent-foreground">
                    {{ config('app.name') }}
                </span>
            </div>
            <button x-on:click="sidebarOpen = false" aria-label="Fechar menu lateral" class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-3 space-y-6 custom-scrollbar">
            <div>
                <h3 class="mb-2 px-3 text-[10px] font-medium text-muted-foreground">Geral</h3>
                <nav class="space-y-0.5">
                    <button type="button" x-on:click="setPanelView('tasks'); sidebarOpen = false"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="panelView === 'tasks' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'">
                        <i class="ph-duotone ph-clipboard-text text-lg"></i>
                        Tarefas
                    </button>

                    <button type="button" data-testid="indicators-view-trigger"
                        wire:click="openIndicators"
                        x-on:click="setPanelView('indicators'); sidebarOpen = false"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="panelView === 'indicators' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'">
                        <i class="ph-duotone ph-chart-pie-slice text-lg"></i>
                        Indicadores
                    </button>

                    <button type="button" data-testid="reports-view-trigger" x-on:click="setPanelView('reports'); sidebarOpen = false"
                        class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors"
                        :class="panelView === 'reports' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground'">
                        <i class="ph-duotone ph-scroll text-lg"></i>
                        <span class="text-left">Relatórios</span>
                    </button>
                </nav>
            </div>

            <div>
                <button type="button"
                    data-testid="teams-menu-trigger"
                    x-on:click="teamsOpen = !teamsOpen"
                    aria-controls="sidebar-teams-menu"
                    x-bind:aria-expanded="teamsOpen"
                    class="mb-1 flex w-full items-center justify-between rounded-md px-3 py-2 text-left text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                    <span class="flex items-center gap-3">
                        <i class="ph-duotone ph-users-three text-lg" aria-hidden="true"></i>
                        <span>Equipes</span>
                    </span>
                    <i class="ph ph-caret-down text-sm text-muted-foreground transition-transform duration-200" :class="teamsOpen ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>
                <nav id="sidebar-teams-menu"
                    x-show="teamsOpen"
                    x-collapse
                    class="space-y-0.5"
                    aria-label="Equipes disponíveis">
                    @foreach ($teams as $sidebarTeam)
                        @php
                            $sidebarTeamIcon = in_array($sidebarTeam->icon, ['buildings', 'users-three', 'wrench', 'leaf', 'heartbeat', 'graduation-cap', 'shield-check', 'truck', 'lightbulb', 'briefcase'], true)
                                ? $sidebarTeam->icon
                                : 'buildings';
                        @endphp
                        @can('view', $sidebarTeam)
                            <a href="{{ route('teams.tasks', $sidebarTeam) }}"
                                wire:navigate
                                class="flex items-center gap-3 rounded-md py-2 pl-10 pr-3 text-sm font-medium transition-colors {{ $sidebarTeam->is($team) ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground' }}">
                                <i class="ph-duotone ph-{{ $sidebarTeamIcon }} shrink-0 text-base opacity-70" aria-hidden="true"></i>
                                <span class="truncate">{{ $sidebarTeam->name }}</span>
                            </a>
                        @else
                            <div class="flex items-center gap-3 rounded-md py-2 pl-10 pr-3 text-sm font-medium text-muted-foreground"
                                title="Você não possui acesso a esta equipe">
                                <i class="ph-duotone ph-{{ $sidebarTeamIcon }} shrink-0 text-base opacity-60" aria-hidden="true"></i>
                                <span class="truncate">{{ $sidebarTeam->name }}</span>
                                <i class="ph ph-lock-key ml-auto text-xs" aria-hidden="true"></i>
                                <span class="sr-only">Sem acesso</span>
                            </div>
                        @endcan
                    @endforeach
                </nav>
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <h3 class="mb-2 px-3 text-[10px] font-medium text-muted-foreground">Administração</h3>
                    <nav class="space-y-0.5">
                        <button type="button"
                            data-testid="admin-system-menu-trigger"
                            x-on:click="systemSettingsOpen = true; settingsOpen = false; sidebarOpen = false"
                            class="group flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium text-sidebar-foreground transition-colors hover:bg-sidebar-accent hover:text-sidebar-accent-foreground">
                            <i class="ph-duotone ph-gear-six text-lg"></i>
                            <span>Sistema</span>
                        </button>
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

    @if (auth()->user()->isAdmin())
        <div id="modalSystemSettings"
            data-testid="admin-system-settings-dialog"
            x-show="systemSettingsOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-2 sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="system-settings-title">

            <div class="flex h-[calc(100dvh-1rem)] max-h-[760px] w-full max-w-7xl transform overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg transition-[opacity,transform] sm:h-[calc(100dvh-2rem)]"
                x-show="systemSettingsOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-on:click.away="systemSettingsOpen = false">

                <aside class="hidden w-56 shrink-0 flex-col gap-4 border-r border-border bg-muted/40 p-4 md:flex">
                    <div class="px-2 py-1">
                        <h2 class="text-base font-semibold tracking-tight">Sistema</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Configurações administrativas</p>
                    </div>

                    <nav class="space-y-1" aria-label="Configurações do sistema">
                        <button type="button" wire:click="selectSystemTab('teams')"
                            class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors {{ $systemTab === 'teams' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                            <i class="ph-duotone ph-buildings text-lg" aria-hidden="true"></i>
                            Equipes
                        </button>
                        <button type="button" wire:click="selectSystemTab('categories')"
                            class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors {{ $systemTab === 'categories' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                            <i class="ph-duotone ph-tag text-lg" aria-hidden="true"></i>
                            Categorias
                        </button>
                        <button type="button" wire:click="selectSystemTab('users')"
                            class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors {{ $systemTab === 'users' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                            <i class="ph-duotone ph-user-gear text-lg" aria-hidden="true"></i>
                            Usuários
                        </button>
                        <button type="button" wire:click="selectSystemTab('access')"
                            class="flex h-9 w-full items-center gap-3 rounded-md px-3 text-sm font-medium transition-colors {{ $systemTab === 'access' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}">
                            <i class="ph-duotone ph-lock-key-open text-lg" aria-hidden="true"></i>
                            Acesso
                        </button>
                    </nav>
                </aside>

                <main class="flex min-w-0 flex-1 flex-col bg-background">
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
                        <div class="min-w-0">
                            <h2 id="system-settings-title" class="text-base font-semibold tracking-tight">Sistema</h2>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ match($systemTab) {
                                    'teams' => 'Gerencie as equipes do sistema.',
                                    'categories' => 'Gerencie as categorias disponíveis por equipe.',
                                    'users' => 'Gerencie os cargos e privilégios dos usuários.',
                                    'access' => 'Defina se o sistema exige identificação para acesso.',
                                } }}
                            </p>
                        </div>
                        <button type="button" x-on:click="systemSettingsOpen = false" aria-label="Fechar configurações do sistema"
                            class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <i class="ph ph-x text-lg leading-none" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="border-b border-border p-3 md:hidden">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="selectSystemTab('teams')"
                                class="h-9 rounded-md px-3 text-sm font-medium {{ $systemTab === 'teams' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground' }}">
                                Equipes
                            </button>
                            <button type="button" wire:click="selectSystemTab('categories')"
                                class="h-9 rounded-md px-3 text-sm font-medium {{ $systemTab === 'categories' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground' }}">
                                Categorias
                            </button>
                            <button type="button" wire:click="selectSystemTab('users')"
                                class="h-9 rounded-md px-3 text-sm font-medium {{ $systemTab === 'users' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground' }}">
                                Usuários
                            </button>
                            <button type="button" wire:click="selectSystemTab('access')"
                                class="h-9 rounded-md px-3 text-sm font-medium {{ $systemTab === 'access' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground' }}">
                                Acesso
                            </button>
                        </div>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto custom-scrollbar">
                        @if ($systemTab === 'teams')
                            <livewire:admin.team-manager :key="'system-team-manager'" />
                        @elseif ($systemTab === 'categories')
                            <livewire:admin.category-manager :key="'system-category-manager'" />
                        @elseif ($systemTab === 'users')
                            <livewire:admin.user-manager :key="'system-user-manager'" />
                        @else
                            <livewire:admin.system-access-manager :key="'system-access-manager'" />
                        @endif
                    </div>
                </main>
            </div>
        </div>
    @endif

    <div id="modalSettings" 
         data-testid="user-settings-dialog"
         x-show="settingsOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-2 sm:p-4">
        
        <div class="flex h-[calc(100dvh-1rem)] max-h-[760px] w-full max-w-7xl transform overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg transition-[opacity,transform] sm:h-[calc(100dvh-2rem)]"
             x-show="settingsOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-on:click.away="settingsOpen = false">
            
            <aside class="hidden w-56 shrink-0 flex-col gap-4 border-r border-border bg-muted/40 p-4 md:flex">
                <div class="px-2 py-1">
                    <h2 class="text-base font-semibold tracking-tight">Configurações</h2>
                    <p class="mt-1 text-xs text-muted-foreground">Preferências da sua conta</p>
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
                <div class="flex shrink-0 items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold tracking-tight">Configurações</h2>
                        <p class="truncate text-xs text-muted-foreground">Gerencie seu perfil, segurança e aparência.</p>
                    </div>
                    <button type="button" x-on:click="settingsOpen = false" aria-label="Fechar configurações"
                        class="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>

                <div class="border-b border-border p-2 md:hidden">
                    <div class="grid grid-cols-2 gap-1">
                        <button type="button" x-on:click="settingsTab = 'profile'" class="h-9 rounded-md px-2 text-xs font-medium" :class="settingsTab === 'profile' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'">Perfil</button>
                        <button type="button" x-on:click="settingsTab = 'security'" class="h-9 rounded-md px-2 text-xs font-medium" :class="settingsTab === 'security' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'">Segurança</button>
                        <button type="button" x-on:click="settingsTab = 'theme'" class="h-9 rounded-md px-2 text-xs font-medium" :class="settingsTab === 'theme' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'">Tema</button>
                        <button type="button" x-on:click="settingsTab = 'about'" class="h-9 rounded-md px-2 text-xs font-medium" :class="settingsTab === 'about' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'">Sobre</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-4 sm:p-6 md:p-8 custom-scrollbar">
                    <div class="mx-auto max-w-3xl">
                        
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
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-white p-3 text-left text-zinc-950 shadow-sm transition-colors hover:bg-zinc-50"
                                    :class="localStorage.theme === 'light' ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-md border border-zinc-200 bg-zinc-50">
                                        <div class="absolute left-2 right-2 top-2 h-2 rounded-sm bg-white shadow-sm"></div>
                                        <div class="absolute bottom-2 left-2 top-6 w-6 rounded-sm bg-white shadow-sm"></div>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-sm font-medium">Modo claro</span>
                                        <span class="block text-xs text-zinc-500">Branco neutro para ambientes iluminados.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.theme = 'dark'; document.documentElement.classList.add('dark')"
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-zinc-950 p-3 text-left text-zinc-50 shadow-sm transition-colors hover:bg-zinc-900"
                                    :class="localStorage.theme === 'dark' ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-md border border-zinc-800 bg-zinc-950">
                                        <div class="absolute left-2 right-2 top-2 h-2 rounded-sm bg-zinc-800"></div>
                                        <div class="absolute bottom-2 left-2 top-6 w-6 rounded-sm bg-zinc-800"></div>
                                    </div>
                                    <div>
                                        <span class="mb-0.5 block text-sm font-medium">Modo escuro</span>
                                        <span class="block text-xs text-zinc-400">Confortável para ambientes com pouca luz.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.removeItem('theme'); if(window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }"
                                    class="group relative flex flex-col gap-3 rounded-shadcn border bg-card p-3 text-left text-card-foreground shadow-sm transition-colors hover:bg-accent/40"
                                    :class="!('theme' in localStorage) ? 'border-ring ring-2 ring-ring ring-offset-2 ring-offset-background' : 'border-border'">
                                    <div class="relative flex aspect-[4/3] w-full overflow-hidden rounded-md border border-border">
                                        <div class="h-full w-1/2 bg-muted"></div>
                                        <div class="h-full w-1/2 bg-zinc-900"></div>
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

    <header x-show="panelView === 'indicators'" x-cloak
        class="z-20 grid min-h-[65px] shrink-0 grid-cols-1 items-center gap-3 border-b border-border bg-background px-3 py-3 sm:px-6 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:py-4">
        <div class="flex min-w-0 w-full items-center gap-2 md:w-auto">
            <button x-on:click="sidebarOpen = true" aria-label="Abrir menu lateral" class="flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                <i class="ph ph-list text-lg" aria-hidden="true"></i>
            </button>
            <span class="min-w-0 truncate font-semibold tracking-tight text-foreground">
                Indicadores <span class="font-normal text-muted-foreground">/ {{ $team->name }}</span>
            </span>
        </div>
        <div class="hidden md:block" aria-hidden="true"></div>
        <span class="hidden md:block" aria-hidden="true"></span>
    </header>

    @php
        $pendingTasks = max(0, $summary['total'] - $summary['in_progress'] - $summary['completed']);
        $teamIndicators = [
            ['label' => 'Total', 'value' => $summary['total'], 'icon' => 'files', 'bar' => 'bg-zinc-500', 'text' => 'text-zinc-500'],
            ['label' => 'Pendentes', 'value' => $pendingTasks, 'icon' => 'hourglass', 'bar' => 'bg-amber-500', 'text' => 'text-amber-500'],
            ['label' => 'Urgentes', 'value' => $summary['urgent'], 'icon' => 'warning-circle', 'bar' => 'bg-red-500', 'text' => 'text-red-500'],
            ['label' => 'Vencidas', 'value' => $summary['overdue'], 'icon' => 'calendar-x', 'bar' => 'bg-amber-500', 'text' => 'text-amber-500'],
            ['label' => 'Em andamento', 'value' => $summary['in_progress'], 'icon' => 'spinner-gap', 'bar' => 'bg-blue-500', 'text' => 'text-blue-500'],
            ['label' => 'Concluídas', 'value' => $summary['completed'], 'icon' => 'check-circle', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-500'],
        ];
        $teamIndicatorMaximum = max(1, ...array_column($teamIndicators, 'value'));
        $statusIndicators = [
            ['label' => 'Pendentes', 'value' => $pendingTasks, 'color' => 'bg-amber-500'],
            ['label' => 'Em andamento', 'value' => $summary['in_progress'], 'color' => 'bg-blue-500'],
            ['label' => 'Concluídas', 'value' => $summary['completed'], 'color' => 'bg-emerald-500'],
        ];
        $statusTotal = max(1, array_sum(array_column($statusIndicators, 'value')));
        $pendingEnd = ($statusIndicators[0]['value'] / $statusTotal) * 100;
        $inProgressEnd = $pendingEnd + (($statusIndicators[1]['value'] / $statusTotal) * 100);
        $statusPie = $summary['total'] > 0
            ? "conic-gradient(rgb(245 158 11) 0 {$pendingEnd}%, rgb(59 130 246) {$pendingEnd}% {$inProgressEnd}%, rgb(16 185 129) {$inProgressEnd}% 100%)"
            : 'hsl(var(--muted))';
        $urgentPercentage = ($summary['urgent'] / max(1, $summary['total'])) * 100;
        $urgentPie = $summary['total'] > 0
            ? "conic-gradient(rgb(239 68 68) 0 {$urgentPercentage}%, hsl(var(--muted)) {$urgentPercentage}% 100%)"
            : 'hsl(var(--muted))';
    @endphp

    <main x-show="panelView === 'indicators'" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="flex-1 overflow-y-auto bg-muted/40 p-3 sm:p-6 custom-scrollbar">
        <div class="mx-auto max-w-6xl">
            <div class="mb-6 flex flex-col gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Visão da equipe</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight">Panorama operacional</h2>
                    <p class="mt-1 text-sm text-muted-foreground">Distribuição atual das tarefas de {{ $team->name }}.</p>
                </div>
                <div class="grid w-full grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                    <div class="min-w-40">
                        <label for="indicator-user-filter" class="mb-1 block text-[11px] font-medium text-muted-foreground">Responsável</label>
                        <x-system-select id="indicator-user-filter" model="filterAssigneeId" :value="$filterAssigneeId" :options="$users->pluck('name', 'id')->all()" placeholder="Todos" icon="user" />
                    </div>
                    <div class="min-w-40">
                        <label class="mb-1 block text-[11px] font-medium text-muted-foreground">Categoria</label>
                        <x-system-select model="filterCategoryId" :value="$filterCategoryId" :options="$categories->pluck('name', 'id')->all()" placeholder="Todas" icon="tag" />
                    </div>
                    <div class="min-w-40">
                        <label class="mb-1 block text-[11px] font-medium text-muted-foreground">Status</label>
                        <x-system-select model="filterStatus" :value="$filterStatus" :options="collect($statusOptions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" placeholder="Todos" icon="clock" />
                    </div>
                    <div class="min-w-40">
                        <label class="mb-1 block text-[11px] font-medium text-muted-foreground">Urgência</label>
                        <x-system-select model="filterUrgent" :value="$filterUrgent" :options="['1' => 'Somente urgentes', '0' => 'Somente não urgentes']" placeholder="Todas" icon="warning-circle" />
                    </div>
                    <div>
                        <label for="indicator-start-date" class="mb-1 block text-[11px] font-medium text-muted-foreground">Data inicial</label>
                        <div class="relative" x-data>
                            <i class="ph ph-calendar-blank pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground" aria-hidden="true"></i>
                            <input id="indicator-start-date" x-ref="indicatorStartDate" wire:model.live="indicatorStartDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-9 pr-10 text-sm shadow-sm outline-none transition-colors focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                            <button type="button" x-on:click="$refs.indicatorStartDate.showPicker ? $refs.indicatorStartDate.showPicker() : $refs.indicatorStartDate.focus()" aria-label="Abrir calendário da data inicial dos indicadores" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"><i class="ph ph-caret-down text-xs" aria-hidden="true"></i></button>
                        </div>
                    </div>
                    <div>
                        <label for="indicator-end-date" class="mb-1 block text-[11px] font-medium text-muted-foreground">Data final</label>
                        <div class="relative" x-data>
                            <i class="ph ph-calendar-blank pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground" aria-hidden="true"></i>
                            <input id="indicator-end-date" x-ref="indicatorEndDate" wire:model.live="indicatorEndDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-9 pr-10 text-sm shadow-sm outline-none transition-colors focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                            <button type="button" x-on:click="$refs.indicatorEndDate.showPicker ? $refs.indicatorEndDate.showPicker() : $refs.indicatorEndDate.focus()" aria-label="Abrir calendário da data final dos indicadores" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"><i class="ph ph-caret-down text-xs" aria-hidden="true"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <section class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6" aria-label="Resumo das tarefas">
                @foreach ($teamIndicators as $indicator)
                    <article class="min-w-0 rounded-shadcn border border-border bg-card p-3 text-card-foreground shadow-sm sm:p-4">
                        <div class="flex items-center justify-between text-muted-foreground"><span class="text-xs font-medium">{{ $indicator['label'] }}</span><i class="ph-duotone ph-{{ $indicator['icon'] }} text-base {{ $indicator['text'] }}" aria-hidden="true"></i></div>
                        <p class="mt-3 text-2xl font-semibold tracking-tight">{{ $indicator['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="mt-4 rounded-shadcn border border-border bg-card p-3 text-card-foreground shadow-sm sm:p-5" aria-labelledby="team-chart-title">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h3 id="team-chart-title" class="text-base font-semibold tracking-tight">Distribuição de tarefas</h3><p class="mt-1 text-xs text-muted-foreground">Comparação proporcional dos indicadores da equipe.</p></div>
                    <div data-testid="indicator-chart-controls" class="flex h-8 max-w-full items-center overflow-x-auto rounded-md border border-input bg-background p-0.5" role="group" aria-label="Tipo de gráfico">
                        <button type="button" x-on:click="indicatorChart = 'lines'" class="flex h-6 items-center gap-1.5 rounded-sm px-2 text-xs font-medium transition-colors" :class="indicatorChart === 'lines' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'" :aria-pressed="indicatorChart === 'lines'">
                            <i class="ph ph-chart-bar-horizontal text-sm" aria-hidden="true"></i><span>Linhas</span>
                        </button>
                        <button type="button" x-on:click="indicatorChart = 'columns'" class="flex h-6 items-center gap-1.5 rounded-sm px-2 text-xs font-medium transition-colors" :class="indicatorChart === 'columns' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'" :aria-pressed="indicatorChart === 'columns'">
                            <i class="ph ph-chart-bar text-sm" aria-hidden="true"></i><span>Colunas</span>
                        </button>
                        <button type="button" x-on:click="indicatorChart = 'pie'" class="flex h-6 items-center gap-1.5 rounded-sm px-2 text-xs font-medium transition-colors" :class="indicatorChart === 'pie' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'" :aria-pressed="indicatorChart === 'pie'">
                            <i class="ph ph-chart-pie-slice text-sm" aria-hidden="true"></i><span>Pizza</span>
                        </button>
                    </div>
                </div>
                <div x-show="indicatorChart === 'lines'" class="space-y-4" role="img" aria-label="Gráfico de linhas dos indicadores da equipe">
                    @foreach (array_slice($teamIndicators, 1) as $indicator)
                        @php
                            $indicatorPercentage = ($indicator['value'] / $teamIndicatorMaximum) * 100;
                        @endphp
                        <div class="grid grid-cols-[5.5rem_minmax(0,1fr)_2rem] items-center gap-2 sm:grid-cols-[9rem_minmax(0,1fr)_2.5rem] sm:gap-3">
                            <span class="truncate text-xs font-medium text-muted-foreground">{{ $indicator['label'] }}</span>
                            <div class="h-2 overflow-hidden rounded-sm bg-muted"><div class="h-full rounded-sm {{ $indicator['bar'] }} transition-[width] duration-500" style="width: {{ $indicatorPercentage }}%"></div></div>
                            <span class="text-right text-xs font-semibold tabular-nums">{{ $indicator['value'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div x-show="indicatorChart === 'columns'" x-cloak class="grid grid-cols-2 gap-4 sm:grid-cols-5 sm:gap-3" role="img" aria-label="Gráfico de colunas dos indicadores da equipe">
                    @foreach (array_slice($teamIndicators, 1) as $indicator)
                        @php
                            $indicatorPercentage = ($indicator['value'] / $teamIndicatorMaximum) * 100;
                        @endphp
                        <div class="flex min-w-0 flex-col items-center">
                            <span class="mb-2 text-xs font-semibold tabular-nums">{{ $indicator['value'] }}</span>
                            <div class="flex h-44 w-full max-w-16 items-end rounded-md bg-muted p-1">
                                <div class="w-full rounded-sm {{ $indicator['bar'] }} transition-[height] duration-500" style="height: {{ $indicatorPercentage }}%"></div>
                            </div>
                            <span class="mt-2 max-w-full truncate text-center text-xs font-medium text-muted-foreground">{{ $indicator['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div x-show="indicatorChart === 'pie'" x-cloak class="flex flex-col items-center justify-center gap-6 py-2 sm:flex-row" role="img" aria-label="Gráfico de pizza das tarefas por status">
                    <div class="relative h-40 w-40 shrink-0 rounded-full sm:h-48 sm:w-48" style="background: {{ $urgentPie }}">
                        <div class="absolute inset-3 rounded-full border-4 border-card" style="background: {{ $statusPie }}"></div>
                        <div class="absolute inset-10 flex items-center justify-center rounded-full bg-card text-center shadow-sm sm:inset-12">
                            <div><span class="block text-2xl font-semibold tabular-nums">{{ $summary['total'] }}</span><span class="text-xs text-muted-foreground">tarefas</span></div>
                        </div>
                    </div>
                    <div class="w-full max-w-xs space-y-3">
                        @foreach ($statusIndicators as $indicator)
                            <div class="flex items-center justify-between gap-6 text-xs">
                                <span class="flex items-center gap-2 text-muted-foreground"><span class="h-2.5 w-2.5 rounded-sm {{ $indicator['color'] }}"></span>{{ $indicator['label'] }}</span>
                                <span class="font-semibold tabular-nums">{{ $indicator['value'] }}</span>
                            </div>
                        @endforeach
                        <div class="flex items-center justify-between gap-6 border-t border-border pt-3 text-xs">
                            <span class="flex items-center gap-2 text-muted-foreground"><span class="h-2.5 w-2.5 rounded-sm bg-red-500"></span>Urgentes <span class="text-[10px]">(anel externo)</span></span>
                            <span class="font-semibold tabular-nums">{{ $summary['urgent'] }}</span>
                        </div>
                    </div>
                </div>
            </section>

            @php
                $monthlyTaskMaximum = max(1, ...array_column($monthlyTasks, 'value'));
            @endphp
            <section class="mt-4 rounded-shadcn border border-border bg-card p-3 text-card-foreground shadow-sm sm:p-5" aria-labelledby="monthly-task-chart-title">
                <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div><h3 id="monthly-task-chart-title" class="text-base font-semibold tracking-tight">Tarefas por período</h3><p class="mt-1 text-xs text-muted-foreground">Quantidade de tarefas iniciadas no intervalo selecionado.</p></div>
                    <div class="w-full sm:w-44"><label class="mb-1 block text-[11px] font-medium text-muted-foreground">Agrupar por</label><x-system-select model="indicatorGrouping" :value="$indicatorGrouping" :options="['daily' => 'Diário', 'weekly' => 'Semanal', 'monthly' => 'Mensal', 'yearly' => 'Anual']" icon="calendar" /></div>
                </div>
                <div wire:key="period-chart-navigation-{{ $indicatorGrouping }}-{{ $indicatorStartDate }}-{{ $indicatorEndDate }}" x-data="{
                    thumbWidth: 100,
                    thumbLeft: 0,
                    dragging: false,
                    dragStartX: 0,
                    dragStartScroll: 0,
                    updateThumb() {
                        const scroller = this.$refs.periodScroller;
                        if (scroller.clientWidth === 0) {
                            this.thumbWidth = 100;
                            this.thumbLeft = 0;
                            return;
                        }
                        const scrollableWidth = scroller.scrollWidth - scroller.clientWidth;
                        const hasOverflow = scrollableWidth > 1;
                        this.thumbWidth = hasOverflow ? Math.max(12, (scroller.clientWidth / scroller.scrollWidth) * 100) : 100;
                        this.thumbLeft = hasOverflow ? (scroller.scrollLeft / scrollableWidth) * (100 - this.thumbWidth) : 0;
                    },
                    dragThumb(event) {
                        if (!this.dragging) return;
                        const scroller = this.$refs.periodScroller;
                        const trackWidth = this.$refs.periodTrack.clientWidth;
                        const scrollableWidth = scroller.scrollWidth - scroller.clientWidth;
                        if (trackWidth === 0 || scrollableWidth === 0) return;
                        scroller.scrollLeft = this.dragStartScroll + ((event.clientX - this.dragStartX) / trackWidth) * scrollableWidth;
                    },
                    scrollColumns(direction) {
                        this.$refs.periodScroller.scrollBy({
                            left: this.$refs.periodScroller.clientWidth * direction * 0.75,
                            behavior: 'smooth',
                        });
                    }
                }"
                x-init="$nextTick(() => {
                    updateThumb();
                    requestAnimationFrame(() => updateThumb());
                    setTimeout(() => updateThumb(), 50);
                    setTimeout(() => updateThumb(), 250);
                    new ResizeObserver(() => updateThumb()).observe($refs.periodScroller);
                    new MutationObserver(() => requestAnimationFrame(() => updateThumb())).observe($el.closest('main'), { attributes: true, attributeFilter: ['style', 'class'] });
                })"
                x-effect="if (panelView === 'indicators') { $nextTick(() => { requestAnimationFrame(() => updateThumb()); setTimeout(() => updateThumb(), 50); }) }"
                x-on:pointermove.window="dragThumb($event)"
                x-on:pointerup.window="dragging = false"
                x-on:pointercancel.window="dragging = false">
                    <div class="relative">
                        <div x-ref="periodScroller" x-on:scroll="updateThumb()" class="period-chart-scroller grid grid-flow-col auto-cols-[minmax(4rem,1fr)] gap-3 overflow-x-auto" role="img" aria-label="Gráfico de colunas com a quantidade de tarefas por período">
                            @foreach ($monthlyTasks as $month)
                                <div class="flex min-w-0 flex-col items-center">
                                    <span class="mb-2 text-xs font-semibold tabular-nums">{{ $month['value'] }}</span>
                                    <div class="flex h-36 w-full max-w-16 items-end rounded-md bg-muted p-1"><div class="w-full rounded-sm bg-primary transition-[height] duration-500" style="height: {{ ($month['value'] / $monthlyTaskMaximum) * 100 }}%"></div></div>
                                    <span class="mt-2 text-center text-xs font-medium text-muted-foreground">{{ $month['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" x-show="thumbWidth < 100 && thumbLeft > 0" x-cloak x-on:click="scrollColumns(-1)" aria-label="Ver colunas anteriores" class="absolute left-1 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-foreground text-background shadow-sm transition-colors hover:bg-foreground/85"><i class="ph ph-caret-left text-sm" aria-hidden="true"></i></button>
                        <button type="button" x-show="thumbWidth < 100 && thumbLeft + thumbWidth < 99.5" x-cloak x-on:click="scrollColumns(1)" aria-label="Ver próximas colunas" class="absolute right-1 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full bg-foreground text-background shadow-sm transition-colors hover:bg-foreground/85"><i class="ph ph-caret-right text-sm" aria-hidden="true"></i></button>
                    </div>
                    <div x-ref="periodTrack" x-show="thumbWidth < 100" x-cloak class="relative mt-2 h-2">
                        <button type="button" aria-label="Deslizar gráfico horizontalmente" x-on:pointerdown.prevent="dragging = true; dragStartX = $event.clientX; dragStartScroll = $refs.periodScroller.scrollLeft" class="absolute top-0 h-2 cursor-grab rounded-sm bg-muted-foreground/45 active:cursor-grabbing hover:bg-muted-foreground/65" x-bind:style="'width: ' + thumbWidth + '%; left: ' + thumbLeft + '%'"></button>
                    </div>
                </div>
            </section>

            <section class="mt-4 overflow-hidden rounded-shadcn border border-border bg-card shadow-sm" aria-labelledby="indicator-task-list-title">
                <div class="border-b border-border px-4 py-3">
                    <h3 id="indicator-task-list-title" class="text-sm font-semibold">Tarefas dos indicadores</h3>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $tasks->total() }} registros encontrados com os filtros atuais.</p>
                </div>
                <div class="hidden overflow-x-auto sm:block">
                    <table class="w-full min-w-[42rem] text-left text-sm">
                        <thead class="border-b border-border bg-muted/50 text-xs text-muted-foreground">
                            <tr><th class="h-10 px-4 font-medium">Código</th><th class="h-10 px-4 font-medium">Tarefa</th><th class="h-10 px-4 font-medium">Responsáveis</th><th class="h-10 px-4 font-medium">Status</th><th class="h-10 px-4 font-medium">Data final</th></tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            @forelse ($tasks as $task)
                                <tr class="hover:bg-muted/30"><td class="px-4 py-3 text-xs font-medium">{{ $task->code }}</td><td class="px-4 py-3"><span class="block font-medium">{{ $task->title }}</span><span class="block text-xs text-muted-foreground">{{ $task->category?->name }}</span></td><td class="px-4 py-3 text-xs text-muted-foreground">{{ $task->assignees->pluck('name')->join(', ') ?: 'Sem responsável' }}</td><td class="px-4 py-3 text-xs">{{ $task->status->label() }}</td><td class="px-4 py-3 text-xs text-muted-foreground">{{ $task->due_date?->format('d/m/Y') ?? 'Sem data final' }}</td></tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-muted-foreground">Nenhuma tarefa encontrada.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="divide-y divide-border sm:hidden">
                    @forelse ($tasks as $task)
                        <article class="space-y-2 px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0"><p class="text-[10px] font-medium text-muted-foreground">{{ $task->code }}</p><h4 class="mt-0.5 truncate text-sm font-medium">{{ $task->title }}</h4></div>
                                <span class="shrink-0 text-xs font-medium">{{ $task->status->label() }}</span>
                            </div>
                            <p class="truncate text-xs text-muted-foreground">{{ $task->category?->name ?? 'Sem categoria' }} · {{ $task->assignees->pluck('name')->join(', ') ?: 'Sem responsável' }}</p>
                            <p class="text-xs text-muted-foreground">Data final: {{ $task->due_date?->format('d/m/Y') ?? 'Sem data final' }}</p>
                        </article>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-muted-foreground">Nenhuma tarefa encontrada.</p>
                    @endforelse
                </div>
                @if ($tasks->hasPages())<div class="border-t border-border px-4 py-3">{{ $tasks->links() }}</div>@endif
            </section>
        </div>
    </main>

    <header x-show="panelView === 'reports'" x-cloak
        class="z-20 grid min-h-[65px] shrink-0 grid-cols-1 items-center gap-3 border-b border-border bg-background px-3 py-3 sm:px-6 md:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] md:py-4">
        <div class="flex min-w-0 w-full items-center gap-2 md:w-auto">
            <button x-on:click="sidebarOpen = true" aria-label="Abrir menu lateral" class="flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground"><i class="ph ph-list text-lg" aria-hidden="true"></i></button>
            <span class="min-w-0 truncate font-semibold tracking-tight text-foreground">Relatórios <span class="font-normal text-muted-foreground">/ {{ $team->name }}</span></span>
        </div>
        <span class="hidden md:block" aria-hidden="true"></span>
        <span class="hidden md:block" aria-hidden="true"></span>
    </header>

    <main x-show="panelView === 'reports'" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="flex-1 overflow-y-auto bg-muted/40 p-3 sm:p-6 custom-scrollbar">
        <div class="mx-auto max-w-6xl">
            <div class="mb-5"><p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Central de relatórios</p><h2 class="mt-1 text-2xl font-semibold tracking-tight">Escolha uma apresentação</h2><p class="mt-1 text-sm text-muted-foreground">Configure os dados antes de abrir a versão pronta para impressão ou PDF.</p></div>

            <button type="button" x-on:click="reportConfigOpen = true" class="group flex w-full max-w-md items-start gap-3 rounded-shadcn border border-border bg-card p-4 text-left shadow-sm transition-colors hover:bg-accent/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:gap-4 sm:p-5">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground"><i class="ph-duotone ph-file-pdf text-xl" aria-hidden="true"></i></span>
                <span class="min-w-0 flex-1"><span class="block text-base font-semibold">Relatório geral</span><span class="mt-1 block text-xs leading-relaxed text-muted-foreground">Indicadores consolidados e relação completa das tarefas da equipe.</span></span>
                <i class="ph ph-caret-right mt-1 text-sm text-muted-foreground transition-transform group-hover:translate-x-0.5" aria-hidden="true"></i>
            </button>
        </div>
    </main>

    <div x-show="reportConfigOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center bg-black/75 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="report-config-title">
        <div x-show="reportConfigOpen" x-transition x-on:click.outside="reportConfigOpen = false" class="max-h-[calc(100dvh-1rem)] w-full max-w-xl overflow-y-auto rounded-shadcn border border-border bg-background shadow-lg custom-scrollbar">
            <div class="flex items-center justify-between border-b border-border px-4 py-3 sm:px-5 sm:py-4"><div class="min-w-0"><h3 id="report-config-title" class="text-base font-semibold">Relatório geral</h3><p class="mt-1 truncate text-xs text-muted-foreground">Defina os parâmetros da apresentação.</p></div><button type="button" x-on:click="reportConfigOpen = false" aria-label="Fechar configuração do relatório" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground"><i class="ph ph-x text-lg" aria-hidden="true"></i></button></div>
            <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-5">
                <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-medium">Responsável</label><x-system-select model="reportAssigneeId" :value="$reportAssigneeId" :options="$users->pluck('name', 'id')->all()" placeholder="Todos os usuários" icon="user" /></div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium">Data inicial</label>
                    <div class="relative" x-data>
                        <i class="ph-bold ph-calendar-blank pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-base text-foreground" aria-hidden="true"></i>
                        <input x-ref="reportStartDate" wire:model.live="reportStartDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-10 pr-9 text-sm text-foreground shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                        <button type="button" x-on:click="$refs.reportStartDate.showPicker ? $refs.reportStartDate.showPicker() : $refs.reportStartDate.focus()" aria-label="Abrir calendário da data inicial" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-foreground hover:bg-accent"><i class="ph-bold ph-caret-down text-xs" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium">Data final</label>
                    <div class="relative" x-data>
                        <i class="ph-bold ph-calendar-blank pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-base text-foreground" aria-hidden="true"></i>
                        <input x-ref="reportEndDate" wire:model.live="reportEndDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-10 pr-9 text-sm text-foreground shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                        <button type="button" x-on:click="$refs.reportEndDate.showPicker ? $refs.reportEndDate.showPicker() : $refs.reportEndDate.focus()" aria-label="Abrir calendário da data final" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-foreground hover:bg-accent"><i class="ph-bold ph-caret-down text-xs" aria-hidden="true"></i></button>
                    </div>
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-xs font-medium">Estilo do gráfico</label>
                    <div class="grid grid-cols-3 gap-2" role="radiogroup" aria-label="Estilo do gráfico do relatório">
                        @foreach ([['lines', 'chart-bar-horizontal', 'Linhas'], ['columns', 'chart-bar', 'Colunas'], ['pie', 'chart-pie-slice', 'Pizza']] as [$chartValue, $chartIcon, $chartLabel])
                            <button type="button" wire:click="$set('reportChartStyle', '{{ $chartValue }}')" role="radio" aria-checked="{{ $reportChartStyle === $chartValue ? 'true' : 'false' }}" class="flex h-12 flex-col items-center justify-center gap-0.5 rounded-md border px-1 text-xs font-medium transition-colors sm:h-10 sm:flex-row sm:gap-2 sm:px-3 {{ $reportChartStyle === $chartValue ? 'border-primary bg-primary text-primary-foreground shadow-sm' : 'border-input bg-muted/50 text-muted-foreground hover:bg-accent hover:text-accent-foreground' }}"><i class="ph-duotone ph-{{ $chartIcon }} text-base" aria-hidden="true"></i>{{ $chartLabel }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-stretch gap-3 border-t border-border bg-muted/30 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5"><span class="text-xs text-muted-foreground">As cores serão preservadas na impressão.</span><a target="_blank" rel="noopener" href="{{ route('teams.reports.general', ['team' => $team, 'assignee_id' => $reportAssigneeId ?: null, 'start_date' => $reportStartDate ?: null, 'end_date' => $reportEndDate ?: null, 'chart_style' => $reportChartStyle]) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"><i class="ph ph-printer" aria-hidden="true"></i>Imprimir PDF</a></div>
        </div>
    </div>

    <header x-show="panelView === 'tasks'"
        class="z-20 grid min-h-[65px] shrink-0 grid-cols-1 items-center gap-2 border-b border-border bg-background px-3 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:gap-3 sm:px-6 sm:py-4 xl:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
        <div class="flex min-w-0 items-center gap-2 w-full sm:w-auto">
            <button x-on:click="sidebarOpen = true" aria-label="Abrir menu lateral" class="group flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <i class="ph ph-list text-lg"></i>
            </button>
            <div class="min-w-0 flex items-center gap-3">
                <span class="block truncate font-semibold tracking-tight text-foreground">
                    {{ $team->name }} <span class="font-normal text-muted-foreground">/ {{ auth()->user()->name }}</span>
                </span>
            </div>
        </div>

        <div class="order-3 col-span-full flex h-8 w-full max-w-full items-center justify-self-center overflow-x-auto rounded-md border border-border bg-muted p-0.5 xl:order-none xl:col-auto xl:w-auto">
            <button type="button" wire:click="applyQuickFilter('total')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'total' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <i class="ph ph-files text-sm"></i>
                <span class="text-xs font-medium">Total <span class="font-bold">{{ $summary['total'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('pending')"
                class="flex h-full items-center gap-1.5 whitespace-nowrap rounded-sm px-2.5 transition-colors {{ $quickFilter === 'pending' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:bg-background/60 hover:text-foreground' }}">
                <div class="h-1.5 w-1.5 rounded-full bg-amber-500"></div>
                <span class="text-xs font-medium">Pendentes <span class="font-bold">{{ $pendingTasks }}</span></span>
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

        <div class="flex w-full min-w-0 items-center gap-2 sm:w-auto sm:justify-self-end xl:justify-self-end">
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
                    class="hidden h-6 w-7 items-center justify-center rounded-sm transition-colors sm:flex"
                    :class="viewMode === 'list' ? 'bg-accent text-accent-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'">
                    <i class="ph ph-list text-sm"></i>
                </button>
            </div>
            <div class="relative">
                <button type="button" x-on:click="filterOpen = !filterOpen" aria-label="Abrir filtros"
                    class="flex h-8 w-8 items-center justify-center rounded-md border border-input bg-background text-muted-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="({{ $filterAssigneeId !== '' ? 'true' : 'false' }} || {{ $filterCategoryId !== '' ? 'true' : 'false' }} || {{ $filterStatus !== '' ? 'true' : 'false' }} || {{ $filterUrgent !== '' ? 'true' : 'false' }} || {{ !in_array($quickFilter, ['', 'total'], true) ? 'true' : 'false' }}) ? 'bg-accent text-accent-foreground' : ''">
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
                    class="fixed inset-x-3 top-auto z-30 mt-2 rounded-shadcn border border-border bg-popover p-4 text-popover-foreground shadow-md sm:absolute sm:inset-x-auto sm:right-0 sm:top-10 sm:mt-0 sm:w-72">
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
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Responsável</label>
                            <x-system-select model="filterAssigneeId" :value="$filterAssigneeId" :options="$users->pluck('name', 'id')->all()" placeholder="Todos" icon="user" />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Categoria</label>
                            <x-system-select model="filterCategoryId" :value="$filterCategoryId" :options="$categories->pluck('name', 'id')->all()" placeholder="Todas" icon="tag" />
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Status</label>
                            <x-system-select model="filterStatus" :value="$filterStatus" :options="collect($statusOptions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()" placeholder="Todos" icon="clock" />
                        </div>

                        <div>
                            <label class="mb-1 block text-[11px] font-medium text-foreground">Urgência</label>
                            <x-system-select model="filterUrgent" :value="$filterUrgent" :options="['1' => 'Somente urgentes', '0' => 'Somente não urgentes']" placeholder="Todas" icon="warning-circle" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative h-8 min-w-0 flex-1 sm:w-56">
                <i class="ph ph-magnifying-glass pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-sm text-muted-foreground"></i>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..." 
                    class="h-full w-full rounded-md border border-input bg-muted/50 pl-8 pr-3 text-xs shadow-sm outline-none placeholder:text-muted-foreground focus:bg-background focus:ring-2 focus:ring-ring">
            </div>
            <button x-on:click="$wire.resetForm(); modalOpen = true; mode = 'create'; modalView = 'details'" 
                class="flex h-8 items-center justify-center gap-1.5 whitespace-nowrap rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <i class="ph ph-plus text-sm"></i>
                <span class="hidden sm:inline">Nova tarefa</span>
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
         class="fixed bottom-3 left-3 right-3 z-[60] flex items-center gap-3 rounded-shadcn border border-border bg-background px-4 py-3 text-foreground shadow-lg sm:bottom-6 sm:left-auto sm:right-6">
        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground">
            <i class="ph-bold ph-check text-xs"></i>
        </div>
        <span class="text-xs font-bold tracking-tight" x-text="message"></span>
    </div>

    @if (session()->has('error'))
        <div class="m-3 flex items-center gap-3 rounded-md bg-destructive p-4 text-destructive-foreground shadow-sm sm:m-6">
            <i class="ph-fill ph-warning-circle text-2xl"></i>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if ($filterAssigneeId !== '' || $filterCategoryId !== '' || $filterStatus !== '' || $filterUrgent !== '' || !in_array($quickFilter, ['', 'total'], true))
        <div x-show="panelView === 'tasks'" class="mb-4 flex flex-wrap items-center gap-2 px-3 pt-4 sm:px-6">
            @if (!in_array($quickFilter, ['', 'total'], true))
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-faders text-sm"></i>
                    {{ match($quickFilter) {
                        'total' => 'Total',
                        'pending' => 'Pendentes',
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

            @if ($filterAssigneeId !== '')
                <span class="inline-flex items-center gap-1 rounded-md border border-border bg-secondary px-3 py-1 text-xs font-medium text-secondary-foreground">
                    <i class="ph ph-user text-sm"></i>
                    {{ optional($users->firstWhere('id', (int) $filterAssigneeId))->name ?? 'Responsável' }}
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

    <main x-show="panelView === 'tasks'" class="flex-1 overflow-y-auto bg-muted/40 p-3 pb-24 sm:p-6 sm:pb-32 custom-scrollbar">
        <div x-show="viewMode === 'list'" x-cloak
            class="mb-2 hidden grid-cols-[6.5rem_minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_6rem_8rem] gap-4 px-4 text-[10px] font-medium text-muted-foreground lg:grid">
            <span>Código</span>
            <span>Tarefa</span>
            <span>Local</span>
            <span>Categoria</span>
            <span>Data final</span>
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
                    $statusBadgeClass = match ($status) {
                        \App\Domain\Tasks\TaskStatus::Completed => 'border border-emerald-500/45 bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                        \App\Domain\Tasks\TaskStatus::InProgress => 'border border-blue-500/45 bg-blue-500/15 text-blue-700 dark:text-blue-300',
                        default => 'border border-amber-500/50 bg-amber-500/15 text-amber-800 dark:text-amber-300',
                    };
                    $urgentBadgeClass = 'border border-red-500/50 bg-red-500/15 text-red-700 dark:text-red-300';
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
                            <span class="max-w-[45%] truncate rounded-full border border-border bg-muted px-2.5 py-0.5 text-[10px] font-semibold text-foreground">
                                {{ $task->category->name ?? 'Sem categoria' }}
                            </span>
                        </div>

                        <div class="mt-4 min-h-0 flex-1">
                            <h3 class="line-clamp-2 text-sm font-medium leading-5 text-foreground">{{ $task->title }}</h3>
                            <p class="mt-1 line-clamp-1 text-xs text-muted-foreground">{{ $task->location ?: 'Local não informado' }}</p>
                            @if ($task->assignees->isNotEmpty())
                                <p class="mt-1 flex items-center gap-1 truncate text-[10px] text-muted-foreground"><i class="ph ph-user" aria-hidden="true"></i>{{ $task->assignees->pluck('name')->join(', ') }}</p>
                            @endif
                        </div>

                        <div class="mt-3 flex items-end justify-between gap-2 border-t border-border pt-3">
                            <span class="text-[11px] text-muted-foreground">
                                {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Sem data final' }}
                            </span>
                            <div class="flex min-w-0 justify-end gap-1.5">
                                @if($showUrgentBadge)
                                    <span class="flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $urgentBadgeClass }}">
                                        <i class="ph ph-warning-circle"></i>
                                        Urgente
                                    </span>
                                @endif

                                <span class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statusBadgeClass }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusDotClass }}"></span>
                                    {{ $status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div x-show="viewMode === 'list'" x-cloak
                        class="grid min-h-[72px] grid-cols-[minmax(0,1fr)_auto] items-center gap-x-3 gap-y-2 px-3 py-3 sm:px-4 lg:grid-cols-[6.5rem_minmax(0,1.5fr)_minmax(0,1fr)_minmax(0,0.8fr)_7rem_6rem_8rem] lg:gap-4">
                        <span class="text-xs font-semibold text-foreground">{{ $task->code }}</span>
                        <div class="min-w-0" wire:dblclick="startInlineEdit({{ $task->id }}, 'title')" title="Clique duas vezes para editar o título">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'title')
                                <input wire:model="inlineEditValue" wire:keydown.enter.prevent="saveInlineEdit"
                                    wire:keydown.escape.prevent="cancelInlineEdit" wire:blur="saveInlineEdit"
                                    x-on:click.stop x-on:dblclick.stop
                                    x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                    class="h-8 w-full rounded-md border border-input bg-muted/50 px-2 text-sm shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <h3 class="truncate text-sm font-medium text-foreground">{{ $task->title }}</h3>
                                <span class="mt-0.5 block truncate text-[10px] text-muted-foreground">{{ $task->assignees->isNotEmpty() ? $task->assignees->pluck('name')->join(', ') : 'Sem responsável' }}</span>
                                <span class="sr-only">Duplo clique para editar</span>
                            @endif
                        </div>

                        <div class="col-span-2 min-w-0 lg:col-span-1" wire:dblclick="startInlineEdit({{ $task->id }}, 'location')" title="Clique duas vezes para editar o local">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'location')
                                <input wire:model="inlineEditValue" wire:keydown.enter.prevent="saveInlineEdit"
                                    wire:keydown.escape.prevent="cancelInlineEdit" wire:blur="saveInlineEdit"
                                    x-on:click.stop x-on:dblclick.stop
                                    x-init="$nextTick(() => { $el.focus(); $el.select(); })"
                                    placeholder="Local não informado"
                                    class="h-8 w-full rounded-md border border-input bg-muted/50 px-2 text-xs shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <p class="truncate text-xs text-muted-foreground">{{ $task->location ?: 'Local não informado' }}</p>
                            @endif
                        </div>

                        <div class="min-w-0" wire:dblclick="startInlineEdit({{ $task->id }}, 'category_id')" title="Clique duas vezes para editar a categoria">
                            @if ($inlineEditingTaskId === $task->id && $inlineEditingField === 'category_id')
                                <div class="relative z-20" x-data="{ open: true }" x-on:click.stop x-on:dblclick.stop x-on:click.outside="$wire.cancelInlineEdit()">
                                    <button type="button" x-on:click="open = !open" class="flex h-8 w-full items-center gap-2 rounded-md border border-input bg-muted/50 px-2 text-left text-xs shadow-sm outline-none focus:ring-2 focus:ring-ring">
                                        <span class="min-w-0 flex-1 truncate">{{ optional($categories->firstWhere('id', (int) $inlineEditValue))->name ?? 'Categoria' }}</span><i class="ph ph-caret-down text-xs"></i>
                                    </button>
                                    <div x-show="open" x-cloak data-testid="inline-category-options" class="mt-1 max-h-44 min-w-52 overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md">
                                        @foreach($categories as $category)
                                            <button type="button" wire:click="selectInlineCategory({{ $category->id }})" x-on:click="open = false" class="flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-left text-xs hover:bg-accent">
                                                <span class="truncate">{{ $category->name }}</span>@if ((int) $inlineEditValue === $category->id)<i class="ph ph-check"></i>@endif
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
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
                                    class="h-8 w-full rounded-md border border-input bg-muted/50 px-2 text-xs shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark]">
                                @error('inlineEditValue') <span class="mt-1 block text-[10px] text-destructive">{{ $message }}</span> @enderror
                            @else
                                <span class="text-xs text-muted-foreground">
                                    {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Sem data final' }}
                                </span>
                            @endif
                        </div>

                        <button type="button" wire:click.stop="toggleInlineUrgency({{ $task->id }})"
                            class="flex items-center justify-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $showUrgentBadge ? $urgentBadgeClass : 'border border-border bg-muted text-foreground' }}"
                            title="Clique para alternar a prioridade">
                            <i class="ph {{ $isUrgent ? 'ph-warning-circle' : 'ph-minus-circle' }}"></i>
                            {{ $isUrgent ? 'Urgente' : 'Normal' }}
                        </button>

                        <button type="button" wire:click.stop="cycleStatus({{ $task->id }})"
                            class="flex items-center justify-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-semibold {{ $statusBadgeClass }}"
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

    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-2 transition-opacity duration-200 sm:p-4"
         x-show="modalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div class="flex h-[calc(100dvh-1rem)] max-h-[726px] w-full max-w-6xl transform flex-col overflow-hidden rounded-shadcn border border-border bg-background text-foreground shadow-lg transition-[opacity,transform] sm:h-[calc(100dvh-2rem)]"
         x-show="modalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-on:click.away="$wire.closeModal()">
            <div class="flex shrink-0 flex-col items-stretch gap-2 border-b border-border px-3 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-4">
                <div class="flex min-w-0 items-center gap-2">
                    <h2 class="truncate text-base font-semibold tracking-tight text-foreground sm:text-lg"
                        x-text="mode === 'create' ? 'Nova Tarefa' : 'Editar Tarefa - ' + '{{ $form->taskId }}'"></h2>
                </div>
                <div class="flex min-w-0 items-center justify-end gap-1 sm:gap-2">
                    <div class="relative min-w-0 flex-1 sm:flex-none" x-data="{ open: false }" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = !open" aria-label="Selecionar responsáveis"
                            class="flex h-8 w-full min-w-0 items-center gap-1.5 rounded-md border border-input bg-muted/40 px-2.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:max-w-52">
                            <i class="ph ph-user-circle-plus text-base" aria-hidden="true"></i>
                            <span class="truncate">{{ count($form->assigneeIds) > 0 ? optional($users->firstWhere('id', (int) $form->assigneeIds[0]))->name.(count($form->assigneeIds) > 1 ? ' +'.(count($form->assigneeIds) - 1) : '') : 'Responsáveis' }}</span>
                            <i class="ph ph-caret-down text-[10px]" aria-hidden="true"></i>
                        </button>
                        <div x-show="open" x-cloak x-transition class="absolute left-0 z-50 mt-1 w-[min(16rem,calc(100vw-2.5rem))] rounded-md border border-border bg-popover p-1.5 text-popover-foreground shadow-md sm:left-auto sm:right-0 sm:w-64">
                            <p class="px-2 py-1 text-[10px] font-medium uppercase tracking-wider text-muted-foreground">Designar usuários</p>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                @forelse ($users as $user)
                                    <button type="button" wire:click.stop="toggleAssignee({{ $user->id }})" class="flex w-full items-center gap-2 rounded-sm px-2 py-2 text-left text-xs transition-colors hover:bg-accent">
                                        <span class="min-w-0 flex-1 truncate">{{ $user->name }}</span>
                                        @if (in_array($user->id, array_map('intval', $form->assigneeIds), true))<i class="ph ph-check text-sm" aria-hidden="true"></i>@endif
                                    </button>
                                @empty
                                    <p class="px-2 py-3 text-xs text-muted-foreground">Nenhum usuário vinculado à equipe.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <button type="button" x-on:click="modalView = 'attachments'" aria-label="Exibir arquivos da tarefa"
                        :class="modalView === 'attachments' ? 'bg-accent text-accent-foreground' : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'"
                        class="flex h-8 w-8 items-center justify-center rounded-md transition-colors"
                        title="Arquivos da tarefa">
                        <i class="ph ph-files text-xl"></i>
                    </button>
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
                <div class="min-h-0 flex-1 space-y-4 overflow-visible p-4 sm:p-5 lg:overflow-y-auto lg:p-6 custom-scrollbar">
                    
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
                            data-testid="task-title-input"
                            class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:bg-background focus:ring-2 focus:ring-ring">
                        @error('form.title') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">Localização</label>
                        <input wire:model="form.location" type="text" placeholder="Nome da rua, bairro ou praça"
                            class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:bg-background focus:ring-2 focus:ring-ring">
                        @error('form.location') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-foreground">Categoria</label>
                            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                                <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open"
                                    class="flex h-9 w-full items-center gap-2 rounded-md border border-input bg-muted/50 px-3 text-left text-sm shadow-sm outline-none transition-colors hover:bg-accent/50 focus:bg-background focus:ring-2 focus:ring-ring">
                                    <i class="ph ph-tag text-sm text-muted-foreground" aria-hidden="true"></i>
                                    <span class="min-w-0 flex-1 truncate">{{ optional($categories->firstWhere('id', (int) $form->categoryId))->name ?? 'Selecione uma categoria' }}</span>
                                    <i class="ph ph-caret-down text-xs text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
                                </button>
                                <div x-show="open" x-cloak x-transition class="absolute z-40 mt-1 max-h-52 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md custom-scrollbar">
                                    @foreach($categories as $category)
                                        <button type="button" wire:click="$set('form.categoryId', {{ $category->id }})" x-on:click="open = false"
                                            class="flex w-full items-center justify-between rounded-sm px-2.5 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                                            <span class="truncate">{{ $category->name }}</span>
                                            @if ((int) $form->categoryId === $category->id)<i class="ph ph-check text-sm" aria-hidden="true"></i>@endif
                                        </button>
                                    @endforeach
                                    @can('create', \App\Models\Category::class)
                                        <div class="mt-1 border-t border-border p-2" x-on:click.stop>
                                            <label class="sr-only" for="new-category-name">Nova categoria</label>
                                            <div class="flex items-center gap-2">
                                                <input id="new-category-name" wire:model="newCategoryName" wire:keydown.enter.prevent="createNewCategory" type="text" placeholder="Nova categoria" class="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-2 text-sm outline-none focus:ring-2 focus:ring-ring">
                                                <button type="button" wire:click="createNewCategory" aria-label="Adicionar categoria" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-primary text-sm font-medium text-primary-foreground hover:bg-primary/90"><i class="ph ph-plus" aria-hidden="true"></i></button>
                                            </div>
                                            @error('newCategoryName') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    @endcan
                                </div>
                            </div>
                            @error('form.categoryId') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-foreground">Data inicial</label>
                            <div class="relative" x-data>
                                <i class="ph ph-calendar-blank pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground" aria-hidden="true"></i>
                                <input x-ref="taskStartDate" wire:model="form.startDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-9 pr-10 text-sm shadow-sm outline-none transition-colors focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                                <button type="button" x-on:click="$refs.taskStartDate.showPicker ? $refs.taskStartDate.showPicker() : $refs.taskStartDate.focus()" aria-label="Abrir calendário da data inicial" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground"><i class="ph ph-caret-down text-xs" aria-hidden="true"></i></button>
                            </div>
                            @error('form.startDate') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-foreground">Data final</label>
                            <div class="relative" x-data>
                                <i class="ph ph-calendar-blank pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-muted-foreground" aria-hidden="true"></i>
                                <input x-ref="taskDueDate" wire:model="form.dueDate" type="date" class="h-9 w-full appearance-none rounded-md border border-input bg-muted/50 pl-9 pr-10 text-sm shadow-sm outline-none transition-colors focus:bg-background focus:ring-2 focus:ring-ring [color-scheme:light] dark:[color-scheme:dark] [&::-webkit-calendar-picker-indicator]:opacity-0">
                                <button type="button" x-on:click="$refs.taskDueDate.showPicker ? $refs.taskDueDate.showPicker() : $refs.taskDueDate.focus()" aria-label="Abrir calendário da data final" class="absolute right-1 top-1 flex h-7 w-7 items-center justify-center rounded-sm text-muted-foreground transition-colors hover:bg-accent hover:text-accent-foreground">
                                    <i class="ph ph-caret-down text-xs" aria-hidden="true"></i>
                                </button>
                            </div>
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
                            <div class="flex h-5 w-5 items-center justify-center rounded-sm border transition-colors"
                                 :class="$wire.form.isUrgent ? 'border-red-600 bg-red-600 text-white shadow-sm' : 'border-zinc-950 bg-zinc-950 text-white dark:border-zinc-100 dark:bg-zinc-100 dark:text-zinc-950'">
                                <i class="ph ph-check text-[10px] font-bold" x-show="$wire.form.isUrgent"></i>
                            </div>
                        </div>
                    </label>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">Observação</label>
                        <textarea wire:model="form.observation" rows="3" placeholder="Informações extras..."
                            class="block w-full resize-none rounded-md border border-input bg-muted/50 px-3 py-2 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:bg-background focus:ring-2 focus:ring-ring"></textarea>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-foreground">
                            Status
                        </label>

                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-3" role="group" aria-label="Status da tarefa">
                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Pending->value }}')"
                                x-bind:disabled="mode === 'create'"
                                aria-pressed="{{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Pending->value ? 'true' : 'false' }}"
                                class="flex h-10 items-center gap-2 rounded-md border px-3 text-left text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-55 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Pending->value ? 'border-amber-500/50 bg-amber-500/10 text-amber-800 dark:text-amber-300' : 'border-border bg-card text-muted-foreground hover:bg-muted/60 hover:text-foreground' }}">
                                <span class="h-2 w-1 shrink-0 rounded-sm bg-amber-500"></span>
                                <span class="min-w-0 flex-1">Pendente</span>
                                @if ($form->currentStatus === \App\Domain\Tasks\TaskStatus::Pending->value)<i class="ph-bold ph-check text-sm" aria-hidden="true"></i>@endif
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::InProgress->value }}')"
                                x-bind:disabled="mode === 'create'"
                                aria-pressed="{{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::InProgress->value ? 'true' : 'false' }}"
                                class="flex h-10 items-center gap-2 rounded-md border px-3 text-left text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-55 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::InProgress->value ? 'border-blue-500/45 bg-blue-500/10 text-blue-700 dark:text-blue-300' : 'border-border bg-card text-muted-foreground hover:bg-muted/60 hover:text-foreground' }}">
                                <span class="h-2 w-1 shrink-0 rounded-sm bg-blue-500"></span>
                                <span class="min-w-0 flex-1">Em andamento</span>
                                @if ($form->currentStatus === \App\Domain\Tasks\TaskStatus::InProgress->value)<i class="ph-bold ph-check text-sm" aria-hidden="true"></i>@endif
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Completed->value }}')"
                                x-bind:disabled="mode === 'create'"
                                aria-pressed="{{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Completed->value ? 'true' : 'false' }}"
                                class="flex h-10 items-center gap-2 rounded-md border px-3 text-left text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-55 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Completed->value ? 'border-emerald-500/45 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' : 'border-border bg-card text-muted-foreground hover:bg-muted/60 hover:text-foreground' }}">
                                <span class="h-2 w-1 shrink-0 rounded-sm bg-emerald-500"></span>
                                <span class="min-w-0 flex-1">Concluído</span>
                                @if ($form->currentStatus === \App\Domain\Tasks\TaskStatus::Completed->value)<i class="ph-bold ph-check text-sm" aria-hidden="true"></i>@endif
                            </button>
                        </div>
                    </div>
                </div>

                <div class="shrink-0 border-t border-border bg-background px-4 py-3 sm:px-5 sm:py-4 lg:px-6">
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

                <aside class="flex min-h-[22rem] flex-col border-t border-border bg-muted/40 p-4 sm:p-5 lg:min-h-0 lg:overflow-hidden lg:border-l lg:border-t-0 lg:p-6" aria-label="Checklist da tarefa">
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
                                class="group flex items-center rounded-md border border-border bg-card text-card-foreground shadow-sm transition-colors hover:bg-accent/50">
                                <button type="button" wire:click="toggleChecklistItem({{ $index }})"
                                    class="flex min-w-0 flex-1 items-center gap-3 p-2.5 text-left"
                                    aria-label="Alternar conclusão de {{ $item['label'] }}">
                                    <span class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-sm border border-primary transition-colors {{ !empty($item['is_completed']) ? 'bg-primary text-primary-foreground' : 'bg-background' }}">
                                        <i class="ph ph-check text-[10px] {{ !empty($item['is_completed']) ? '' : 'hidden' }}"></i>
                                    </span>
                                    <span class="flex-1 truncate text-sm {{ !empty($item['is_completed']) ? 'text-muted-foreground line-through' : 'text-foreground' }}">{{ $item['label'] }}</span>
                                </button>

                                <button type="button"
                                    wire:click="removeChecklistItem({{ $index }})"
                                    aria-label="Remover {{ $item['label'] }} do checklist"
                                    class="mr-2.5 text-muted-foreground opacity-0 transition-colors hover:text-destructive focus:opacity-100 group-hover:opacity-100">
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
                            class="h-9 flex-1 rounded-md border border-input bg-muted/60 px-3 text-sm shadow-sm outline-none placeholder:text-muted-foreground focus:bg-background focus:ring-2 focus:ring-ring">
                        <button type="button" wire:click="addChecklistItem"
                            aria-label="Adicionar item ao checklist"
                            class="flex h-9 w-9 items-center justify-center rounded-md bg-primary text-primary-foreground shadow transition-colors hover:bg-primary/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                            <i class="ph ph-plus font-bold"></i>
                        </button>
                    </div>
                    @error('form.newChecklistItem') <span class="mt-1 block text-xs text-red-500">{{ $message }}</span> @enderror
                </aside>
                </div>

                <section class="flex h-full flex-col overflow-hidden p-4 sm:p-6" x-show="modalView === 'attachments'">
                    <div class="mb-4 flex shrink-0 items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold tracking-tight text-foreground">Arquivos</h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">Anexe documentos e imagens relacionados a esta tarefa.</p>
                        </div>
                        <span class="text-[11px] font-medium text-muted-foreground">{{ count($form->attachments) }} {{ count($form->attachments) === 1 ? 'arquivo' : 'arquivos' }}</span>
                    </div>

                    @if ($form->taskId)
                        <div class="mb-4 rounded-md border border-dashed border-border bg-muted/30 p-3">
                            <label for="task-attachments" class="block text-xs font-medium text-foreground">Selecionar arquivos</label>
                            <p class="mt-1 text-[11px] text-muted-foreground">Até 5 arquivos por envio, com no máximo 10 MB cada. PDF, documentos, planilhas, imagens ou TXT.</p>
                            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-center">
                                <input id="task-attachments" type="file" wire:model="pendingAttachments" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.webp,.txt" class="block w-full text-xs text-muted-foreground file:mr-3 file:h-8 file:rounded-md file:border-0 file:bg-background file:px-3 file:text-xs file:font-medium file:text-foreground hover:file:bg-accent">
                                <button type="button" wire:click="uploadAttachments" wire:loading.attr="disabled" wire:target="pendingAttachments,uploadAttachments" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md bg-primary px-3 text-xs font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-60"><i class="ph ph-upload-simple text-sm" aria-hidden="true"></i>Enviar</button>
                            </div>
                            <div wire:loading wire:target="pendingAttachments,uploadAttachments" class="mt-2 text-xs text-muted-foreground">Preparando arquivos…</div>
                            @error('pendingAttachments') <p class="mt-2 text-xs text-destructive">{{ $message }}</p> @enderror
                            @error('pendingAttachments.*') <p class="mt-2 text-xs text-destructive">{{ $message }}</p> @enderror
                        </div>
                    @else
                        <div class="mb-4 rounded-md border border-dashed border-border bg-muted/30 p-4 text-xs text-muted-foreground">Crie a tarefa antes de adicionar arquivos.</div>
                    @endif

                    <ul class="min-h-0 flex-1 space-y-2 overflow-y-auto custom-scrollbar">
                        @forelse ($form->attachments as $attachment)
                            <li wire:key="task-attachment-{{ $attachment['id'] }}" class="flex items-center gap-3 rounded-md border border-border bg-card p-3">
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"><i class="ph ph-file text-lg" aria-hidden="true"></i></span>
                                <a href="{{ route('teams.tasks.attachments.download', ['team' => $team, 'task' => $form->taskId, 'attachment' => $attachment['id']]) }}" class="min-w-0 flex-1 truncate text-xs font-medium text-foreground hover:underline">{{ $attachment['name'] }}</a>
                                <span class="hidden shrink-0 text-[10px] text-muted-foreground sm:inline">{{ number_format($attachment['size'] / 1024, 1, ',', '.') }} KB</span>
                                <button type="button" wire:click="removeAttachment({{ $attachment['id'] }})" wire:confirm="Remover este arquivo?" aria-label="Remover {{ $attachment['name'] }}" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive"><i class="ph ph-trash text-base" aria-hidden="true"></i></button>
                            </li>
                        @empty
                            <li class="flex flex-col items-center justify-center rounded-md border border-dashed border-border bg-muted/20 px-6 py-10 text-center"><i class="ph ph-files mb-2 text-3xl text-muted-foreground" aria-hidden="true"></i><p class="text-xs font-medium text-foreground">Nenhum arquivo anexado.</p></li>
                        @endforelse
                    </ul>
                </section>

                <div class="flex h-full flex-col overflow-hidden p-4 sm:p-6" x-show="modalView === 'history'">
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
                                                    @php
                                                        $formatHistoryValue = static function (mixed $value) use ($key, $users): string {
                                                            if (! is_array($value)) {
                                                                return (string) $value;
                                                            }

                                                            if ($key === 'assignee_ids') {
                                                                return collect($value)
                                                                    ->map(fn ($id) => $users->firstWhere('id', (int) $id)?->name)
                                                                    ->filter()
                                                                    ->join(', ') ?: 'Sem responsável';
                                                            }

                                                            return collect($value)->map(fn ($entry) => is_scalar($entry) ? (string) $entry : '')
                                                                ->filter()->join(', ');
                                                        };
                                                        $historyFrom = $formatHistoryValue($diff['from'] ?? '');
                                                        $historyTo = $formatHistoryValue($diff['to'] ?? '');
                                                    @endphp
                                                    <div>
                                                        <span class="mb-1 block text-[10px] font-medium text-muted-foreground">Alteração em {{ config('app.locale') === 'pt_BR' ? trans("fields.{$key}") : $key }}</span>
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                            <div class="flex-1 px-2 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded border border-red-100 dark:border-red-900/30 line-through decoration-red-300 dark:decoration-red-800 break-all">
                                                                {{ $historyFrom }}
                                                            </div>
                                                            <i class="ph ph-arrow-right hidden shrink-0 text-muted-foreground sm:block"></i>
                                                            <i class="ph ph-arrow-down shrink-0 self-center text-muted-foreground sm:hidden"></i>
                                                            <div class="flex-1 px-2 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded border border-emerald-100 dark:border-emerald-900/30 break-all">
                                                                {{ $historyTo }}
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
        .period-chart-scroller { -ms-overflow-style: none; scrollbar-width: none; }
        .period-chart-scroller::-webkit-scrollbar { display: none; width: 0; height: 0; }
        [x-cloak] { display: none !important; }
    </style>
</div>
