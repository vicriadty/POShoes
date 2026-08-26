# PRD — POShoes

## 1. Ringkasan Produk

**POShoes** adalah aplikasi Point of Sale, manajemen order, dan manajemen operasional untuk UMKM jasa perawatan sepatu. Aplikasi menangani layanan seperti cuci sepatu, repaint, repair, dan layanan tambahan, mulai dari penerimaan sepatu sampai pembayaran dan pengambilan oleh pelanggan.

POShoes dirancang sebagai PWA dengan pendekatan mobile-first untuk kasir dan teknisi. Sistem menggunakan Laravel sebagai backend modular monolith, Svelte sebagai frontend, MySQL sebagai sumber kebenaran data, Redis untuk cache/queue/lock, Docker untuk lingkungan pengembangan dan deployment, serta WhatsApp Business Platform Cloud API untuk mengirim invoice dan notifikasi status kepada pelanggan.

## 2. Tujuan

- Mempercepat pembuatan order shoe care di kasir.
- Menyimpan identitas pelanggan, detail sepatu, layanan, foto kondisi, harga, dan pembayaran secara terpusat.
- Memantau status pengerjaan setiap layanan.
- Mengurangi kehilangan informasi mengenai kondisi sepatu sebelum pengerjaan.
- Mengelola stok bahan seperti cairan pembersih, cat, lem, sol, tali, dan kemasan.
- Mengirim invoice serta notifikasi status order melalui WhatsApp.
- Menyediakan laporan penjualan, order, penggunaan bahan, dan performa operasional.
- Menyediakan fondasi yang dapat dikembangkan menjadi multi-cabang dan pickup/delivery.

## 3. Sasaran Pengguna

### 3.1 Owner/Admin

- Mengelola master data, harga, pengguna, role, laporan, dan konfigurasi toko.
- Melihat performa penjualan, order, dan penggunaan bahan.

### 3.2 Kasir

- Membuat order baru.
- Mendata pelanggan dan sepatu.
- Memotret kondisi awal.
- Memilih layanan.
- Menerima pembayaran.
- Mencetak atau mengirim invoice.
- Memproses pengambilan sepatu.

### 3.3 Teknisi

- Melihat antrean pekerjaan.
- Mengubah status pengerjaan.
- Menambahkan catatan teknis.
- Mencatat bahan yang digunakan.
- Mengunggah foto proses atau hasil akhir.

### 3.4 Pelanggan

Pelanggan tidak wajib memiliki akun pada MVP. Pelanggan menerima invoice dan notifikasi melalui WhatsApp serta dapat menerima tautan status order jika fitur tersebut diaktifkan.

## 4. Batasan MVP

MVP berfokus pada satu bisnis atau satu cabang dengan satu atau beberapa workstation. Fitur berikut tidak wajib pada MVP:

- Offline-first penuh.
- Marketplace.
- Akuntansi double-entry.
- Chatbot WhatsApp.
- Payment gateway otomatis.
- Multi-cabang kompleks.
- Aplikasi native Android/iOS.

PWA harus offline-tolerant: draft order dan upload foto dapat ditangani saat koneksi tidak stabil, tetapi finalisasi transaksi dan pembayaran tetap divalidasi oleh server.

## 5. Stack Teknis

### 5.1 Backend

- PHP dan Laravel versi stabil yang dipilih saat implementasi.
- Laravel modular monolith.
- Laravel Sanctum untuk autentikasi SPA/API.
- Laravel Queue untuk pekerjaan asynchronous.
- Laravel Horizon untuk monitoring queue Redis.
- Laravel Scheduler untuk pekerjaan terjadwal.
- Laravel Notifications untuk notifikasi internal.
- Laravel HTTP Client untuk integrasi WhatsApp.
- Laravel Policies dan permission-based authorization.

### 5.2 Frontend

- Svelte.js.
- SvelteKit hanya jika kebutuhan routing/frontend mandiri mengharuskan; default proyek menggunakan Svelte terintegrasi dengan Laravel melalui adapter yang disepakati oleh implementer.
- TypeScript.
- Vite.
- Tailwind CSS.
- PWA plugin berbasis Vite/Workbox.
- IndexedDB melalui Dexie.js untuk draft lokal dan antrean upload terbatas.

### 5.3 Infrastruktur

