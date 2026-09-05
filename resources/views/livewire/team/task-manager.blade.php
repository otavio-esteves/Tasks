<div class="task-reference-font h-screen flex flex-col overflow-hidden bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 selection:bg-slate-300 dark:selection:bg-slate-700"
     wire:ignore.self
     x-data="{ 
        sidebarOpen: false,
        adminOpen: {{ request()->routeIs('admin.*') ? 'true' : 'false' }},
        filterOpen: false,
        modalOpen: false, 
        settingsOpen: false,
        settingsTab: 'profile',
        modalView: 'details', 
        mode: 'create' 
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

    {{-- TODO técnico: mover Phosphor Icons para asset local via Vite quando o pacote for incorporado ao build. --}}
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

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
        class="fixed top-0 left-0 h-full w-80 bg-slate-50 dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800 z-50 transform transition-transform duration-300 ease-out flex flex-col shadow-2xl"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        
        <div class="p-2.5 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0">
            <div class="flex items-center gap-1">
                <div class="text-black p-1.5 rounded-md shadow-slate-900/20 dark:text-white">
                    <x-application-logo class="h-6 w-6" />
                </div>
                <span class="font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ config('app.name') }}
                </span>
            </div>
            <button x-on:click="sidebarOpen = false" class="text-slate-400 transition-colors p-2">
                <i class="ph ph-x text-lg"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-3 space-y-6 custom-scrollbar">
            <div>
                <h3 class="px-3 text-[10px] font-bold uppercase text-slate-500 mb-2 tracking-wider">Geral</h3>
                <nav class="space-y-0.5">
                    <a href="{{ route('teams.tasks', $team) }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('teams.tasks') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <i class="ph-duotone ph-clipboard-text text-lg"></i>
                        Tarefas
                    </a>

                    <a href="{{ route('dashboard') }}"
                        wire:navigate
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('dashboard') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                        <i class="ph-duotone ph-chart-pie-slice text-lg"></i>
                        Dashboard
                    </a>

                    <a href="#"
                        class="flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white transition-colors">
                        <i class="ph-duotone ph-scroll text-lg"></i>
                        Relatórios
                    </a>
                </nav>
            </div>

            @if (auth()->user()->isAdmin())
                <div>
                    <h3 class="px-3 text-[10px] font-bold uppercase text-slate-500 mb-2 tracking-wider">Administração</h3>
                    <nav class="space-y-0.5">
                        <button type="button"
                            x-on:click="adminOpen = !adminOpen"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-md text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white transition-colors group">
                            <div class="flex items-center gap-3">
                                <i class="ph-duotone ph-bank text-lg"></i>
                                <span>Equipes</span>
                            </div>
                            <i class="ph ph-caret-down text-slate-400 transition-transform duration-200" :class="adminOpen ? 'rotate-180' : ''"></i>
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
                                class="flex items-center gap-3 px-3 pl-10 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('admin.teams') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                                <i class="ph-duotone ph-buildings text-lg opacity-70"></i>
                                Listagem
                            </a>

                            <a href="{{ route('admin.categories') }}"
                                wire:navigate
                                class="flex items-center gap-3 px-3 pl-10 py-2 text-sm font-medium rounded-md transition-colors {{ request()->routeIs('admin.categories') ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-200/50 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white' }}">
                                <i class="ph-duotone ph-tag text-lg opacity-70"></i>
                                Categorias
                            </a>
                        </div>
                    </nav>
                </div>
            @endif
        </div>

        <div class="py-2 px-3 border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 shrink-0">
            <button x-on:click="settingsOpen = true; sidebarOpen = false"
                class="w-full flex items-center gap-3 hover:bg-slate-100 dark:hover:bg-slate-800 py-2 px-2.5 rounded-lg transition-colors group text-left">
                <div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-slate-500 font-bold text-xs">
                    {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->take(2)->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))->implode('') }}
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-xs font-bold text-slate-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="text-[10px] text-slate-500">Configurações</span>
                </div>
                <i class="ph ph-gear ml-auto mt-0.5 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300"></i>
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
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm">
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-4xl rounded-xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all flex h-[600px] max-h-[90vh]"
             x-show="settingsOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-on:click.away="settingsOpen = false">
            
            <aside class="w-64 border-r border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 p-6 flex flex-col gap-6 shrink-0 hidden md:flex">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">Configurações</h2>
                </div>

                <nav class="space-y-1">
                    <button x-on:click="settingsTab = 'profile'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-semibold rounded-lg transition-all"
                        :class="settingsTab === 'profile' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'">
                        <i class="ph-duotone ph-user text-lg"></i>
                        Perfil
                    </button>
                    <button x-on:click="settingsTab = 'security'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors"
                        :class="settingsTab === 'security' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'">
                        <i class="ph-duotone ph-shield text-lg"></i>
                        Segurança
                    </button>
                    <button x-on:click="settingsTab = 'theme'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors"
                        :class="settingsTab === 'theme' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'">
                        <i class="ph-duotone ph-paint-brush text-lg"></i>
                        Tema
                    </button>
                    <button x-on:click="settingsTab = 'about'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg transition-colors"
                        :class="settingsTab === 'about' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm border border-slate-200 dark:border-slate-700' : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-white'">
                        <i class="ph-duotone ph-info text-lg"></i>
                        Sobre
                    </button>
                    <div class="pt-4 mt-4 border-t border-slate-200 dark:border-slate-800">
                        <button x-on:click="$wire.logout()"
                            class="w-full flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-lg text-red-600 hover:bg-red-50 dark:hover:bg-red-900/10 transition-colors">
                            <i class="ph-duotone ph-sign-out text-lg"></i>
                            Encerrar Sessão
                        </button>
                    </div>
                </nav>
            </aside>

            <main class="flex-1 flex flex-col min-w-0 bg-white dark:bg-slate-900">
                <div class="flex items-center justify-end p-4 shrink-0">
                    <button type="button" x-on:click="settingsOpen = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors duration-200 p-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-8 md:px-12 pb-12 custom-scrollbar">
                    <div class="max-w-3xl mx-auto">
                        
                        <div x-show="settingsTab === 'profile'" class="flex flex-col-reverse md:flex-row gap-8 md:gap-12">
                            <div class="flex-1 space-y-6">
                                <livewire:profile.update-profile-information-form />
                            </div>
                            <div class="md:w-48 flex flex-col items-center">
                                <label class="block w-full text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-4 text-center">Iniciais</label>
                                <div class="w-32 h-32 md:w-40 md:h-40 rounded-full bg-slate-200 dark:bg-slate-800 border-4 border-white dark:border-slate-900 shadow-xl flex items-center justify-center">
                                    <span class="text-4xl font-bold text-slate-400 dark:text-slate-600">
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
                                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-1">Aparência e Tema</h3>
                                <p class="text-sm text-slate-500 dark:text-slate-400">Personalize como a interface é exibida no seu dispositivo.</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button x-on:click="localStorage.theme = 'light'; document.documentElement.classList.remove('dark')"
                                    class="group relative flex flex-col gap-3 p-4 rounded-xl border-2 text-left transition-all duration-200 bg-white"
                                    :class="localStorage.theme === 'light' ? 'ring-2 ring-slate-900 dark:ring-white border-transparent' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                    <div class="w-full aspect-[4/3] rounded-lg bg-slate-100 border border-slate-200 overflow-hidden relative">
                                        <div class="absolute top-2 left-2 right-2 h-2 bg-white rounded-full shadow-sm"></div>
                                        <div class="absolute top-6 left-2 bottom-2 w-6 bg-white rounded shadow-sm"></div>
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-900 text-sm mb-0.5">Modo Claro</span>
                                        <span class="block text-xs text-slate-500">Visual limpo para ambientes iluminados.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.theme = 'dark'; document.documentElement.classList.add('dark')"
                                    class="group relative flex flex-col gap-3 p-4 rounded-xl border-2 text-left transition-all duration-200 bg-slate-900"
                                    :class="localStorage.theme === 'dark' ? 'ring-2 ring-slate-900 dark:ring-white border-transparent' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                    <div class="w-full aspect-[4/3] rounded-lg bg-slate-950 border border-slate-800 overflow-hidden relative">
                                        <div class="absolute top-2 left-2 right-2 h-2 bg-slate-800 rounded-full"></div>
                                        <div class="absolute top-6 left-2 bottom-2 w-6 bg-slate-800 rounded"></div>
                                    </div>
                                    <div>
                                        <span class="block font-bold text-white text-sm mb-0.5">Modo Escuro</span>
                                        <span class="block text-xs text-slate-400">Confortável para ambientes com pouca luz.</span>
                                    </div>
                                </button>

                                <button x-on:click="localStorage.removeItem('theme'); if(window.matchMedia('(prefers-color-scheme: dark)').matches) { document.documentElement.classList.add('dark'); } else { document.documentElement.classList.remove('dark'); }"
                                    class="group relative flex flex-col gap-3 p-4 rounded-xl border-2 text-left transition-all duration-200 bg-slate-50 dark:bg-slate-800"
                                    :class="!('theme' in localStorage) ? 'ring-2 ring-slate-900 dark:ring-white border-transparent' : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600'">
                                    <div class="w-full aspect-[4/3] rounded-lg bg-slate-100 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 overflow-hidden relative flex">
                                        <div class="w-1/2 h-full bg-slate-200 dark:bg-slate-800"></div>
                                        <div class="w-1/2 h-full bg-slate-800 dark:bg-slate-900"></div>
                                        <div class="absolute top-2 left-2 right-2 h-2 bg-slate-400/50 rounded-full shadow-sm"></div>
                                        <div class="absolute top-6 left-2 bottom-2 w-6 bg-slate-400/50 rounded shadow-sm"></div>
                                    </div>
                                    <div>
                                        <span class="block font-bold text-slate-900 dark:text-white text-sm mb-0.5">Sistema</span>
                                        <span class="block text-xs text-slate-500 dark:text-slate-400">Acompanha o tema do sistema.</span>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div x-show="settingsTab === 'about'" class="space-y-6">
                            <div class="bg-slate-50 dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center sm:items-start gap-5">
                                <div class="w-16 h-16 rounded-xl bg-white dark:bg-slate-900 flex items-center justify-center shadow-sm text-slate-900 dark:text-white shrink-0">
                                    <x-application-logo class="h-8 w-8" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-slate-900 dark:text-white">{{ config('app.name') }}</h4>
                                    <span class="text-xs font-semibold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">Alpha 1.0</span>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mt-2">
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

    <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-6 py-4 flex flex-col md:flex-row items-center justify-between z-20 shrink-0 gap-3 transition-colors duration-200">
        <div class="flex items-center gap-2 w-full md:w-auto">
            <button x-on:click="sidebarOpen = true" class="h-8 w-8 flex items-center justify-center rounded-md border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition-all duration-200 group shrink-0">
                <i class="ph ph-list text-lg"></i>
            </button>
            <div class="flex items-center gap-3">
                <span class="font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ $team->name }} <span class="text-slate-400 font-normal">/ {{ auth()->user()->name }}</span>
                </span>
            </div>
        </div>

        <div class="h-8 flex items-center bg-slate-50 dark:bg-slate-800 rounded-md px-1 border border-slate-200 dark:border-slate-700 shadow-inner overflow-x-auto max-w-full transition-colors duration-200">
            <button type="button" wire:click="applyQuickFilter('total')"
                class="flex items-center px-2.5 h-full gap-1.5 border-r border-slate-200 dark:border-slate-700 whitespace-nowrap transition-colors rounded-md {{ $quickFilter === '' ? 'bg-white dark:bg-slate-700/70 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-200/50 dark:hover:bg-slate-700/50' }}">
                <i class="ph ph-files text-slate-400 text-sm"></i>
                <span class="text-xs font-medium">Total <span class="font-bold">{{ $summary['total'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('urgent')"
                class="flex items-center px-2.5 h-full gap-1.5 border-r border-slate-200 dark:border-slate-700 whitespace-nowrap transition-colors rounded-md {{ $quickFilter === 'urgent' ? 'bg-red-50 dark:bg-red-950/40 text-red-600 dark:text-red-300' : 'text-red-500 dark:text-red-400 hover:bg-red-100/50 dark:hover:bg-red-950/20' }}">
                <div class="w-1.5 h-1.5 rounded-full bg-red-500 shadow-[0_0_5px_rgba(239,68,68,0.5)]"></div>
                <span class="text-xs font-medium">Urgentes <span class="font-bold">{{ $summary['urgent'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('overdue')"
                class="flex items-center px-2.5 h-full gap-1.5 border-r border-slate-200 dark:border-slate-700 whitespace-nowrap transition-colors rounded-md {{ $quickFilter === 'overdue' ? 'bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-300' : 'text-amber-500 dark:text-amber-400 hover:bg-amber-100/50 dark:hover:bg-amber-950/20' }}">
                <div class="w-1.5 h-1.5 rounded-full bg-amber-500 shadow-[0_0_5px_rgba(245,158,11,0.5)]"></div>
                <span class="text-xs font-medium">Vencidas <span class="font-bold">{{ $summary['overdue'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('in_progress')"
                class="flex items-center px-2.5 h-full gap-1.5 border-r border-slate-200 dark:border-slate-700 whitespace-nowrap transition-colors rounded-md {{ $quickFilter === 'in_progress' ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-300' : 'text-blue-500 dark:text-blue-400 hover:bg-blue-100/50 dark:hover:bg-blue-950/20' }}">
                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_5px_rgba(59,130,246,0.5)]"></div>
                <span class="text-xs font-medium">Em And. <span class="font-bold">{{ $summary['in_progress'] }}</span></span>
            </button>
            <button type="button" wire:click="applyQuickFilter('completed')"
                class="flex items-center px-2.5 h-full gap-1.5 whitespace-nowrap transition-colors rounded-md {{ $quickFilter === 'completed' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-300' : 'text-emerald-500 dark:text-emerald-400 hover:bg-emerald-100/50 dark:hover:bg-emerald-950/20' }}">
                <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_5px_rgba(16,185,129,0.5)]"></div>
                <span class="text-xs font-medium">Concluídas <span class="font-bold">{{ $summary['completed'] }}</span></span>
            </button>
        </div>

        <div class="flex items-center gap-2 w-full md:w-auto">
            <div class="relative">
                <button type="button" x-on:click="filterOpen = !filterOpen"
                    class="h-8 w-8 flex items-center justify-center text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700 rounded-md bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                    :class="({{ $filterCategoryId !== '' ? 'true' : 'false' }} || {{ $filterStatus !== '' ? 'true' : 'false' }} || {{ $filterUrgent !== '' ? 'true' : 'false' }} || {{ $quickFilter !== '' ? 'true' : 'false' }}) ? 'ring-2 ring-slate-400 dark:ring-slate-600 text-slate-900 dark:text-white' : ''">
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
                    class="absolute right-0 top-10 w-72 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-2xl p-4 z-30">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Filtros</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Refine as tarefas exibidas no painel.</p>
                        </div>
                        <button type="button" wire:click="clearFilters" x-on:click="filterOpen = false"
                            class="text-xs font-semibold text-slate-400 hover:text-slate-900 dark:text-slate-500 dark:hover:text-white transition-colors">
                            Limpar
                        </button>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Categoria</label>
                            <select wire:model.live="filterCategoryId"
                                class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-pointer appearance-none text-slate-900 dark:text-slate-100">
                                <option value="">Todas</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Status</label>
                            <select wire:model.live="filterStatus"
                                class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-pointer appearance-none text-slate-900 dark:text-slate-100">
                                <option value="">Todos</option>
                                @foreach($statusOptions as $statusOption)
                                    <option value="{{ $statusOption->value }}">{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400 mb-1">Urgência</label>
                            <select wire:model.live="filterUrgent"
                                class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-pointer appearance-none text-slate-900 dark:text-slate-100">
                                <option value="">Todas</option>
                                <option value="1">Somente urgentes</option>
                                <option value="0">Somente não urgentes</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="relative flex-1 md:w-56 h-8">
                <i class="ph ph-magnifying-glass absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-sm"></i>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar..." 
                    class="w-full h-full pl-8 pr-3 text-xs border border-slate-300 dark:border-slate-700 rounded-md bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 dark:focus:ring-slate-600 focus:outline-none transition-shadow shadow-sm placeholder-slate-400 dark:placeholder-slate-500">
            </div>
            <button x-on:click="$wire.resetForm(); modalOpen = true; mode = 'create'; modalView = 'details'" 
                class="h-8 flex items-center justify-center gap-1.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 px-3 rounded-md font-bold text-xs hover:bg-slate-800 dark:hover:bg-slate-200 transition-colors shadow-sm whitespace-nowrap">
                <span>Nova Tarefa +</span>
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
         class="fixed bottom-6 right-6 z-[60] flex items-center gap-3 px-4 py-3 bg-slate-900 border border-slate-800 text-white rounded-xl shadow-2xl">
        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-500">
            <i class="ph-bold ph-check text-xs"></i>
        </div>
        <span class="text-xs font-bold tracking-tight" x-text="message"></span>
    </div>

    @if (session()->has('error'))
        <div class="m-6 p-4 bg-red-500 text-white rounded-xl shadow-lg flex items-center gap-3">
            <i class="ph-fill ph-warning-circle text-2xl"></i>
            <span class="font-bold text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if ($filterCategoryId !== '' || $filterStatus !== '' || $filterUrgent !== '' || $quickFilter !== '')
        <div class="px-6 pt-4 flex flex-wrap items-center gap-2">
            @if ($quickFilter !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <i class="ph ph-faders text-sm"></i>
                    {{ match($quickFilter) {
                        'urgent' => 'Urgentes',
                        'overdue' => 'Vencidas',
                        'in_progress' => 'Em andamento',
                        'completed' => 'Concluidas',
                        default => 'Filtro rapido',
                    } }}
                </span>
            @endif

            @if ($filterCategoryId !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <i class="ph ph-tag text-sm"></i>
                    {{ optional($categories->firstWhere('id', (int) $filterCategoryId))->name ?? 'Categoria' }}
                </span>
            @endif

            @if ($filterStatus !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <i class="ph ph-clock text-sm"></i>
                    {{ collect($statusOptions)->firstWhere('value', $filterStatus)?->label() ?? 'Status' }}
                </span>
            @endif

            @if ($filterUrgent !== '')
                <span class="inline-flex items-center gap-1 rounded-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 px-3 py-1 text-xs font-semibold text-slate-600 dark:text-slate-300">
                    <i class="ph ph-warning-circle text-sm"></i>
                    {{ $filterUrgent === '1' ? 'Urgentes' : 'Nao urgentes' }}
                </span>
            @endif

            <button type="button" wire:click="clearFilters"
                class="inline-flex items-center gap-1 rounded-full bg-slate-900 dark:bg-white px-3 py-1 text-xs font-semibold text-white dark:text-slate-900">
                Limpar filtros
            </button>
        </div>
    @endif

    <main class="flex-1 overflow-y-auto p-6 pb-32 bg-slate-100 dark:bg-slate-950 transition-colors duration-200 custom-scrollbar">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            @forelse($tasks as $task)
                @php
                    $status = $task->status;
                    $isCompleted = $status === \App\Domain\Tasks\TaskStatus::Completed;
                    $isUrgent = (bool) $task->is_urgent;

                    $topBorderClass = match ($status) {
                        \App\Domain\Tasks\TaskStatus::Completed => 'bg-emerald-500',
                        default => ($isUrgent ? 'bg-red-500' : 'bg-blue-500'),
                    };

                    $statusBadgeClass = match ($status) {
                        \App\Domain\Tasks\TaskStatus::Completed => 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border border-emerald-100 dark:border-emerald-900/50',
                        \App\Domain\Tasks\TaskStatus::InProgress => 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50',
                        default => 'bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 border border-amber-100 dark:border-amber-900/50',
                    };

                    $statusIcon = match ($status) {
                        \App\Domain\Tasks\TaskStatus::Completed => 'ph-check-circle',
                        \App\Domain\Tasks\TaskStatus::InProgress => 'ph-spinner animate-spin-slow',
                        default => 'ph-clock',
                    };

                    $showUrgentBadge = $isUrgent && !$isCompleted;
                    $urgentBadgeClass = 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 border border-red-100 dark:border-red-900/50';
                @endphp
                <div x-on:click="$wire.edit({{ $task->id }}, 'details'); modalView = 'details'; mode = 'edit'; modalOpen = true"
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-slate-200 dark:border-slate-800 relative overflow-hidden group hover:shadow-md transition-all duration-200 cursor-pointer">
                    
                    <div class="h-1.5 w-full absolute top-0 {{ $topBorderClass }}"></div>
                    
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-lg font-bold text-slate-800 dark:text-white">{{ $task->code }}</span>
                            <span class="bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold px-2 py-1 rounded border border-slate-200 dark:border-slate-700 uppercase">
                                {{ substr($task->category->name ?? 'S/CAT', 0, 7) }}.
                            </span>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-slate-700 dark:text-slate-200 mb-1 line-clamp-1">{{ $task->title }}</h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1">{{ $task->location ?: 'Local não informado' }}</p>
                            <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                                {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '--/--/----' }}
                            </p>
                        </div>

                        <div class="flex justify-between items-center mt-4">
                            <button type="button" 
                                x-on:click.stop="$wire.edit({{ $task->id }}, 'checklist'); modalView = 'checklist'; mode = 'edit'; modalOpen = true"
                                class="w-7 h-7 flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                <i class="ph ph-list-checks text-lg"></i>
                            </button>

                            <div class="flex gap-1.5">
                                @if($showUrgentBadge)
                                    <span class="text-[10px] font-bold px-2.5 py-1.5 rounded-full flex items-center gap-1.5 transition-colors {{ $urgentBadgeClass }}">
                                        <i class="ph ph-warning-circle"></i>
                                        Urgente
                                    </span>
                                @endif

                                <span class="text-[10px] font-bold px-2.5 py-1.5 rounded-full flex items-center gap-1.5 transition-colors {{ $statusBadgeClass }}">
                                    <i class="ph {{ $statusIcon }}"></i>
                                    {{ $status->label() }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
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

    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm transition-all duration-300"
         x-show="modalOpen" 
         x-transition:enter="ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         x-cloak>
        
        <div class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden transform transition-all flex flex-col max-h-[90vh] h-[90vh] md:h-[621px]"
         x-show="modalOpen"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-on:click.away="if (!$wire.showCategoryModal) $wire.closeModal()">            
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 dark:border-slate-800 shrink-0">
                <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white" 
                        x-text="mode === 'create' ? 'Nova Tarefa' : 'Editar Tarefa - ' + '{{ $form->taskId }}'"></h2>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="modalView = 'details'"
                        class="transition-colors"
                        :class="modalView === 'details' ? 'text-slate-900 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                        title="Detalhes da Tarefa">
                        <i class="ph ph-info text-2xl"></i>
                    </button>
                    <button type="button" x-on:click="modalView = 'checklist'"
                        class="transition-colors"
                        :class="modalView === 'checklist' ? 'text-slate-900 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                        title="Checklist">
                        <i class="ph ph-list-checks text-2xl"></i>
                    </button>
                    <button type="button" x-on:click="modalView = 'history'"
                        class="transition-colors"
                        :class="modalView === 'history' ? 'text-slate-900 dark:text-white' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-200'"
                        title="Histórico de Alterações">
                        <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                    </button>
                    <div class="h-4 w-px bg-slate-200 dark:bg-slate-700 mx-1"></div>
                    <button type="button" x-on:click="$wire.closeModal()" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <i class="ph ph-x text-2xl"></i>
                    </button>
                </div>
            </div>

            <form wire:submit="save" class="flex flex-col flex-1 overflow-hidden">
                <div class="space-y-4 p-6 overflow-y-auto custom-scrollbar flex-1" x-show="modalView === 'details'">
                    
                    @if ($errors->any())
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 border border-red-100 dark:border-red-800 text-red-600 dark:text-red-400 rounded-lg">
                            <ul class="list-disc list-inside text-xs font-bold uppercase">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Título da Tarefa</label>
                        <input wire:model="form.title" type="text" placeholder="Ex: Reparo de calçada"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-slate-400 outline-none transition-all text-slate-900 dark:text-slate-100 placeholder-slate-400">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Localização (Endereço/Ponto)</label>
                        <input wire:model="form.location" type="text" placeholder="Nome da rua, bairro ou praça"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-slate-400 outline-none text-slate-900 dark:text-slate-100 placeholder-slate-400">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Categoria</label>
                            <div class="space-y-2">
                                <select wire:model.live="form.categoryId" class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm outline-none cursor-pointer appearance-none text-slate-900 dark:text-slate-100">
                                    <option value="">Selecione...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                    <option value="new" class="font-bold text-slate-900 dark:text-white border-t">+ Criar nova categoria...</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Prazo Conclusão</label>
                            <input wire:model="form.dueDate" type="date" class="w-full h-10 px-3 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm text-slate-900 dark:text-slate-100 [color-scheme:light] dark:[color-scheme:dark]">
                        </div>
                    </div>

                    <label class="relative block cursor-pointer group touch-manipulation select-none">
                        <input wire:model="form.isUrgent" type="checkbox" class="sr-only">
                        <div class="flex items-center justify-between p-4 rounded-2xl border transition-all duration-75 bg-slate-50 dark:bg-slate-950/40 border-slate-200 dark:border-slate-800"
                             :class="$wire.form.isUrgent ? 'bg-red-50/50 dark:bg-red-950/20 border-red-200 dark:border-red-900' : ''">
                            <div class="flex items-center gap-4">
                                <div class="flex items-center justify-center w-10 h-10 rounded-xl transition-colors"
                                     :class="$wire.form.isUrgent ? 'bg-red-500 text-white' : 'bg-red-100 dark:bg-red-900/30 text-red-600'">
                                    <i class="ph-fill ph-warning text-xl"></i>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900 dark:text-white leading-none mb-1">Prioridade Urgente</span>
                                    <span class="text-xs text-slate-500">Destaque visual imediato no painel</span>
                                </div>
                            </div>
                            <div class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                 :class="$wire.form.isUrgent ? 'bg-red-500 border-red-500' : 'border-slate-200 dark:border-slate-700'">
                                <i class="ph ph-check text-white text-[10px] font-bold" x-show="$wire.form.isUrgent"></i>
                            </div>
                        </div>
                    </label>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">Observação</label>
                        <textarea wire:model="form.observation" rows="3" placeholder="Informações extras..."
                            class="block w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-slate-400 outline-none resize-none text-slate-900 dark:text-slate-100 placeholder-slate-400"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-2">
                            Status da Tarefa
                        </label>

                        <div class="inline-flex w-full rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/60 p-1" role="group">
                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Pending->value }}')"
                                class="flex-1 h-8 rounded-md px-2 text-[10px] font-bold transition-all flex items-center justify-center gap-1.5 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Pending->value ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Pendente
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::InProgress->value }}')"
                                class="flex-1 h-8 rounded-md px-2 text-[10px] font-bold transition-all flex items-center justify-center gap-1.5 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::InProgress->value ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                Em andamento
                            </button>

                            <button type="button"
                                wire:click="selectStatus('{{ \App\Domain\Tasks\TaskStatus::Completed->value }}')"
                                class="flex-1 h-8 rounded-md px-2 text-[10px] font-bold transition-all flex items-center justify-center gap-1.5 {{ $form->currentStatus === \App\Domain\Tasks\TaskStatus::Completed->value ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Concluído
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col h-full overflow-hidden p-6" x-show="modalView === 'checklist'">
                    <div class="flex items-center justify-between gap-3 mb-2 shrink-0">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Checklist de Execução</label>
                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
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
                                <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden border border-slate-200/50 dark:border-slate-700/50 p-0.5">
                                    <div class="h-full bg-slate-900 dark:bg-white rounded-full transition-all duration-500 ease-out" style="width: {{ $percentage }}%"></div>
                                </div>
                                <span class="text-[10px] font-black text-slate-900 dark:text-white bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full shrink-0">{{ $percentage }}%</span>
                            </div>
                        </div>
                    @endif

                    <ul class="space-y-2 flex-1 overflow-y-auto custom-scrollbar mb-4 min-h-0">
                        @forelse ($form->checklistItems as $index => $item)
                            <li wire:key="checklist-item-{{ $index }}"
                                class="flex items-center gap-3 p-2 bg-slate-50 dark:bg-slate-800/50 rounded-lg group border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-all">
                                <button type="button"
                                    wire:click="toggleChecklistItem({{ $index }})"
                                    class="flex-shrink-0 w-5 h-5 rounded border flex items-center justify-center transition-colors {{ !empty($item['is_completed']) ? 'bg-slate-900 border-slate-900 dark:bg-slate-100 dark:border-slate-100' : 'bg-white border-slate-300 dark:bg-slate-800 dark:border-slate-600' }}">
                                    <i class="ph ph-check text-xs {{ !empty($item['is_completed']) ? 'text-white dark:text-slate-900' : 'hidden' }}"></i>
                                </button>

                                <span class="text-sm flex-1 truncate {{ !empty($item['is_completed']) ? 'line-through text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200' }}">
                                    {{ $item['label'] }}
                                </span>

                                <button type="button"
                                    wire:click="removeChecklistItem({{ $index }})"
                                    class="text-slate-400 hover:text-red-500 transition-colors opacity-0 group-hover:opacity-100 focus:opacity-100">
                                    <i class="ph ph-trash text-lg"></i>
                                </button>
                            </li>
                        @empty
                            <li class="flex flex-col items-center justify-center text-center px-6 py-10 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-950/20">
                                <i class="ph ph-list-plus text-3xl text-slate-300 mb-2"></i>
                                <p class="text-xs text-slate-400 font-medium">Nenhum item adicionado.</p>
                                <p class="text-xs text-slate-400">Cadastre as etapas da execução para acompanhar o andamento.</p>
                            </li>
                        @endforelse
                    </ul>

                    <div class="flex gap-2 shrink-0 pt-2 border-t border-transparent">
                        <input wire:model.live="form.newChecklistItem" type="text" placeholder="Adicionar etapa..."
                            wire:keydown.enter.prevent="addChecklistItem"
                            class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-sm focus:ring-2 focus:ring-slate-400 outline-none transition-all text-slate-900 dark:text-slate-100">
                        <button type="button" wire:click="addChecklistItem"
                            class="px-3 py-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg transition-colors">
                            <i class="ph ph-plus font-bold"></i>
                        </button>
                    </div>
                </div>

                <div class="flex flex-col h-full overflow-hidden p-6" x-show="modalView === 'history'">
                    <div class="flex items-center justify-between gap-3 mb-2 shrink-0">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Histórico de Alterações</label>
                        <span class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                            {{ count($form->historyItems) }} {{ count($form->historyItems) === 1 ? 'registro' : 'registros' }}
                        </span>
                    </div>

                    <ul class="space-y-3 flex-1 overflow-y-auto custom-scrollbar mb-4 min-h-0 pr-2">
                        @forelse ($form->historyItems as $index => $item)
                            <li wire:key="history-item-{{ $index }}" x-data="{ expanded: false }"
                                class="flex flex-col gap-2 p-4 bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-xl">
                                
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-200 break-words leading-relaxed">
                                            {!! nl2br(e($item['description'])) !!}
                                        </p>
                                    </div>
                                    @if(!empty($item['metadata']))
                                        <button type="button" @click="expanded = !expanded" class="shrink-0 w-6 h-6 flex items-center justify-center rounded-md text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                                            <i class="ph text-sm transition-transform" :class="expanded ? 'ph-caret-up' : 'ph-caret-down'"></i>
                                        </button>
                                    @endif
                                </div>

                                <div class="flex items-center gap-3 mt-1 pt-3 border-t border-slate-200 dark:border-slate-700/50">
                                    <span class="text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase flex items-center gap-1.5">
                                        <div class="w-4 h-4 rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center">
                                            <i class="ph ph-user text-[10px] text-slate-500 dark:text-slate-400"></i>
                                        </div>
                                        {{ $item['user_name'] ?? 'Sistema' }}
                                    </span>
                                    <div class="w-1 h-1 rounded-full bg-slate-300 dark:bg-slate-600"></div>
                                    <span class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase flex items-center gap-1">
                                        <i class="ph ph-clock"></i> {{ $item['created_at'] }}
                                    </span>
                                </div>

                                @if(!empty($item['metadata']))
                                    <div x-show="expanded" x-collapse class="mt-2 text-xs">
                                        <div class="bg-slate-100 dark:bg-slate-900/50 rounded-lg p-3 space-y-3 border border-slate-200 dark:border-slate-700/50">
                                            @foreach($item['metadata'] as $key => $diff)
                                                @if($key !== 'checklist')
                                                    <div>
                                                        <span class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Alteração em {{ config('app.locale') === 'pt_BR' ? trans("fields.{$key}") : $key }}</span>
                                                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-2">
                                                            <div class="flex-1 px-2 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded border border-red-100 dark:border-red-900/30 line-through decoration-red-300 dark:decoration-red-800 break-all">
                                                                {{ $diff['from'] }}
                                                            </div>
                                                            <i class="ph ph-arrow-right text-slate-400 hidden sm:block shrink-0"></i>
                                                            <i class="ph ph-arrow-down text-slate-400 sm:hidden shrink-0 self-center"></i>
                                                            <div class="flex-1 px-2 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded border border-emerald-100 dark:border-emerald-900/30 break-all">
                                                                {{ $diff['to'] }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div>
                                                        <span class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Alterações no Checklist</span>
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
                                                                    <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300 font-medium">
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
                            <li class="flex flex-col items-center justify-center text-center px-6 py-10 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-slate-50/50 dark:bg-slate-950/20">
                                <i class="ph ph-clock-counter-clockwise text-3xl text-slate-300 mb-2"></i>
                                <p class="text-xs text-slate-400 font-medium">Nenhum registro no histórico.</p>
                                <p class="text-xs text-slate-400">Adicione notas sobre o andamento e alterações da tarefa.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="px-6 pb-6 pt-0 shrink-0 border-t-0">
                    <div class="flex gap-3">
                        <button type="button" x-on:click="$wire.closeModal()"
                            class="flex-1 px-4 py-2.5 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold text-sm rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2.5 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-sm rounded-xl hover:bg-slate-800 dark:hover:bg-slate-100 transition-all shadow-lg">
                            <span x-text="mode === 'create' ? 'Criar Tarefa' : 'Salvar Alterações'"></span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    {{-- Modal de Criacao de Categoria --}}
    @if($showCategoryModal)
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
            <div class="bg-white dark:bg-slate-900 w-full max-w-sm rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden animate-in fade-in zoom-in duration-200">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Nova Categoria</h3>
                    <button type="button" wire:click="closeCategoryModal" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <i class="ph ph-x text-xl"></i>
                    </button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Nome da Categoria</label>
                        <input wire:model="newCategoryName" type="text" placeholder="Ex: Manutencao Eletrica"
                            wire:keydown.enter.prevent="createNewCategory"
                            class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm outline-none focus:ring-2 focus:ring-slate-400 transition-all text-slate-900 dark:text-slate-100 placeholder-slate-400">
                        @error('newCategoryName')
                            <span class="text-[10px] font-bold text-red-500 uppercase tracking-tight">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" wire:click="closeCategoryModal"
                            class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 font-bold text-xs rounded-xl hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors uppercase">
                            Cancelar
                        </button>
                        <button type="button" wire:click="createNewCategory"
                            class="flex-1 px-4 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 font-bold text-xs rounded-xl hover:bg-slate-800 dark:hover:bg-slate-100 transition-all shadow-lg uppercase">
                            Criar Categoria
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

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
