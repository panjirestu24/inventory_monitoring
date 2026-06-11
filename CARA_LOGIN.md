# 🔐 PANDUAN LOGIN - Sistem Inventory Percetakan

## 📌 LANGKAH LOGIN

### 1. Buka Halaman Login
Ketik di browser: `http://localhost/inventory_monitoring/login.php`

### 2. Masukkan Email & Password
Gunakan salah satu akun demo di bawah:

---

## 👥 AKUN DEMO

### 🔴 Admin (Akses Penuh)
```
Email    : admin@percetakan.com
Password : password
```
**Hak Akses:** Bisa semua (tambah, edit, hapus, lihat)

---

### 🟡 Operator (Akses Menengah)
```
Email    : operator1@percetakan.com
Password : password
```
**Hak Akses:** Bisa input stok, update order, lihat data

---

### 🟢 Viewer (Hanya Lihat)
```
Email    : viewer@percetakan.com
Password : password
```
**Hak Akses:** Hanya bisa lihat data, tidak bisa edit/hapus

---

## 🚀 SETELAH LOGIN

Setelah login berhasil, kamu akan otomatis diarahkan ke **Dashboard**.

Di pojok kiri bawah, akan muncul:
- **Avatar** → Inisial nama kamu
- **Nama** → Nama lengkap user
- **Role** → Admin / Operator / Viewer
- **Tombol Logout** → Untuk keluar

---

## 🔒 KEAMANAN

### Session Login
- Session akan tersimpan selama browser terbuka
- Kalau tutup browser, harus login lagi
- Kalau klik "Logout", session langsung dihapus

### Proteksi Halaman
- Semua halaman (`index.php`, `api/*.php`) sudah dilindungi
- Kalau belum login, otomatis redirect ke `login.php`
- Tidak bisa akses dashboard tanpa login

---

## 🛠️ CARA KERJA SISTEM LOGIN

### File yang Terlibat:

#### 1. `login.php` (Halaman Login)
**Fungsi:**
- Tampilkan form email & password
- Terima input user
- Cek ke database apakah email & password benar
- Kalau benar → buat session, redirect ke dashboard
- Kalau salah → tampilkan error

**Kode Penting:**
```php
// Cek email & password di database
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Verifikasi password (pakai bcrypt)
if ($user && password_verify($password, $user['password'])) {
    // Login berhasil → buat session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    
    // Redirect ke dashboard
    header('Location: index.php');
}
```

#### 2. `auth_check.php` (Cek Login)
**Fungsi:**
- Cek apakah user sudah login (session ada?)
- Kalau belum login → redirect ke `login.php`
- Kalau sudah login → lanjut ke halaman yang diminta

**Kode:**
```php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Belum login → redirect
    header('Location: login.php');
    exit;
}
```

**Cara Pakai:**
```php
<?php
// Di awal file yang butuh login, tambahkan ini:
require_once 'auth_check.php';
?>
```

#### 3. `logout.php` (Keluar)
**Fungsi:**
- Hapus semua session
- Redirect ke halaman login

**Kode:**
```php
session_start();
session_unset();   // Hapus semua session
session_destroy(); // Hancurkan session
header('Location: login.php');
```

#### 4. `index.php` (Dashboard - Sudah Dilindungi)
**Kode di Awal:**
```php
<?php
// Pastikan user sudah login
require_once 'auth_check.php';
?>
```

**Tampilkan Info User:**
```php
<div class="user-name"><?= $_SESSION['user_name'] ?></div>
<div class="user-role"><?= $_SESSION['user_role'] ?></div>
```

---

## 🔑 PASSWORD HASH (Keamanan)

### Kenapa Pakai Hashing?
Password di database **TIDAK** disimpan dalam bentuk asli (plain text), tapi dalam bentuk **hash** (encrypted).

