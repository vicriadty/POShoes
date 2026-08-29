<script lang="ts">
    import Input from './Input.svelte';

    export let label: string;
    export let error: string | undefined = undefined;
    export let hint: string | undefined = undefined;
    export let value: string | number | null = '';
    export let type: 'text' | 'password' | 'email' | 'tel' | 'number' = 'text';
    export let placeholder = '';
    export let inputmode: 'numeric' | 'tel' | 'email' | 'text' | undefined = undefined;
    export let required = false;
</script>

<label class="block">
    <span class="mb-1 block text-sm font-medium text-slate-700">{label}</span>
    <Input {type} {placeholder} {inputmode} {required} bind:value />
    {#if hint && !error}
        <span class="mt-1 block text-xs text-slate-500">{hint}</span>
    {/if}
    {#if error}
        <span class="mt-1 block text-xs text-red-600">{error}</span>
    {/if}
</label>