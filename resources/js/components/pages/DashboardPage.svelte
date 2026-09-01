<script lang="ts">
    import { user } from '@/lib/auth';
    import Link from '@/components/ui/Link.svelte';
    import Card from '@/components/ui/Card.svelte';

    $: name = $user?.name ?? '';
</script>

<div class="space-y-4">
    <section class="rounded-2xl bg-brand-600 p-5 text-white">
        <p class="text-sm opacity-80">Selamat datang,</p>
        <h2 class="text-xl font-bold">{name}</h2>
        <p class="mt-1 text-sm opacity-80">Kelola layanan sepatu dari satu tempat.</p>
    </section>

    <Link
        to="/orders/new"
        class="flex items-center justify-between rounded-2xl border border-brand-200 bg-brand-50 p-5 transition hover:bg-brand-100"
    >
        <div>
            <h3 class="font-semibold text-brand-800">Buat Order Baru</h3>
            <p class="text-sm text-brand-600">Terima sepatu & catat layanan</p>
        </div>
        <span class="text-2xl">➕</span>
    </Link>

    <div class="grid grid-cols-2 gap-4">
        <Link to="/orders" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-brand-300">
            <span class="text-xl">📋</span>
            <h4 class="mt-2 font-semibold text-slate-900">Daftar Order</h4>
            <p class="text-xs text-slate-500">Lihat semua order</p>
        </Link>
        <Link to="/customers" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-brand-300">
            <span class="text-xl">👥</span>
            <h4 class="mt-2 font-semibold text-slate-900">Customer</h4>
            <p class="text-xs text-slate-500">Kelola pelanggan</p>
        </Link>
        <Link to="/shift" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-brand-300">
            <span class="text-xl">🕒</span>
            <h4 class="mt-2 font-semibold text-slate-900">Shift</h4>
            <p class="text-xs text-slate-500">Buka / tutup shift</p>
        </Link>
        <Link to="/profile" class="rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-brand-300">
            <span class="text-xl">👤</span>
            <h4 class="mt-2 font-semibold text-slate-900">Profil</h4>
            <p class="text-xs text-slate-500">Akun & keluar</p>
        </Link>
    </div>

    <Card title="Status Hari Ini">
        <p class="text-sm text-slate-500">
            Ringkasan order dan pekerjaan akan tampil di sini setelah modul order selesai (Phase 3+).
        </p>
    </Card>
</div>