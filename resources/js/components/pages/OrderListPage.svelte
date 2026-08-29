<script lang="ts">
    import { onMount } from 'svelte';
    import { listDrafts, deleteDraft } from '@/lib/db';
    import type { DraftOrder } from '@/types';
    import Link from '@/components/ui/Link.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Card from '@/components/ui/Card.svelte';
    import { toast } from '@/lib/toast';

    let drafts: DraftOrder[] = [];
    let loading = true;

    onMount(async () => {
        drafts = await listDrafts();
        loading = false;
    });

    async function remove(id: number): Promise<void> {
        await deleteDraft(id);
        drafts = await listDrafts();
        toast.info('Draft dihapus');
    }

    function fmtDate(ts: number): string {
        return new Date(ts).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    }
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Order</h2>
        <Link
            to="/orders/new"
            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >+ Buat</Link>
    </div>

    <p class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-700">
        Draft order tersimpan lokal (offline-ready). List order server resmi hadir di Phase 3.
    </p>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if drafts.length === 0}
        <EmptyState title="Belum ada draft order" message="Buat order baru untuk mulai mencatat sepatu pelanggan.">
            <Link
                to="/orders/new"
                class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white"
            >+ Buat Order</Link>
        </EmptyState>
    {:else}
        <div class="space-y-3">
            {#each drafts as d}
                <Card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{d.customerName || 'Tanpa nama'}</p>
                            <p class="text-xs text-slate-500">{d.customerPhone || '—'}</p>
                            <p class="mt-1 text-xs text-slate-400">{fmtDate(d.updatedAt)} · {d.items.length} layanan</p>
                        </div>
                        <div class="flex items-center gap-2">
                            {#if d.synced}
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">tersinkron</span>
                            {:else}
                                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">draft</span>
                            {/if}
                            <button
                                type="button"
                                class="text-slate-400 hover:text-red-600"
                                onclick={() => remove(d.id!)}
                                aria-label="Hapus draft"
                            >🗑</button>
                        </div>
                    </div>
                </Card>
            {/each}
        </div>
    {/if}
</div>