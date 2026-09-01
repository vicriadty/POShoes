<script lang="ts">
    import { onMount } from 'svelte';
    import { fetchOrder, fetchPayments, fetchPaymentMethods, createPayment, voidPayment, refundPayment } from '@/lib/domain';
    import { toast } from '@/lib/toast';
    import { ApiError } from '@/lib/api';
    import Button from '@/components/ui/Button.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Field from '@/components/ui/Field.svelte';
    import Link from '@/components/ui/Link.svelte';
    import Modal from '@/components/ui/Modal.svelte';
    import type { Payment, PaymentMethod, ServiceOrder } from '@/types';

    export let id: string = '';

    let order: ServiceOrder | undefined;
    let payments: Payment[] = [];
    let methods: PaymentMethod[] = [];
    let loading = true;
    let error = '';

    // Form pembayaran
    let amountInput = '';
    let methodId = 0;
    let reference = '';
    let submitting = false;

    // Modal void/refund
    let voidTarget: Payment | null = null;
    let refundTarget: Payment | null = null;
    let actionAmount = '';
    let actionReason = '';
    let actionSubmitting = false;

    function fmtRp(n: number): string {
        return 'Rp ' + n.toLocaleString('id-ID');
    }

    function fmtDt(iso: string | null): string {
        return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
    }

    async function loadAll(): Promise<void> {
        try {
            const orderRes = await fetchOrder(Number(id));
            order = orderRes.data;
            const [payRes, methodRes] = await Promise.all([
                fetchPayments(Number(id)),
                fetchPaymentMethods({ active_only: true }),
            ]);
            payments = payRes.data;
            methods = methodRes.data;
        } catch (e) {
            error = (e as Error).message;
        } finally {
            loading = false;
        }
    }

    onMount(loadAll);

    function quickAmount(n: number): void {
        amountInput = String(n);
    }

    function remaining(): number {
        return order?.remaining_amount ?? 0;
    }

    async function submitPayment(): Promise<void> {
        const amount = Number(amountInput);
        if (!methodId) {
            toast.error('Pilih metode pembayaran.');
            return;
        }
        if (!amount || amount <= 0) {
            toast.error('Masukkan jumlah yang valid.');
            return;
        }
        if (amount > remaining()) {
            toast.error('Jumlah melebihi sisa tagihan.');
            return;
        }
        submitting = true;
        try {
            await createPayment(Number(id), {
                payment_method_id: methodId,
                amount,
                reference: reference.trim() || undefined,
            });
            toast.success('Pembayaran diterima.');
            amountInput = '';
            reference = '';
            await loadAll();
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal menyimpan pembayaran.');
        } finally {
            submitting = false;
        }
    }

    function openVoid(p: Payment): void {
        voidTarget = p;
        actionReason = '';
        actionAmount = '';
    }

    async function doVoid(): Promise<void> {
        if (!voidTarget) return;
        actionSubmitting = true;
        try {
            await voidPayment(Number(id), voidTarget.id, actionReason.trim() || undefined);
            toast.success('Pembayaran di-void.');
            voidTarget = null;
            await loadAll();
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal void pembayaran.');
        } finally {
            actionSubmitting = false;
        }
    }

    function openRefund(p: Payment): void {
        refundTarget = p;
        actionAmount = '';
        actionReason = '';
    }

    async function doRefund(): Promise<void> {
        if (!refundTarget) return;
        const amount = Number(actionAmount);
        if (!amount || amount <= 0) {
            toast.error('Masukkan jumlah refund.');
            return;
        }
        actionSubmitting = true;
        try {
            await refundPayment(Number(id), refundTarget.id, {
                payment_method_id: refundTarget.payment_method_id,
                amount,
                reference: actionReason.trim() || undefined,
            });
            toast.success('Refund diproses.');
            refundTarget = null;
            await loadAll();
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal refund.');
        } finally {
            actionSubmitting = false;
        }
    }

    function canVoid(p: Payment): boolean {
        return !p.is_voided && p.amount > 0;
    }

    function canRefund(p: Payment): boolean {
        return !p.is_voided && p.amount > 0;
    }
</script>

