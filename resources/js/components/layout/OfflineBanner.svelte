<script lang="ts">
    import { onMount, onDestroy } from 'svelte';

    let online = navigator.onLine;

    function update(): void {
        online = navigator.onLine;
    }

    onMount(() => {
        window.addEventListener('online', update);
        window.addEventListener('offline', update);
        return () => {
            window.removeEventListener('online', update);
            window.removeEventListener('offline', update);
        };
    });
</script>

{#if !online}
    <div class="fixed inset-x-0 top-0 z-[70] bg-amber-500 px-4 py-2 text-center text-sm font-medium text-white">
        Offline — beberapa aksi menunggu koneksi
    </div>
{/if}