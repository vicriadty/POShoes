# Business Rules — POShoes

Aturan bisnis pendukung yang ditetapkan pada ADR-0001. Dokumen ini berfokus pada
**snapshot harga** (D2, D10) dan **kebijakan stok non-negatif** (D8). Aturan status order
ada di [order-state-machine](order-state-machine.md).

## 1. Snapshot harga pada `service_order_items.unit_price`

Tujuan: perubahan harga pada master service di masa depan tidak mengubah transaksi lama
(PRD §204). Diterapkan saat **penerimaan** lalu menjadi **final saat inspeksi/approval**
(ADR D2).

### Alur

1. **Estimasi saat penerimaan:** kasir memilih layanan dari `service_catalogs`. Harganya
   di-snapshot ke `service_order_items.unit_price` pada saat itu. Nilai ini bersifat estimasi.
2. **Selama inspeksi:** bila hasil inspeksi menyimpang dari estimasi, item masuk ke
   `waiting_approval` pada tingkat order. Delta harga dicatat.
3. **Approval:** saat transisi `waiting_approval` → `approved` (ADR D5), nilai
   `unit_price` yang baru di-snapshot ulang, dan diiringi `price_approved_by` +
   `price_approved_at`. Nilai lama tidak ditimpa tanpa audit (lihat §3).

### Aturan snapshot

- `service_order_items.unit_price` selalu mereferensikan **nilai pada saat snapshot**, bukan
  query ke master saat order dibuka kembali.
- Setiap perubahan nilai wajib mencatat baris audit (`audit_logs`) berisi `from`, `to`,
  `changed_by`, `reason`, `service_order_id`, `service_order_item_id`.
- Bila tidak ada delta (hasil inspeksi cocok), `unit_price` tetap, dan tidak perlu approval —
  transisi langsung `inspection` → `approved`.

### Konsekuensi pada total order

- `subtotal` order = Σ `service_order_items.subtotal` (memakai `unit_price` snapshot).
- `discount_amount`, `tax_amount`, `total_amount`, `paid_amount`, `remaining_amount` dihitung
  ulang dari snapshot saat status berubah dari estimasi ke final.
- `remaining_amount = total_amount - paid_amount` dan dipakai sebagai guard pickup (ADR D4).

### Format & presisi

- Semua nominal: **integer rupiah** (PRD §267). Tidak ada floating point.
- Nilai disimpan tanpa desimal; konversi unit display berada di layer presentasi.

## 2. Kebijakan stok: saldo non-negatif (hard)

Tujuan: saldo stok tidak boleh negatif pada MVP (ADR D8). Aturan keras, bukan configurable.

### Definisi

- `stock_balances.quantity` tidak boleh menjadi negatif.
- Saldo adalah turunan dari ledger `stock_movements` (immutable). Setiap `stock_movement`
  mencatat delta (`quantity` bertanda +/−), `type`, `reference_type`, `reference_id`,
  `occurred_at`, `created_by`.

### Aturan penting (ambil dari PRD §296)

- Bahan **tidak otomatis berkurang** saat layanan dipilih. Pengurangan terjadi saat
  **pemakaian dikonfirmasi** oleh teknisi/admin, dicatat sebagai `stock_usages`
  (yang menimbulkan `stock_movement` negatif).
- Konsekuensinya: memilih layanan tidak menulis `stock_usages`. Entri `stock_usages` adalah
  aksi eksplisit oleh teknisi/admin.

### Guard & locking

- Saat mencatat `stock_movement` negatif, saldo diverifikasi dalam **transaction database** dan
  menggunakan **row lock** (PRD §610): `SELECT ... FOR UPDATE` pada `stock_balances`.
- Jika `quantity + delta < 0` → tolak (throw exception / reject), tidak ada saldo negatif.
- Penyesuaian (adjustment) yang akan menghasilkan nilai < 0 juga ditolak kecuali memerlukan
  prosedur khusus — pada MVP dibiarkan ditolak; teknisi/admin melakukan stock opname.

### Status bahan menunggu

- Bila bahan tidak cukup saat item produksi, item layanan pindah ke `waiting_material`
  (lihat order-state-machine). Ini tidak menulis `stock_usage` sampai bahan benar-benar
  digunakan (dikonfirmasi). Kebijakan stok non-negatif berarti menunggu restock/admin decision
  alih-alih membiarkan saldo turun di bawah nol.

### Audit

- Setiap mutasi stok (masuk, pemakaian, adjustment) dicatat di `audit_logs` dan menjadi
  bagian riwayat `stock_movements`. Penyesuaian stok dan pemakaian wajib memiliki
  `created_by` dan `reference`.

## 3. Aturan lintas yang terkait

- **Pembayaran & pickup:** `remaining_amount == 0` sebelum `picked_up` (ADR D4,
  order-state-machine).
- **Nomor WhatsApp unik:** satu nomor = satu customer aktif (ADR D6, er-design).
- **Approval harga tercatat di sistem:** transisi `waiting_approval` → `approved` selalu
  punya `approved_by`/`approved_at`, tidak bergantung pada status WhatsApp (ADR D5).
- **1 order = beberapa sepatu, 1 sepatu = 1+ layanan:** relasi via pivot `order_item_shoes`
  (ADR D1, er-design).
