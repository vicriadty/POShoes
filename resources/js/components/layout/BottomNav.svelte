<script lang="ts">
    import Link from '@/components/ui/Link.svelte';
    import { location } from '@/lib/router';

    const items = [
        { to: '/', label: 'Dashboard', icon: '🏠' },
        { to: '/orders', label: 'Order', icon: '📋' },
        { to: '/profile', label: 'Akun', icon: '👤' },
    ];

    $: path = $location;
</script>

<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white pb-[env(safe-area-inset-bottom)]">
    <div class="mx-auto flex max-w-lg items-stretch justify-around">
        {#each items as item}
            <Link
                to={item.to}
                class="flex flex-1 flex-col items-center gap-0.5 py-2.5 text-xs {path === item.to ||
                (item.to !== '/' && path.startsWith(item.to))
                    ? 'text-brand-600 font-medium'
                    : 'text-slate-500'}"
            >
                <span class="text-lg leading-none">{item.icon}</span>
                <span>{item.label}</span>
            </Link>
        {/each}
    </div>
</nav>