<script lang="ts">
    import { onMount } from 'svelte';
    import type { Component } from 'svelte';
    import { isAuthenticated } from '@/lib/auth';
    import { location, navigate, parsePath } from '@/lib/router';
    import LoginPage from '@/components/pages/LoginPage.svelte';
    import DashboardPage from '@/components/pages/DashboardPage.svelte';
    import OrderListPage from '@/components/pages/OrderListPage.svelte';
    import OrderCreatePage from '@/components/pages/OrderCreatePage.svelte';
    import OrderDetailPage from '@/components/pages/OrderDetailPage.svelte';
    import CustomerPage from '@/components/pages/CustomerPage.svelte';
    import ProfilePage from '@/components/pages/ProfilePage.svelte';
    import AppShell from '@/components/layout/AppShell.svelte';
    import OfflineBanner from '@/components/layout/OfflineBanner.svelte';

    interface RouteResult {
        component: Component;
        id?: string;
        isDraft?: boolean;
    }

    const authed = isAuthenticated;

    onMount(() => {
        const unsub = authed.subscribe((a) => {
            const path = window.location.pathname;
            if (!a && path !== '/login') {
                navigate('/login', { replace: true });
            } else if (a && path === '/login') {
                navigate('/', { replace: true });
            }
        });
        return unsub;
    });

    $: route = getRoute($location);

    function getRoute(path: string): RouteResult {
        if (!$authed) return { component: LoginPage };
        const { base, rest } = parsePath(path);
        switch (base) {
            case 'login':
                return { component: LoginPage };
            case 'orders': {
                const [action] = rest;
                if (action === 'new') return { component: OrderCreatePage };
                if (action === 'draft') return { component: OrderDetailPage, id: rest[1], isDraft: true };
                if (action) return { component: OrderDetailPage, id: action };
                return { component: OrderListPage };
            }
            case 'customers':
                return { component: CustomerPage };
            case 'profile':
                return { component: ProfilePage };
            default:
                return { component: DashboardPage };
        }
    }
</script>

<OfflineBanner />
{#if $authed}
    <AppShell>
        <svelte:component this={route.component} id={route.id} isDraft={route.isDraft} />
    </AppShell>
{:else}
    <LoginPage />
{/if}