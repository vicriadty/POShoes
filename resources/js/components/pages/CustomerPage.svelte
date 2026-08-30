<script lang="ts">
    import { onMount } from 'svelte';
    import { fetchCustomers, createCustomer } from '@/lib/domain';
    import { toast } from '@/lib/toast';
    import Button from '@/components/ui/Button.svelte';
    import Card from '@/components/ui/Card.svelte';
    import EmptyState from '@/components/ui/EmptyState.svelte';
    import Field from '@/components/ui/Field.svelte';
    import Modal from '@/components/ui/Modal.svelte';
    import type { Customer } from '@/types';

    let customers: Customer[] = [];
    let loading = true;
    let search = '';
    let showForm = false;

    // Form
    let formName = '';
    let formPhone = '';
    let formEmail = '';
    let formAddress = '';
    let formNotes = '';
    let submitting = false;
    let fieldErrors: Record<string, string[]> = {};

    async function load(): Promise<void> {
        loading = true;
        try {
            const res = await fetchCustomers({ search: search || undefined, per_page: 50 });
            customers = res.data;
        } catch (e) {
            toast.error((e as Error).message);
            customers = [];
        } finally {
            loading = false;
        }
    }

    onMount(load);

    async function handleSearch(): Promise<void> {
        await load();
    }

    function openForm(): void {
        formName = '';
        formPhone = '';
        formEmail = '';
        formAddress = '';
        formNotes = '';
        fieldErrors = {};
        showForm = true;
    }

    async function submit(): Promise<void> {
        if (!formName.trim() || !formPhone.trim()) {
            toast.error('Nama dan No. WhatsApp wajib diisi.');
            return;
        }
        submitting = true;
        fieldErrors = {};
        try {
            await createCustomer({
                name: formName.trim(),
                phone_wa: formPhone.trim(),
                email: formEmail.trim() || undefined,
                address: formAddress.trim() || undefined,
                notes: formNotes.trim() || undefined,
            });
            toast.success('Customer berhasil disimpan.');
            showForm = false;
            await load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) {
                fieldErrors = err.errors;
            }
            toast.error(err.message ?? 'Gagal menyimpan customer.');
        } finally {
            submitting = false;
        }
    }

    function phoneDisplay(c: Customer): string {
        return c.phone_wa_international || c.phone_wa;
    }
</script>

<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-slate-900">Customer</h2>
        <Button type="button" onclick={openForm} size="sm">+ Tambah</Button>
    </div>

    <div class="flex items-center gap-2">
        <input
            type="search"
            bind:value={search}
            onkeydown={(e) => e.key === 'Enter' && handleSearch()}
            placeholder="Cari nama / nomor WhatsApp…"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
        />
        <Button type="button" variant="secondary" size="sm" onclick={handleSearch}>Cari</Button>
    </div>

    {#if loading}
        <div class="py-8 text-center text-sm text-slate-400">Memuat…</div>
    {:else if customers.length === 0}
        <EmptyState
            icon="👥"
            title="Belum ada customer"
            message="Tambah customer untuk mulai menerima order."
        >
            <Button type="button" onclick={openForm}>+ Tambah Customer</Button>
        </EmptyState>
    {:else}
        <div class="space-y-3">
            {#each customers as c}
                <Card>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-semibold text-slate-900">{c.name}</p>
                            <p class="text-xs text-slate-500">{phoneDisplay(c)}</p>
                            {#if c.order_count}
                                <p class="mt-1 text-xs text-slate-400">{c.order_count} order</p>
                            {/if}
                        </div>
                        <button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-600">
                            Buat Order
                        </button>
                    </div>
                </Card>
            {/each}
        </div>
    {/if}

    <Modal open={showForm} title="Tambah Customer" onClose={() => (showForm = false)}>
        <div class="space-y-3">
            <Field
                label="Nama"
                bind:value={formName}
                placeholder="Nama lengkap"
                error={fieldErrors.name?.[0]}
            />
            <Field
                label="No. WhatsApp"
                type="tel"
                inputmode="tel"
                bind:value={formPhone}
                placeholder="08xx…"
                error={fieldErrors.phone_wa?.[0]}
                hint="Format 08xx / +62 — otomatis dinormalisasi."
            />
            <Field
                label="Email (opsional)"
                type="email"
                bind:value={formEmail}
                error={fieldErrors.email?.[0]}
            />
            <Field
                label="Alamat (opsional)"
                bind:value={formAddress}
                error={fieldErrors.address?.[0]}
            />
            <label class="block">
                <span class="mb-1 block text-sm font-medium text-slate-700">Catatan (opsional)</span>
                <textarea
                    bind:value={formNotes}
                    rows="2"
                    class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100"
                ></textarea>
            </label>
            <Button type="button" onclick={submit} full loading={submitting}>Simpan</Button>
        </div>
    </Modal>
</div>
