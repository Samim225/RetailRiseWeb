// متغیرهای عمومی
let currentPage = 'dashboard';
let products = [];
let cart = [];
let customers = [];

// راه‌اندازی اولیه
document.addEventListener('DOMContentLoaded', function() {
    initApp();
    loadAllData();
    showPage('dashboard');
});

// مقداردهی اولیه برنامه
function initApp() {
    // رویداد فرم‌ها
    document.getElementById('addProductForm').addEventListener('submit', saveProduct);
    document.getElementById('addCustomerForm').addEventListener('submit', saveCustomer);
    
    // محاسبه قیمت کل هنگام تغییر مقدار
    document.getElementById('sellQuantity').addEventListener('input', calculateTotalPrice);
    
    // بارگذاری محصولات در dropdown
    loadProductsForSale();
}

// بارگذاری همه داده‌ها
async function loadAllData() {
    try {
        await loadProducts();
        await loadCart();
        await loadCustomers();
        await loadDashboardStats();
        await loadReports();
    } catch (error) {
        showAlert('خطا در بارگذاری داده‌ها', 'error');
    }
}

// توابع مدیریت صفحه
function showPage(pageName) {
    // مخفی کردن همه صفحات
    document.querySelectorAll('.page').forEach(page => {
        page.classList.remove('active');
    });
    
    // نشان دادن صفحه مورد نظر
    document.getElementById(pageName).classList.add('active');
    currentPage = pageName;
    
    // بستن منو در موبایل
    if (window.innerWidth <= 768) {
        toggleMenu();
    }
    
    // بارگذاری داده‌های صفحه
    switch(pageName) {
        case 'products':
            loadProducts();
            break;
        case 'cart':
            loadCart();
            break;
        case 'reports':
            loadReports();
            break;
        case 'accounts':
            loadCustomers();
            break;
    }
}

function toggleMenu() {
    document.getElementById('sideMenu').classList.toggle('active');
}

// توابع API
async function callAPI(endpoint, method = 'GET', data = null) {
    const url = `api/api.php?action=${endpoint}`;
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (result.success) {
            return result.data;
        } else {
            throw new Error(result.message || 'خطای سرور');
        }
    } catch (error) {
        console.error('API Error:', error);
        showAlert(error.message, 'error');
        throw error;
    }
}

// مدیریت محصولات
async function loadProducts() {
    try {
        products = await callAPI('getProducts');
        displayProducts(products);
    } catch (error) {
        console.error('خطا در بارگذاری محصولات:', error);
    }
}