**Contoh:**
```
Password asli : password
Password hash : $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

### Cara Kerja:
1. **Saat Register/Buat User:**
   ```php
   $hashed = password_hash('password', PASSWORD_BCRYPT);
   // Hasil: $2y$10$92IXUNpkjO0rOQ...
   ```

2. **Saat Login:**
   ```php
   // Ambil hash dari database
   $hash_dari_db = $user['password'];
   
   // Bandingkan dengan password yang diinput user
   if (password_verify($_POST['password'], $hash_dari_db)) {
       // Password benar! Login berhasil
   }
   ```

### Keuntungan:
✅ Kalau database bocor, hacker tidak bisa tahu password asli  
✅ Tidak bisa di-decrypt (hanya bisa diverifikasi)  
✅ Tiap hash unik (walau password sama, hash beda)  

---

## 🧪 TESTING LOGIN

### Test 1: Login Berhasil
1. Buka `login.php`
2. Email: `admin@percetakan.com`
3. Password: `password`
4. Klik "Login Sekarang"
5. ✅ Harus masuk ke dashboard
6. ✅ Nama user muncul di sidebar

### Test 2: Password Salah
1. Buka `login.php`
2. Email: `admin@percetakan.com`
3. Password: `salah123`
4. Klik "Login Sekarang"
5. ❌ Muncul error: "Email atau password salah!"

### Test 3: Email Tidak Terdaftar
1. Buka `login.php`
2. Email: `notfound@test.com`
3. Password: `password`
4. Klik "Login Sekarang"
5. ❌ Muncul error: "Email atau password salah!"

### Test 4: Proteksi Halaman
1. **Tanpa Login:** Buka `http://localhost/inventory_monitoring/index.php`
2. ✅ Otomatis redirect ke `login.php`
3. **Sudah Login:** Buka `index.php`
4. ✅ Masuk ke dashboard

### Test 5: Logout
1. Login dulu
2. Klik tombol "Logout" di sidebar
3. ✅ Otomatis redirect ke `login.php`
4. Coba akses `index.php` lagi
5. ✅ Redirect lagi ke login (session sudah dihapus)

---

## 🎨 DESAIN HALAMAN LOGIN

### Fitur Desain:
✅ Dark theme modern (konsisten dengan dashboard)  
✅ Gradient background  
✅ Animasi slide-up saat load  
✅ Glow effect pada tombol  
✅ Responsive (mobile-friendly)  
✅ Info akun demo langsung di halaman  

---

## ❓ TROUBLESHOOTING

### ❌ Error: "Session sudah dimulai"
**Penyebab:** `session_start()` dipanggil 2x  
**Solusi:** Pastikan cuma ada 1x `session_start()` per halaman

### ❌ Redirect loop (login → dashboard → login → dst)
**Penyebab:** Session tidak tersimpan  
**Solusi:**
1. Cek PHP session sudah enabled
2. Cek folder temp PHP bisa ditulis
3. Cek `session.save_path` di `php.ini`

### ❌ Password selalu salah (padahal benar)
**Penyebab:** Hash di database salah / tidak sesuai  
**Solusi:**
1. Pastikan import `db_inventory.sql` dengan benar
2. Cek tabel `users`, kolom `password` harus berisi hash panjang
3. Kalau kosong/salah, jalankan SQL:
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
   WHERE email = 'admin@percetakan.com';
   ```

---

## 🚀 CARA TAMBAH USER BARU

### Lewat SQL (Manual):
```sql
INSERT INTO users (name, email, password, role) 
VALUES (
    'User Baru',
    'userbaru@percetakan.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'operator'
);
```

### Lewat PHP (Generate Hash):
```php
<?php
// File: generate_password.php
$password = 'password123';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo $hash;
?>
```

Buka `generate_password.php` di browser → copy hash → paste ke SQL INSERT.

---

## 📚 REFERENSI

- **PHP Session:** https://www.php.net/manual/en/book.session.php
- **Password Hashing:** https://www.php.net/manual/en/function.password-hash.php
- **SQL Injection Prevention:** Pakai Prepared Statement (PDO)

---

**🎉 Selamat! Sistem login sudah jalan!**

Sekarang website kamu sudah punya:
✅ Halaman login  
✅ Proteksi halaman  
✅ Session management  
✅ Password hashing  
✅ Logout  

**Langkah selanjutnya:** Buka `login.php` → coba login!
