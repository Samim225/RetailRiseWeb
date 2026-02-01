<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'database.php';

$db = new Database();
$pdo = $db->getConnection();

$action = $_GET['action'] ?? '';

// هندل کردن درخواست‌های مختلف
switch ($action) {
    // محصولات
    case 'getProducts':
        getProducts();
        break;
    case 'addProduct':
        addProduct();
        break;
    case 'deleteProduct':
        deleteProduct();
        break;
    case 'getProduct':
        getProduct();
        break;
        
    // مشتریان
    case 'getCustomers':
        getCustomers();
        break;
    case 'addCustomer':
        addCustomer();
        break;
        
    // سبد خرید
    case 'getCart':
        getCart();
        break;
    case 'addToCart':
        addToCart();
        break;
    case 'removeFromCart':
        removeFromCart();
        break;
    case 'sellAllCart':
        sellAllCart();
        break;
        
    // فروش
    case 'sellProduct':
        sellProduct();
        break;
        
    // آمار و گزارشات
    case 'getDashboardStats':
        getDashboardStats();
        break;
    case 'getReports':
        getReports();
        break;
        
    // مدیریت پایگاه داده
    case 'backupDatabase':
        backupDatabase();
        break;
    case 'clearAllData':
        clearAllData();
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action not found']);
        break;
}

// ========== توابع محصولات ==========
function getProducts() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $products]);
}

function addProduct() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO products (name, stock, price, cost, profit, is_kg) VALUES (?, ?, ?, ?, ?, ?)");
    $success = $stmt->execute([
        $data['name'],
        $data['stock'],
        $data['price'],
        $data['cost'],
        $data['profit'],
        $data['is_kg']
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add product']);
    }
}

function deleteProduct() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $success = $stmt->execute([$data['id']]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
}

function getProduct() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$data['id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        echo json_encode(['success' => true, 'data' => $product]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
}

// ========== توابع مشتریان ==========
function getCustomers() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM customers ORDER BY created_at DESC");
    $customers = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $customers]);
}

function addCustomer() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("INSERT INTO customers (name, phone, address, debt) VALUES (?, ?, ?, ?)");
    $success = $stmt->execute([
        $data['name'],
        $data['phone'],
        $data['address'],
        $data['debt']
    ]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Customer added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add customer']);
    }
}

// ========== توابع سبد خرید ==========
function getCart() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM cart ORDER BY created_at DESC");
    $cart = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $cart]);
}

function addToCart() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // بررسی وجود محصول در سبد
    $stmt = $pdo->prepare("SELECT * FROM cart WHERE product_id = ?");
    $stmt->execute([$data['product_id']]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // به‌روزرسانی مقدار
        $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity + ?, total = total + ? WHERE product_id = ?");
        $success = $stmt->execute([
            $data['quantity'],
            $data['total'],
            $data['product_id']
        ]);
    } else {
        // افزودن جدید
        $stmt = $pdo->prepare("INSERT INTO cart (product_id, name, quantity, price, total, profit) VALUES (?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $data['product_id'],
            $data['name'],
            $data['quantity'],
            $data['price'],
            $data['total'],
            $data['profit']
        ]);
    }
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Added to cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
}

function removeFromCart() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ?");
    $success = $stmt->execute([$data['id']]);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Removed from cart']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove from cart']);
    }
}

function sellAllCart() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // دریافت همه آیتم‌های سبد
        $stmt = $pdo->query("SELECT * FROM cart");
        $cartItems = $stmt->fetchAll();
        
        foreach ($cartItems as $item) {
            // ثبت در تاریخچه فروش
            $stmt = $pdo->prepare("INSERT INTO sales_history (product_id, product_name, quantity, price, total, profit) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $item['product_id'],
                $item['name'],
                $item['quantity'],
                $item['price'],
                $item['total'],
                $item['profit']
            ]);
            
            // کاهش موجودی محصول
            $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // خالی کردن سبد
        $pdo->exec("DELETE FROM cart");
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'All items sold successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to sell items: ' . $e->getMessage()]);
    }
}

