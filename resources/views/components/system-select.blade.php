@props([
    'model',
    'options' => [],
    'value' => '',
    'placeholder' => 'Selecione...',
    'icon' => null,
    'disabled' => false,
    'clearable' => true,
])

@php
    $selectedLabel = $options[(string) $value] ?? $options[$value] ?? $placeholder;
@endphp

<div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
    <button type="button" x-on:click="open = !open" x-bind:aria-expanded="open" @disabled($disabled)
        {{ $attributes->class(['flex h-9 w-full items-center gap-2 rounded-md border border-input bg-muted/50 px-3 text-left text-sm shadow-sm outline-none transition-colors hover:bg-accent/50 focus:bg-background focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50']) }}>
        @if ($icon)
            <i class="ph ph-{{ $icon }} text-sm text-muted-foreground" aria-hidden="true"></i>
        @endif
        <span class="min-w-0 flex-1 truncate">{{ $selectedLabel }}</span>
        <i class="ph ph-caret-down text-xs text-muted-foreground transition-transform" :class="open ? 'rotate-180' : ''" aria-hidden="true"></i>
    </button>

    <div x-show="open" x-cloak x-transition
        class="absolute z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground shadow-md custom-scrollbar">
        @if ($clearable)
            <button type="button" wire:click="$set('{{ $model }}', '')" x-on:click="open = false"
                class="flex w-full items-center justify-between rounded-sm px-2.5 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                <span class="truncate text-muted-foreground">{{ $placeholder }}</span>
                @if ((string) $value === '')<i class="ph ph-check text-sm" aria-hidden="true"></i>@endif
            </button>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <button type="button" wire:click="$set('{{ $model }}', '{{ $optionValue }}')" x-on:click="open = false"
                class="flex w-full items-center justify-between rounded-sm px-2.5 py-2 text-left text-sm transition-colors hover:bg-accent hover:text-accent-foreground">
                <span class="truncate">{{ $optionLabel }}</span>
                @if ((string) $value === (string) $optionValue)<i class="ph ph-check text-sm" aria-hidden="true"></i>@endif
            </button>
        @endforeach
    </div>
</div>
