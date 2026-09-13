<div class="p-3 sm:p-5">
    @if (session()->has('access-message'))
        <div class="mb-4 rounded-md border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300" role="status">{{ session('access-message') }}</div>
    @endif
    @if (session()->has('access-error'))
        <div class="mb-4 rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">{{ session('access-error') }}</div>
    @endif

    <form wire:submit="save" class="max-w-2xl rounded-shadcn border border-border bg-card p-4 shadow-sm sm:p-5">
        <div class="flex items-start justify-between gap-5">
            <div>
                <h3 class="text-sm font-semibold">Exigir identificação</h3>
                <p class="mt-1 text-xs leading-relaxed text-muted-foreground">Quando desativado, visitantes entram pela conta comum escolhida abaixo. A equipe e o histórico dessa conta continuam sendo respeitados.</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
                <input type="checkbox" wire:model.live="loginRequired" class="peer sr-only">
                <span class="h-6 w-10 rounded-full border border-input bg-muted transition-colors peer-checked:border-primary peer-checked:bg-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ring"></span>
                <span class="absolute left-1 h-4 w-4 rounded-full bg-background shadow-sm transition-transform peer-checked:translate-x-4"></span>
                <span class="sr-only">Exigir login</span>
            </label>
        </div>

        <div class="mt-5 space-y-1.5" @if ($loginRequired) aria-disabled="true" @endif>
            <label for="public-access-user" class="text-xs font-medium">Conta usada no acesso sem login</label>
            <x-system-select id="public-access-user" model="publicUserId" :value="$publicUserId"
                :options="$publicUsers->mapWithKeys(fn ($user) => [$user->id => $user->name.' · '.$user->team?->name])->all()"
                placeholder="Selecione um usuário comum..." icon="user" :disabled="$loginRequired" />
            @error('publicUserId') <p class="text-xs text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="mt-5 flex flex-col items-stretch gap-3 border-t border-border pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-muted-foreground">Administradores ainda podem entrar por <span class="font-medium text-foreground">/login?admin=1</span>.</p>
            <button type="submit" class="inline-flex h-9 shrink-0 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90">Salvar acesso</button>
        </div>
    </form>
</div>