- Docker dan Docker Compose untuk development.
- Nginx.
- PHP-FPM.
- MySQL 8.x.
- Redis.
- MinIO untuk object storage lokal; S3-compatible storage untuk production.
- Mailpit untuk email lokal.
- Supervisor atau process manager untuk worker/scheduler di production.

### 5.4 Paket tambahan

Wajib atau sangat disarankan:

- `laravel/sanctum`.
- `laravel/horizon`.
- `spatie/laravel-permission`.
- `spatie/laravel-activitylog`.
- `league/flysystem-aws-s3-v3`.
- `maatwebsite/excel`.
- `barryvdh/laravel-dompdf` atau generator PDF setara.
- `intervention/image`.
- `pestphp/pest`.
- `laravel/dusk` untuk alur browser kritis.
- Sentry atau solusi error monitoring setara.

Jangan menambahkan package hanya untuk menggantikan fitur Laravel yang sudah memadai. Integrasi WhatsApp harus dibungkus dalam interface internal agar provider dapat diganti.

## 6. Arsitektur Sistem

```text
Svelte PWA (mobile-first)
          |
       HTTPS
          |
        Nginx
          |
 Laravel App / API / Web
      |       |       |
    MySQL   Redis   Object Storage
              |
        Queue Worker + Horizon
              |
       WhatsApp Cloud API
```

Gunakan modular monolith. Modul dipisahkan berdasarkan domain, tetapi tetap berada dalam satu repository dan deployment utama.

Contoh struktur:

```text
app/
├── Domain/
│   ├── Customers/
│   ├── ServiceOrders/
│   ├── Services/
│   ├── Payments/
│   ├── Invoices/
│   ├── Inventory/
│   ├── Messaging/
│   └── Reports/
├── Actions/
├── Jobs/
├── Events/
├── Listeners/
├── Policies/
└── Http/
    ├── Controllers/
    ├── Requests/
    └── Resources/
```

## 7. Modul Fungsional

### 7.1 Autentikasi dan otorisasi

- Login/logout.
- Reset password.
- Role: owner, admin, kasir, teknisi.
- Permission granular.
- Pembatasan akses berdasarkan cabang/gudang jika multi-cabang diaktifkan.
- Audit aktivitas kritis.

### 7.2 Master customer

- Buat, ubah, lihat, dan cari customer.
- Nomor WhatsApp wajib untuk fitur notifikasi.
- Normalisasi nomor ke format internasional.
- Riwayat order customer.
- Catatan customer.
- Persetujuan komunikasi WhatsApp bila diperlukan.

### 7.3 Master layanan

Data minimal `service_catalogs`:

- Kode layanan.
- Nama.
- Kategori.
- Deskripsi.
- Harga dasar.
- Estimasi durasi.
- Status aktif.
- Kebutuhan foto before/after.
- Bahan standar yang digunakan.

Contoh layanan:

- Basic Cleaning.
- Deep Cleaning.
- Fast Cleaning.
- Repaint.
- Repair Sole.
- Repair Stitching.
- Ganti Tali.
- Add-on deodorizing.

Harga pada order harus disimpan sebagai snapshot agar perubahan harga di masa depan tidak mengubah transaksi lama.

### 7.4 Penerimaan sepatu

- Input satu atau beberapa sepatu dalam satu order.
- Brand, model, warna, ukuran, material, dan jumlah pasang.
- Catatan kondisi customer.
- Catatan internal kasir/teknisi.
- Foto before.
- Penandaan kerusakan awal.
- Nomor order unik dan mudah dibaca.
- Estimasi selesai.
- Status persetujuan harga.

### 7.5 Service order

Status tingkat order:

```text
draft
received
inspection
waiting_approval
approved
in_progress
quality_check
ready_for_pickup
picked_up
cancelled
```

Status item layanan:

```text
pending
in_progress
waiting_material
quality_check
completed
cancelled
```

Fitur:

- Satu order dapat memiliki banyak item layanan.
- Setiap item dapat memiliki status dan catatan berbeda.
- Assignment ke teknisi.
- Riwayat status immutable.
- Estimasi selesai pada tingkat order dan item.
- Approval perubahan harga.
- Catatan pembatalan dan alasan.

### 7.6 Pembayaran dan invoice

