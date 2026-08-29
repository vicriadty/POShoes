<script lang="ts">
    import type { HTMLInputAttributes } from 'svelte/elements';

    export let value: string | number | null = '';
    export let type: HTMLInputAttributes['type'] = 'text';
    export let placeholder = '';
    export let disabled = false;
    export let required = false;
    export let name: string | undefined = undefined;
    export let inputmode: HTMLInputAttributes['inputmode'] | undefined = undefined;
</script>

<input
    {type}
    {name}
    {placeholder}
    {disabled}
    {required}
    {inputmode}
    bind:value
    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100 disabled:bg-slate-100 disabled:text-slate-400"
/>