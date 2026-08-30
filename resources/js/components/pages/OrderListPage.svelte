<script lang="ts">
    import { onMount } from 'svelte';
    import { listDrafts, deleteDraft } from '@/lib/db';
    import { fetchOrders } from '@/lib/domain';
    import type { DraftOrder, ServiceOrder } from '@/types';
    import Link from '@/components/ui/Link.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Card from '@/components/ui/Card.svelte';
    import { toast } from '@/lib/toast';

    let serverOrders: ServiceOrder[] = [];
    let drafts: DraftOrder[] = [];
    let loading = true;
    let serverError = '';

    onMount(async () => {
        drafts = await listDrafts();
        try {
            const res = await fetchOrders({ per_page: 50 });
            serverOrders = res.data;
        } catch (e) {
            serverError = (e as Error).message;
        } finally {
            loading = false;
        }
    });

    async function removeDraft(id: number): Promise<void> {
        await deleteDraft(id);
        drafts = await listDrafts();
        toast.info('Draft dihapus');
    }

    function fmtDate(ts: number): string {
        return new Date(ts).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
    }

    function fmtIso(iso: string | null): string {
        return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
    }

    function fmtRp(n: number): string {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    const statusLabel: Record<string, string> = {
        draft: 'Draft',
        received: 'Diterima',
        inspection: 'Inspeksi',
        waiting_approval: 'Menunggu Approval',
        approved: 'Disetujui',
        in_progress: 'Dikerjakan',
        quality_check: 'QC',
        ready_for_pickup: 'Siap Diambil',
        picked_up: 'Diambil',
        cancelled: 'Dibatalkan',
    };
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Order</h2>
        <Link
            to="/orders/new"
            class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700"
        >+ Buat</Link>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else}
        <!-- Draft lokal -->
        {#if drafts.length > 0}
            <div>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Draft Lokal</h3>
                <div class="space-y-3">
                    {#each drafts as d}
                        <Card>
                            <div class="flex items-center justify-between">
                                <Link to={`/orders/draft/${d.id}`} class="block flex-1">
                                    <p class="font-semibold text-slate-900">{d.customerName || 'Tanpa nama'}</p>
                                    <p class="text-xs text-slate-500">{d.customerPhone || '—'}</p>
                                    <p class="mt-1 text-xs text-slate-400">{fmtDate(d.updatedAt)} · {d.items.length} layanan</p>
                                </Link>
                                <div class="flex items-center gap-2">
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">draft</span>
                                    <button
                                        type="button"
                                        class="text-slate-400 hover:text-red-600"
                                        onclick={() => removeDraft(d.id!)}
                                        aria-label="Hapus draft"
                                    >🗑</button>
                                </div>
                            </div>
                        </Card>
                    {/each}
                </div>
            </div>
        {/if}

        <!-- Order server -->
        <div>
            <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Order Server</h3>
            {#if serverError}
                <p class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-700">
                    Draft tersimpan lokal (offline-ready). {serverError}
                </p>
            {:else if serverOrders.length === 0}
                {#if drafts.length === 0}
                    <EmptyState
                        icon="📋"
                        title="Belum ada order"
                        message="Buat order baru untuk mulai mencatat sepatu pelanggan."
                    >
                        <Link to="/orders/new" class="rounded-xl bg-brand-600 px-4 py-2 text-sm font-medium text-white">+ Buat Order</Link>
                    </EmptyState>
                {:else}
                    <p class="rounded-xl bg-sky-50 px-4 py-3 text-sm text-sky-700">
                        Belum ada order terkirim ke server.
                    </p>
                {/if}
            {:else}
                <div class="space-y-3">
                    {#each serverOrders as o}
                        <Card>
                            <Link to={`/orders/${o.id}`} class="block">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-slate-900">{o.order_number}</p>
                                        <p class="text-xs text-slate-500">
                                            {o.customer?.name ?? `#${o.customer_id}`} · {fmtIso(o.received_at ?? o.created_at)}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-400">{o.items?.length ?? 0} layanan</p>
                                    </div>
                                    <div class="text-right">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">
                                            {statusLabel[o.status] ?? o.status}
                                        </span>
                                        <p class="mt-1 text-sm font-semibold text-slate-900">{fmtRp(o.total_amount)}</p>
                                    </div>
                                </div>
                            </Link>
                        </Card>
                    {/each}
                </div>
            {/if}
        </div>
    {/if}
</div>
