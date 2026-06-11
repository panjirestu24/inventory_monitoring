# 📖 ALUR FITUR LENGKAP - Sistem Inventory Percetakan

## 🎯 TUJUAN DOKUMEN
Menjelaskan **alur lengkap** setiap fitur dari **User → Frontend → Backend → Database → Response**.

---

## 🔄 ARSITEKTUR KOMUNIKASI

```
USER (Browser)
    ↓ Klik tombol / Isi form
FRONTEND (index.php + app.js)
    ↓ HTTP Request (fetch API)
BACKEND (api/*.php)
    ↓ SQL Query
DATABASE (MySQL - db_inventory)
    ↓ Data Result
BACKEND (Format JSON)
    ↓ HTTP Response
FRONTEND (Render ke HTML)
    ↓ Tampil di layar
USER (Lihat hasil)
```

---

## 1️⃣ FITUR: EDIT BAHAN BAKU (LENGKAP!)

### 📌 Alur Lengkap:

#### **STEP 1: User Klik Tombol Edit**
```
Lokasi: Halaman "Bahan Baku" → Tabel Items → Tombol Edit (icon pensil)
```

**Yang Terjadi:**
```javascript
// Di tabel, setiap row punya tombol:
<button onclick="openEditItemModal(${item.id})">Edit</button>

// User klik → JavaScript jalankan fungsi:
function openEditItemModal(id)
```

---

#### **STEP 2: JavaScript Ambil Data Item dari Server**
```javascript
// app.js - baris ~490
async function openEditItemModal(id) {
  // 1. Kirim request ke API untuk ambil data 1 item
  const data = await apiFetch(`api/items.php?action=get&id=${id}`);
  
  // 2. Cek apakah berhasil
  if (!data?.berhasil) {
    showToast('Gagal mengambil data item', 'error');
    return;
  }
  
  const item = data.data; // Data item dari database
  
  // 3. Isi form modal dengan data item
  document.getElementById('item-id').value = item.id;  // Hidden field
  document.getElementById('item-code').value = item.code;
  document.getElementById('item-name').value = item.name;
  // ... dst untuk field lainnya
  
  // 4. Buka modal
  openModal('modal-add-item');
}
```

**Request ke Server:**
```
GET http://localhost/inventory_monitoring/api/items.php?action=get&id=5
```

---

#### **STEP 3: Backend Proses Request**
```php
// api/items.php
case 'GET':
    if ($action === 'get') {
        $id = (int)($_GET['id'] ?? 0);  // Ambil ID dari URL
        
        // Query database
        $stmt = $pdo->prepare("
            SELECT i.*, 
                   u.symbol as unit_symbol, 
                   c.name as category_name 
            FROM items i
            JOIN units u ON i.unit_id = u.id
            JOIN categories c ON i.category_id = c.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        $item = $stmt->fetch();  // Ambil 1 baris
        
        // Kirim response JSON
        echo json_encode([
            'berhasil' => (bool)$item, 
            'data' => $item
        ]);
    }
    break;
```

**SQL yang Dijalankan:**
```sql
SELECT i.*, u.symbol as unit_symbol, c.name as category_name 
FROM items i
JOIN units u ON i.unit_id = u.id
JOIN categories c ON i.category_id = c.id
WHERE i.id = 5
```

**Response JSON:**
```json
{
  "berhasil": true,
  "data": {
    "id": 5,
    "code": "ITM005",
    "name": "Tinta Cyan Offset",
    "category_id": 2,
    "unit_id": 3,
    "stock": 25,
    "min_stock": 5,
    "purchase_price": 320000,
    "unit_symbol": "Kg",
    "category_name": "Tinta"
  }
}
```

---

#### **STEP 4: User Edit Data & Klik Simpan**
```
User ubah:
- Nama item: "Tinta Cyan Offset" → "Tinta Cyan Offset Premium"
- Min Stock: 5 → 10
- Harga Beli: 320000 → 350000

Lalu klik tombol "Simpan"
```

