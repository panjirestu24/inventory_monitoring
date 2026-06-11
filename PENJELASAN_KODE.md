# 💡 PENJELASAN KODE - Cara Kerja Website

## 🎯 TUJUAN DOKUMEN INI
Menjelaskan bagaimana kode website bekerja dalam **bahasa yang mudah dipahami pemula**.

---

## 🏗️ ARSITEKTUR SISTEM

Website ini menggunakan arsitektur **3 Layer**:

```
┌─────────────────┐
│   FRONTEND      │  ← index.php + CSS + JavaScript
│  (Tampilan)     │     (Yang dilihat user di browser)
└────────┬────────┘
         │
         │ HTTP Request (fetch API)
         │
┌────────▼────────┐
│   BACKEND       │  ← File PHP di folder api/
│  (Logic/API)    │     (Ambil/simpan data)
└────────┬────────┘
         │
         │ SQL Query
         │
┌────────▼────────┐
│   DATABASE      │  ← MySQL (db_inventory)
│  (Penyimpanan)  │     (Tempat data disimpan)
└─────────────────┘
```

**Alur Kerja:**
1. User klik tombol di browser → JavaScript kirim request ke PHP
2. PHP terima request → query ke database MySQL
3. Database kirim hasil → PHP format jadi JSON
4. JavaScript terima JSON → tampilkan di browser

---

## 📂 PENJELASAN FILE PER FILE

### 1. `index.php` (Halaman Utama)

**Fungsi:** Tampilan website yang dilihat user.

**Struktur:**
```html
<!DOCTYPE html>
<html>
<head>
  <!-- Link CSS & Font -->
  <link rel="stylesheet" href="css/app.css">
</head>
<body>
  
  <!-- SIDEBAR (Menu Navigasi) -->
  <aside class="sidebar">
    <div class="nav-item" data-page="dashboard">Dashboard</div>
    <div class="nav-item" data-page="items">Bahan Baku</div>
    <!-- dst -->
  </aside>

  <!-- ISI HALAMAN -->
  <main class="page-content">
    
    <!-- Halaman Dashboard -->
    <div class="page" id="page-dashboard">
      <div id="dashboard-stats"></div>
      <div id="dashboard-machines"></div>
    </div>

    <!-- Halaman Items -->
    <div class="page" id="page-items">
      <table id="items-table"></table>
    </div>

  </main>

  <!-- JavaScript -->
  <script src="js/app.js"></script>
</body>
</html>
```

**Konsep Penting:**
- Website ini **Single Page Application (SPA)** → semua halaman ada di 1 file
- Navigasi TIDAK pindah halaman, tapi **ganti tampilan** pakai JavaScript
- Div dengan class `page` = 1 halaman. Yang aktif: class `active`

---

### 2. `css/app.css` (Style/Tampilan)

**Fungsi:** Ngatur warna, ukuran, animasi, layout.

**Contoh Kode:**
```css
/* Variabel warna (biar gampang ganti tema) */
:root {
  --primary: #6366f1;      /* Warna utama (ungu) */
  --bg-base: #0f0f1a;       /* Background gelap */
  --text-primary: #f1f5f9;  /* Warna teks putih */
}

/* Sidebar */
.sidebar {
  width: 260px;           /* Lebar sidebar */
  background: #161627;    /* Warna background */
  border-right: 1px solid rgba(99,102,241,0.15); /* Garis kanan */
}

/* Tombol */
.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6); /* Gradien */
  color: white;
  padding: 8px 16px;
  border-radius: 8px;     /* Sudut melengkung */
}

/* Hover effect (pas mouse lewat) */
.btn-primary:hover {
  box-shadow: 0 6px 20px rgba(99,102,241,0.5); /* Shadow glow */
  transform: translateY(-1px); /* Naik 1px */
}
```

**Konsep CSS yang Dipakai:**
- **CSS Variables** (`--primary`) → gampang ganti tema
- **Flexbox** → layout yang fleksibel
- **Grid** → layout kolom otomatis
- **Transition** → animasi smooth
- **Media Query** → responsive (mobile-friendly)

---

### 3. `js/app.js` (Logic Frontend)

**Fungsi:** Ambil data dari API, tampilkan di HTML, handle user interaction.

#### **A. Struktur Dasar**