// ========== توابع فروش ==========
function sellProduct() {
    global $pdo;
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    try {
        $pdo->beginTransaction();
        
        // دریافت اطلاعات محصول
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$data['product_id']]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // ثبت در تاریخچه فروش
        $stmt = $pdo->prepare("INSERT INTO sales_history (product_id, product_name, quantity, total) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['product_id'],
            $product['name'],
            $data['quantity'],
            $data['total']
        ]);
        
        // کاهش موجودی
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$data['quantity'], $data['product_id']]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Product sold successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to sell product: ' . $e->getMessage()]);
    }
}

// ========== توابع آمار و گزارشات ==========
function getDashboardStats() {
    global $pdo;
    
    $stats = [];
    
    // تعداد کل محصولات
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // ارزش کل موجودی
    $stmt = $pdo->query("SELECT SUM(stock * price) as total FROM products WHERE stock > 0");
    $stats['total_value'] = $stmt->fetch()['total'] ?? 0;
    
    // محصولات فعال (موجودی بیشتر از 0)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock > 0");
    $stats['active_items'] = $stmt->fetch()['count'];
    
    // درآمد امروز
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT SUM(total) as income FROM sales_history WHERE DATE(sale_date) = ?");
    $stmt->execute([$today]);
    $stats['today_income'] = $stmt->fetch()['income'] ?? 0;
    
    // فروش امروز
    $stmt = $pdo->prepare("SELECT SUM(quantity) as sales FROM sales_history WHERE DATE(sale_date) = ?");
    $stmt->execute([$today]);
    $stats['today_sales'] = $stmt->fetch()['sales'] ?? 0;
    
    // موجودی کم (کمتر از 5)
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE stock > 0 AND stock <= 5");
    $stats['low_stock'] = $stmt->fetch()['count'];
    
    // سود خالص امروز
    $stmt = $pdo->prepare("SELECT SUM(profit) as profit FROM sales_history WHERE DATE(sale_date) = ?");
    $stmt->execute([$today]);
    $stats['net_profit'] = $stmt->fetch()['profit'] ?? 0;
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

function getReports() {
    global $pdo;
    
    $reports = [];
    
    // درآمد کل
    $stmt = $pdo->query("SELECT SUM(total) as income FROM sales_history");
    $reports['total_income'] = $stmt->fetch()['income'] ?? 0;
    
    // هزینه‌های کل
    $stmt = $pdo->query("SELECT SUM(amount) as expenses FROM expenses");
    $reports['total_expenses'] = $stmt->fetch()['expenses'] ?? 0;
    
    // سود کل
    $stmt = $pdo->query("SELECT SUM(profit) as profit FROM sales_history");
    $reports['total_profit'] = $stmt->fetch()['profit'] ?? 0;
    
    // داده‌های نمودار (فروش 7 روز گذشته)
    $chart_data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(total) as sales FROM sales_history WHERE DATE(sale_date) = ?");
        $stmt->execute([$date]);
        $sales = $stmt->fetch()['sales'] ?? 0;
        
        $chart_data['labels'][] = $date;
        $chart_data['data'][] = $sales;
    }
    
    $reports['chart_data'] = $chart_data;
    
    echo json_encode(['success' => true, 'data' => $reports]);
}

// ========== توابع مدیریت پایگاه داده ==========
function backupDatabase() {
    global $db;
    
    $backup_file = $db->backup();
    
    if ($backup_file) {
        echo json_encode(['success' => true, 'message' => 'Backup created: ' . basename($backup_file)]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create backup']);
    }
}

function clearAllData() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        $tables = ['products', 'customers', 'cart', 'sales_history', 'expenses'];
        
        foreach ($tables as $table) {
            $pdo->exec("DELETE FROM $table");
            
            // بازنشانی شمارنده AUTOINCREMENT
            if ($table !== 'cart') { // cart معمولاً AUTOINCREMENT ندارد
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='$table'");
            }
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'All data cleared successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Failed to clear data: ' . $e->getMessage()]);
    }
}
?>