- DP atau pembayaran penuh.
- Pembayaran sebagian dan pelunasan.
- Tunai, transfer, QRIS, kartu, atau metode configurable.
- Perhitungan subtotal, diskon, pajak, total, bayar, dan kembalian.
- Invoice PDF.
- Nomor invoice/order unik.
- Pengembalian dana dengan otorisasi.
- Rekap pembayaran berdasarkan shift.

Nominal uang harus disimpan sebagai integer dalam rupiah, bukan floating point.

### 7.7 Pengerjaan teknisi

- Daftar antrean pekerjaan.
- Filter berdasarkan status, layanan, prioritas, dan estimasi selesai.
- Assignment teknisi.
- Mulai pekerjaan.
- Tambah catatan pekerjaan.
- Catat bahan aktual.
- Upload foto during/after.
- Kirim item ke quality check.
- Tandai selesai atau kembalikan untuk perbaikan.

### 7.8 Inventory bahan

MVP mengelola stok bahan, bukan hanya produk retail.

- Master bahan.
- Satuan dan konversi sederhana.
- Stok minimum.
- Stok masuk.
- Penyesuaian stok.
- Stock opname.
- Pemakaian bahan per order/item layanan.
- Riwayat mutasi stok.
- Notifikasi stok minimum.
- Supplier dan pembelian pada fase lanjutan.

Bahan tidak boleh otomatis berkurang hanya karena layanan dipilih. Pengurangan stok terjadi saat pemakaian dikonfirmasi oleh teknisi atau admin.

### 7.9 WhatsApp

Gunakan adapter provider:

```text
MessagingService
├── MetaWhatsAppCloudService
├── OptionalBspService
└── FakeMessagingService
```

Pesan utama:

- Konfirmasi order diterima.
- Bukti pembayaran.
- Persetujuan perubahan harga.
- Status sedang dikerjakan.
- Order siap diambil.
- Pengingat order belum diambil.
- Invoice PDF atau tautan invoice.

Pengiriman dilakukan melalui queue. Simpan message ID provider, status, payload ter-redact, waktu kirim, waktu delivered/read, dan error.

Business-initiated message harus menggunakan template WhatsApp yang sesuai aturan provider. Template dikelola di konfigurasi provider, bukan hard-code di controller.

### 7.10 Laporan

MVP:

- Pendapatan per periode.
- Jumlah order per status.
- Layanan terlaris.
- Pendapatan per layanan.
- Piutang atau sisa pembayaran.
- Order terlambat.
- Penggunaan bahan.
- Performa teknisi sederhana.

## 8. Data Model

Tabel inti:

```text
users
roles
permissions
branches
workstations

customers
service_categories
service_catalogs
service_materials

service_orders
service_order_items
shoe_items
shoe_photos
shoe_conditions
service_order_status_histories
work_assignments

payments
invoices
invoice_items

stock_items
stock_balances
stock_movements
stock_usages
stock_adjustments
stock_adjustment_items

whatsapp_messages
notifications
audit_logs
```

### 8.1 `service_orders`

```text
id
order_number
customer_id
branch_id
received_by
status
received_at
estimated_completed_at
completed_at
subtotal
discount_amount
tax_amount
total_amount
paid_amount
remaining_amount
customer_notes
internal_notes
created_at
updated_at
```

### 8.2 `service_order_items`

```text
id
service_order_id
service_catalog_id
service_name
quantity
unit_price
discount_amount
subtotal
estimated_duration_minutes
status
notes
assigned_to
created_at
updated_at
```

### 8.3 `shoe_items`

```text
id
service_order_id
brand
model
color
size
material
pair_count
condition_summary
customer_description
internal_description
```

### 8.4 `shoe_photos`

```text
id
service_order_id
shoe_item_id
type
file_path
mime_type
file_size
captured_by
created_at
```

### 8.5 `service_order_status_histories`

```text
id
service_order_id
service_order_item_id
from_status
to_status
reason
changed_by
created_at
```

Riwayat status tidak boleh dihapus dari UI biasa.

### 8.6 `whatsapp_messages`

```text
id
customer_id
service_order_id
message_type
template_name
recipient_phone
provider_message_id
status
payload_hash
sent_at
delivered_at
read_at
failed_at
error_code
error_message
created_at
updated_at
```

