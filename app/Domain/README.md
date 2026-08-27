# Domain Structure

Modular monolith. Setiap domain adalah batas internal (bukan service terpisah) dalam satu
repository & deployment (PRD §6). Domain mengandung logic jahat yang TIDAK ditaruh di controller
atau komponen Svelte (PRD §16.5).

Struktur folder per modul:

```text
app/Domain/<Modul>/
├── Enums/
├── Actions/
├── Models/        (domain model, bila terpisah dari App\Models)
├── Policies/
├── Jobs/
├── Events/
└── Http/
    ├── Controllers/
    ├── Requests/
    └── Resources/
```

Modul-modul pada MVP:

- `Customers` — master customer, normalisasi nomor WA (ADR D6).
- `ServiceOrders` — intake, state machine, assignment, status history.
- `Services` — kategori & katalog layanan, snapshot harga (ADR D2).
- `Payments` — pembayaran partial/full, refund, shift kasir.
- `Invoices` — invoice, PDF/secure link (ADR D10).
- `Inventory` — stok bahan, mutasi, kebijakan non-negatif (ADR D8).
- `Messaging` — adapter WhatsApp (ADR D11), log pesan, webhook.
- `Reports` — agregasi & export.

> Fase 1 hanya menyiapkan kerangka + enum status. Struktur implementasi penuh mengikuti
> phase berikutnya (Phase 3–8) dan dirujuk oleh [order-state-machine](order-state-machine.md).
