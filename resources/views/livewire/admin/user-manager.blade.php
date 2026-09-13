<div class="p-3 sm:p-5">
    @if (session()->has('user-message'))
        <div class="mb-4 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ session('user-message') }}</div>
    @endif
    @if (session()->has('user-error'))
        <div class="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">{{ session('user-error') }}</div>
    @endif

    <section class="overflow-hidden rounded-shadcn border border-border bg-card shadow-sm">
        <div class="flex flex-col items-stretch gap-3 border-b border-border px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
            <div><h3 class="text-sm font-semibold">Cargos dos usuários</h3><p class="mt-1 text-xs text-muted-foreground">Defina quem administra o sistema. Pelo menos um administrador deve permanecer ativo.</p></div>
            <button type="button" wire:click="openCreate" class="inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-md bg-primary px-3 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"><i class="ph ph-plus" aria-hidden="true"></i>Novo usuário</button>
        </div>
        <div class="divide-y divide-border">
            @foreach ($users as $user)
                <div wire:key="system-user-{{ $user->id }}" class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                        <p class="truncate text-xs text-muted-foreground">{{ $user->email }} · {{ $user->team?->name ?? 'Sem equipe' }}</p>
                    </div>
                    <div class="flex h-8 w-full shrink-0 items-center rounded-md border border-input bg-muted p-0.5 sm:w-auto" role="group" aria-label="Cargo de {{ $user->name }}">
                        <button type="button" wire:click="setRole({{ $user->id }}, 'common')" class="h-6 flex-1 rounded-sm px-3 text-xs font-medium sm:flex-none {{ ! $user->is_admin ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">Comum</button>
                        <button type="button" wire:click="setRole({{ $user->id }}, 'admin')" class="h-6 flex-1 rounded-sm px-3 text-xs font-medium sm:flex-none {{ $user->is_admin ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground' }}">Administrador</button>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($isCreateOpen)
        <div class="fixed inset-0 z-[80] flex items-center justify-center bg-black/75 p-2 sm:p-4" role="dialog" aria-modal="true" aria-labelledby="create-user-title">
            <div class="max-h-[calc(100dvh-1rem)] w-full max-w-lg overflow-y-auto rounded-shadcn border border-border bg-background shadow-lg custom-scrollbar">
                <div class="flex items-center justify-between gap-3 border-b border-border px-4 py-3 sm:px-5 sm:py-4">
                    <div class="min-w-0"><h3 id="create-user-title" class="text-base font-semibold">Novo usuário</h3><p class="mt-0.5 truncate text-xs text-muted-foreground">Crie uma conta e defina seu acesso inicial.</p></div>
                    <button type="button" wire:click="closeCreate" class="flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground hover:bg-accent hover:text-accent-foreground" aria-label="Fechar"><i class="ph ph-x text-lg"></i></button>
                </div>
                <form wire:submit="create">
                    <div class="space-y-4 p-4 sm:p-5">
                        <div><label class="mb-1.5 block text-xs font-medium">Nome</label><input wire:model="name" type="text" class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring">@error('name')<p class="mt-1 text-xs text-destructive">{{ $message }}</p>@enderror</div>
                        <div><label class="mb-1.5 block text-xs font-medium">E-mail</label><input wire:model="email" type="email" class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring">@error('email')<p class="mt-1 text-xs text-destructive">{{ $message }}</p>@enderror</div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div><label class="mb-1.5 block text-xs font-medium">Senha inicial</label><input wire:model="password" type="password" class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring">@error('password')<p class="mt-1 text-xs text-destructive">{{ $message }}</p>@enderror</div>
                            <div><label class="mb-1.5 block text-xs font-medium">Confirmar senha</label><input wire:model="passwordConfirmation" type="password" class="h-9 w-full rounded-md border border-input bg-muted/50 px-3 text-sm shadow-sm outline-none focus:bg-background focus:ring-2 focus:ring-ring"></div>
                        </div>
                        <div><label class="mb-1.5 block text-xs font-medium">Equipe</label><x-system-select model="teamId" :value="$teamId" :options="$teams->pluck('name', 'id')->all()" placeholder="Selecione uma equipe..." icon="users-three" />@error('teamId')<p class="mt-1 text-xs text-destructive">{{ $message }}</p>@enderror</div>
                        <label class="flex cursor-pointer items-center justify-between rounded-md border border-border bg-muted/30 p-3"><span><span class="block text-sm font-medium">Administrador</span><span class="block text-xs text-muted-foreground">Acesso às configurações gerais do sistema.</span></span><input wire:model.live="isAdministrator" type="checkbox" class="system-checkbox"></label>
                    </div>
                    <div class="flex justify-end gap-2 border-t border-border bg-muted/30 px-4 py-3 sm:px-5"><button type="button" wire:click="closeCreate" class="h-9 rounded-md border border-input bg-background px-4 text-sm font-medium shadow-sm hover:bg-accent">Cancelar</button><button type="submit" class="h-9 rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">Criar usuário</button></div>
                </form>
            </div>
        </div>
    @endif
</div>
