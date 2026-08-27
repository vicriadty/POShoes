# Order & Item State Machine — POShoes

Definisi transisi status order dan item layanan, trigger aksi, guard, dan policy.
Selaras dengan ADR-0001 (D2 approval harga, D4 lunas-before-pickup, D5 approval tercatat di sistem).

## Status Order (tingkat order)

```text
draft → received → inspection → waiting_approval → approved → in_progress
      → quality_check → ready_for_pickup → picked_up
      ↘ cancelled
```

Setiap transisi menulis baris immutable ke `service_order_status_histories`
(`from_status`, `to_status`, `reason`, `changed_by`). Status order tidak boleh diubah langsung
tanpa state transition tervalidasi (PRD §16.6) dan diperiksa oleh policy.

### Matriks transisi & guard

| From | To | Trigger aksi | Guard / Policy |
|---|---|---|---|
| (any) | `draft` | create order | order punya ≥1 `service_order_items` |
| `draft` | `received` | kasir konfirmasi/publish | minim 1 item layanan (bukan draft), foto before ada bila `requires_before_after_photo` |
| `received` | `inspection` | kasir/teknisi mulai inspeksi | — |
| `inspection` | `waiting_approval` | perubahan harga/material ditemukan | harga final < estimasi OR perubahan material → wajib approval bila beda dari estimasi |
| `inspection` | `approved` | hasil inspeksi cocok dengan estimasi | tidak ada delta harga |
| `waiting_approval` | `approved` | approval dicatat di sistem | `price_approved_by` + `price_approved_at` terisi (ADR D5; WA bukan syarat) |
| `waiting_approval` | `inspection` | customer menolak / revisi | reason wajib |
| `approved` | `in_progress` | assignment + mulai kerja | ada `work_assignment` |
| `in_progress` | `quality_check` | teknisi kirim ke QC | semua item produksi selesai / menunggu QC |
| `quality_check` | `in_progress` | tidak lolos QC (rework) | reason wajib |
| `quality_check` | `ready_for_pickup` | lolos QC | tidak ada item belum selesai |
| `ready_for_pickup` | `picked_up` | pickup oleh customer | **`remaining_amount == 0` (ADR D4, hard rule)** |
| (any, belum `picked_up`) | `cancelled` | pembatalan | reason wajib; alur refund bila sudah ada payment |

> **Guard pickup (D4):** `picked_up` hanya diizinkan bila `remaining_amount == 0`.
> Bukan switch — aturan keras pada MVP. Aksi ini diverifikasi ulang di dalam database
> transaction untuk mencegah race condition (pembayaran dan pickup bersamaan).

## Status Item Layanan (tingkat item)

```text
pending → in_progress → waiting_material → in_progress → quality_check → completed
   ↘ cancelled
```

| From | To | Trigger | Guard |
|---|---|---|---|
| `pending` | `in_progress` | teknisi mulai item | item di-assign ke teknisi |
| `in_progress` | `waiting_material` | bahan tidak tersedia | — |
| `waiting_material` | `in_progress` | bahan tersedia | stok tersedia (kebijakan stok non-negatif) |
| `in_progress` | `quality_check` | item selesai dikerjakan | catatan kerja + foto during wajib bila dikonfigurasi |
| `quality_check` | `in_progress` | rework | reason wajib |
| `quality_check` | `completed` | QC lolos | — |
| (any, belum `completed`) | `cancelled` | pembatalan item | reason wajib |

## Agregasi status order dari item

Status order adalah **agregasi** dari status item (PRD §496, §830), tapi perbaikan status manual
tetap melewati policy.

- Semua item = `completed` dan QC lolos → order ke `ready_for_pickup`.
- Ada item `waiting_material` → order `in_progress` (atau `waiting_material` bila semua).
- Item `pending` belum pernah dimulai → order tetap `in_progress` setelah `approved`.
- `inspection`/`waiting_approval`/`approved` bersifat gate manual sebelum produksi dimulai.

> Aturan: agregasi menentukan status **target yang valid**, tetapi transisi tetap harus
> melewati state machine agar menghasilkan audit trail yang benar. Jangan set status order
> secara langsung dari pemantauan item tanpa melalui transition handler.

## Model implementasi (referensi Laravel)

- Enum status: `OrderStatus`, `ItemStatus`.
- Service class per domain, mis. `ServiceOrders\Actions\TransitionOrderStatus` yang:
  1. memvalidasi transisi ada di matriks,
  2. mengecek guard (mis. `remaining_amount == 0`),
  3. menulis `service_order_status_histories`,
  4. berjalan dalam database transaction,
  5. check policy/permission.

Status `draft`, `received`, dan foto before dijelaskan lebih lanjut pada
[business-rules](business-rules.md) dan [er-design](er-design.md).
