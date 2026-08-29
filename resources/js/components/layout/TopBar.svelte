<script lang="ts">
    import { user } from '@/lib/auth';

    $: name = $user?.name ?? 'POShoes';
</script>

<header class="sticky top-0 z-30 border-b border-slate-200 bg-white px-4 py-3">
    <div class="mx-auto flex max-w-lg items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white">
                {name.charAt(0).toUpperCase()}
            </span>
            <div>
                <h1 class="text-sm font-semibold text-slate-900">POShoes</h1>
                <p class="text-xs text-slate-500">{name}</p>
            </div>
        </div>
        <button type="button" class="text-slate-400 hover:text-slate-600" aria-label="Info">
            <span class="sr-only">Info</span>⋮
        </button>
    </div>
</header>