## 9. Aturan Bisnis

- Nomor order harus unik dan mudah dibaca.
- Satu order memiliki minimal satu item layanan sebelum dapat dikonfirmasi.
- Customer harus memiliki nomor WhatsApp yang valid sebelum notifikasi dikirim.
- Order yang sudah `picked_up` tidak boleh diedit tanpa prosedur koreksi berotorisasi.
- Pembatalan harus menyimpan alasan dan user pelaksana.
- Perubahan harga setelah inspeksi wajib dicatat dan, bila dikonfigurasi, memerlukan persetujuan customer.
- Pembayaran tidak boleh melebihi total tanpa aturan kembalian yang eksplisit.
- Setiap pembayaran dan pemakaian bahan harus memiliki audit trail.
- Status order dihitung dari status item, tetapi perubahan status manual tetap melalui policy.
- Foto kondisi awal dibuat sebelum sepatu masuk proses pengerjaan.
- Stok bahan dikurangi berdasarkan pemakaian aktual, bukan pilihan layanan semata.
- Notifikasi dikirim setelah transaksi database berhasil commit.
- Pengiriman WhatsApp harus idempotent untuk mencegah pesan ganda.

## 10. API Utama

Gunakan prefix `/api/v1`.

```text
POST   /auth/login
POST   /auth/logout
GET    /me

GET    /customers
POST   /customers
GET    /customers/{customer}

GET    /services
POST   /services
PUT    /services/{service}

GET    /service-orders
POST   /service-orders
GET    /service-orders/{order}
PUT    /service-orders/{order}
POST   /service-orders/{order}/approve
POST   /service-orders/{order}/status
POST   /service-orders/{order}/photos
POST   /service-orders/{order}/assign
POST   /service-orders/{order}/pickup

POST   /service-orders/{order}/payments
GET    /service-orders/{order}/invoice
POST   /service-orders/{order}/send-invoice

GET    /inventory/items
GET    /inventory/movements
POST   /inventory/usages
POST   /inventory/adjustments

GET    /reports/revenue
GET    /reports/orders
GET    /reports/material-usage

POST   /webhooks/whatsapp
```

Endpoint webhook WhatsApp tidak menggunakan autentikasi user biasa. Gunakan verifikasi signature/token provider, validasi payload, idempotency, dan logging aman.

## 11. PWA Requirements

- Installable melalui browser.
- Responsive mobile-first.
- Touch target nyaman untuk kasir.
- Navigasi utama maksimal beberapa langkah.
- Status koneksi terlihat jelas.
- Draft order tersimpan lokal.
- Foto dapat masuk antrean upload.
- Retry upload otomatis dengan batas percobaan.
- Tidak menganggap pembayaran lokal sebagai final sebelum server mengonfirmasi.
- Cache hanya asset dan data non-sensitif yang diperlukan.
- Logout menghapus data lokal sensitif.
- PWA tidak menyimpan seluruh database customer secara offline.

## 12. Docker Services

Development Compose minimal:

```text
nginx
app
worker
scheduler
mysql
redis
minio
mailpit
```

Aturan:

- `app`, `worker`, dan `scheduler` memakai base image aplikasi yang sama.
- MySQL dan Redis tidak diekspos ke internet.
- Gunakan named volume untuk data persisten.
- Gunakan health check.
- Konfigurasi provider melalui environment variables/secrets.
- Gunakan multi-stage build untuk asset Svelte.
- Sediakan `.env.example` lengkap.
- Sediakan Makefile atau script untuk setup developer.

Contoh command developer:

```text
make up
make install
make migrate
make seed
make test
make down
```

## 13. Non-Functional Requirements

### Performance

- Response endpoint kasir normal di bawah 500 ms pada kondisi jaringan lokal/normal, di luar pekerjaan asynchronous.
- Pencarian layanan dan customer memiliki pagination atau limit.
- Upload foto dikompresi dan tidak memblokir penyimpanan order lebih lama dari yang diperlukan.
- Laporan besar diproses melalui queue.

### Reliability

- Transaksi order, pembayaran, dan perubahan stok memakai database transaction.
- Row locking digunakan saat saldo stok diubah.
- Job eksternal memiliki retry dan failed job handling.
- Backup database terjadwal.
- Restore backup diuji secara berkala.

### Security

