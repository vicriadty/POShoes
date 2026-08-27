# Role & Permission Matrix — POShoes

Finalisasi role dan permission granular untuk MVP (PRD §7.1, phase 1 exit criteria:
"kasir tidak dapat mengakses fitur admin"). Menggunakan `spatie/laravel-permission`.

## Role

| Role | Deskripsi | Cabang |
|---|---|---|
| `owner` | Pemilik bisnis. Akses penuh, termasuk konfigurasi toko dan master data. | semua |
| `admin` | Pengelola operasional cabang. Master data, laporan, koreksi transaksi. | satu (opsional all) |
| `kasir` | Pembuatan order, customer, pembayaran, pickup. | satu |
| `teknisi` | Work queue, perubahan status, catatan kerja, bahan, foto. | satu |

> Untuk MVP single-branch (ADR D9), `admin`/`kasir`/`teknisi` di-scope ke satu cabang.
> `owner` dapat melihat seluruh cabang.

## Permission

Format nama permission: `domain.action` (mis. `service_orders.create`).
Daftar di bawah adalah kerangka; turunan per-resource akan diturunkan saat Phase 3+.

### Autentikasi & akun

- `auth.login`, `auth.logout`, `users.view_own`

### User & role (admin/owner)

- `users.view`, `users.create`, `users.update`, `users.delete`
- `roles.view`, `roles.assign`

### Customer

- `customers.view`, `customers.create`, `customers.update`, `customers.delete`

### Service catalog

- `services.view`, `services.create`, `services.update`, `services.delete`

### Service order

- `service_orders.view`, `service_orders.create`, `service_orders.update`
- `service_orders.approve`       — approve harga
- `service_orders.change_status` — pindah status order
- `service_orders.assign`        — assign teknisi
- `service_orders.pickup`        — proses pickup
- `service_orders.cancel`        — pembatalan (plus refund)

### Payment & invoice

- `payments.create`, `payments.view`
- `payments.void` / `payments.refund` — pengecualian, butuh otorisasi admin/owner
- `invoices.view`, `invoices.send`

### Teknisi

- `work.view`          — lihat antrean
- `work.item_status`   — ubah status item (di-scope ke assignee)
- `work.notes`         — tambah catatan kerja
- `work.photos`        — upload foto during/after
- `work.material_usage` — catat pemakaian bahan

### Inventory

- `inventory.items.view`
- `inventory.adjust`   — penyesuaian
- `inventory.usage`    — pemakaian (umumnya teknisi)
- `inventory.stocktake` — stock opname

### Reporting

- `reports.view`, `reports.export`

### Messaging

- `whatsapp.view`, `whatsapp.resend`

### Audit

- `audit.view` — lihat audit log (admin/owner)

## Matriks Role × Permission (intisari)

| Permission | Owner | Admin | Kasir | Teknisi |
|---|---|---|---|---|
| users/roles manage | ✅ | ⚠️ partial | ❌ | ❌ |
| customers CRUD | ✅ | ✅ | ✅ | ❌ |
| services CRUD | ✅ | ✅ | ❌ | ❌ |
| service_orders create/update | ✅ | ✅ | ✅ | ❌ |
| service_orders approve | ✅ | ✅ | ❌ | ❌ |
| service_orders change_status | ✅ | ✅ | ⚠️ (pickup) | ⚠️ (item) |
| service_orders assign/cancel | ✅ | ✅ | ❌ | ❌ |
| payments create/view | ✅ | ✅ | ✅ | ❌ |
| payments void/refund | ✅ | ✅ | ❌ | ❌ |
| work queue & item_status | ✅ | ✅ | ❌ | ✅ (assignee) |
| materials usage | ✅ | ✅ | ❌ | ✅ |
| inventory adjust/stocktake | ✅ | ✅ | ❌ | ❌ |
| reports + export | ✅ | ✅ | ❌ | ❌ |
| whatsapp resend | ✅ | ✅ | ⚠️ | ❌ |
| audit view | ✅ | ✅ | ❌ | ❌ |

> ⚠️ partial/kondisional dijelaskan pada aturan policy di implementasi.
> `service_orders.change_status` untuk kasir dibatasi pada transisi `pickup`; untuk teknisi
> dibatasi pada status item dan transisi order yang berkaitan dengan pengerjaan (lihat
> [order-state-machine](order-state-machine.md)).

## Policy notes

- Teknisi hanya boleh mengubah status item yang **di-assign** padanya
  (selaras ADR D4/order-state-machine guard).
- Kasir tidak boleh `approve` perubahan harga (hanya admin/owner), namun kasir dapat
  menginisiasi transisi ke `waiting_approval`.
- Void/refund selalu memerlukan policy + audit event (PRD §625).
- Pembatalan memerlukan reason + user pelaksana (PRD §490, §492).
