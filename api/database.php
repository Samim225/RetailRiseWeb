<?php
// اتصال به پایگاه داده SQLite
class Database {
    private $pdo;
    private $db_file = '../db/store.db';
    
    public function __construct() {
        try {
            // ایجاد دایرکتوری db اگر وجود ندارد
            if (!file_exists('../db')) {
                mkdir('../db', 0777, true);
            }
            
            // اتصال به پایگاه داده
            $this->pdo = new PDO("sqlite:" . $this->db_file);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // تنظیم کدگذاری UTF-8
            $this->pdo->exec("PRAGMA encoding = 'UTF-8'");
            $this->pdo->exec("PRAGMA foreign_keys = ON");
            
            // ایجاد جداول اگر وجود ندارند
            $this->createTables();
            
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    private function createTables() {
        // جدول محصولات
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            stock REAL NOT NULL DEFAULT 0,
            price REAL NOT NULL DEFAULT 0,
            cost REAL NOT NULL DEFAULT 0,
            profit REAL NOT NULL DEFAULT 0,
            is_kg INTEGER NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // جدول مشتریان
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            address TEXT,
            debt REAL NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // جدول سبد خرید
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS cart (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            quantity REAL NOT NULL,
            price REAL NOT NULL,
            total REAL NOT NULL,
            profit REAL NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
        
        // جدول تاریخچه فروش
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS sales_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id INTEGER NOT NULL,
            product_name TEXT NOT NULL,
            quantity REAL NOT NULL,
            price REAL NOT NULL,
            total REAL NOT NULL,
            profit REAL NOT NULL DEFAULT 0,
            customer_id INTEGER,
            sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
        )");
        
        // جدول هزینه‌ها
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            description TEXT NOT NULL,
            amount REAL NOT NULL,
            category TEXT,
            expense_date DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // بک‌آپ از دیتابیس
    public function backup() {
        $backup_file = '../db/backup_' . date('Y-m-d_H-i-s') . '.db';
        if (copy($this->db_file, $backup_file)) {
            return $backup_file;
        }
        return false;
    }
}
?>