- HTTPS di production.
- Password di-hash dengan mekanisme Laravel.
- Authorization menggunakan policy dan permission.
- Validasi server-side pada seluruh request.
- Upload membatasi MIME type, ukuran, dan extension.
- Token WhatsApp tidak dimasukkan ke repository.
- Log tidak boleh menyimpan access token atau data sensitif penuh.
- Audit event untuk void, refund, price change, stock adjustment, dan perubahan role.

### Observability

- Application log terstruktur.
- Monitoring queue.
- Error tracking.
- Health endpoint untuk aplikasi, database, Redis, dan storage.
- Metrics minimal: jumlah order, queue latency, WhatsApp failure, failed jobs, dan penggunaan storage.

## 14. Acceptance Criteria MVP

- Kasir dapat membuat customer dan order baru dari perangkat mobile.
- Kasir dapat menambahkan beberapa layanan dalam satu order.
- Kasir dapat merekam minimal satu sepatu dan foto kondisi awal.
- Sistem menghitung total dan sisa pembayaran dengan benar.
- Kasir dapat menerima DP dan pelunasan.
- Sistem menghasilkan invoice dengan nomor unik.
- Order memiliki riwayat status yang dapat diaudit.
- Teknisi dapat melihat pekerjaan yang ditugaskan dan mengubah status sesuai permission.
- Sistem dapat mencatat pemakaian bahan.
- Stok bahan berubah secara atomik dan memiliki riwayat mutasi.
- Invoice atau notifikasi dapat dikirim melalui WhatsApp queue.
- Job WhatsApp gagal dapat di-retry atau dikirim ulang secara manual.
- Webhook WhatsApp tidak membuat event duplikat.
- PWA dapat di-install dan tetap usable pada koneksi tidak stabil untuk draft order.
- Test otomatis mencakup pembuatan order, pembayaran, perubahan status, stok, permission, dan WhatsApp job.

## 15. Tahapan Implementasi

### Phase 0 — Discovery dan Project Foundation

Tujuan: menyepakati batasan dan menyiapkan repository.

Deliverables:

- Finalisasi user role dan permission.
- Finalisasi alur bisnis status order.
- Finalisasi daftar layanan dan metode pembayaran.
- ERD awal.
- API convention.
- Repository Git.
- Docker Compose development.
- `.env.example`.
- CI dasar untuk lint dan test.
- Definition of Done.

Exit criteria:

- Tim menyetujui state machine order.
- Semua service development dapat dijalankan dengan satu command.
- Migration kosong/awal berhasil dijalankan.

### Phase 1 — Core Application dan Access Control

Tujuan: membuat fondasi Laravel dan akses pengguna.

Implementasi:

- Laravel modular structure.
- Database connection.
- Authentication.
- Sanctum.
- Role dan permission.
- User management minimal.
- Branch/workstation dasar.
- Audit log dasar.
- Error handling dan API response convention.

Testing:

- Login/logout.
- Unauthorized access.
- Permission per role.
- Audit event.

Exit criteria:

- User dapat login.
- Kasir tidak dapat mengakses fitur admin.
- Semua endpoint utama memiliki authorization policy.

### Phase 2 — Svelte PWA Shell

Tujuan: menyiapkan antarmuka kasir mobile-first.

Implementasi:

- Svelte + TypeScript + Vite.
- Tailwind CSS.
- Layout mobile-first.
- Routing dan protected route.
- Reusable form, modal, table/card, toast, loading, empty state.
- PWA manifest.
- Service worker asset caching.
- Offline indicator.
- IndexedDB draft order dasar.

Halaman:

- Login.
- Dashboard.
- Daftar order.
- Buat order.
- Detail order.
- Profile/logout.

Exit criteria:

- PWA dapat di-install.
- Navigasi kasir dapat digunakan pada layar mobile.
- Session expired ditangani dengan baik.

### Phase 3 — Customer, Service Catalog, dan Order Intake

Tujuan: menyelesaikan proses penerimaan sepatu.

Implementasi:

- CRUD customer.
- CRUD kategori dan layanan.
- Search customer dan layanan.
- Create service order.
- Multiple service order items.
- Shoe item.
- Customer notes dan internal notes.
- Generate order number.
- Foto before.
- Estimasi selesai.
- Validasi order.

