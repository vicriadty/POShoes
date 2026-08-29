/**
 * Router ringan untuk SPA (tanpa dependensi pihak ketiga).
 *
 * Memakai `window.history` (pushState/popstate) + Svelte store agar kompatibel
 * dengan Svelte 5. Navigasi imperatif via `navigate()`; pembacaan path via `$location`.
 */

import { writable } from 'svelte/store';

function currentPath(): string {
    return window.location.pathname + window.location.search;
}

export const location = writable<string>(currentPath());

export function navigate(to: string, opts: { replace?: boolean } = {}): void {
    const path = to.startsWith('/') ? to : `/${to}`;
    if (opts.replace) {
        window.history.replaceState({}, '', path);
    } else {
        window.history.pushState({}, '', path);
    }
    location.set(path);
}

if (typeof window !== 'undefined') {
    window.addEventListener('popstate', () => location.set(currentPath()));
    window.addEventListener('click', () => {
        // pastikan location sinkron bila terjadi reload partial
    });
}

/**
 * Parse path `/orders/123` menjadi `{ base, params }`.
 * `base` adalah token pertama (slug halaman).
 */
export function parsePath(path: string): { base: string; rest: string[] } {
    const clean = path.split('?')[0].replace(/^\/+|\/+$/g, '');
    const parts = clean.split('/').filter(Boolean);
    const [base = '', ...rest] = parts;
    return { base, rest };
}