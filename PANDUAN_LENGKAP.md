# 📚 PANDUAN LENGKAP - Sistem Inventory Percetakan

## 📖 DAFTAR ISI
1. [Cara Install](#cara-install)
2. [Penjelasan Struktur Folder](#penjelasan-struktur-folder)
3. [Penjelasan Database](#penjelasan-database)
4. [Cara Menggunakan Website](#cara-menggunakan-website)
5. [Troubleshooting](#troubleshooting)

---

## 🚀 CARA INSTALL

### Langkah 1: Persiapan
- Pastikan **Laragon** sudah terinstall dan berjalan
- Pastikan Apache & MySQL sudah running (lampu hijau)

### Langkah 2: Import Database
1. Buka browser, ketik: `http://localhost/phpmyadmin`
2. Klik tab **"Database"** di bagian atas
3. Buat database baru dengan nama: `db_inventory`
4. Klik database `db_inventory` yang baru dibuat
5. Klik tab **"Import"**
6. Klik tombol **"Choose File"**
7. Pilih file: `C:\laragon\www\inventory_monitoring\database\db_inventory.sql`
8. Scroll ke bawah, klik tombol **"Go"** atau **"Kirim"**
9. Tunggu sampai muncul pesan sukses (hijau)

### Langkah 3: Cek Koneksi Database
File `config/database.php` sudah dikonfigurasi default Laragon:
```php
DB_HOST: localhost
DB_USER: root
DB_PASS: (kosong)
DB_NAME: db_inventory
```
**Jika Laragon kamu pakai password, edit file ini!**

### Langkah 4: Buka Website
Buka browser, ketik: `http://localhost/inventory_monitoring`

✅ **Selesai!** Website sudah bisa digunakan.

---

## 📁 PENJELASAN STRUKTUR FOLDER

```
inventory_monitoring/
│
├── index.php              ← Halaman utama website (tampilan)
│
├── css/
│   └── app.css           ← Semua style/tampilan (warna, layout, dll)
│
├── js/
│   └── app.js            ← Logic program (fetch data, tampilkan, dll)
│
├── api/                  ← Backend (PHP) - komunikasi dengan database
│   ├── dashboard.php     ← API untuk halaman dashboard
│   ├── items.php         ← API untuk bahan baku (CRUD + stok)
│   ├── orders.php        ← API untuk order cetak
│   ├── machines.php      ← API untuk status mesin
│   ├── reports.php       ← API untuk laporan
│   ├── categories.php    ← API untuk kategori bahan
│   ├── units.php         ← API untuk satuan
│   ├── suppliers.php     ← API untuk data supplier
│   └── customers.php     ← API untuk data pelanggan
│
├── config/
│   └── database.php      ← Koneksi ke database MySQL
│
└── database/
    └── db_inventory.sql  ← File SQL (struktur tabel + data awal)
```

### Penjelasan Singkat:
- **index.php** → Ini yang kamu buka di browser (tampilan website)
- **css/app.css** → Ngatur warna, ukuran, animasi
- **js/app.js** → Ambil data dari API, tampilkan di website
- **api/** → File PHP yang ngambil/simpan data ke database
- **config/database.php** → Sambungan ke database
- **database/db_inventory.sql** → Blueprint database kamu

---

## 🗄️ PENJELASAN DATABASE

Database `db_inventory` punya **16 tabel**:

### Tabel Utama:

#### 1. `items` (Bahan Baku)
Menyimpan semua bahan baku percetakan.
- **code**: Kode unik item (contoh: ITM001)
- **name**: Nama bahan (contoh: Kertas HVS A4)
- **stock**: Jumlah stok saat ini
- **min_stock**: Batas minimum (kalau di bawah ini = alert!)
- **purchase_price**: Harga beli
- **location**: Lokasi penyimpanan (Gudang A-1)

#### 2. `stock_transactions` (Mutasi Stok)
Catat setiap perubahan stok (masuk/keluar).
- **type**: in (masuk) atau out (keluar)
- **quantity**: Jumlah barang
- **stock_before**: Stok sebelum transaksi
- **stock_after**: Stok sesudah transaksi
- **notes**: Catatan tambahan

#### 3. `orders` (Order Cetak)
Pesanan dari pelanggan.
- **order_number**: Nomor unik order
- **customer_id**: Siapa yang pesan
- **status**: pending → confirmed → in_progress → completed
- **priority**: low, normal, high, urgent
- **grand_total**: Total harga order

#### 4. `machines` (Mesin Produksi)
Data mesin percetakan.
- **status**: active, idle, maintenance, offline
- **type**: Jenis mesin (Offset, Digital, dll)
- **last_maintenance**: Terakhir maintenance

#### 5. `machine_logs` (Log Mesin)
Catat setiap aktivitas mesin (start, stop, error).
- **event**: start, stop, pause, resume, error
- **duration_minutes**: Durasi kerja
- **order_id**: Mesin lagi ngerjain order apa

### Tabel Pendukung:
- **users**: Data pengguna (admin, operator)
- **categories**: Kategori bahan (Kertas, Tinta, Plate, dll)
- **units**: Satuan (Rim, Kg, Liter, Lembar, dll)
- **suppliers**: Data supplier/pemasok
- **customers**: Data pelanggan
- **purchases**: Pembelian bahan baku dari supplier
- **notifications**: Notifikasi sistem
- **activity_logs**: Log aktivitas user
- **settings**: Pengaturan aplikasi

---

## 🎯 CARA MENGGUNAKAN WEBSITE

### 1. Dashboard
**Fungsi:** Lihat ringkasan semua data penting.
- **Statistik**: Total bahan, stok kritis, order aktif, pendapatan
- **Chart**: Grafik order 7 hari terakhir & nilai stok per kategori
- **Status Mesin**: Mesin mana yang sedang jalan
- **Stok Kritis**: Bahan yang hampir habis
- **Order Terbaru**: 8 order terakhir

### 2. Monitoring Realtime
**Fungsi:** Pantau mesin & order secara live (auto refresh tiap 15 detik).

**Tab Kartu Mesin:**
- Lihat status setiap mesin (aktif/idle/maintenance)
- Lihat pekerjaan yang sedang dikerjakan
- Ubah status mesin (klik tombol)

**Tab Kanban Order:**
- Lihat order dalam bentuk papan Kanban
- Kolom: Pending → Dikonfirmasi → Proses → QC → Selesai
- Drag & drop untuk pindah status (coming soon)

### 3. Bahan Baku
**Fungsi:** Kelola semua bahan baku percetakan.

**Fitur:**
- **Cari**: Ketik kode/nama bahan
- **Filter**: Filter per kategori, atau cuma tampilkan stok kritis
- **Tambah**: Klik tombol "Tambah Item"
- **Lihat**: Tabel menampilkan stok, min stok, progress bar
- **Aksi**: Stok masuk, edit, hapus

**Cara Tambah Item Baru:**
1. Klik tombol "Tambah Item"
2. Isi form:
   - Kode Item (contoh: ITM013)
   - Nama Item (contoh: Tinta Black HP)
   - Kategori (pilih dari dropdown)
   - Satuan (pilih dari dropdown)
   - Stok Awal, Min Stok
   - Harga Beli, Harga Jual
   - Lokasi (Gudang A-1)
3. Klik "Simpan"

### 4. Mutasi Stok
**Fungsi:** Catat stok masuk & keluar.

**Stok Masuk (Hijau):**
1. Pilih item dari dropdown
2. Masukkan jumlah
3. Masukkan harga satuan (opsional)
4. Tulis catatan (contoh: "Dari supplier PT XYZ")
5. Klik "Catat Stok Masuk"

**Stok Keluar (Merah):**
1. Pilih item
2. Masukkan jumlah
3. Tulis catatan (contoh: "Untuk order ORD-2401-0005")
4. Klik "Catat Stok Keluar"

**Riwayat Mutasi:**
- Pilih item dari dropdown untuk lihat historynya
- Tampil: waktu, tipe (masuk/keluar), jumlah, stok sebelum/sesudah

### 5. Order Cetak
**Fungsi:** Kelola pesanan cetak dari pelanggan.

**Cara Buat Order Baru:**
1. Klik "Buat Order"
2. Isi form:
   - Pelanggan (pilih dari dropdown)
   - Judul Pekerjaan (contoh: "Cetak Brosur 1000 Lembar")
   - Prioritas (Low/Normal/High/Urgent)
   - Mesin & Operator (opsional)
   - Jumlah & Harga Satuan
   - Diskon & PPN (otomatis hitung total)
   - Tanggal Mulai & Jatuh Tempo
   - Catatan
3. Klik "Buat Order"

**Update Status Order:**
1. Klik tombol "Update" di tabel order
2. Pilih status baru:
   - Pending → baru masuk, belum dikonfirmasi
   - Dikonfirmasi → sudah approved
   - Dalam Proses → sedang dikerjakan
   - Quality Check → QC sebelum serah terima
   - Selesai → sudah jadi & diserahkan
   - Dibatalkan → order dibatalkan
3. Pilih mesin (kalau lagi proses)
4. Klik "Update"

### 6. Mesin
**Fungsi:** Lihat semua mesin & ubah statusnya.
- **Kartu mesin** menampilkan: nama, status, pekerjaan saat ini
- **Tombol aksi**: Aktif, Maintenance, Idle

### 7. Supplier
**Fungsi:** Data pemasok bahan baku.
- Lihat kode, nama, kontak, telepon, kota
- Tambah supplier baru (form modal)

### 8. Laporan
**Fungsi:** Buat laporan & export ke CSV.

**3 Jenis Laporan:**
1. **Laporan Stok**: Semua item, stok, nilai
2. **Mutasi Stok**: History stok masuk/keluar per periode
3. **Laporan Order**: Semua order per periode

**Cara Pakai:**
1. Pilih tab laporan
2. Pilih tanggal dari-sampai
3. Klik "Tampilkan"
4. Klik "Export CSV" untuk download

---

## 🔧 TROUBLESHOOTING

### ❌ Error: "Koneksi database gagal"
**Penyebab:** Database belum dibuat atau koneksi salah.
**Solusi:**
1. Cek apakah MySQL di Laragon sudah jalan (lampu hijau)
2. Buka phpMyAdmin, cek apakah database `db_inventory` ada
3. Kalau belum, import file `database/db_inventory.sql`
4. Cek file `config/database.php`, pastikan username/password benar

### ❌ Halaman blank/putih
**Penyebab:** Error PHP yang tidak ditampilkan.
**Solusi:**
1. Buka file `config/database.php`
2. Cek ada error message di browser console (F12)
3. Cek error log di `C:\laragon\logs\`

### ❌ Data tidak muncul
**Penyebab:** API tidak jalan atau JavaScript error.
**Solusi:**
1. Tekan F12 di browser → tab "Console"
2. Lihat apakah ada error merah
3. Tekan F12 → tab "Network" → refresh halaman
4. Cek apakah request ke `api/dashboard.php` berhasil (status 200)
5. Klik request tersebut, lihat response-nya

### ❌ Chart tidak muncul
**Penyebab:** Chart.js tidak load.
**Solusi:**
1. Cek koneksi internet (Chart.js dari CDN)
2. Atau download Chart.js secara manual

### ❌ Error 404
**Penyebab:** File tidak ditemukan.
**Solusi:**
1. Pastikan folder `inventory_monitoring` ada di `C:\laragon\www\`
2. Akses harus: `http://localhost/inventory_monitoring`
3. Bukan: `http://localhost/inventory_monitoring/index.php`

---

## 📞 BUTUH BANTUAN?

Kalau masih bingung, cek file-file ini:
- `README.md` → Panduan install singkat
- `database/db_inventory.sql` → Struktur database
- `config/database.php` → Setting koneksi

Atau cari tutorial di YouTube tentang:
- "Cara pakai Laragon untuk pemula"
- "Cara import database di phpMyAdmin"
- "Cara membuat website PHP MySQL"

---

**Semoga membantu! Selamat belajar! 🚀**