**Yang Terjadi:**
```javascript
// Form di-submit → jalankan fungsi:
async function submitAddItem(e) {
  e.preventDefault();  // Jangan reload halaman
  
  const itemId = document.getElementById('item-id').value;
  const isEdit = itemId !== '';  // Ada ID = mode edit
  
  // 1. Kumpulkan data dari form
  const data = {
    name: document.getElementById('item-name').value,  // Tinta Cyan Offset Premium
    category_id: document.getElementById('item-category').value,
    min_stock: document.getElementById('item-min-stock').value,  // 10
    purchase_price: document.getElementById('item-buy-price').value,  // 350000
    // ... data lainnya
  };
  
  // 2. Kalau edit, kirim PUT request
  if (isEdit) {
    data.id = itemId;  // 5
    res = await apiPut('api/items.php', data);
  }
  
  // 3. Cek response
  if (res?.berhasil) {
    showToast('Item berhasil diupdate', 'success');
    closeModal('modal-add-item');
    loadItems();  // Refresh tabel
  }
}
```

**Request ke Server:**
```
PUT http://localhost/inventory_monitoring/api/items.php
Content-Type: application/json

{
  "id": 5,
  "name": "Tinta Cyan Offset Premium",
  "category_id": 2,
  "min_stock": 10,
  "purchase_price": 350000,
  ...
}
```

---

#### **STEP 5: Backend Update Database**
```php
// api/items.php
case 'PUT':
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    
    // Query UPDATE
    $stmt = $pdo->prepare("
        UPDATE items 
        SET name = ?, 
            category_id = ?, 
            min_stock = ?, 
            purchase_price = ?, 
            ...
        WHERE id = ?
    ");
    
    $stmt->execute([
        $data['name'],           // Tinta Cyan Offset Premium
        $data['category_id'],    // 2
        $data['min_stock'],      // 10
        $data['purchase_price'], // 350000
        $id                      // 5
    ]);
    
    // Response
    echo json_encode([
        'berhasil' => true, 
        'pesan' => 'Item berhasil diupdate'
    ]);
    break;
```

**SQL yang Dijalankan:**
```sql
UPDATE items 
SET name = 'Tinta Cyan Offset Premium', 
    category_id = 2, 
    min_stock = 10, 
    purchase_price = 350000
WHERE id = 5
```

**Response JSON:**
```json
{
  "berhasil": true,
  "pesan": "Item berhasil diupdate"
}
```

---

#### **STEP 6: Frontend Refresh Tabel**
```javascript
// Setelah berhasil update, panggil:
loadItems();

// Fungsi ini akan:
async function loadItems() {
  // 1. Ambil semua item dari server (data terbaru)
  const data = await apiFetch('api/items.php?action=list');
  
  // 2. Simpan ke variabel global
  allItems = data?.data || [];
  
  // 3. Render ulang tabel
  renderItemsTable(allItems);
}
```

---

#### **STEP 7: User Lihat Hasil**
```
✅ Toast muncul: "Item berhasil diupdate"
✅ Modal tertutup
✅ Tabel refresh → data baru tampil:
   - Nama: "Tinta Cyan Offset Premium"
   - Min Stock: 10 Kg
   - Harga: Rp 350.000
```

---

### 📊 FLOW DIAGRAM LENGKAP

