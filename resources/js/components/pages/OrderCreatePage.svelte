<script lang="ts">
    import { navigate } from '@/lib/router';
    import { saveDraft } from '@/lib/db';
    import { toast } from '@/lib/toast';
    import Button from '@/components/ui/Button.svelte';
    import Field from '@/components/ui/Field.svelte';
    import Card from '@/components/ui/Card.svelte';
    import type { DraftOrderItem } from '@/types';

    let customerName = '';
    let customerPhone = '';
    let notes = '';
    let items: DraftOrderItem[] = [];
    let serviceName = '';
    let quantity = 1;
    let unitPrice = 0;

    function addItem(): void {
        if (!serviceName.trim()) return;
        items = [
            ...items,
            {
                serviceName: serviceName.trim(),
                quantity: Math.max(1, quantity),
                unitPrice: Math.max(0, unitPrice),
            },
        ];
        serviceName = '';
        quantity = 1;
        unitPrice = 0;
    }

    function removeItem(index: number): void {
        items = items.filter((_, i) => i !== index);
    }

    function total(): number {
        return items.reduce((sum, it) => sum + it.quantity * it.unitPrice, 0);
    }

    async function submit(): Promise<void> {
        if (items.length === 0) {
            toast.error('Tambahkan minimal satu layanan.');
            return;
        }
        await saveDraft({
            customerName,
            customerPhone,
            notes,
            items,
        });
        toast.success('Draft order tersimpan lokal.');
        navigate('/orders');
    }
</script>

<div class="space-y-4">
    <h2 class="text-lg font-bold text-slate-900">Buat Order</h2>

    <Card title="Pelanggan">
        <div class="space-y-3">
            <Field label="Nama" bind:value={customerName} placeholder="Nama pelanggan" />
            <Field label="No. WhatsApp" type="tel" bind:value={customerPhone} placeholder="08xxxx" />
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Catatan</span>
                <textarea
                    bind:value={notes}
                    rows="2"
                    placeholder="Kondisi sepatu, catatan pelanggan…"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                ></textarea>
            </label>
        </div>
    </Card>

    <Card title="Layanan">
        <div class="space-y-3">
            {#each items as item, i}
                <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm">
                    <div>
                        <p class="font-medium text-slate-800">{item.serviceName}</p>
                        <p class="text-xs text-slate-500">{item.quantity} × {item.unitPrice.toLocaleString('id-ID')}</p>
                    </div>
                    <button type="button" class="text-slate-400 hover:text-red-600" onclick={() => removeItem(i)}>🗑</button>
                </div>
            {/each}

            <div class="grid grid-cols-2 gap-2">
                <Field label="Layanan" bind:value={serviceName} placeholder="Deep Cleaning…" />
            </div>
            <div class="grid grid-cols-2 gap-2">
                <Field label="Jumlah" type="number" bind:value={quantity} />
                <Field label="Harga" type="number" inputmode="numeric" bind:value={unitPrice} />
            </div>
            <Button type="button" variant="secondary" onclick={addItem} full>+ Tambah Layanan</Button>
        </div>
    </Card>

    <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm">
        <span class="text-sm font-medium text-slate-700">Total</span>
        <span class="text-xl font-bold text-slate-900">Rp {total().toLocaleString('id-ID')}</span>
    </div>

    <Button type="button" onclick={submit} full loading={false}>
        Simpan Draft
    </Button>
</div>