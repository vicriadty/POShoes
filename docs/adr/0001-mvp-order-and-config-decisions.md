# ADR-0001 — Keputusan Order, Harga, Pembayaran, dan Scoping MVP

- Status: Draft
- Tanggal: 2026-08-26
- Terkait: PRD §18 (Keputusan yang Perlu Dikonfirmasi Sebelum Phase 3)

## Ringkasan

Merekam resolusi untuk 11 keputusan terbuka yang tercantum pada PRD §18, yang harus dikonfirmasi
sebelum implementasi Phase 3 (Customer, Service Catalog, dan Order Intake). Semua keputusan
selaras dengan prinsip PRD: modular monolith, mobile-first, satu cabang untuk MVP, penghormatan
terhadap multi-cabang pada data model, dan integrasi WhatsApp yang bersifat asynchronous.

## Konteks

Sebelum Phase 3, batasan domain beberapa aturan bisnis belum ditetapkan. Tanpa keputusan ini, ERD,
state machine order, relasi sepatu ↔ layanan, dan batasan pembayaran tidak dapat difinalisasi.
Keputusan diambil dengan bias sederhana (simplicity) dan menghindari fitur yang menambah beban
operasional sebelum benar-benar dibutuhkan.

## Keputusan

### D1 — Satu order dapat berisi beberapa pasang sepatu

**Keputusan:** YA. Satu order milik satu pelanggan dapat memuat beberapa sepatu.

- Satu baris `shoe_items` merepresentasikan satu pasang sepatu (hapus tafsir `pair_count` sebagai
  jumlah pasang pada level order; gunakan untuk "jumlah pasang" pada level item layanan bila perlu).
- Tambahkan relasi `shoe_items` ↔ `service_order_items` agar diketahui sepatu mana menerima layanan
  mana.
- Model hubungan yang dipakai MVP: **1 order = beberapa sepatu, 1 sepatu = 1+ item layanan**.
  Pivot 1 sepatu → banyak layanan dengan harga berbeda dibangun belakangan bila terbukti.

**Catatan data model:** PRD §7.5 tidak mendefinisikan tautan antara `shoe_items` dan
`service_order_items`. Tautan ini harus ditambahkan pada ERD. Keputusan ini memperjelas hal
tersebut dan menuntut penyesuaian skema sebelum Phase 3.

### D2 — Harga ditentukan saat penerimaan atau setelah inspeksi

**Keputusan:** Harga diestimasi saat penerimaan berdasarkan snapshot harga master, lalu determinasi
final terjadi pada inspeksi dengan approval bila terdapat perubahan.

- Kasir menginput estimasi saat penerimaan; nilai diambil sebagai snapshot dari master price
  (sesuai PRD "Harga pada order harus disimpan sebagai snapshot").
- Bila inspeksi menemukan perbedaan (terutama kenaikan), status order bergerak ke `waiting_approval`.
- Saat disetujui, `service_order_items.unit_price` menyimpan snapshot harga final yang baru.
- Perubahan harga setelah inspeksi selalu dicatat dan dapat diaudit. (selaras PRD §7.4, §7.5)

### D3 — DP wajib atau opsional

**Keputusan:** OPSIONAL pada MVP. Bukan aturan hard-code.

- DP tidak menjadi keharusan. Deposit dianggap sebagai salah satu bentuk pembayaran parsial.
- Kemampuan untuk "mewajibkan minimal DP" dipertimbangkan pada tahap lanjutan (di konfigurasi toko),
  tidak diblokir oleh skema saat ini.

### D4 — Order boleh diambil sebelum lunas

**Keputusan:** TIDAK. Order TIDAK boleh diambil sebelum pembayaran lunas. Bukan switch yang dapat
dikonfigurasi — ini aturan default yang bersifat hard pada MVP.

- Aksi pickup diblokir bila `remaining_amount > 0`.
- Implikasi: hampir tidak ada piutang pada MVP; modul outstanding tetap dibangun untuk laporan
  (selaras PRD §7.10 "sisa pembayaran") tetapi jarang menghasilkan nilai bukan nol pada alur normal.

### D5 — Customer wajib menyetujui perubahan harga melalui WhatsApp

**Keputusan:** Approval perubahan harga tercatat di SISTEM sebagai source of truth. WhatsApp hanya
salah satu channel, bukan syarat wajib.

- Transisi `waiting_approval` → `approved` selalu memiliki `approved_by` (approved oleh user/teknis
  yang sah), bukan hanya mengandalkan status read WhatsApp.
- WhatsApp dipakai untuk pengantar notifikasi persetujuan, dan status WA dapat memberi petunjuk,
  tetapi tidak memblokir transisi bila delivery gagal. (selaras PRD §19)

### D6 — Satu nomor WhatsApp dimiliki beberapa customer