function displayProducts(productsList) {
    const container = document.getElementById('productsList');
    container.innerHTML = '';
    
    if (productsList.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">محصولی یافت نشد</div>';
        return;
    }
    
    productsList.forEach(product => {
        const stockClass = product.stock <= 5 ? 'low' : '';
        const item = document.createElement('div');
        item.className = 'product-item';
        item.innerHTML = `
            <div class="product-info">
                <h4>${product.name}</h4>
                <p>قیمت: $${product.price.toFixed(2)}</p>
                <p class="product-stock ${stockClass}">موجودی: ${product.stock} ${product.is_kg ? 'کیلوگرم' : 'عدد'}</p>
            </div>
            <div class="product-actions">
                <button class="action-icon" onclick="editProduct(${product.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="action-icon delete" onclick="deleteProduct(${product.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(item);
    });
}

function searchProducts() {
    const query = document.getElementById('searchProduct').value.toLowerCase();
    const filtered = products.filter(p => 
        p.name.toLowerCase().includes(query) ||
        p.price.toString().includes(query)
    );
    displayProducts(filtered);
}

function showAddProductForm() {
    document.getElementById('addProductForm').reset();
    document.getElementById('addProductModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
}

async function saveProduct(e) {
    e.preventDefault();
    
    const productData = {
        name: document.getElementById('productName').value,
        stock: parseFloat(document.getElementById('productStock').value),
        price: parseFloat(document.getElementById('productPrice').value),
        cost: parseFloat(document.getElementById('productCost').value) || 0,
        profit: parseFloat(document.getElementById('productProfit').value) || 0,
        is_kg: document.getElementById('isKG').checked ? 1 : 0
    };
    
    try {
        await callAPI('addProduct', 'POST', productData);
        showAlert('محصول با موفقیت ذخیره شد', 'success');
        closeModal();
        loadAllData();
    } catch (error) {
        showAlert('خطا در ذخیره محصول', 'error');
    }
}

async function deleteProduct(id) {
    if (!confirm('آیا از حذف این محصول اطمینان دارید؟')) return;
    
    try {
        await callAPI('deleteProduct', 'DELETE', { id });
        showAlert('محصول حذف شد', 'success');
        loadAllData();
    } catch (error) {
        showAlert('خطا در حذف محصول', 'error');
    }
}

// مدیریت فروش
async function loadProductsForSale() {
    try {
        const products = await callAPI('getProducts');
        const select = document.getElementById('productSelect');
        select.innerHTML = '<option value="">-- انتخاب کنید --</option>';
        
        products.forEach(product => {
            if (product.stock > 0) {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = `${product.name} (${product.stock} ${product.is_kg ? 'کیلوگرم' : 'عدد'} - $${product.price})`;
                select.appendChild(option);
            }
        });
    } catch (error) {
        console.error('خطا در بارگذاری محصولات:', error);
    }
}

async function loadProductInfo() {
    const productId = document.getElementById('productSelect').value;
    if (!productId) return;
    
    try {
        const product = await callAPI('getProduct', 'POST', { id: productId });
        
        document.getElementById('currentStock').value = `${product.stock} ${product.is_kg ? 'کیلوگرم' : 'عدد'}`;
        document.getElementById('unitPrice').value = `$${product.price}`;
        document.getElementById('sellQuantity').max = product.stock;
        document.getElementById('sellQuantity').value = 1;
        
        calculateTotalPrice();
    } catch (error) {
        showAlert('خطا در بارگذاری اطلاعات محصول', 'error');
    }
}

function calculateTotalPrice() {
    const quantity = parseFloat(document.getElementById('sellQuantity').value) || 0;
    const price = parseFloat(document.getElementById('unitPrice').value.replace('$', '')) || 0;
    const total = quantity * price;
    
    document.getElementById('totalPrice').value = `$${total.toFixed(2)}`;
}

async function addToCart() {
    const productId = document.getElementById('productSelect').value;
    const quantity = parseFloat(document.getElementById('sellQuantity').value);
    
    if (!productId || quantity <= 0) {
        showAlert('لطفا محصول و مقدار را انتخاب کنید', 'warning');
        return;
    }
    
    try {
        const product = await callAPI('getProduct', 'POST', { id: productId });
        
        if (quantity > product.stock) {
            showAlert('مقدار انتخابی بیش از موجودی است', 'error');
            return;
        }
        
        const cartItem = {
            product_id: productId,
            name: product.name,
            quantity: quantity,
            price: product.price,
            total: quantity * product.price,
            profit: quantity * product.profit
        };
        
        await callAPI('addToCart', 'POST', cartItem);
        showAlert('محصول به سبد خرید اضافه شد', 'success');
        loadCart();
        loadProducts();
    } catch (error) {
        showAlert('خطا در افزودن به سبد', 'error');
    }
}

async function directSell() {
    if (!confirm('آیا از فروش فوری این محصول اطمینان دارید؟')) return;
    
    const productId = document.getElementById('productSelect').value;
    const quantity = parseFloat(document.getElementById('sellQuantity').value);
    
    if (!productId || quantity <= 0) {
        showAlert('لطفا محصول و مقدار را انتخاب کنید', 'warning');
        return;
    }
    
    try {
        const saleData = {
            product_id: productId,
            quantity: quantity,
            total: parseFloat(document.getElementById('totalPrice').value.replace('$', ''))
        };
        
        await callAPI('sellProduct', 'POST', saleData);
        showAlert('فروش با موفقیت ثبت شد', 'success');
        loadAllData();
    } catch (error) {
        showAlert('خطا در ثبت فروش', 'error');
    }
}

// مدیریت سبد خرید
async function loadCart() {
    try {
        cart = await callAPI('getCart');
        displayCart(cart);
    } catch (error) {
        console.error('خطا در بارگذاری سبد خرید:', error);
    }
}

function displayCart(cartItems) {
    const container = document.getElementById('cartItems');
    const countSpan = document.getElementById('cart-count');
    const totalSpan = document.getElementById('cart-total');
    
    container.innerHTML = '';
    
    if (cartItems.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">سبد خرید خالی است</div>';
        countSpan.textContent = '0';
        totalSpan.textContent = '$0';
        return;
    }
    
    let total = 0;
    let count = 0;
    
    cartItems.forEach(item => {
        total += item.total;
        count += item.quantity;
        
        const cartItem = document.createElement('div');
        cartItem.className = 'cart-item';
        cartItem.innerHTML = `
            <div class="product-info">
                <h4>${item.name}</h4>
                <p>تعداد: ${item.quantity}</p>
                <p>قیمت واحد: $${item.price.toFixed(2)}</p>
            </div>
            <div class="product-actions">
                <p class="product-stock">$${item.total.toFixed(2)}</p>
                <button class="action-icon delete" onclick="removeFromCart(${item.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        container.appendChild(cartItem);
    });
    
    countSpan.textContent = count;
    totalSpan.textContent = `$${total.toFixed(2)}`;
}

async function removeFromCart(id) {
    if (!confirm('آیا از حذف این آیتم اطمینان دارید؟')) return;
    
    try {
        await callAPI('removeFromCart', 'DELETE', { id });
        showAlert('آیتم از سبد خرید حذف شد', 'success');
        loadCart();
        loadProducts();
    } catch (error) {
        showAlert('خطا در حذف آیتم', 'error');
    }
}

async function sellAllCart() {
    if (cart.length === 0) {
        showAlert('سبد خرید خالی است', 'warning');
        return;
    }
    
    if (!confirm(`آیا از فروش ${cart.length} آیتم با مجموع $${document.getElementById('cart-total').textContent.replace('$', '')} اطمینان دارید؟`)) return;
    
    try {
        await callAPI('sellAllCart');
        showAlert('همه آیتم‌ها با موفقیت فروخته شدند', 'success');
        loadAllData();
    } catch (error) {
        showAlert('خطا در فروش آیتم‌ها', 'error');
    }
}

