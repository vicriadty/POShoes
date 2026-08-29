<script lang="ts">
    import { toasts } from '@/lib/toast';

    const styleByType = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-red-600 text-white',
        info: 'bg-slate-800 text-white',
    };
</script>

<div class="pointer-events-none fixed inset-x-0 top-4 z-[60] flex flex-col items-center gap-2 px-4">
    {#each $toasts as t}
        <div class="pointer-events-auto flex w-full max-w-sm items-center rounded-xl px-4 py-3 text-sm shadow-lg {styleByType[t.type]}">
            <span class="mr-2">
                {#if t.type === 'success'}✓{:else if t.type === 'error'}✕{:else}ℹ{/if}
            </span>
            <span>{t.message}</span>
        </div>
    {/each}
</div>