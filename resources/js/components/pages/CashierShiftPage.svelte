<script lang="ts">
    import { onMount } from 'svelte';
    import { fetchCurrentShift, openShift, closeShift } from '@/lib/domain';
    import { toast } from '@/lib/toast';
    import { ApiError } from '@/lib/api';
    import Button from '@/components/ui/Button.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Field from '@/components/ui/Field.svelte';
    import type { CashierShift } from '@/types';

    let shift: CashierShift | null = null;
    let loading = true;
    let error = '';

    // Open form
    let openingBalance = '';
    let opening = false;

    // Close form
    let closingBalance = '';
    let closing = false;

    function fmtRp(n: number | null): string {
        return n === null ? '—' : 'Rp ' + n.toLocaleString('id-ID');
    }

    function fmtDt(iso: string | null): string {
        return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
    }

    async function load(): Promise<void> {
        try {
            const res = await fetchCurrentShift();
            shift = res.data;
        } catch (e) {
            error = (e as Error).message;
        } finally {
            loading = false;
        }
    }

    onMount(load);

    async function doOpen(): Promise<void> {
        opening = true;
        try {
            const res = await openShift({
                opening_balance: Number(openingBalance) || 0,
            });
            shift = res.data;
            openingBalance = '';
            toast.success('Shift dibuka.');
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal membuka shift.');
        } finally {
            opening = false;
        }
    }

    async function doClose(): Promise<void> {
        if (!shift) return;
        const balance = Number(closingBalance);
        if (balance < 0) {
            toast.error('Masukkan saldo kas yang valid.');
            return;
        }
        closing = true;
        try {
            const res = await closeShift(shift.id, { closed_balance: balance });
            shift = res.data;
            closingBalance = '';
            toast.success('Shift ditutup.');
        } catch (e) {
            if (e instanceof ApiError) toast.error(e.message);
            else toast.error('Gagal menutup shift.');
        } finally {
            closing = false;
        }
    }
</script>

<div class="space-y-4">
    <h2 class="text-lg font-bold text-slate-900">Shift Kasir</h2>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if error}
        <div class="py-8 text-center text-sm text-red-600">{error}</div>
    {:else if !shift}
        <!-- Belum ada shift aktif -->
        <Card>
            <div class="space-y-3">
                <p class="text-sm text-slate-600">Belum ada shift aktif. Buka shift untuk mulai menerima pembayaran.</p>
                <Field label="Kas awal (Rp)" type="number" inputmode="numeric" bind:value={openingBalance} placeholder="0" />
                <Button type="button" onclick={doOpen} full loading={opening}>Buka Shift</Button>
            </div>
        </Card>
    {:else if shift.is_open}
        <!-- Shift aktif -->
        <Card title="Shift Aktif">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Dibuka</span>
                    <span class="font-medium text-slate-800">{fmtDt(shift.opened_at)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Kas awal</span>
                    <span class="font-medium text-slate-800">{fmtRp(shift.opening_balance)}</span>
                </div>
            </div>
        </Card>

        <Card title="Tutup Shift">
            <div class="space-y-3">
                <Field label="Saldo kas akhir (Rp)" type="number" inputmode="numeric" bind:value={closingBalance} placeholder="0" />
                <Button type="button" variant="danger" onclick={doClose} full loading={closing}>Tutup Shift</Button>
            </div>
        </Card>
    {:else}
        <!-- Shift sudah ditutup -->
        <Card title="Shift Terakhir">
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Dibuka</span>
                    <span class="font-medium text-slate-800">{fmtDt(shift.opened_at)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Ditutup</span>
                    <span class="font-medium text-slate-800">{fmtDt(shift.closed_at)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Kas awal</span>
                    <span class="font-medium text-slate-800">{fmtRp(shift.opening_balance)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Kas akhir</span>
                    <span class="font-medium text-slate-800">{fmtRp(shift.closed_balance)}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Seharusnya</span>
                    <span class="font-medium text-slate-800">{fmtRp(shift.expected_amount)}</span>
                </div>
                <div class="flex justify-between border-t border-slate-100 pt-2">
                    <span class="text-slate-500">Selisih</span>
                    <span class="font-semibold {shift.discrepancy !== null && shift.discrepancy !== 0 ? 'text-amber-700' : 'text-emerald-700'}">
                        {fmtRp(shift.discrepancy)}
                    </span>
                </div>
            </div>
        </Card>
    {/if}
</div>
