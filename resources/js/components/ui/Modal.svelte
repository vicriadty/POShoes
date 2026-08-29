<script lang="ts">
    import { tick } from 'svelte';
    export let open = false;
    export let title = '';
    export let onClose: () => void = () => {};

    $: if (open) {
        tick();
    }

    function handleBackdrop(e: MouseEvent): void {
        if (e.target === e.currentTarget) onClose();
    }

    function handleKeydown(e: KeyboardEvent): void {
        if (e.key === 'Escape') onClose();
    }
</script>

{#if open}
    <div
        class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/50 sm:items-center"
        role="dialog"
        aria-modal="true"
        tabindex="-1"
        onclick={handleBackdrop}
        onkeydown={handleKeydown}
        onfocusout={(e) => e.currentTarget.blur()}
    >
        <div class="max-h-[90vh] w-full overflow-y-auto rounded-t-2xl bg-white p-5 shadow-xl sm:max-w-md sm:rounded-2xl">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-slate-900">{title}</h2>
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100"
                    onclick={onClose}
                    aria-label="Tutup"
                >×</button>
            </div>
            <slot />
        </div>
    </div>
{/if}