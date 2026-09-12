@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-9 rounded-md border border-input bg-background px-3 text-sm text-foreground shadow-sm outline-none placeholder:text-muted-foreground focus:border-ring focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50']) }}>
