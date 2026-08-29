/**
 * Auth state & persistence untuk SPA.
 *
 * Token Bearer dari Phase 1 disimpan di localStorage agar bertahan di PWA.
 * `isInitialized` menandakan usaha validasi `/me` selesai.
 */

import { writable, type Writable } from 'svelte/store';
import { api, setOnUnauthorized } from './api';

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    branch_id: number | null;
    phone_wa: string | null;
}

interface LoginResponse {
    data: {
        user: AuthUser;
        token: string;
        token_type: string;
    };
}

interface MeResponse {
    data: AuthUser;
}

const TOKEN_KEY = 'poshoes_token';
const USER_KEY = 'poshoes_user';

export const token: Writable<string | null> = writable(localStorage.getItem(TOKEN_KEY));
export const user: Writable<AuthUser | null> = writable(loadUser());
export const isAuthenticated: Writable<boolean> = writable(Boolean(localStorage.getItem(TOKEN_KEY)));
export const isInitialized: Writable<boolean> = writable(false);
export const bootError: Writable<string | null> = writable(null);

function loadUser(): AuthUser | null {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as AuthUser;
    } catch {
        return null;
    }
}

function persistToken(value: string | null): void {
    if (value) {
        localStorage.setItem(TOKEN_KEY, value);
    } else {
        localStorage.removeItem(TOKEN_KEY);
    }
}

function persistUser(value: AuthUser | null): void {
    if (value) {
        localStorage.setItem(USER_KEY, JSON.stringify(value));
    } else {
        localStorage.removeItem(USER_KEY);
    }
}

function applyAuth(newToken: string | null, newUser: AuthUser | null): void {
    persistToken(newToken);
    persistUser(newUser);
    token.set(newToken);
    user.set(newUser);
    isAuthenticated.set(Boolean(newToken));
}

export async function login(email: string, password: string): Promise<AuthUser> {
    const res = await api.post<LoginResponse>('/auth/login', { email, password });
    applyAuth(res.data.token, res.data.user);
    return res.data.user;
}

export async function logout(): Promise<void> {
    const t = localStorage.getItem(TOKEN_KEY);
    if (t) {
        try {
            await api.post('/auth/logout', undefined, { token: t });
        } catch {
            // abaikan — tetap bersihkan lokal
        }
    }
    applyAuth(null, null);
}

export async function refreshMe(): Promise<AuthUser | null> {
    const t = localStorage.getItem(TOKEN_KEY);
    if (!t) return null;

    try {
        const res = await api.get<MeResponse>('/auth/me', { token: t });
        applyAuth(t, res.data);
        return res.data;
    } catch {
        applyAuth(null, null);
        return null;
    }
}

export function clearAuth(): void {
    applyAuth(null, null);
}

setOnUnauthorized(() => {
    applyAuth(null, null);
});