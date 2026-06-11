<?php
// ============================================================
// KONFIGURASI DATABASE
// Ubah sesuai dengan pengaturan Laragon kamu
// ============================================================
define('DB_HOST', 'localhost');     // Host database
define('DB_USER', 'root');          // Username database (default Laragon: root)
define('DB_PASS', '');              // Password database (default Laragon: kosong)
define('DB_NAME', 'db_inventory');  // Nama database
define('DB_CHARSET', 'utf8mb4');    // Charset (jangan diubah)

// ============================================================
// CLASS DATABASE - Mengelola Koneksi ke Database
// ============================================================
class Database {
    private static $instance = null;  // Menyimpan instance tunggal
    private $conn;                     // Koneksi PDO

    // Konstruktor - membuat koneksi database
    private function __construct() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Tampilkan error
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Return array asosiatif
            PDO::ATTR_EMULATE_PREPARES   => false,                   // Prepared statement asli
        ];
        try {
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode([
                'berhasil' => false, 
                'pesan' => 'Koneksi database gagal: ' . $e->getMessage()
            ]));
        }
    }

    // Mendapatkan instance tunggal (Singleton Pattern)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Mendapatkan koneksi PDO
    public function getConnection() {
        return $this->conn;
    }
}

// Fungsi helper untuk mendapatkan koneksi database dengan cepat
function db() {
    return Database::getInstance()->getConnection();
}