```javascript
// ===== VARIABEL GLOBAL =====
let allItems = [];        // Menyimpan data items
let allOrders = [];       // Menyimpan data orders
let currentPage = 'dashboard';  // Halaman aktif saat ini

// ===== FUNGSI INIT (Jalan pertama kali) =====
document.addEventListener('DOMContentLoaded', async () => {
  feather.replace();           // Load icon
  await loadRefData();         // Ambil data referensi
  navigate('dashboard');       // Buka halaman dashboard
});

// ===== NAVIGASI (Pindah Halaman) =====
function navigate(page) {
  // 1. Sembunyikan semua halaman
  document.querySelectorAll('.page').forEach(p => 
    p.classList.remove('active')
  );
  
  // 2. Tampilkan halaman yang dipilih
  document.getElementById(`page-${page}`).classList.add('active');
  
  // 3. Load data halaman
  if (page === 'dashboard') loadDashboard();
  if (page === 'items') loadItems();
}
```

#### **B. Ambil Data dari API (AJAX)**

**Cara Lama (XMLHttpRequest):**
```javascript
var xhr = new XMLHttpRequest();
xhr.open('GET', 'api/dashboard.php', true);
xhr.onload = function() {
  var data = JSON.parse(xhr.responseText);
  console.log(data);
};
xhr.send();
```

**Cara Modern (Fetch API):** ← Yang dipakai
```javascript
async function loadDashboard() {
  // 1. Kirim request ke API
  const response = await fetch('api/dashboard.php');
  
  // 2. Convert response jadi JSON
  const data = await response.json();
  
  // 3. Tampilkan data
  document.getElementById('stat-items').textContent = data.stats.total_items;
  
  // 4. Render tabel/card
  renderDashboardMachines(data.mesin);
}
```

**Penjelasan `async/await`:**
- `async` = fungsi yang berjalan secara asynchronous (tidak blocking)
- `await` = tunggu sampai selesai baru lanjut baris berikutnya
- **Contoh analogi:** Pesan makanan online
  - `await` = tunggu sampai datang
  - Tanpa `await` = langsung makan (error, masakannya belum datang!)

#### **C. Render Data ke HTML**

```javascript
function renderItemsTable(items) {
  const tbody = document.getElementById('items-tbody');
  
  // 1. Cek kalau data kosong
  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="9">Tidak ada data</td></tr>';
    return;
  }
  
  // 2. Loop data, buat HTML
  tbody.innerHTML = items.map(item => `
    <tr>
      <td>${item.code}</td>
      <td>${item.name}</td>
      <td>${item.stock} ${item.unit_symbol}</td>
      <td>
        <button onclick="editItem(${item.id})">Edit</button>
      </td>
    </tr>
  `).join('');
}
```

**Penjelasan:**
- `map()` = loop array, return array baru
- **Template Literal** (`` ` ``) = string dengan ${variable}
- `join('')` = gabungkan array jadi 1 string

#### **D. Submit Form (POST Data)**

```javascript
async function submitAddItem(e) {
  e.preventDefault();  // Jangan reload halaman
  
  // 1. Ambil data dari form
  const data = {
    code: document.getElementById('item-code').value,
    name: document.getElementById('item-name').value,
    stock: document.getElementById('item-stock').value
  };
  
  // 2. Kirim ke API
  const response = await fetch('api/items.php?action=add', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(data)  // Convert object → JSON string
  });
  
  // 3. Cek hasil
  const result = await response.json();
  if (result.berhasil) {
    alert('Item berhasil ditambahkan!');
    closeModal('modal-add-item');
    loadItems();  // Refresh tabel
  }
}
```

---

### 4. `config/database.php` (Koneksi Database)

**Fungsi:** Membuat koneksi ke database MySQL.

```php
<?php
// ===== SETTING DATABASE =====
define('DB_HOST', 'localhost');  // Server database
define('DB_USER', 'root');       // Username
define('DB_PASS', '');           // Password (kosong)
define('DB_NAME', 'db_inventory'); // Nama database

// ===== CLASS DATABASE =====
class Database {
    private static $instance = null;  // Singleton pattern
    private $conn;  // PDO connection
    
    // Konstruktor (jalan otomatis saat objek dibuat)
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
        
        try {
            // Buat koneksi PDO
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Koneksi gagal: " . $e->getMessage());
        }
    }
    
    // Ambil instance (biar cuma 1 koneksi)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Ambil koneksi PDO
    public function getConnection() {
        return $this->conn;
    }
}

// ===== FUNGSI HELPER =====
function db() {
    return Database::getInstance()->getConnection();
}
?>
```

**Konsep Penting:**
- **PDO** = PHP Data Objects (cara modern akses database)
- **Singleton Pattern** = cuma bikin 1 koneksi (hemat resource)
- **try-catch** = tangani error dengan baik

---

### 5. `api/dashboard.php` (API Dashboard)

**Fungsi:** Ambil data stats, machines, low stock, dll untuk dashboard.

```php
<?php
header('Content-Type: application/json');  // Response berupa JSON
require_once '../config/database.php';