```
┌─────────────────────────────────────────────────────────────┐
│ USER: Klik tombol Edit                                      │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ JS: openEditItemModal(5)                                    │
│ → fetch('api/items.php?action=get&id=5')                   │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ PHP: SELECT * FROM items WHERE id=5                         │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ MYSQL: Return 1 row data item                               │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ PHP: echo json_encode(['berhasil'=>true, 'data'=>$item])   │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ JS: Isi form dengan data item                               │
│ → Buka modal                                                │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ USER: Edit field → Klik "Simpan"                            │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ JS: submitAddItem()                                         │
│ → PUT fetch('api/items.php', {body: dataForm})             │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ PHP: UPDATE items SET ... WHERE id=5                        │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ MYSQL: Update berhasil (affected rows: 1)                   │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ PHP: echo json_encode(['berhasil'=>true])                  │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ JS: Toast sukses → Close modal → Refresh tabel             │
└────────────────────┬────────────────────────────────────────┘
                     ↓
┌─────────────────────────────────────────────────────────────┐
│ USER: Lihat data baru di tabel ✅                           │
└─────────────────────────────────────────────────────────────┘
```

---

## 2️⃣ FITUR: TAMBAH/EDIT PELANGGAN (LENGKAP!)

### 📌 Alur Tambah Pelanggan Baru:

#### **STEP 1: User Klik "Tambah Pelanggan"**
```
Halaman: Pelanggan → Tombol "Tambah Pelanggan"
```

```javascript
// User klik → jalankan:
function openAddCustomerModal() {
  // 1. Reset form
  document.getElementById('customer-id').value = '';  // Kosongkan hidden ID
  document.querySelector('#modal-add-customer form').reset();
  
  // 2. Set judul modal
  document.getElementById('modal-customer-title').textContent = 'Tambah Pelanggan';
  
  // 3. Buka modal
  openModal('modal-add-customer');
}
```

---

#### **STEP 2: User Isi Form & Klik Simpan**
```
User isi:
- Kode: CUS006
- Nama: PT. Sukses Makmur
- Kontak: Budi Santoso
- Telepon: 0812-3456-7890
- Email: budi@suksesmakmur.com
- Kota: Bandung
- Alamat: Jl. Raya No. 123

Klik "Simpan"
```

```javascript
async function submitAddCustomer(e) {
  e.preventDefault();
  
  const customerId = document.getElementById('customer-id').value;
  const isEdit = customerId !== '';  // Kosong = mode tambah
  
  // 1. Kumpulkan data
  const data = {
    code: document.getElementById('cust-code').value,  // CUS006
    name: document.getElementById('cust-name').value,  // PT. Sukses Makmur
    contact_person: document.getElementById('cust-contact').value,
    phone: document.getElementById('cust-phone').value,
    email: document.getElementById('cust-email').value,
    city: document.getElementById('cust-city').value,
    address: document.getElementById('cust-address').value,
    notes: document.getElementById('cust-notes').value,
  };
  
  // 2. Kirim POST request (karena mode tambah)
  const res = await apiPost('api/customers.php', data);
  
  // 3. Cek response
  if (res?.berhasil) {
    showToast('Pelanggan berhasil ditambahkan', 'success');
    closeModal('modal-add-customer');
    loadCustomers();  // Refresh tabel
  }
}
```

**Request:**
```
POST http://localhost/inventory_monitoring/api/customers.php
Content-Type: application/json

{
  "code": "CUS006",
  "name": "PT. Sukses Makmur",
  "contact_person": "Budi Santoso",
  "phone": "0812-3456-7890",
  "email": "budi@suksesmakmur.com",
  "city": "Bandung",
  "address": "Jl. Raya No. 123",
  "notes": ""
}
```

---

#### **STEP 3: Backend Insert ke Database**
```php
// api/customers.php
case 'POST':
    $data = json_decode(file_get_contents('php://input'), true);
    
    // INSERT query
    $stmt = $pdo->prepare("
        INSERT INTO customers 
        (code, name, contact_person, phone, email, city, address, notes) 
        VALUES (?,?,?,?,?,?,?,?)
    ");
    
    $stmt->execute([
        $data['code'],           // CUS006
        $data['name'],           // PT. Sukses Makmur
        $data['contact_person'], // Budi Santoso
        $data['phone'],          // 0812-3456-7890
        $data['email'],          // budi@suksesmakmur.com
        $data['city'],           // Bandung
        $data['address'],        // Jl. Raya No. 123
        $data['notes']           // (kosong)
    ]);
    
    // Response
    echo json_encode([
        'berhasil' => true, 
        'pesan' => 'Pelanggan berhasil ditambahkan',
        'id' => $pdo->lastInsertId()  // ID pelanggan baru
    ]);
    break;
```

