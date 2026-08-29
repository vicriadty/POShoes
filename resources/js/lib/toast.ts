import { writable } from 'svelte/store';

export interface Toast {
    id: number;
    type: 'success' | 'error' | 'info';
    message: string;
}

let counter = 0;

export const toasts = writable<Toast[]>([]);

function push(type: Toast['type'], message: string): void {
    const id = ++counter;
    toasts.update((list) => [...list, { id, type, message }]);
    setTimeout(() => {
        toasts.update((list) => list.filter((t) => t.id !== id));
    }, 3500);
}

export const toast = {
    success: (m: string) => push('success', m),
    error: (m: string) => push('error', m),
    info: (m: string) => push('info', m),
};