Testing:

- Customer duplicate handling.
- Multiple item order.
- Validation nomor WhatsApp.
- Upload dan retry foto.
- Snapshot harga layanan.

Exit criteria:

- Kasir dapat membuat order lengkap dari mobile.
- Order draft dapat dilanjutkan.
- Harga lama tidak berubah ketika master service diubah.

### Phase 4 — Payment, Invoice, dan Pickup

Tujuan: mengelola uang dan penyelesaian order.

Implementasi:

- Payment model dan payment methods.
- DP, pembayaran penuh, dan pelunasan.
- Kalkulasi uang menggunakan integer.
- Invoice PDF.
- Payment receipt.
- Cashier shift dasar.
- Pickup verification.
- Status picked up.
- Void/refund dengan permission.

Testing:

- Total dan kembalian.
- Pembayaran berulang.
- Concurrency pada pembayaran.
- Invoice number uniqueness.
- Pickup dengan sisa pembayaran.

Exit criteria:

- Order dapat dibayar sebagian dan dilunasi.
- Invoice dapat dibuat ulang tanpa menggandakan payment.
- Order tidak dapat diambil tanpa aturan pelunasan yang dikonfigurasi.

### Phase 5 — Teknisi dan Workflow Pengerjaan

Tujuan: memindahkan order dari kasir ke proses operasional.

Implementasi:

- Work queue teknisi.
- Assignment.
- Status item dan status order.
- Status history.
- Catatan teknisi.
- Foto during/after.
- Quality check.
- Rework.
- Approval perubahan harga.

Testing:

- Valid transition status.
- Invalid transition ditolak.
- Permission teknisi.
- Perubahan harga dan audit trail.
- Order multi-item dengan status berbeda.

Exit criteria:

- Owner dapat melihat seluruh antrean.
- Teknisi hanya dapat melakukan aksi yang diizinkan.
- Status order agregat konsisten dengan status item.

### Phase 6 — Inventory Bahan

Tujuan: mencatat stok dan konsumsi bahan secara akurat.

Implementasi:

- Master stock item.
- Stock balance.
- Stock movement.
- Stock usage per service item.
- Stock adjustment.
- Stock opname.
- Minimum stock.
- Riwayat kartu stok.
- Notifikasi stok rendah.

Testing:

- Concurrent stock update.
- Insufficient stock policy.
- Rollback transaction.
- Stock adjustment audit.
- Perhitungan saldo dari movement.

Exit criteria:

- Setiap perubahan stok memiliki referensi.
- Saldo tidak menjadi negatif kecuali kebijakan mengizinkannya.
- Pemakaian bahan dapat ditelusuri ke order.

### Phase 7 — WhatsApp Integration

Tujuan: mengirim komunikasi customer secara reliable.

Implementasi:

- Messaging interface.
- Meta WhatsApp Cloud API adapter.
- Template configuration.
- Queue job.
- Retry/backoff.
- Message log.
- Webhook verification.
- Delivery/read/failed status.
- Invoice document/link sending.
- Manual resend.
- Fake provider untuk test.

Testing:

- Mock HTTP provider.
- Successful send.
- 4xx non-retryable.
- 429/5xx retryable.
- Webhook idempotency.
- Duplicate prevention.
- Redaction log.

Exit criteria:

- Invoice dapat dikirim dari order.
- Status WhatsApp tersimpan.
- Kegagalan provider tidak menggagalkan transaksi kasir.
- Pesan yang sama tidak terkirim berulang karena retry.

### Phase 8 — Reports, Dashboard, dan Realtime

Tujuan: menyediakan visibilitas operasional.

Implementasi:

- Dashboard order.
- Revenue report.
- Service performance.
- Technician performance.
- Outstanding payment.
- Delayed order.
- Material usage.
- Export Excel/PDF melalui queue.
- Laravel Reverb hanya jika polling tidak mencukupi.

Exit criteria:

- Laporan memiliki filter tanggal dan cabang.
- Export besar tidak memblokir request.
- Angka laporan dapat direkonsiliasi dengan transaksi.

### Phase 9 — Hardening dan Production Readiness

Tujuan: menyiapkan sistem untuk penggunaan nyata.

Implementasi:

