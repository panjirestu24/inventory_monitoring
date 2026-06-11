# 🖨️ Sistem Inventory & Monitoring Percetakan

## 📦 APA INI?
Website untuk mengelola inventory (bahan baku) dan monitoring produksi percetakan secara **realtime**.

---

## 🚀 CARA INSTALL (Pemula-Friendly)

### Langkah 1: Import Database
1. Buka browser, ketik: `http://localhost/phpmyadmin`
2. Klik **"Database"** → buat database baru → nama: `db_inventory`
3. Klik database `db_inventory` → klik tab **"Import"**
4. Klik **"Choose File"** → pilih file: `database/db_inventory.sql`
5. Klik tombol **"Go"** → tunggu sampai sukses ✅

### Langkah 2: Setting Database (Opsional)
Kalau Laragon kamu pakai password, edit file: `config/database.php`
```php
DB_HOST: localhost
DB_USER: root
DB_PASS: (isi password kamu, default kosong)
DB_NAME: db_inventory
```

### Langkah 3: Buka Website
Ketik di browser: `http://localhost/inventory_monitoring`

✅ **SELESAI!** Website sudah jalan.

---

## 📁 STRUKTUR FOLDER (Apa Fungsi Setiap File)

```
inventory_monitoring/
│
├── index.php              ← Halaman utama (yang dibuka di browser)
│
├── css/
│   └── app.css           ← Semua style (warna, animasi, layout)
│
├── js/
│   └── app.js            ← Logic JavaScript (ambil data, tampilkan)
│
├── api/                  ← Backend PHP (komunikasi dengan database)
│   ├── dashboard.php     ← API halaman dashboard
│   ├── items.php         ← API bahan baku (tambah, edit, hapus, stok)
│   ├── orders.php        ← API order cetak
│   ├── machines.php      ← API status mesin
│   ├── reports.php       ← API laporan & export CSV
│   ├── categories.php    ← API kategori bahan
│   ├── units.php         ← API satuan (Rim, Kg, dll)
│   ├── suppliers.php     ← API data supplier
│   └── customers.php     ← API data pelanggan
│
├── config/
│   └── database.php      ← Koneksi ke database MySQL
│
└── database/
    └── db_inventory.sql  ← File SQL (struktur + data contoh)
```

---

## 🗄️ DATABASE (16 Tabel)

| Tabel | Fungsi |
|---|---|
| **items** | Bahan baku (kertas, tinta, dll) + stok |
| **stock_transactions** | Riwayat stok masuk/keluar (audit trail) |
| **orders** | Order cetak dari pelanggan |
| **order_items** | Detail bahan yang dipakai per order |
| **machines** | Data mesin percetakan |
| **machine_logs** | Log aktivitas mesin (start, stop, error) |
| **purchases** | Pembelian bahan dari supplier |
| **purchase_items** | Detail item pembelian |
| **suppliers** | Data pemasok bahan baku |
| **customers** | Data pelanggan |
| **categories** | Kategori bahan (Kertas, Tinta, Plate, dll) |
| **units** | Satuan (Rim, Lembar, Kg, Liter, dll) |
| **users** | Pengguna sistem (admin, operator) |
| **notifications** | Notifikasi (stok rendah, dll) |
| **activity_logs** | Log aktivitas user |
| **settings** | Pengaturan aplikasi |

### Data Contoh yang Sudah Ada:
✅ 3 user (admin, operator, viewer)  
✅ 6 kategori bahan  
✅ 10 satuan  
✅ 4 supplier  
✅ 5 pelanggan  
✅ 5 mesin  
✅ 12 bahan baku (dengan stok awal)  

---

## 🎯 FITUR UTAMA

### 1. 📊 Dashboard
- Statistik ringkasan (total item, stok kritis, order aktif, pendapatan)
- Chart order 7 hari terakhir
- Chart nilai stok per kategori
- Status mesin realtime
- Daftar stok kritis (progress bar)
- Order terbaru

### 2. 📡 Monitoring Realtime
- **Kartu Mesin:** Status setiap mesin (aktif/idle/maintenance/offline)
- **Kanban Board:** Order dalam bentuk papan kanban (pending → proses → selesai)
- Auto refresh setiap 15 detik

### 3. 📦 Bahan Baku
- Tabel semua bahan dengan progress bar stok
- Filter per kategori
- Filter stok kritis
- Tambah item baru
- Stok masuk/keluar langsung dari tabel

### 4. 🔄 Mutasi Stok
- Form stok masuk (hijau)
- Form stok keluar (merah)
- Riwayat mutasi per item
- Tampilkan stok sebelum & sesudah

### 5. 📝 Order Cetak
- Buat order baru (dengan kalkulasi otomatis)
- Update status order (pending → completed)
- Filter per status
- Lihat detail order

### 6. 🏭 Mesin
- Status semua mesin
- Ubah status mesin (tombol cepat)
- Lihat pekerjaan yang sedang dikerjakan

### 7. 🚚 Supplier
- Data pemasok bahan
- Tambah supplier baru
- Kontak person & detail

### 8. 👥 Pelanggan
- Data semua pelanggan
- Kontak & kota

### 9. 📈 Laporan
- **Laporan Stok:** Semua item + nilai
- **Mutasi Stok:** History per periode
- **Laporan Order:** Semua order per periode
- Export ke CSV

---

## 💻 TEKNOLOGI YANG DIPAKAI

- **Frontend:** HTML5, CSS3 (Dark Theme Modern), Vanilla JavaScript
- **Backend:** PHP 8+ (PDO)
- **Database:** MySQL 8+
- **Chart:** Chart.js 4.4
- **Icons:** Feather Icons
- **Font:** Google Fonts (Inter)

---

## 🔑 LOGIN DEFAULT (Belum Ada Halaman Login)

Saat ini belum ada halaman login. Data user ada di tabel `users`:

```sql
Email: admin@percetakan.com
Password: password (sudah di-hash dengan bcrypt)

Email: operator1@percetakan.com
Password: password

Email: viewer@percetakan.com
Password: password
```

Kalau mau buat halaman login, bisa ditambahkan nanti.

---

## 🐛 TROUBLESHOOTING

### ❌ Database gagal connect
- Cek MySQL di Laragon sudah jalan (lampu hijau)
- Cek database `db_inventory` sudah diimport
- Cek `config/database.php` → username/password benar

### ❌ Data tidak muncul
- Tekan F12 → tab "Console" → cek error
- Tab "Network" → cek request API (status 200 = OK)
- Pastikan semua file `api/*.php` ada

### ❌ Chart tidak muncul
- Cek internet (Chart.js dari CDN online)

### ❌ Halaman blank
- Cek error di `C:\laragon\logs\apache_error.log`

---

## 📚 DOKUMENTASI LENGKAP

Baca file: **`PANDUAN_LENGKAP.md`** untuk tutorial step-by-step lebih detail.

---

## 🎓 COCOK UNTUK

- Tugas akhir / skripsi
- Belajar PHP & MySQL
- Project percetakan kecil-menengah
- Belajar REST API
- Belajar SPA (Single Page Application)

---

**Dibuat dengan ❤️ untuk belajar coding**
