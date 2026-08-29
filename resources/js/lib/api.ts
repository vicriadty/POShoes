/**
 * API client untuk POShoes SPA.
 *
 * - Mengirim header Authorization: Bearer <token>.
 * - Mengurai envelope response `{ data, meta }` dari backend.
 * - Error API distandarkan menjadi pesan + status.
 * - HTTP 401 (unauthenticated) memicu callback `onUnauthorized` (logout).
 */

export interface ApiErrorBody {
    message?: string;
    errors?: Record<string, string[]>;
    exception?: string;
}

export class ApiError extends Error {
    status: number;
    errors: Record<string, string[]> | undefined;

    constructor(status: number, message: string, errors?: Record<string, string[]>) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

interface RequestOptions extends Omit<RequestInit, 'body'> {
    body?: unknown;
    token?: string | null;
}

const BASE_URL = '/api/v1';

let onUnauthorizedHandler: () => void = () => {};

export function setOnUnauthorized(handler: () => void): void {
    onUnauthorizedHandler = handler;
}

async function request<T>(method: string, path: string, { body, token, ...rest }: RequestOptions = {}): Promise<T> {
    const headers = new Headers(rest.headers);
    headers.set('Accept', 'application/json');

    if (body !== undefined) {
        headers.set('Content-Type', 'application/json');
    }
    if (token) {
        headers.set('Authorization', `Bearer ${token}`);
    }

    const init: RequestInit = { ...rest, method, headers };
    if (body !== undefined) {
        init.body = JSON.stringify(body);
    }

    const response = await fetch(`${BASE_URL}${path}`, init);
    const data = await response.json().catch(() => null);

    if (response.status === 401) {
        onUnauthorizedHandler();
    }

    if (!response.ok) {
        const errorBody = data as ApiErrorBody | null;
        throw new ApiError(response.status, errorBody?.message ?? 'Terjadi kesalahan.', errorBody?.errors);
    }

    return data as T;
}

export const api = {
    get: <T>(path: string, opts?: RequestOptions) => request<T>('GET', path, opts),

    post: <T>(path: string, body?: unknown, opts?: RequestOptions) =>
        request<T>('POST', path, { ...opts, body }),

    put: <T>(path: string, body?: unknown, opts?: RequestOptions) =>
        request<T>('PUT', path, { ...opts, body }),

    patch: <T>(path: string, body?: unknown, opts?: RequestOptions) =>
        request<T>('PATCH', path, { ...opts, body }),

    delete: <T>(path: string, opts?: RequestOptions) => request<T>('DELETE', path, opts),
};