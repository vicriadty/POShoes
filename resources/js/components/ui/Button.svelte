<script lang="ts">
    import Spinner from './Spinner.svelte';

    export let type: 'button' | 'submit' | 'reset' = 'button';
    export let variant: 'primary' | 'secondary' | 'danger' | 'ghost' = 'primary';
    export let size: 'sm' | 'md' | 'lg' = 'md';
    export let disabled = false;
    export let loading = false;
    export let full = false;

    const base =
        'inline-flex items-center justify-center gap-2 rounded-xl font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 disabled:opacity-50 disabled:cursor-not-allowed';
    const variants = {
        primary: 'bg-brand-600 text-white hover:bg-brand-700',
        secondary: 'bg-white text-slate-700 border border-slate-300 hover:bg-slate-50',
        danger: 'bg-red-600 text-white hover:bg-red-700',
        ghost: 'bg-transparent text-slate-600 hover:bg-slate-100',
    };
    const sizes = {
        sm: 'h-9 px-3 text-sm',
        md: 'h-11 px-4 text-sm',
        lg: 'h-12 px-5 text-base',
    };
</script>

<button
    {type}
    {disabled}
    {...$$restProps}
    class="{base} {variants[variant]} {sizes[size]} {full ? 'w-full' : ''}"
>
    {#if loading}
        <Spinner extraClass="h-4 w-4" />
    {/if}
    <slot />
</button>