// مدیریت مشتریان
async function loadCustomers() {
    try {
        customers = await callAPI('getCustomers');
        displayCustomers(customers);
    } catch (error) {
        console.error('خطا در بارگذاری مشتریان:', error);
    }
}

function displayCustomers(customersList) {
    const container = document.getElementById('customersList');
    container.innerHTML = '';
    
    if (customersList.length === 0) {
        container.innerHTML = '<div class="alert alert-warning">مشتری‌ای یافت نشد</div>';
        return;
    }
    
    customersList.forEach(customer => {
        const customerItem = document.createElement('div');
        customerItem.className = 'customer-item';
        customerItem.innerHTML = `
            <div class="product-info">
                <h4>${customer.name}</h4>
                <p>${customer.phone || 'بدون شماره'}</p>
                <p>${customer.address || 'بدون آدرس'}</p>
            </div>
            <div class="product-actions">
                <p class="product-stock ${customer.debt > 0 ? 'low' : ''}">$${customer.debt.toFixed(2)}</p>
                <button class="action-icon" onclick="editCustomer(${customer.id})">
                    <i class="fas fa-edit"></i>
                </button>
            </div>
        `;
        container.appendChild(customerItem);
    });
}

function showAddCustomerForm() {
    document.getElementById('addCustomerForm').reset();
    document.getElementById('addCustomerModal').classList.add('active');
    document.getElementById('modalOverlay').classList.add('active');
}

async function saveCustomer(e) {
    e.preventDefault();
    
    const customerData = {
        name: document.getElementById('customerName').value,
        phone: document.getElementById('customerPhone').value,
        address: document.getElementById('customerAddress').value,
        debt: parseFloat(document.getElementById('customerDebt').value) || 0
    };
    
    try {
        await callAPI('addCustomer', 'POST', customerData);
        showAlert('مشتری با موفقیت ذخیره شد', 'success');
        closeModal();
        loadAllData();
    } catch (error) {
        showAlert('خطا در ذخیره مشتری', 'error');
    }
}

// گزارشات و آمار
async function loadDashboardStats() {
    try {
        const stats = await callAPI('getDashboardStats');
        
        document.getElementById('total-products').textContent = stats.total_products || 0;
        document.getElementById('total-value').textContent = `$${stats.total_value || 0}`;
        document.getElementById('active-items').textContent = stats.active_items || 0;
        document.getElementById('today-income').textContent = `$${stats.today_income || 0}`;
        document.getElementById('today-sales').textContent = stats.today_sales || 0;
        document.getElementById('low-stock').textContent = stats.low_stock || 0;
        document.getElementById('net-profit').textContent = `$${stats.net_profit || 0}`;
    } catch (error) {
        console.error('خطا در بارگذاری آمار:', error);
    }
}

async function loadReports() {
    try {
        const reports = await callAPI('getReports');
        
        document.getElementById('total-income').textContent = `$${reports.total_income || 0}`;
        document.getElementById('total-expenses').textContent = `$${reports.total_expenses || 0}`;
        document.getElementById('total-profit').textContent = `$${reports.total_profit || 0}`;
        
        // ایجاد نمودار
        createChart(reports.chart_data);
    } catch (error) {
        console.error('خطا در بارگذاری گزارشات:', error);
    }
}

function createChart(chartData) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (window.salesChart) {
        window.salesChart.destroy();
    }
    
    window.salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels || [],
            datasets: [{
                label: 'فروش روزانه',
                data: chartData.data || [],
                borderColor: '#1164b8',
                backgroundColor: 'rgba(17, 100, 184, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                }
            }
        }
    });
}

// توابع کمکی
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    // اضافه کردن به صفحه
    const container = document.querySelector('main');
    container.insertBefore(alert, container.firstChild);
    
    // حذف خودکار بعد از 5 ثانیه
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function closeModal() {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.classList.remove('active');
    });
    document.getElementById('modalOverlay').classList.remove('active');
}

// توابع مدیریت پایگاه داده
async function backupDatabase() {
    try {
        await callAPI('backupDatabase');
        showAlert('پشتیبان‌گیری با موفقیت انجام شد', 'success');
    } catch (error) {
        showAlert('خطا در پشتیبان‌گیری', 'error');
    }
}

async function restoreDatabase() {
    // در اینجا باید رابط کاربری آپلود فایل پشتیبان ایجاد شود
    showAlert('این قابلیت نیاز به پیاده‌سازی آپلود فایل دارد', 'warning');
}

async function clearAllData() {
    if (!confirm('⚠️ اخطار! این عملیات همه داده‌ها را پاک می‌کند. آیا مطمئن هستید؟')) return;
    
    try {
        await callAPI('clearAllData');
        showAlert('همه داده‌ها پاک شدند', 'success');
        loadAllData();
    } catch (error) {
        showAlert('خطا در پاک کردن داده‌ها', 'error');
    }
}