**SQL:**
```sql
INSERT INTO customers 
(code, name, contact_person, phone, email, city, address, notes) 
VALUES 
('CUS006', 'PT. Sukses Makmur', 'Budi Santoso', '0812-3456-7890', 
 'budi@suksesmakmur.com', 'Bandung', 'Jl. Raya No. 123', '')
```

**Response:**
```json
{
  "berhasil": true,
  "pesan": "Pelanggan berhasil ditambahkan",
  "id": 6
}
```

---

#### **STEP 4: User Lihat Hasil**
```
✅ Toast: "Pelanggan berhasil ditambahkan"
✅ Modal tertutup
✅ Tabel refresh → pelanggan baru muncul di baris terakhir
```

---

### 📌 Alur Edit Pelanggan (Mirip dengan Edit Item):

```
1. User klik tombol Edit → openEditCustomerModal(6)
2. JS ambil data: GET api/customers.php?action=get&id=6
3. PHP return data pelanggan
4. JS isi form modal dengan data
5. User edit → klik Simpan
6. JS kirim: PUT api/customers.php (dengan data baru)
7. PHP: UPDATE customers SET ... WHERE id=6
8. Response berhasil → Refresh tabel
```

---

## 3️⃣ PERBANDINGAN: TAMBAH vs EDIT

### Tambah Item/Customer:
```javascript
// Ciri-ciri mode TAMBAH:
const itemId = '';  // ID kosong
const isEdit = false;

// Request: POST
const res = await apiPost('api/items.php?action=add', data);

// SQL: INSERT INTO items ...
```

### Edit Item/Customer:
```javascript
// Ciri-ciri mode EDIT:
const itemId = '5';  // Ada ID
const isEdit = true;

// Request: PUT
data.id = itemId;
const res = await apiPut('api/items.php', data);

// SQL: UPDATE items SET ... WHERE id=5
```

---

## 🎯 KESIMPULAN ALUR UNIVERSAL

**Semua fitur mengikuti pola yang sama:**

```
1. USER ACTION (klik button/submit form)
   ↓
2. JAVASCRIPT FUNCTION (handle event)
   ↓
3. HTTP REQUEST (GET/POST/PUT/DELETE)
   ↓
4. PHP API (terima request)
   ↓
5. SQL QUERY (ambil/insert/update/delete data)
   ↓
6. DATABASE (eksekusi query)
   ↓
7. PHP RESPONSE (format JSON)
   ↓
8. JAVASCRIPT (terima response)
   ↓
9. DOM MANIPULATION (tampilkan hasil)
   ↓
10. USER (lihat perubahan)
```

---

## 📚 TIPS DEBUGGING

### Kalau Ada Error, Cek:

1. **Browser Console (F12 → Console)**
   - Lihat error JavaScript
   - Lihat request/response (tab Network)

2. **PHP Error Log**
   - `C:\laragon\logs\php_error.log`
   - Lihat error SQL atau syntax PHP

3. **Database**
   - Buka phpMyAdmin → cek data berubah atau tidak
   - Jalankan SQL query manual untuk test

4. **Response JSON**
   - F12 → Network → klik request → tab "Response"
   - Pastikan response sesuai yang diharapkan

---

**🎉 Sekarang kamu paham alur lengkap setiap fitur!**

Dengan memahami alur ini, kamu bisa:
✅ Menambah fitur baru sendiri  
✅ Debug error dengan cepat  
✅ Modifikasi fitur yang ada  
✅ Paham komunikasi frontend-backend  

**Selamat belajar! 🚀**
