<script lang="ts">
    import { onMount } from 'svelte';
    import { navigate } from '@/lib/router';
    import { saveDraft } from '@/lib/db';
    import { toast } from '@/lib/toast';
    import { fetchCustomers, fetchServices, createOrder, createCustomer } from '@/lib/domain';
    import { ApiError } from '@/lib/api';
    import Button from '@/components/ui/Button.svelte';
    import Field from '@/components/ui/Field.svelte';
    import Card from '@/components/ui/Card.svelte';
    import Modal from '@/components/ui/Modal.svelte';
    import type { Customer, ServiceCatalog } from '@/types';

    // Master data
    let customers: Customer[] = [];
    let services: ServiceCatalog[] = [];
    let loadingMaster = true;
    let customerQuery = '';

    // Form state
    let selectedCustomerId: number | null = null;
    let customerNotes = '';
    let estimatedDate = '';
    let selectedServiceId = 0;
    let quantity = 1;
    let shoeBrand = '';
    let shoeModel = '';
    let shoeSize = '';
    let shoeColor = '';

    interface Line {
        serviceId: number;
        serviceName: string;
        unitPrice: number;
        quantity: number;
        note?: string;
    }
    let lines: Line[] = [];
    let shoes: Array<{ brand: string; model: string; size: string; color: string }> = [];

    // Customer quick-add
    let showCustomerModal = false;
    let newCustomerName = '';
    let newCustomerPhone = '';

    onMount(async () => {
        try {
            const [cRes, sRes] = await Promise.all([
                fetchCustomers({ per_page: 100 }),
                fetchServices({ active_only: true, per_page: 100 }),
            ]);
            customers = cRes.data;
            services = sRes.data;
        } catch (e) {
            toast.error((e as Error).message);
        } finally {
            loadingMaster = false;
        }
    });

    async function searchCustomers(): Promise<void> {
        try {
            const res = await fetchCustomers({ search: customerQuery || undefined, per_page: 50 });
            customers = res.data;
        } catch {
            // abaikan — biarkan list lama
        }
    }

    function selectCustomer(id: number): void {
        selectedCustomerId = id;
    }

    function addLine(): void {
        const svc = services.find((s) => s.id === Number(selectedServiceId));
        if (!svc) {
            toast.error('Pilih layanan terlebih dahulu.');
            return;
        }
        lines = [
            ...lines,
            {
                serviceId: svc.id,
                serviceName: svc.name,
                unitPrice: svc.base_price,
                quantity: Math.max(1, quantity),
            },
        ];
        selectedServiceId = 0;
        quantity = 1;
    }

    function removeLine(index: number): void {
        lines = lines.filter((_, i) => i !== index);
    }

    function addShoe(): void {
        if (!shoeBrand.trim()) {
            toast.error('Isi minimal merk sepatu.');
            return;
        }
        shoes = [...shoes, { brand: shoeBrand.trim(), model: shoeModel.trim(), size: shoeSize.trim(), color: shoeColor.trim() }];
        shoeBrand = '';
        shoeModel = '';
        shoeSize = '';
        shoeColor = '';
    }

    function removeShoe(index: number): void {
        shoes = shoes.filter((_, i) => i !== index);
    }

    function total(): number {
        return lines.reduce((sum, it) => sum + it.unitPrice * it.quantity, 0);
    }

    function selectedCustomer(): Customer | null {
        return customers.find((c) => c.id === selectedCustomerId) ?? null;
    }

    function buildPayload() {
        return {
            customer_id: selectedCustomerId as number,
            items: lines.map((l) => ({
                service_catalog_id: l.serviceId,
                quantity: l.quantity,
                notes: l.note || null,
            })),
            shoes: shoes.length
                ? shoes.map((s) => ({
                      brand: s.brand,
                      model: s.model || null,
                      size: s.size || null,
                      color: s.color || null,
                  }))
                : [],
            customer_notes: customerNotes || null,
            estimated_completed_at: estimatedDate ? new Date(estimatedDate).toISOString() : null,
        };
    }

    async function submit(server: boolean): Promise<void> {
        if (lines.length === 0) {
            toast.error('Tambahkan minimal satu layanan.');
            return;
        }

        // Simpan draft lokal selalu (offline-ready).
        const cust = selectedCustomer();
        await saveDraft({
            customerName: cust?.name ?? 'Customer baru',
            customerPhone: cust?.phone_wa ?? newCustomerPhone,
            notes: customerNotes,
            items: lines.map((l) => ({
                serviceName: l.serviceName,
                quantity: l.quantity,
                unitPrice: l.unitPrice,
                note: l.note,
            })),
        });

        if (!server) {
            toast.success('Draft order tersimpan lokal.');
            navigate('/orders');
            return;
        }

        if (!selectedCustomerId) {
            toast.error('Pilih customer terlebih dahulu untuk kirim ke server.');
            return;
        }

        try {
            await createOrder(buildPayload());
            toast.success('Order berhasil dibuat.');
            navigate('/orders');
        } catch (e) {
            if (e instanceof ApiError) {
                toast.error(e.message);
            } else {
                toast.error('Gagal mengirim order. Draft tersimpan lokal.');
            }
        }
    }

    async function quickAddCustomer(): Promise<void> {
        if (!newCustomerName.trim() || !newCustomerPhone.trim()) {
            toast.error('Nama dan No. WhatsApp wajib.');
            return;
        }
        try {
            const res = await createCustomer({ name: newCustomerName.trim(), phone_wa: newCustomerPhone.trim() });
            customers = [res.data, ...customers];
            selectedCustomerId = res.data.id;
            newCustomerName = '';
            newCustomerPhone = '';
            showCustomerModal = false;
            toast.success('Customer ditambahkan.');
        } catch (e) {
            toast.error((e as Error).message);
        }
    }

    function fmtRp(n: number): string {
        return 'Rp ' + n.toLocaleString('id-ID');
    }
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Buat Order</h2>
        <button type="button" class="text-slate-400 hover:text-slate-600" onclick={() => navigate('/orders')}>✕</button>
    </div>

    {#if loadingMaster}
        <div class="py-8 text-center text-sm text-slate-400">Memuat master data…</div>
    {:else}
        <!-- Pelanggan -->
        <Card title="Pelanggan">
            <div class="space-y-3">
                <div class="flex items-center gap-2">
                    <input
                        type="search"
                        bind:value={customerQuery}
                        onkeydown={(e) => e.key === 'Enter' && searchCustomers()}
                        placeholder="Cari nama / nomor WA…"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    />
                    <Button type="button" variant="secondary" size="sm" onclick={searchCustomers}>Cari</Button>
                </div>

                <select
                    bind:value={selectedCustomerId}
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm"
                >
                    <option value={0}>Pilih customer…</option>
                    {#each customers as c}
                        <option value={c.id}>{c.name} — {c.phone_wa}</option>
                    {/each}
                </select>

                <Button type="button" variant="ghost" size="sm" onclick={() => (showCustomerModal = true)}>
                    + Customer baru
                </Button>

                {#if selectedCustomer()}
                    <div class="rounded-xl bg-slate-50 px-3 py-2 text-sm">
                        <p class="font-medium text-slate-800">{selectedCustomer()!.name}</p>
                        <p class="text-xs text-slate-500">{selectedCustomer()!.phone_wa_international}</p>
                    </div>
                {/if}
            </div>
        </Card>

        <!-- Layanan -->
        <Card title="Layanan">
            <div class="space-y-3">
                {#each lines as line, i}
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm">
                        <div>
                            <p class="font-medium text-slate-800">{line.serviceName}</p>
                            <p class="text-xs text-slate-500">{line.quantity} × {fmtRp(line.unitPrice)}</p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-red-600" onclick={() => removeLine(i)}>🗑</button>
                    </div>
                {/each}

                <div class="grid grid-cols-1 gap-2">
                    <select
                        bind:value={selectedServiceId}
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm"
                    >
                        <option value={0}>Pilih layanan…</option>
                        {#each services as s}
                            <option value={s.id}>{s.name} — {fmtRp(s.base_price)}</option>
                        {/each}
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <Field label="Jumlah" type="number" bind:value={quantity} />
                    <div class="flex items-end">
                        <Button type="button" variant="secondary" onclick={addLine} full>+ Tambah</Button>
                    </div>
                </div>
            </div>
        </Card>

        <!-- Sepatu -->
        <Card title="Sepatu">
            <div class="space-y-3">
                {#each shoes as s, i}
                    <div class="flex items-center justify-between rounded-xl bg-slate-50 px-3 py-2 text-sm">
                        <div>
                            <p class="font-medium text-slate-800">{s.brand}{s.model ? ` ${s.model}` : ''}</p>
                            <p class="text-xs text-slate-500">{s.color || '—'} · {s.size ? `Size ${s.size}` : '—'}</p>
                        </div>
                        <button type="button" class="text-slate-400 hover:text-red-600" onclick={() => removeShoe(i)}>🗑</button>
                    </div>
                {/each}

                <div class="grid grid-cols-2 gap-2">
                    <Field label="Merk" bind:value={shoeBrand} placeholder="Nike, Adidas…" />
                    <Field label="Model" bind:value={shoeModel} placeholder="Air Max…" />
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <Field label="Ukuran" bind:value={shoeSize} placeholder="42" />
                    <Field label="Warna" bind:value={shoeColor} placeholder="Putih" />
                </div>
                <Button type="button" variant="secondary" onclick={addShoe} full>+ Tambah Sepatu</Button>
            </div>
        </Card>

        <!-- Catatan & estimasi -->
        <Card title="Catatan & Estimasi">
            <div class="space-y-3">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-slate-700">Catatan pelanggan</span>
                    <textarea
                        bind:value={customerNotes}
                        rows="2"
                        placeholder="Kondisi sepatu, permintaan khusus…"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                    ></textarea>
                </label>
                <Field label="Estimasi selesai" type="date" bind:value={estimatedDate} />
            </div>
        </Card>

        <div class="flex items-center justify-between rounded-2xl bg-white p-4 shadow-sm">
            <span class="text-sm font-medium text-slate-700">Total</span>
            <span class="text-xl font-bold text-slate-900">{fmtRp(total())}</span>
        </div>

        <Button type="button" onclick={() => submit(false)} variant="secondary" full>
            Simpan Draft
        </Button>
        <Button type="button" onclick={() => submit(true)} full>
            Buat Order
        </Button>
    {/if}

    <Modal open={showCustomerModal} title="Customer Baru" onClose={() => (showCustomerModal = false)}>
        <div class="space-y-3">
            <Field label="Nama" bind:value={newCustomerName} placeholder="Nama lengkap" />
            <Field label="No. WhatsApp" type="tel" inputmode="tel" bind:value={newCustomerPhone} placeholder="08xx…" />
            <Button type="button" onclick={quickAddCustomer} full>Simpan Customer</Button>
        </div>
    </Modal>
</div>
