<script lang="ts">
    import { onMount } from 'svelte';
    import { getDraft } from '@/lib/db';
    import { fetchOrder } from '@/lib/domain';
    import type { DraftOrder, ServiceOrder } from '@/types';
    import Link from '@/components/ui/Link.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Card from '@/components/ui/Card.svelte';

    export let id: string = '';
    export let isDraft = false;

    let serverOrder: ServiceOrder | undefined;
    let draft: DraftOrder | undefined;
    let loading = true;
    let error = '';

    function fmtRp(n: number): string {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function fmtIso(iso: string | null): string {
        return iso ? new Date(iso).toLocaleDateString('id-ID') : '—';
    }

    onMount(async () => {
        try {
            if (isDraft) {
                const numId = Number(id);
                draft = numId ? await getDraft(numId) : undefined;
            } else {
                const res = await fetchOrder(Number(id));
                serverOrder = res.data;
            }
        } catch (e) {
            error = (e as Error).message;
        } finally {
            loading = false;
        }
    });

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
    <div class="flex items-center gap-2">
        <Link to="/orders" class="text-slate-500">←</Link>
        <h2 class="text-lg font-bold text-slate-900">{isDraft ? 'Detail Draft' : serverOrder?.order_number ?? 'Detail Order'}</h2>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if error}
        <EmptyState title="Gagal memuat" message={error}>
            <Link to="/orders" class="text-sm text-brand-600">Kembali ke daftar</Link>
        </EmptyState>
    {:else if isDraft && !draft}
        <EmptyState title="Draft tidak ditemukan" message="Draft ini mungkin sudah terhapus.">
            <Link to="/orders" class="text-sm text-brand-600">Kembali ke daftar</Link>
        </EmptyState>
    {:else if !isDraft && !serverOrder}
        <EmptyState title="Order tidak ditemukan" message="Order ini mungkin sudah dihapus.">
            <Link to="/orders" class="text-sm text-brand-600">Kembali ke daftar</Link>
        </EmptyState>
    {:else}
        {#if isDraft && draft}
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
                            <span class="text-sm font-medium text-slate-900">{fmtRp(it.quantity * it.unitPrice)}</span>
                        </div>
                    {/each}
                </div>
                <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
                    <span class="text-sm font-medium text-slate-700">Total</span>
                    <span class="font-bold text-slate-900">{fmtRp(draft.items.reduce((s, it) => s + it.quantity * it.unitPrice, 0))}</span>
                </div>
            </Card>
        {:else if serverOrder}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-700">Status</span>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {statusLabel[serverOrder.status] ?? serverOrder.status}
                    </span>
                </div>
            </div>

            <Card title="Pelanggan">
                <p class="font-semibold text-slate-900">{serverOrder.customer?.name ?? `#${serverOrder.customer_id}`}</p>
                {#if serverOrder.customer?.phone_wa}
                    <p class="text-sm text-slate-500">{serverOrder.customer.phone_wa_international}</p>
                {/if}
                {#if serverOrder.customer_notes}<p class="mt-2 text-sm text-slate-600">{serverOrder.customer_notes}</p>{/if}
                <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-slate-500">
                    <div>Diterima: {fmtIso(serverOrder.received_at)}</div>
                    <div>Estimasi: {fmtIso(serverOrder.estimated_completed_at)}</div>
                </div>
            </Card>

            <Card title="Layanan">
                <div class="divide-y divide-slate-100">
                    {#each serverOrder.items ?? [] as it}
                        <div class="flex items-center justify-between py-2">
                            <div>
                                <span class="text-sm text-slate-800">{it.service_name} <span class="text-slate-400">×{it.quantity}</span></span>
                                <div class="text-xs text-slate-500">{statusLabel[it.status] ?? it.status}</div>
                            </div>
                            <span class="text-sm font-medium text-slate-900">{fmtRp(it.subtotal)}</span>
                        </div>
                    {/each}
                </div>
                <div class="mt-2 space-y-1 border-t border-slate-100 pt-2 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span><span>{fmtRp(serverOrder.subtotal)}</span>
                    </div>
                    {#if serverOrder.discount_amount > 0}
                        <div class="flex justify-between text-slate-600">
                            <span>Diskon</span><span>-{fmtRp(serverOrder.discount_amount)}</span>
                        </div>
                    {/if}
                    {#if serverOrder.tax_amount > 0}
                        <div class="flex justify-between text-slate-600">
                            <span>Pajak</span><span>{fmtRp(serverOrder.tax_amount)}</span>
                        </div>
                    {/if}
                    <div class="flex justify-between font-semibold text-slate-900">
                        <span>Total</span><span>{fmtRp(serverOrder.total_amount)}</span>
                    </div>
                    {#if serverOrder.remaining_amount > 0}
                        <div class="flex justify-between text-amber-700">
                            <span>Sisa</span><span>{fmtRp(serverOrder.remaining_amount)}</span>
                        </div>
                    {/if}
                </div>
            </Card>

            {#if (serverOrder.shoes ?? []).length > 0}
                <Card title="Sepatu">
                    <div class="divide-y divide-slate-100">
                        {#each serverOrder.shoes ?? [] as s}
                            <div class="py-2 text-sm">
                                <p class="font-medium text-slate-800">{s.brand ?? '—'}{s.model ? ` ${s.model}` : ''}</p>
                                <p class="text-xs text-slate-500">{s.color ?? '—'} · {s.size ? `Size ${s.size}` : '—'}</p>
                            </div>
                        {/each}
                    </div>
                </Card>
            {/if}

            {#if serverOrder.remaining_amount > 0 && serverOrder.status !== 'cancelled' && serverOrder.status !== 'picked_up'}
                <Link
                    to={`/orders/${serverOrder.id}/payments`}
                    class="block rounded-2xl bg-brand-600 px-4 py-3 text-center font-medium text-white hover:bg-brand-700"
                >Terima Pembayaran</Link>
            {/if}

            {#if serverOrder.status !== 'cancelled' && serverOrder.status !== 'picked_up'}
                <Link
                    to={`/orders/${serverOrder.id}/invoice`}
                    class="block rounded-2xl border border-slate-300 px-4 py-3 text-center font-medium text-slate-700 hover:bg-slate-50"
                >Lihat Invoice</Link>
            {/if}

            {#if (serverOrder.status_histories ?? []).length > 0}
                <Card title="Riwayat Status">
                    <ol class="space-y-2">
                        {#each serverOrder.status_histories ?? [] as h}
                            <li class="flex items-start justify-between text-sm">
                                <div>
                                    <span class="text-slate-700">{statusLabel[h.to_status] ?? h.to_status}</span>
                                    {#if h.reason}<p class="text-xs text-slate-500">{h.reason}</p>{/if}
                                </div>
                                <span class="text-xs text-slate-400">{fmtIso(h.created_at)}</span>
                            </li>
                        {/each}
                    </ol>
                </Card>
            {/if}
        {/if}
    {/if}
</div>
