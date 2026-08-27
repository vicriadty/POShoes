# Service Catalog & Payment Methods — POShoes

Finalisasi daftar layanan default dan metode pembayaran untuk seed MVP (PRD §7.3, §7.6).
Nilai berikut di-seed sebagai `service_categories`, `service_catalogs`, dan konfigurasi
`payment_methods`.

## Layanan default

Berdasarkan contoh PRD §7.3:

| Kode | Nama | Kategori | Harga dasar* | Estimasi (menit) | Foto before/after |
|---|---|---|---|---|---|
| BC | Basic Cleaning | Cleaning | 40000 | 30 | ya |
| DC | Deep Cleaning | Cleaning | 75000 | 60 | ya |
| FC | Fast Cleaning | Cleaning | 25000 | 20 | ya |
| RP | Repaint | Repaint | 120000 | 120 | ya |
| RSL | Repair Sole | Repair | 90000 | 90 | ya |
| RST | Repair Stitching | Repair | 50000 | 60 | ya |
| GT | Ganti Tali | Add-on | 15000 | 10 | tidak |
| AD | Add-on Deodorizing | Add-on | 20000 | 15 | tidak |

> *Harga dasar untuk seed awal dan **snapshot saat penerimaan**. Nilai sebenarnya dikelola
> oleh admin dan tidak mengikat order lama (ADR D2 / business-rules: snapshot).
> Satuan rupiah integer.

> Kebutuhan bahan standar (`service_materials`) diisi saat master inventory selesai
> (Phase 6). Kolom `requires_before_after_photo` mengendalikan keharusan foto (lihat
> [order-state-machine](order-state-machine.md) — guard `received`).

## Metode pembayaran configurable

Metode pembayaran disimpan sebagai konfigurasi, dapat dinonaktifkan per toko.

| Kode | Nama | Tipe | Aktif default |
|---|---|---|---|
| cash | Tunai | manual | ya |
| transfer | Transfer Bank | manual | ya |
| qris | QRIS | manual | ya |
| card | Kartu Debit/Kredit | manual | ya |
| other | Lainnya | manual | tidak |

> MVP tidak mengimplementasikan payment-gateway otomatis (PRD §58 exclude). Semua metode
> bersifat **manual-confirm** di kasir. Gate otomatis hanya bila iterasi lanjut.

## Aturan uang & shift

- Semua nominal: **integer rupiah** (PRD §267, [business-rules](business-rules.md)).
- Pembayaran parsial (DP) dan pelunasan didukung (ADR D3 — DP opsional).
- Pickup memerlukan `remaining_amount == 0` (ADR D4, [order-state-machine](order-state-machine.md)).
- Shift kasir dasar: `cashier_shift` mencatat pembukaan/penguasaan dan rekap per shift (PRD §7.6).

## Metode penyimpanan

> Seeder awal `DatabaseSeeder` atau modul `ServiceCatalog`. Skema `service_categories` dan
> `service_catalogs` sesuai [er-design](er-design.md). `payment_methods` disimpan sebagai
> data konfigurasi (bisa tabel/config) — disarankan tabel `payment_methods` pada Phase 4.
