<script lang="ts">
    import { onMount } from 'svelte';
    import { navigate } from '@/lib/router';
    import { logout, user } from '@/lib/auth';
    import Button from '@/components/ui/Button.svelte';
    import Card from '@/components/ui/Card.svelte';

    let loggingOut = false;

    onMount(() => {
        if (!$user) navigate('/login', { replace: true });
    });

    async function signOut(): Promise<void> {
        loggingOut = true;
        await logout();
    }
</script>

<div class="space-y-4">
    <h2 class="text-lg font-bold text-slate-900">Profil</h2>

    <Card title="Akun">
        {#if $user}
            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-600 text-xl font-bold text-white">
                    {$user.name.charAt(0).toUpperCase()}
                </span>
                <div>
                    <p class="font-semibold text-slate-900">{$user.name}</p>
                    <p class="text-sm text-slate-500">{$user.email}</p>
                    {#if $user.phone_wa}<p class="text-xs text-slate-400">{$user.phone_wa}</p>{/if}
                </div>
            </div>
        {/if}
    </Card>

    <Button type="button" variant="danger" onclick={signOut} full loading={loggingOut}>
        Keluar
    </Button>
</div>