**Keputusan:** TIDAK pada MVP. Satu nomor WhatsApp (ternormalisasi internasional) = satu customer
aktif.

- Uniqueness constraint pada nomor WhatsApp customer aktif.
- Bila nomor sudah ada, sistem menawarkan customer yang sudah terdaftar (de-duplikasi via pencarian),
  bukan membuat customer baru.
- Kasus "shared number" dalam satu rumah tangga tidak ditangani pada MVP (dikenali sebagai batasan).

### D7 — Biaya pickup/delivery termasuk MVP

**Keputusan:** TIDAK masuk MVP.

- Skema menyediakan jalur add-on fee sesuai pola add-on layanan (mis. add-on deodorizing) sehingga
  biaya tambahan tetap mungkin, tetapi workflow pickup/delivery penuh (rute, kurir, ongkir, wilayah)
  tidak dibangun pada MVP. (selaras PRD §18 "multi-branch dan pickup/delivery" sebagai pengembangan)

### D8 — Stok bahan boleh negatif

**Keputusan:** TIDAK boleh negatif pada MVP. Ini aturan hard (block), bukan configurable.

- Penurunan stok yang menyebabkan saldo negatif ditolak. Teknisi/admin harus memproses penyesuaian.
- Peringatan stok minimum (`low_stock`) disediakan untuk memitigasi kondisi sebelum saldo habis.
- Selaras dengan preconditions PRD: "Saldo tidak menjadi negatif kecuali kebijakan mengizinkannya";
  pada MVP kebijakan tidak mengizinkan.

### D9 — Multi-cabang sejak awal

**Keputusan:** Data model & schema dipersiapkan untuk multi-cabang; logika bisnis dan laporan tetap
single-branch pada MVP.

- `branches` dan `branch_id` (pada `service_orders`) tetap ada di skema.
- Tidak ada komponen UI atau alur bisnis multi-cabang pada MVP.

### D10 — Invoice dikirim sebagai PDF langsung atau tautan aman

**Keputusan:** Mulai dari tautan invoice yang aman (signed/tokenized) yang juga dapat di-render menjadi
PDF. PDF langsung hanya sebagai fallback.

- Secure link menyederhanakan pengiriman (satu mekanisme untuk webview + cetak) dan selaras dengan
  batasan template business-initiated WhatsApp.
- PDF dapat disimpan ke object storage sebagai artefak bila diperlukan.

### D11 — Provider WhatsApp: Meta Cloud API langsung atau BSP lokal

**Keputusan:** Meta WhatsApp Cloud API langsung untuk MVP, dibungkus di balik interface
`MessagingService`.

- Implementasi melalui adapter `MetaWhatsAppCloudService`, serta `FakeMessagingService` untuk
  pengujian (selaras PRD §7.9 dan §16 "tidak memanggil provider langsung dari controller").
- Pindah ke BSP lokal hanya mengganti adapter; tidak menyentuh domain.

## Konsekuensi

Positif:

- Model hubungan sepatu ↔ layanan menjadi jelas dan siap untuk Phase 3.
- Aturan "harus lunas sebelum pickup" menyederhanakan alur uang dan mengurangi risiko piutang.
- Pengurangan stok yang disiplin menghindari saldo negatif.
- Approve harga yang tercatat di sistem menjaga audibilitas tanpa hard-coupling ke WhatsApp.

Negatif / pembatasan yang disadari:

- "Shared WhatsApp number" (satu nomor dua pelanggan) tidak didukung pada MVP.
- "Boleh mengambil sebelum lunas" tidak didukung — arus yang butuh piutang harus menunggu iterasi
  berikutnya.
- Stok tidak dapat menjadi negatif, yang berarti teknisi/admin harus lebih disiplin dalam stock
  opname dan penyesuaian.

Risiko / butuh validasi lanjut:

- Relasi `shoe_items` ↔ `service_order_items` perlu diuji ke realitas alur kasir (dapat berubah
  menjadi pivot bila terbukti dibutuhkan 1 sepatu → banyak layanan).

## Tindak Lanjut

- [x] Perbarui ERD untuk menambahkan relasi `shoe_items` ↔ `service_order_items`
      → [docs/design/er-design.md](er-design.md).
- [x] Pasang constraint unik pada nomor WhatsApp customer aktif
      → [docs/design/er-design.md](er-design.md).
- [x] Finalisasi state machine order (termasuk transisi `waiting_approval` ↔ `approved` dan guard
      pickup pada `remaining_amount == 0`)
      → [docs/design/order-state-machine.md](order-state-machine.md).
- [x] Definisikan konvensi snapshot harga final pada `service_order_items.unit_price`
      → [docs/design/business-rules.md](business-rules.md).
- [x] Definisikan kebijakan stock yang menolak saldo negatif
      → [docs/design/business-rules.md](business-rules.md).