<div class="space-y-4">
    <div class="flex items-center gap-2">
        <Link to={`/orders/${id}`} class="text-slate-500">←</Link>
        <h2 class="text-lg font-bold text-slate-900">Pembayaran</h2>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if error}
        <div class="py-8 text-center text-sm text-red-600">{error}</div>
    {:else if order}
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between text-sm">
                <span class="text-slate-500">{order.order_number} · {order.customer?.name}</span>
                <span class="font-semibold text-slate-900">{fmtRp(order.total_amount)}</span>
            </div>
            <div class="mt-2 flex items-center justify-between">
                <span class="text-sm text-slate-600">Sisa tagihan</span>
                <span class="text-xl font-bold text-brand-700">{fmtRp(remaining())}</span>
            </div>
            {#if remaining() === 0}
                <p class="mt-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs text-emerald-700">Order lunas ✓</p>
            {/if}
        </div>

        <!-- Form pembayaran -->
        {#if remaining() > 0 && order.status !== 'cancelled' && order.status !== 'picked_up'}
            <Card title="Terima Pembayaran">
                <div class="space-y-3">
                    <div class="grid grid-cols-3 gap-2">
                        <Button type="button" variant="secondary" size="sm" onclick={() => quickAmount(remaining())}>Lunas</Button>
                        <Button type="button" variant="secondary" size="sm" onclick={() => quickAmount(Math.ceil(remaining() / 2))}>50%</Button>
                        <Button type="button" variant="secondary" size="sm" onclick={() => quickAmount(0)}>Kosongkan</Button>
                    </div>

                    <Field label="Jumlah (Rp)" type="number" inputmode="numeric" bind:value={amountInput} placeholder="0" />

                    <div>
                        <span class="mb-1 block text-sm font-medium text-slate-700">Metode</span>
                        <select
                            bind:value={methodId}
                            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm"
                        >
                            <option value={0}>Pilih metode…</option>
                            {#each methods as m}
                                <option value={m.id}>{m.name}</option>
                            {/each}
                        </select>
                    </div>

                    <Field label="Referensi (opsional)" bind:value={reference} placeholder="No. transfer / struk…" />

                    <Button type="button" onclick={submitPayment} full loading={submitting}>
                        Simpan Pembayaran
                    </Button>
                </div>
            </Card>
        {/if}

        <!-- Riwayat pembayaran -->
        <Card title="Riwayat Pembayaran">
            {#if payments.length === 0}
                <p class="py-4 text-center text-sm text-slate-400">Belum ada pembayaran.</p>
            {:else}
                <div class="divide-y divide-slate-100">
                    {#each payments as p}
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-sm font-medium text-slate-800">
                                    {p.method?.name ?? `Metode #${p.payment_method_id}`}
                                    {#if p.is_voided}
                                        <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs text-red-600">void</span>
                                    {:else if p.amount < 0}
                                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">refund</span>
                                    {/if}
                                </p>
                                <p class="text-xs text-slate-500">{p.payment_number} · {fmtDt(p.received_at)}</p>
                                {#if p.void_reason}<p class="text-xs text-red-500">Alasan: {p.void_reason}</p>{/if}
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold {p.amount < 0 ? 'text-amber-700' : 'text-slate-900'}">
                                    {p.amount < 0 ? '-' : ''}{fmtRp(Math.abs(p.amount))}
                                </p>
                                {#if canVoid(p)}
                                    <button type="button" class="mt-1 text-xs text-red-500 hover:underline" onclick={() => openVoid(p)}>void</button>
                                {/if}
                                {#if canRefund(p)}
                                    <button type="button" class="ml-2 mt-1 text-xs text-amber-600 hover:underline" onclick={() => openRefund(p)}>refund</button>
                                {/if}
                            </div>
                        </div>
                    {/each}
                </div>
            {/if}
        </Card>

        <!-- Invoice link -->
        <Card>
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium text-slate-800">Invoice</p>
                    <p class="text-xs text-slate-500">Lihat atau unduh PDF invoice</p>
                </div>
                <Link to={`/orders/${id}/invoice`} class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">
                    Buka
                </Link>
            </div>
        </Card>
    {/if}

    <!-- Modal void -->
    <Modal open={voidTarget !== null} title="Void Pembayaran" onClose={() => (voidTarget = null)}>
        {#if voidTarget}
            <div class="space-y-3">
                <p class="text-sm text-slate-600">
                    Batalkan pembayaran <strong>{voidTarget.payment_number}</strong> sebesar {fmtRp(voidTarget.amount)}?
                </p>
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700">Alasan</span>
                    <input
                        bind:value={actionReason}
                        placeholder="Opsional"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    />
                </label>
                <Button type="button" variant="danger" onclick={doVoid} full loading={actionSubmitting}>
                    Void Pembayaran
                </Button>
            </div>
        {/if}
    </Modal>

    <!-- Modal refund -->
    <Modal open={refundTarget !== null} title="Refund Pembayaran" onClose={() => (refundTarget = null)}>
        {#if refundTarget}
            <div class="space-y-3">
                <p class="text-sm text-slate-600">
                    Refund dari <strong>{refundTarget.payment_number}</strong> (maks {fmtRp(refundTarget.amount)}).
                </p>
                <Field label="Jumlah refund (Rp)" type="number" inputmode="numeric" bind:value={actionAmount} placeholder="0" />
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700">Referensi</span>
                    <input
                        bind:value={actionReason}
                        placeholder="Opsional"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    />
                </label>
                <Button type="button" variant="danger" onclick={doRefund} full loading={actionSubmitting}>
                    Proses Refund
                </Button>
            </div>
        {/if}
    </Modal>
</div>
