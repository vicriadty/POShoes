# ERD / Data Model Design — POShoes

Revisi terhadap skema inti PRD §8, dengan penyesuaian yang ditetapkan pada
[ADR-0001](0001-mvp-order-and-config-decisions.md). Fokus: pemisahan antara `shoe_items`
(fisik sepatu) dan `service_order_items` (layanan), serta relasi antara keduanya.

> Konvensi: nominal uang disimpan sebagai **integer rupiah**. Semua tabel status/audit
> memiliki `created_at`/`updated_at`. Primary key numerik atau UUID ditentukan saat
> implementasi; diagram di bawah menggunakan konsep.

## Entitas Inti

```text
users ─┬─ roles (pivot via permission)
       │
       ├─ branches
       └─ workstations (milik branch)

branches 1──N service_orders
users    1──N service_orders (received_by, dsb.)

customers 1──N service_orders
service_categories 1──N service_catalogs
service_catalogs 1──N service_order_items (snapshot)

service_orders 1──N service_order_items
service_orders 1──N shoe_items
service_orders 1──N payments
service_orders 1──N service_order_status_histories
service_orders 1──N whatsapp_messages
service_orders 1──1 invoices (konsep; pembayaran dapat banyak, invoice agregat)

shoe_items 1──N shoe_photos
service_order_items N──N service_materials (bahan standar)
```

## Relasi Kunci: `shoe_items` ↔ `service_order_items`

Ini adalah titik yang menjadi **gap di PRD** dan diperjelas di ADR-0001 (D1). Satu pasang
sepatu dapat dirawat oleh satu atau lebih layanan, dan satu layanan dapat diterapkan ke satu
atau lebih sepatu.

### Opsi yang dipilih untuk MVP: pivot eksplisit

Karena sebuah layanan (mis. *Deep Cleaning*) dapat dikerjakan terhadap lebih dari satu sepatu
dalam satu order, dan satu sepatu dapat menerima lebih dari satu layanan, model hubungan
adalah **many-to-many antara `shoe_items` dan `service_order_items`** — bukan hanya dua foreign
key di salah satu sisi.

```text
shoe_items 1──N order_item_shoes N──1 service_order_items
```

`order_item_shoes` adalah pivot yang merekam pemetaan, membawa kolom yang bergantung pada
kombinasi (mis. `quantity`, `notes`).

```text
order_item_shoes
  id
  service_order_item_id
  shoe_item_id
  quantity          -- jumlah pasang pada kombinasi ini; default 1
  notes
  created_at
  updated_at
```

**Alasan memilih pivot, bukan FK tunggal:**

- `service_order_items.unit_price` dan `estimated_duration_minutes` adalah sifat dari *layanan*,
  bukan dari *sepatu*. Menempatkannya di sisi sepatu akan mengulang data per sepatu.
- `shoe_photos.type` (before/during/after) lebih cocok menempel pada sepatu, bukan pada layanan
  (lihat §7.7 — foto during menyertai pengerjaan, foto before menyertai penerimaan).
- Dengan pivot, dapat dihitung "total pasang sepatu per layanan" dan "total layanan per sepatu"
  tanpa denormalisasi.

> **Catatan keputusan:** ADR-0001 (D1) mengarahkan "1 sepatu = 1+ item layanan". Opsi pivot
> di atas merupakan pengejawantahan yang **mendukung** hal itu sejak awal, sehingga menutup
> risiko yang dicatat di ADR (perlunya pivot di kemudian hari). Jika ternyata diputuskan bahwa
> MVP hanya butuh 1 layanan per sepatu, turunkan ke `service_order_item_id` di `shoe_items`;
> biarkan MVP dengan pivot agar tidak perlu remediasi skema.

## Tabel Inti (kolom inti)

### IAM & organisasi

- `users` — id, name, email, password, role, branch_id, phone.
- `roles` / `permissions` — role/permission via `spatie/laravel-permission`.
- `branches` — id, name, code, address, phone.
- `workstations` — id, branch_id, name, code.

### Master

- `customers` — id, name, phone_wa (unique, ternormalisasi internasional), email?, address,
  notes, communication_consent_at?, created_by.
  - **Unique constraint:** `phone_wa` unik pada pelanggan **aktif** (ADR-0001 D6).
    Implementasi: kolom `phone_wa` + `phone_wa_normalized`; gunakan index unik, dan jika soft
    delete dipakai, sertakan guard `deleted_at IS NULL` di level aplikasi + atur milik indeks.
- `service_categories` — id, name, code, active.
- `service_catalogs` — id, code, category_id, name, description, base_price, estimated_duration_minutes,
  requires_before_after_photo, active.
- `service_materials` — id, service_catalog_id, stock_item_id, quantity.

### Order

- `service_orders` — id, order_number (unique), customer_id, branch_id, received_by,
  status, received_at, estimated_completed_at, completed_at,
  subtotal, discount_amount, tax_amount, total_amount, paid_amount, remaining_amount,
  customer_notes, internal_notes.
- `service_order_items` — id, service_order_id, service_catalog_id, service_name (snapshot),
  quantity, unit_price (snapshot final), discount_amount, subtotal, estimated_duration_minutes,
  status, notes, assigned_to, price_approved_by, price_approved_at.
- `shoe_items` — id, service_order_id, brand, model, color, size, material,
  condition_summary, customer_description, internal_description.
- `shoe_photos` — id, service_order_id, shoe_item_id, type (before/during/after), file_path,
  mime_type, file_size, captured_by.
- `shoe_conditions` — id, shoe_item_id, area, defect_type, severity, notes, photo_id?.
- `order_item_shoes` — pivot, lihat bagian Relasi Kunci.
- `service_order_status_histories` — id, service_order_id, service_order_item_id, from_status,
  to_status, reason, changed_by, created_at. (immutable)

### Pembayaran & invoice

- `payments` — id, service_order_id, payment_number, method, amount, received_at,
  received_by, references, refunded_amount, voided_at, voided_by.
- `invoices` — id, invoice_number, service_order_id, issued_at, due_at?, status.
- `invoice_items` — snapshot item invoice.

### Inventory

- `stock_items` — id, name, sku, unit, min_stock, active.
- `stock_balances` — id, stock_item_id, branch_id, quantity.
- `stock_movements` — id, stock_item_id, branch_id, type, quantity (delta), reference_type,
  reference_id, occurred_at, created_by. (immutable ledger)
- `stock_usages` — id, service_order_id, service_order_item_id?, stock_item_id, quantity, by.
- `stock_adjustments` — id, stock_adjustment_no, reason, adjusted_at, adjusted_by.
- `stock_adjustment_items` — id, stock_adjustment_id, stock_item_id, quantity.

### Messaging & audit

- `whatsapp_messages` — id, customer_id, service_order_id, message_type, template_name,
  recipient_phone, provider_message_id, status, payload_hash, sent_at, delivered_at, read_at,
  failed_at, error_code, error_message. (lihat ADR D11)
- `notifications` — notifikasi internal user.
- `audit_logs` — event log aktivitas kritis (void, refund, price change, stock adjustment,
  role change) via `spatie/laravel-activitylog`.

## Aturan Lingkup (referenced)

- Nomor WhatsApp unik per pelanggan aktif (ADR D6).
- Stok tidak boleh negatif — diverifikasi saat mencatat `stock_movements` (ADR D8, lihat
  [business-rules](business-rules.md)).
- Snapshot harga pada `service_order_items.unit_price` (ADR D2, D10; lihat
  [business-rules](business-rules.md)).
- Guard pickup: `remaining_amount == 0` (ADR D4; lihat
  [order-state-machine](order-state-machine.md)).
