<script lang="ts">
    import { isAuthenticated } from '@/lib/auth';
    import Toast from '@/components/ui/Toast.svelte';
    import TopBar from './TopBar.svelte';
    import BottomNav from './BottomNav.svelte';

    $: authed = $isAuthenticated;
</script>

<div class="flex h-full flex-col bg-slate-50">
    <TopBar />
    <main class="flex-1 overflow-y-auto px-4 pb-24 pt-4">
        <slot />
    </main>
    <BottomNav />
    <Toast />
</div>