$pdo = db();  // Ambil koneksi database

// ===== QUERY DATABASE =====

// 1. Total items & stok rendah
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN stock <= min_stock THEN 1 ELSE 0 END) as low_stock
    FROM items 
    WHERE is_active=1
");
$result = $stmt->fetch();  // Ambil 1 baris

// 2. Order hari ini
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM orders 
    WHERE DATE(created_at) = CURDATE()
");
$ordersToday = $stmt->fetchColumn();  // Ambil 1 kolom

// 3. Daftar mesin
$stmt = $pdo->query("
    SELECT m.*, o.order_number, o.title as current_job
    FROM machines m
    LEFT JOIN orders o ON m.id = o.machine_id AND o.status = 'in_progress'
    ORDER BY m.code
");
$machines = $stmt->fetchAll();  // Ambil semua baris (array)

// ===== KIRIM RESPONSE JSON =====
echo json_encode([
    'berhasil' => true,
    'stats' => [
        'total_items' => (int)$result['total'],
        'low_stock' => (int)$result['low_stock'],
        'orders_today' => (int)$ordersToday
    ],
    'mesin' => $machines
]);
?>
```

**Konsep SQL:**
- `COUNT(*)` = hitung jumlah baris
- `SUM(CASE WHEN...)` = conditional sum
- `LEFT JOIN` = gabung 2 tabel (tetap tampil walau tidak match)
- `CURDATE()` = tanggal hari ini

**Konsep PHP:**
- `fetch()` = ambil 1 baris hasil query (return array)
- `fetchAll()` = ambil semua baris (return array of arrays)
- `fetchColumn()` = ambil 1 kolom (return value)
- `json_encode()` = convert array PHP → JSON string

---

### 6. `api/items.php` (API Bahan Baku)

**Fungsi:** CRUD (Create, Read, Update, Delete) items + stok masuk/keluar.

```php
<?php
header('Content-Type: application/json');
require_once '../config/database.php';

$pdo = db();
$method = $_SERVER['REQUEST_METHOD'];  // GET/POST/PUT/DELETE
$action = $_GET['action'] ?? 'list';

// ===== ROUTING (Tentukan aksi berdasarkan method) =====
switch ($method) {
    
    // ===== GET (Ambil Data) =====
    case 'GET':
        if ($action === 'list') {
            // Ambil semua items
            $stmt = $pdo->query("
                SELECT i.*, u.symbol as unit_symbol, c.name as category_name
                FROM items i
                JOIN units u ON i.unit_id = u.id
                JOIN categories c ON i.category_id = c.id
                WHERE i.is_active = 1
                ORDER BY i.name
            ");
            echo json_encode(['berhasil' => true, 'data' => $stmt->fetchAll()]);
        }
        break;
    
    // ===== POST (Tambah Data Baru) =====
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if ($action === 'add') {
            // Insert item baru
            $stmt = $pdo->prepare("
                INSERT INTO items (code, name, category_id, unit_id, stock, min_stock, purchase_price)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['code'], 
                $data['name'], 
                $data['category_id'], 
                $data['unit_id'],
                $data['stock'] ?? 0,
                $data['min_stock'] ?? 0,
                $data['purchase_price'] ?? 0
            ]);
            
            echo json_encode([
                'berhasil' => true, 
                'pesan' => 'Item berhasil ditambahkan',
                'id' => $pdo->lastInsertId()  // ID item baru
            ]);
        }
        
        elseif ($action === 'stock_in') {
            // Stok masuk
            $itemId = $data['item_id'];
            $qty = $data['quantity'];
            
            // 1. Ambil stok sekarang
            $stmt = $pdo->prepare("SELECT stock FROM items WHERE id=?");
            $stmt->execute([$itemId]);
            $before = (float)$stmt->fetchColumn();
            
            // 2. Hitung stok baru
            $after = $before + $qty;
            
            // 3. Update stok
            $pdo->prepare("UPDATE items SET stock=? WHERE id=?")
                ->execute([$after, $itemId]);
            
            // 4. Catat transaksi
            $pdo->prepare("
                INSERT INTO stock_transactions 
                (item_id, type, reference_type, quantity, stock_before, stock_after, notes, created_by)
                VALUES (?,?,?,?,?,?,?,?)
            ")->execute([
                $itemId, 'in', 'purchase', $qty, $before, $after, 
                $data['notes'] ?? '', 1
            ]);
            
            echo json_encode(['berhasil' => true, 'pesan' => 'Stok masuk berhasil dicatat']);
        }
        break;
    
    // ===== PUT (Update Data) =====
    case 'PUT':
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $pdo->prepare("
            UPDATE items 
            SET name=?, category_id=?, unit_id=?, min_stock=?, purchase_price=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['name'], 
            $data['category_id'], 
            $data['unit_id'],
            $data['min_stock'], 
            $data['purchase_price'],
            $data['id']
        ]);
        echo json_encode(['berhasil' => true, 'pesan' => 'Item berhasil diupdate']);
        break;
    
    // ===== DELETE (Hapus Data) =====
    case 'DELETE':
        $id = $_GET['id'];
        // Soft delete (ubah is_active jadi 0, data tidak benar-benar dihapus)
        $pdo->prepare("UPDATE items SET is_active=0 WHERE id=?")->execute([$id]);
        echo json_encode(['berhasil' => true, 'pesan' => 'Item berhasil dihapus']);
        break;
}
?>
```

**Konsep Penting:**

**1. REST API Verbs:**
- `GET` = Ambil data (SELECT)
- `POST` = Tambah data baru (INSERT)
- `PUT` = Update data (UPDATE)
- `DELETE` = Hapus data (DELETE)

**2. Prepared Statement:**
```php
// ❌ SALAH (SQL Injection!)
$stmt = $pdo->query("SELECT * FROM items WHERE id=" . $_GET['id']);

