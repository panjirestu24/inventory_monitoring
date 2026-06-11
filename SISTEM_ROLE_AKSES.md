# 🔐 SISTEM ROLE & HAK AKSES (RBAC)

## 📌 TUJUAN
Mengatur siapa bisa apa berdasarkan **role/jabatan** user.

---

## 👥 3 ROLE YANG ADA

### 1️⃣ **ADMIN** (Administrator) 
**Hak Akses:** ⭐⭐⭐⭐⭐ FULL ACCESS

✅ Lihat semua data  
✅ Tambah data baru  
✅ Edit data  
✅ **Hapus data**  
✅ Export laporan  
✅ Kelola user (future)  

**Badge di Header:** 🛡️ Admin (hijau)

---

### 2️⃣ **OPERATOR** (Staff Operasional)
**Hak Akses:** ⭐⭐⭐⭐ HAMPIR FULL

✅ Lihat semua data  
✅ Tambah data baru  
✅ Edit data  
❌ **TIDAK BISA Hapus data** ← Perbedaan utama  
✅ Export laporan  

**Kenapa tidak bisa hapus?**  
Untuk keamanan. Operator bisa input/edit, tapi hapus data hanya bisa Admin.

**Tombol yang Disembunyikan:**
- Tombol "Hapus" di tabel items
- Tombol "Hapus" di tabel customers
- Tombol merah (danger) dengan fungsi delete

---

### 3️⃣ **VIEWER** (Pengamat/Read-Only)
**Hak Akses:** ⭐ READ ONLY

✅ **Hanya lihat data**  
❌ TIDAK BISA tambah  
❌ TIDAK BISA edit  
❌ TIDAK BISA hapus  
❌ TIDAK BISA input stok  

**Badge di Header:** 👁️ Mode Read-Only (merah)

**Yang Disembunyikan/Disabled:**
- Semua tombol "Tambah" (hijau/biru)
- Semua tombol "Edit" (kuning)
- Semua tombol "Hapus" (merah)
- Tombol "Simpan" di modal
- Input stok masuk/keluar
- Tombol update status order/mesin
- Semua form input (disabled)

**Yang Masih Bisa:**
- Search/filter data
- Lihat tabel
- Lihat chart
- Lihat dashboard

---

## 🔧 CARA KERJA SISTEM

### 1️⃣ **PHP Side (Backend)**

#### File: `helpers.php`
```php
function hasPermission($action) {
    $role = $_SESSION['user_role'];  // admin/operator/viewer
    
    $permissions = [
        'admin' => ['view', 'create', 'edit', 'delete', 'export'],
        'operator' => ['view', 'create', 'edit', 'export'],
        'viewer' => ['view']
    ];
    
    return in_array($action, $permissions[$role]);
}
```

**Pengecekan di Backend:**
```php
// Sebelum delete
if (!hasPermission('delete')) {
    echo json_encode(['berhasil' => false, 'pesan' => 'Akses ditolak']);
    exit;
}

// Lanjut hapus data
```

---

### 2️⃣ **JavaScript Side (Frontend)**

#### Pass Role dari PHP ke JS:
```php
<!-- index.php -->
<script>
window.APP_CONFIG = {
  userRole: '<?= $userRole ?>',  // admin/operator/viewer
  permissions: {
    canView: true/false,
    canCreate: true/false,
    canEdit: true/false,
    canDelete: true/false,
    canExport: true/false
  }
};
</script>
```

#### Hide Tombol Berdasarkan Role:
```javascript
// app.js
function applyRoleBasedUI() {
  const role = window.APP_CONFIG.userRole;
  
  if (role === 'viewer') {
    // Hide semua tombol action
    hideElements('button[onclick*="edit"]');
    hideElements('button[onclick*="delete"]');
    hideElements('.btn-primary[onclick*="openAdd"]');
    
    // Disable semua input
    document.querySelectorAll('input, select, textarea').forEach(el => {
      el.disabled = true;
    });
  }
  
  if (role === 'operator') {
    // Hide tombol hapus aja
    hideElements('button[onclick*="delete"]');
  }
}
```

