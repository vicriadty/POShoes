<script lang="ts">
    import { onMount } from 'svelte';
    import { navigate } from '@/lib/router';
    import { login, isAuthenticated } from '@/lib/auth';
    import { ApiError } from '@/lib/api';
    import Button from '@/components/ui/Button.svelte';
    import Field from '@/components/ui/Field.svelte';

    let email = '';
    let password = '';
    let error = '';
    let loading = false;

    onMount(() => {
        if ($isAuthenticated) navigate('/', { replace: true });
    });

    async function submit(): Promise<void> {
        if (loading) return;
        error = '';
        loading = true;
        try {
            await login(email, password);
            // Rute terproteksi akan me-render shell.
        } catch (e) {
            if (e instanceof ApiError) {
                error = e.message;
            } else {
                error = 'Terjadi kesalahan tak terduga.';
            }
        } finally {
            loading = false;
        }
    }

    function handleSubmit(e: SubmitEvent): void {
        e.preventDefault();
        submit();
    }
</script>

{#if $isAuthenticated}
    <!-- guard di App akan me-redirect ke / -->
{/if}

<div class="flex min-h-dvh flex-col justify-center bg-slate-50 px-5">
    <div class="mx-auto w-full max-w-sm">
        <div class="mb-6 flex justify-center">
            <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-brand-600 text-2xl font-bold text-white">
                👟
            </span>
        </div>
        <h1 class="mb-1 text-center text-2xl font-bold text-slate-900">POShoes</h1>
        <p class="mb-8 text-center text-sm text-slate-500">Masuk untuk mengelola order dan layanan</p>

        <form class="space-y-4" onsubmit={handleSubmit}>
            <Field label="Email" type="email" bind:value={email} placeholder="kasir@poshoes.test" required />
            <Field label="Password" type="password" bind:value={password} placeholder="••••••••" required />

            {#if error}
                <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{error}</p>
            {/if}

            <Button type="submit" full loading={loading}>
                Masuk
            </Button>
        </form>
    </div>
</div>