// ✅ BENAR (Aman dari SQL Injection)
$stmt = $pdo->prepare("SELECT * FROM items WHERE id=?");
$stmt->execute([$_GET['id']]);
```

**3. JSON Input/Output:**
```php
// Input: ambil JSON dari request body
$data = json_decode(file_get_contents('php://input'), true);

// Output: kirim response JSON
echo json_encode(['berhasil' => true, 'data' => $items]);
```

---

## 🔄 ALUR DATA LENGKAP

### Contoh: User Tambah Item Baru

```
1. USER: Klik tombol "Tambah Item"
   └─> JavaScript: openAddItemModal()

2. USER: Isi form → klik "Simpan"
   └─> JavaScript: submitAddItem(event)
       └─> fetch('api/items.php?action=add', {POST, body: JSON})

3. PHP: api/items.php menerima request
   └─> $method = 'POST', $action = 'add'
   └─> Ambil data dari JSON
   └─> INSERT INTO items...
   └─> Kirim response: {berhasil: true, id: 99}

4. JavaScript: Terima response
   └─> if (result.berhasil) alert('Sukses!')
   └─> closeModal()
   └─> loadItems() // Refresh tabel

5. USER: Lihat item baru di tabel ✅
```

---

## 🧠 KONSEP PENTING UNTUK DIPAHAMI

### 1. Single Page Application (SPA)
- **Tradisional:** Klik link → pindah halaman (reload)
- **SPA:** Klik link → ganti konten (tanpa reload) ← lebih cepat!

### 2. AJAX (Asynchronous JavaScript)
- Ambil data dari server **tanpa reload** halaman
- Pakai `fetch()` atau `XMLHttpRequest`

### 3. JSON (JavaScript Object Notation)
Format data untuk komunikasi frontend ↔ backend
```json
{
  "berhasil": true,
  "data": [
    {"id": 1, "name": "Kertas HVS"},
    {"id": 2, "name": "Tinta Black"}
  ]
}
```

### 4. PDO (PHP Data Objects)
Cara modern akses database di PHP.
- **Keuntungan:** Aman dari SQL Injection, support banyak database

### 5. Prepared Statement
Query dengan placeholder `?` untuk keamanan.
```php
$stmt = $pdo->prepare("SELECT * FROM items WHERE id=?");
$stmt->execute([$id]);  // Otomatis di-escape
```

---

## 📚 BELAJAR LEBIH LANJUT

### JavaScript:
- Array methods: `map()`, `filter()`, `reduce()`
- Async/await, Promise
- DOM manipulation
- Fetch API

### PHP:
- PDO
- REST API
- OOP (Object Oriented Programming)
- Session & Authentication

### SQL:
- JOIN (INNER, LEFT, RIGHT)
- Aggregate functions (COUNT, SUM, AVG)
- Subquery
- Transaction

### CSS:
- Flexbox
- Grid
- CSS Variables
- Animation/Transition

---

**Semoga membantu pemahaman kamu! 🚀**