#### Cek Permission Sebelum Submit:
```javascript
async function deleteItem(id) {
  // Cek permission dulu
  if (!checkPermission('delete')) {
    showToast('Akses ditolak! Role Anda tidak bisa hapus', 'error');
    return;
  }
  
  // Lanjut hapus
  const res = await apiFetch(...);
}
```

---

## 🧪 CARA TEST SISTEM ROLE

### Test 1: Login sebagai ADMIN
```
Email: admin@percetakan.com
Password: password
```

**Yang Harus Tampil:**
- ✅ Badge "🛡️ Admin" di header
- ✅ Semua tombol ada (Tambah, Edit, Hapus)
- ✅ Bisa klik semua tombol
- ✅ Bisa submit form
- ✅ Bisa hapus data

**Test:**
1. Buka "Bahan Baku"
2. Klik "Tambah Item" → ✅ Modal muncul
3. Isi form → Klik Simpan → ✅ Berhasil
4. Klik Edit item → ✅ Bisa edit
5. Klik Hapus item → ✅ Bisa hapus

---

### Test 2: Login sebagai OPERATOR
```
Email: operator1@percetakan.com
Password: password
```

**Yang Harus Tampil:**
- ✅ Tombol "Tambah" ada
- ✅ Tombol "Edit" ada
- ❌ **Tombol "Hapus" HILANG!**
- ✅ Bisa input stok masuk/keluar

**Test:**
1. Buka "Bahan Baku"
2. Lihat tabel → ❌ Tombol hapus (trash icon) TIDAK ADA
3. Klik "Tambah Item" → ✅ Modal muncul
4. Isi form → Simpan → ✅ Berhasil
5. Klik Edit → ✅ Bisa edit
6. Coba hapus → ❌ Tombol tidak ada
7. Buka "Mutasi Stok" → ✅ Bisa input stok

**Kalau Coba Paksa (via Console):**
```javascript
// Buka F12 → Console → ketik:
deleteItem(5)

// Response:
🔴 Akses ditolak! Role "operator" tidak punya hak akses untuk delete
```

---

### Test 3: Login sebagai VIEWER
```
Email: viewer@percetakan.com
Password: password
```

**Yang Harus Tampil:**
- ❌ **Semua tombol action HILANG**
- ❌ Tombol "Tambah" hilang
- ❌ Tombol "Edit" hilang
- ❌ Tombol "Hapus" hilang
- ✅ Badge "👁️ Mode Read-Only" muncul
- ❌ Semua input form DISABLED (tidak bisa diklik)

**Test:**
1. Login → ✅ Badge merah "Mode Read-Only" muncul
2. Buka "Bahan Baku"
3. Lihat tabel → ✅ Data tampil
4. Cari tombol "Tambah Item" → ❌ HILANG
5. Lihat kolom aksi → ❌ Tombol edit/hapus HILANG
6. Buka "Mutasi Stok"
7. Coba input stok → ❌ Form DISABLED (abu-abu)
8. Klik input field → ❌ Tidak bisa diklik
9. Buka "Dashboard" → ✅ Bisa lihat stats & chart
10. Buka "Laporan" → ✅ Bisa lihat data

**Kalau Coba Paksa (via Console):**
```javascript
deleteItem(5)
// Response: Akses ditolak!

submitStockIn()
// Response: Akses ditolak!
```

---

## 📊 TABEL PERBANDINGAN

| Fitur | Admin | Operator | Viewer |
|-------|-------|----------|--------|
| **Lihat Data** | ✅ | ✅ | ✅ |
| **Search/Filter** | ✅ | ✅ | ✅ |
| **Dashboard** | ✅ | ✅ | ✅ |
| **Laporan** | ✅ | ✅ | ✅ |
| **Tambah Item** | ✅ | ✅ | ❌ |
| **Edit Item** | ✅ | ✅ | ❌ |
| **Hapus Item** | ✅ | ❌ | ❌ |
| **Input Stok** | ✅ | ✅ | ❌ |
| **Tambah Pelanggan** | ✅ | ✅ | ❌ |
| **Edit Pelanggan** | ✅ | ✅ | ❌ |
| **Hapus Pelanggan** | ✅ | ❌ | ❌ |
| **Buat Order** | ✅ | ✅ | ❌ |
| **Update Status Order** | ✅ | ✅ | ❌ |
| **Export CSV** | ✅ | ✅ | ❌ |

