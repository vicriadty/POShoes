<script lang="ts">
    import { onMount } from 'svelte';
    import { getDraft, listDrafts } from '@/lib/db';
    import type { DraftOrder } from '@/types';
    import Link from '@/components/ui/Link.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Card from '@/components/ui/Card.svelte';

    export let id: string = '';
    let draft: DraftOrder | undefined;
    let loading = true;

    function total(): number {
        return draft?.items.reduce((sum, it) => sum + it.quantity * it.unitPrice, 0) ?? 0;
    }

    onMount(async () => {
        const numId = Number(id);
        const found = numId ? await getDraft(numId) : undefined;
        if (!found) {
            const all = await listDrafts();
            draft = all[0];
        } else {
            draft = found;
        }
        loading = false;
    });
</script>

<div class="space-y-4">
    <div class="flex items-center gap-2">
        <Link to="/orders" class="text-slate-500">←</Link>
        <h2 class="text-lg font-bold text-slate-900">Detail Draft</h2>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if !draft}
        <EmptyState title="Draft tidak ditemukan" message="Draft ini mungkin sudah terhapus.">
            <Link to="/orders" class="text-sm text-brand-600">Kembali ke daftar</Link>
        </EmptyState>
    {:else}
        <Card title="Pelanggan">
            <p class="font-semibold text-slate-900">{draft.customerName || 'Tanpa nama'}</p>
            <p class="text-sm text-slate-500">{draft.customerPhone || '—'}</p>
            {#if draft.notes}<p class="mt-2 text-sm text-slate-600">{draft.notes}</p>{/if}
        </Card>

        <Card title="Layanan">
            <div class="divide-y divide-slate-100">
                {#each draft.items as it}
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-slate-800">{it.serviceName} <span class="text-slate-400">×{it.quantity}</span></span>
                        <span class="text-sm font-medium text-slate-900">{(it.quantity * it.unitPrice).toLocaleString('id-ID')}</span>
                    </div>
                {/each}
            </div>
            <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                <span class="text-sm font-medium text-slate-700">Total</span>
                <span class="font-bold text-slate-900">Rp {total().toLocaleString('id-ID')}</span>
            </div>
        </Card>
    {/if}
</div>