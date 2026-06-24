# PrintTrack — Sistem Inventory & Monitoring Percetakan

Sistem manajemen order, inventory bahan baku, dan monitoring pengiriman untuk usaha percetakan.

## Teknologi
- **Backend:** PHP 8.1+ (PDO, tanpa framework)
- **Frontend:** HTML5 + Vanilla JS (SPA)
- **Database:** MySQL 8+
- **Library:** Chart.js 4.4, Feather Icons
- **Environment:** Laragon

---

## Cara Install

1. Copy folder ke `C:\laragon\www\inventory_monitoring\`
2. Buka phpMyAdmin → buat database `db_inventory`
3. Import `database/db_inventory.sql`
4. Jika database sudah ada sebelumnya, jalankan juga:
   - `database/migrate_deliveries.sql` (tambah tabel deliveries)
   - `database/migrate_proof_image.sql` (tambah kolom foto bukti)
5. Buka: `http://localhost/inventory_monitoring/login.php`

---

## Akun Default

| Role     | Email                        | Password |
|----------|------------------------------|----------|
| Admin    | admin@percetakan.com         | password |
| Operator | operator1@percetakan.com     | password |
| Viewer   | viewer@percetakan.com        | password |

---

## Halaman Utama

| URL                  | Fungsi                                      | Akses       |
|----------------------|---------------------------------------------|-------------|
| `login.php`          | Login staff                                 | Publik      |
| `index.php`          | Dashboard & semua modul internal            | Login       |
| `order_baru.php`     | Input order baru + cetak nota               | Login       |
| `track.php`          | Tracking status pesanan oleh pelanggan      | Publik      |

---

## Alur Kerja

### Input Order (Pelanggan Datang Langsung)
```
1. Admin/Operator buka order_baru.php
2. Isi nama + no HP pelanggan
   → Autocomplete jika pelanggan pernah order
   → Otomatis simpan ke database jika baru
3. Isi detail pesanan (jenis, jumlah, harga)
4. Klik "Simpan & Tampilkan Nota"
5. Nota muncul → Cetak → Berikan ke pelanggan
6. Pelanggan menyimpan nomor order dari nota
```

### Update Status Produksi
```
index.php → Order Cetak
→ Klik dot stepper untuk maju ke status berikutnya:
  Pending → Dikonfirmasi → Proses → QC → Selesai
→ Setelah Selesai → tombol "Kirim" muncul
→ Isi data pengiriman
```

### Update Status Pengiriman
```
index.php → Pengiriman
→ Klik dot stepper:
  Disiapkan → Dikirim → Tiba di Tujuan → Diterima
→ Saat Tiba di Tujuan: upload foto bukti sebelum konfirmasi Diterima
```

### Tracking oleh Pelanggan
```
Buka track.php
→ Masukkan nomor order (dari nota)
→ Lihat status produksi + pengiriman
→ Foto bukti tersedia setelah dikonfirmasi diterima
```

---

## Struktur File

```
inventory_monitoring/
├── index.php              # App utama (SPA)
├── order_baru.php         # Form input order + nota
├── track.php              # Halaman tracking publik
├── login.php / logout.php
├── auth_check.php         # Middleware session
├── helpers.php            # RBAC helpers
├── api/
│   ├── dashboard.php      # Statistik & data ringkasan
│   ├── orders.php         # CRUD + update status order
│   ├── customers.php      # Data pelanggan + riwayat
│   ├── deliveries.php     # Pengiriman + upload bukti foto
│   ├── items.php          # Bahan baku + mutasi stok
│   ├── machines.php       # Data mesin
│   ├── categories.php     # Kategori bahan
│   ├── units.php          # Satuan
│   └── reports.php        # Laporan & export CSV
├── css/app.css
├── js/app.js
├── uploads/proof/         # Foto bukti pengiriman
├── config/database.php
└── database/
    ├── db_inventory.sql          # Schema + seed data
    ├── migrate_deliveries.sql    # Migrasi tabel deliveries
    └── migrate_proof_image.sql   # Migrasi kolom proof_image
```

---

## Role & Hak Akses

| Aksi              | Admin | Operator | Viewer |
|-------------------|-------|----------|--------|
| Lihat semua data  | ✅    | ✅       | ✅     |
| Tambah order      | ✅    | ✅       | ❌     |
| Update status     | ✅    | ✅       | ❌     |
| Upload bukti foto | ✅    | ✅       | ❌     |
| Hapus data        | ✅    | ❌       | ❌     |
| Export laporan    | ✅    | ✅       | ❌     |

---

## Catatan Keamanan

- Semua endpoint API dilindungi session (kecuali `track` yang publik)
- Upload foto dibatasi: JPG/PNG/WEBP, maks 5MB
- Konfirmasi "Diterima" hanya bisa dilakukan jika status sebelumnya adalah "Tiba di Tujuan"
- Input order menggunakan database transaction untuk konsistensi data
