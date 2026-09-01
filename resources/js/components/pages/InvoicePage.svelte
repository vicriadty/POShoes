<script lang="ts">
    import { onMount } from 'svelte';
    import { fetchOrder, fetchInvoice, sendInvoice, downloadInvoicePdf } from '@/lib/domain';
    import { toast } from '@/lib/toast';
    import { ApiError } from '@/lib/api';
    import Button from '@/components/ui/Button.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Link from '@/components/ui/Link.svelte';
    import type { Invoice, ServiceOrder } from '@/types';

    export let id: string = '';

    let order: ServiceOrder | undefined;
    let invoice: Invoice | undefined;
    let loading = true;
    let error = '';
    let sending = false;

    function fmtRp(n: number): string {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function fmtDt(iso: string | null): string {
        return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
    }

    const statusLabel: Record<string, string> = {
        draft: 'Draft',
        issued: 'Diterbitkan',
        paid: 'Lunas',
        cancelled: 'Dibatalkan',
    };

    async function load(): Promise<void> {
        try {
            const [orderRes, invRes] = await Promise.all([
                fetchOrder(Number(id)),
                fetchInvoice(Number(id)),
            ]);
            order = orderRes.data;
            invoice = invRes.data;
        } catch (e) {
            error = (e as Error).message;
        } finally {
            loading = false;
        }
    }

    onMount(load);

    async function doSend(): Promise<void> {
        if (!invoice) return;
        sending = true;
        try {
            const res = await sendInvoice(Number(id));
            invoice = res.data;
            toast.success('Invoice ditandai terkirim.');
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal menandai terkirim.');
        } finally {
            sending = false;
        }
    }

    function openPdf(): void {
        downloadInvoicePdf(Number(id))
            .then((url) => window.open(url, '_blank'))
            .catch((e) => {
                if (e instanceof ApiError) toast.error(e.message);
                else toast.error('Gagal mengunduh PDF.');
            });
    }
</script>

<div class="space-y-4">
    <div class="flex items-center gap-2">
        <Link to={`/orders/${id}`} class="text-slate-500">←</Link>
        <h2 class="text-lg font-bold text-slate-900">Invoice</h2>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if error}
        <div class="py-8 text-center text-sm text-red-600">{error}</div>
    {:else if invoice && order}
        <Card>
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-lg font-bold text-slate-900">{invoice.invoice_number}</p>
                        <p class="text-xs text-slate-500">{order.order_number}</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                        {statusLabel[invoice.status] ?? invoice.status}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500">
                    <div>Pelanggan: <span class="text-slate-800">{order.customer?.name ?? '—'}</span></div>
                    <div>Diterbitkan: {fmtDt(invoice.issued_at)}</div>
                    {#if invoice.sent_at}
                        <div>Dikirim: {fmtDt(invoice.sent_at)}</div>
                    {/if}
                </div>
            </div>
        </Card>

        <Card title="Rincian">
            <div class="divide-y divide-slate-100">
                {#each order.items ?? [] as it}
                    <div class="flex items-center justify-between py-2">
                        <span class="text-sm text-slate-800">{it.service_name} <span class="text-slate-400">×{it.quantity}</span></span>
                        <span class="text-sm font-medium text-slate-900">{fmtRp(it.subtotal)}</span>
                    </div>
                {/each}
            </div>
            <div class="mt-2 space-y-1 border-t border-slate-100 pt-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal</span><span>{fmtRp(order.subtotal)}</span>
                </div>
                {#if order.discount_amount > 0}
                    <div class="flex justify-between text-slate-600">
                        <span>Diskon</span><span>-{fmtRp(order.discount_amount)}</span>
                    </div>
                {/if}
                {#if order.tax_amount > 0}
                    <div class="flex justify-between text-slate-600">
                        <span>Pajak</span><span>{fmtRp(order.tax_amount)}</span>
                    </div>
                {/if}
                <div class="flex justify-between font-semibold text-slate-900">
                    <span>Total</span><span>{fmtRp(order.total_amount)}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Sudah dibayar</span><span>{fmtRp(order.paid_amount)}</span>
                </div>
                <div class="flex justify-between text-amber-700">
                    <span>Sisa</span><span>{fmtRp(order.remaining_amount)}</span>
                </div>
            </div>
        </Card>

        <div class="space-y-2">
            <Button type="button" onclick={openPdf} full>Unduh / Lihat PDF</Button>
            {#if invoice.status !== 'cancelled'}
                <Button type="button" variant="secondary" onclick={doSend} full loading={sending}>
                    Tandai Terkirim
                </Button>
            {/if}
        </div>
    {/if}
</div>