---

## 🛡️ KEAMANAN

### 1. Double Check (Frontend + Backend)

**Frontend (JavaScript):**
- Hide tombol → User tidak lihat
- Disable input → User tidak bisa klik
- Alert "Akses ditolak" kalau coba paksa

**Backend (PHP):**
- Cek role di session
- Return error kalau role tidak sesuai
- Query tidak dijalankan kalau role salah

**Contoh:**
```javascript
// FRONTEND: Hide tombol
if (role === 'viewer') hideElements('.btn-danger');

// USER coba paksa via console
deleteItem(5);

// JS: Cek permission
if (!checkPermission('delete')) {
  showToast('Akses ditolak', 'error');
  return;  // Stop di sini!
}

// USER tetap kirim request manual (pakai curl/postman)
// → Request sampai ke backend

// BACKEND: Cek lagi
if (!hasPermission('delete')) {
  echo json_encode(['berhasil' => false, 'pesan' => 'Akses ditolak']);
  exit;  // Stop di sini!
}

// Baru jalankan DELETE query
```

### 2. Session Tidak Bisa Diubah Manual
```javascript
// User coba ubah role via console
window.APP_CONFIG.userRole = 'admin';

// ❌ TIDAK AKAN BERPENGARUH!
// Karena backend cek $_SESSION di server, bukan dari JavaScript
```

---

## 🔧 CARA MENAMBAH ROLE BARU

Misalnya mau tambah role **"Manager"** yang bisa lihat & export, tapi tidak bisa edit/hapus:

### 1. Update `helpers.php`:
```php
$permissions = [
    'admin' => ['view', 'create', 'edit', 'delete', 'export'],
    'operator' => ['view', 'create', 'edit', 'export'],
    'manager' => ['view', 'export'],  // ← Role baru
    'viewer' => ['view']
];
```

### 2. Update `app.js`:
```javascript
function applyRoleBasedUI() {
  const role = window.APP_CONFIG.userRole;
  
  if (role === 'manager') {
    // Hide tombol tambah, edit, hapus
    hideElements('.btn-primary[onclick*="openAdd"]');
    hideElements('button[onclick*="edit"]');
    hideElements('button[onclick*="delete"]');
    
    // Tapi tombol export tetap ada
  }
}
```

### 3. Tambah User Manager di Database:
```sql
INSERT INTO users (name, email, password, role) 
VALUES ('Manager', 'manager@percetakan.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'manager');
```

---

## ❓ FAQ

### Q: Viewer bisa lihat password pelanggan?
**A:** Tidak, karena password di-hash. Viewer cuma lihat data biasa (nama, kota, dll).

### Q: Operator bisa hapus data via SQL langsung?
**A:** Kalau dia punya akses phpMyAdmin, bisa. Tapi itu sudah masalah keamanan server.

### Q: Gimana kalau role salah di database?
**A:** Login ulang, karena role dicek saat login dan disimpan di session.

### Q: Bisa ubah role tanpa logout?
**A:** Tidak, harus logout → admin update role di database → login lagi.

---

## 🎯 KESIMPULAN

Sistem role ini membuat website lebih aman karena:

✅ **Admin** → Full control (owner/manager)  
✅ **Operator** → Bisa kerja tapi tidak bisa hapus (staff)  
✅ **Viewer** → Hanya lihat (client/auditor)  

**Keamanan Berlapis:**
1. ✅ UI hide tombol (user tidak lihat)
2. ✅ JavaScript cek permission (user tidak bisa klik)
3. ✅ Backend cek role (user tidak bisa paksa)

---

**🎉 Sekarang sistem kamu punya Role-Based Access Control yang proper!**