- Security review.
- Performance testing.
- Database index review.
- Backup dan restore test.
- Health checks.
- Error monitoring.
- Queue monitoring.
- Log rotation.
- Production Docker image.
- Staging deployment.
- User acceptance testing.
- Runbook operasional.
- Seed demo dan onboarding guide.

Exit criteria:

- Backup berhasil dipulihkan.
- Tidak ada blocker pada UAT.
- Monitoring dan alert aktif.
- Deployment dapat diulang.
- Tim mengetahui prosedur failed job, resend WhatsApp, koreksi transaksi, dan restore backup.

## 16. Instruksi Implementasi untuk AI Agent

AI Agent harus:

1. Membaca PRD ini sebelum membuat perubahan.
2. Mengimplementasikan phase secara berurutan.
3. Tidak melompati migration, authorization, validation, dan test.
4. Membuat migration, model, policy, request, action/service, controller, resource, job, dan test sesuai domain.
5. Tidak menaruh business logic utama pada controller atau komponen Svelte.
6. Tidak mengubah status order tanpa state transition yang tervalidasi.
7. Menggunakan database transaction untuk order, payment, dan stock mutation.
8. Menggunakan row lock MySQL untuk perubahan saldo stok.
9. Menggunakan queue untuk WhatsApp, PDF besar, export, dan pekerjaan eksternal.
10. Tidak memanggil provider WhatsApp langsung dari controller.
11. Menulis fake adapter untuk pengujian provider eksternal.
12. Tidak menyimpan secret di repository.
13. Menambahkan test untuk setiap business rule baru.
14. Menjalankan lint, static analysis, migration test, unit test, dan feature test sebelum menyatakan phase selesai.
15. Memperbarui dokumentasi endpoint dan perubahan schema.
16. Menghentikan implementasi dan meminta klarifikasi jika aturan bisnis bertentangan atau belum ditentukan.

### Format output setiap task AI Agent

```text
## Task
Deskripsi singkat.

## Changes
File dan perubahan yang dibuat.

## Database
Migration/index/constraint yang ditambahkan.

## Business Rules
Aturan yang diimplementasikan.

## Tests
Test yang ditambahkan dan hasilnya.

## Risks
Risiko, asumsi, atau keputusan yang masih perlu dikonfirmasi.
```

## 17. Definition of Done

Sebuah fitur dianggap selesai jika:

- Acceptance criteria terpenuhi.
- Authorization dan validation tersedia.
- Error response konsisten.
- Migration aman dan memiliki index yang diperlukan.
- Unit/feature test tersedia.
- Tidak ada secret hard-code.
- Loading, empty, error, dan retry state tersedia pada UI.
- Audit log tersedia untuk aksi kritis.
- Dokumentasi API atau usage diperbarui.
- Docker development dapat menjalankan fitur tersebut.
- Lint dan test pipeline berhasil.

## 18. Keputusan yang Perlu Dikonfirmasi Sebelum Phase 3

- Apakah satu order dapat berisi beberapa pasang sepatu?
- Apakah harga ditentukan saat penerimaan atau setelah inspeksi teknisi?
- Apakah DP wajib atau opsional?
- Apakah order boleh diambil sebelum lunas?
- Apakah customer wajib menyetujui perubahan harga melalui WhatsApp?
- Apakah satu nomor WhatsApp dapat dimiliki beberapa customer?
- Apakah biaya pickup/delivery termasuk MVP?
- Apakah stok bahan boleh negatif?
- Apakah bisnis memerlukan multi-cabang sejak awal?
- Apakah invoice dikirim sebagai PDF langsung atau tautan aman?
- Apakah provider yang digunakan Meta Cloud API langsung atau BSP lokal?

## 19. Prinsip Utama

- MySQL adalah sumber kebenaran untuk order, pembayaran, status, dan stok.
- Redis bukan sumber kebenaran saldo stok.
- Semua perubahan kritis dapat diaudit.
- Foto before/after adalah bagian dari bukti operasional.
- WhatsApp bersifat asynchronous dan dapat gagal tanpa membatalkan transaksi.
- PWA harus membantu koneksi tidak stabil, bukan menyamarkan transaksi yang belum tersinkron.
- Modular monolith diprioritaskan sampai kebutuhan pemisahan service benar-benar terbukti.
- Kesederhanaan operasional lebih penting daripada jumlah teknologi.
