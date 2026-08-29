<script lang="ts">
    export let title = '';
    export let subtitle = '';
    export let padding = true;
</script>

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    {#if title}
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="font-semibold text-slate-900">{title}</h3>
                {#if subtitle}<p class="text-sm text-slate-500">{subtitle}</p>{/if}
            </div>
            <slot name="actions" />
        </div>
    {/if}
    <div class="{padding ? 'p-5' : ''}">
        <slot />
    </div>
</div>