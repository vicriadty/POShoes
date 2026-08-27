# API Convention — POShoes

Konvensi konsisten untuk seluruh endpoint `/api/v1` (PRD §10). Tujuan: keseragaman response,
error, resource, authorization, dan pagination agar mudah dikonsumsi Svelte PWA dan audited.

## Prefix & versioning

- Base path: `/api/v1`.
- Endpoint API `Spa` digrupkan dengan middleware `auth:sanctum`.
- Webhook tidak masuk auth user biasa (PRD §545).

## Response envelope

Sukses:

```json
{
  "data": { ... }
}
```

Koleksi (paginated):

```json
{
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 50,
    "last_page": 4
  }
}
```

Kustom:

- Aksi tanpa body (mis. `POST /status`): 200 dengan `{ "data": { "status": "..." } }` atau 204.
- Validasi: 422 dengan `{ "message": "...", "errors": { "field": ["..."] } }`.
- Error umum: `{ "message": "..." }`.

## Kode status

| Kode | Digunakan |
|---|---|
| 200 | success |
| 201 | resource created |
| 204 | no content (action success) |
| 401 | unauthenticated |
| 403 | forbidden (policy) |
| 404 | not found |
| 409 | conflict (state machine invalid, stock negative) |
| 422 | validation error |
| 500 | server error (via handler, redacted di production) |

> Gunakan exception `Dom\\DomainException` & `ValidationException` untuk status 409/422
> agar ditangani terpusat.

## Resource naming & serialization

- Naming field: `snake_case`, waktu `ISO-8601` (UTC), uang `integer` rupiah.
- Sedikit field, `type` eksplisit: `id`, `type`, `attributes` OR flat `snake_case` —
  **dipilih flat `snake_case`** (lebih sederhana untuk Svelte; tidak butuh JSON:API penuh).

## Authorization

- Setiap endpoint sensitif dideklarasikan `can:permission` di route, dan/atau through
  Policy di controller/action.
- Policy untuk resource: `viewAny`, `view`, `create`, `update`, `delete`, plus aksi domain
  (`approve`, `changeStatus`, `assign`, `pickup`, `cancel`, `void`, `refund`, `adjustStock`).

## Pagination

- Query: `?page=1&per_page=15` (default 15, max 100).
- Endpoint daftar (customer, service, order, inventory) selalu paginated.
- Endpoint laporan besar menggunakan `per_page` dan export via queue.

## Validasi & request

- Form Request per domain (`app/Http/Requests`).
- Aturan khusus:
  - `phone_wa` dinormalisasi & unique di antara customer aktif (ADR D6).
  - `order_number` / `invoice_number` unique & machine-readable (PRD §488).
  - Total uang dihitung server-side; jangan percaya nilai frontend.
  - Transisi status divalidasi terhadap state machine (409 bila invalid).

## Errors

- Form Request: `{ "message": "The given data was invalid.", "errors": { ... } }`.
- Domain: `{ "message": "Order cannot be picked up: remaining balance.", "code": "..." }`.
- Gunakan `App\Support\ApiResponse` trait/helper agar konsisten.

## Webhook

- `POST /api/v1/webhooks/whatsapp` — tanpa `auth:sanctum`; verifikasi signature provider,
  idempotency via `provider_message_id`, logging aman (PRD §545, §500).
