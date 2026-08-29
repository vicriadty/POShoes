<script lang="ts">
    export let icon = '📋';
    export let title = 'Belum ada data';
    export let message = '';
</script>

<div class="flex flex-col items-center justify-center px-6 py-12 text-center">
    <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl">{icon}</div>
    <h3 class="text-base font-semibold text-slate-800">{title}</h3>
    {#if message}
        <p class="mt-1 text-sm text-slate-500">{message}</p>
    {/if}
    <div class="mt-4">
        <slot />
    </div>
</div>