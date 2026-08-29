<script lang="ts">
    import { navigate } from '@/lib/router';

    export let to: string;
    export let href = to;

    function handleClick(e: MouseEvent): void {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return;
        e.preventDefault();
        navigate(to);
    }
</script>

<a {href} onclick={handleClick} {...$$restProps}>
    <slot />
</a>