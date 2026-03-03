-- 1. ROLES & USERS (Auth & RBAC)
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE -- admin, staff, stockman, cashier, customer
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- 2. CATALOG (Products & Suppliers)
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    contact_info VARCHAR(255)
);

CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    category_id INT,
    supplier_id INT,
    base_unit VARCHAR(20) DEFAULT 'pcs', -- How it's sold at retail
    units_per_box INT DEFAULT 1,         -- The multiplier for the 'explode' feature
    current_cost_price DECIMAL(10,2) NOT NULL, -- For calculating future profit
    current_selling_price DECIMAL(10,2) NOT NULL,
    critical_level INT DEFAULT 10,       -- For your KPI dashboard
    FOREIGN KEY (category_id) REFERENCES categories(category_id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id)
);

-- 3. INVENTORY LOGISTICS (Tracking locations & Expiry)
CREATE TABLE locations (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(50) NOT NULL UNIQUE -- 'Wholesale', 'Retail Warehouse', 'Store Shelf'
);

-- Using 'batches' allows you to track expiry dates for the same product accurately
CREATE TABLE inventory_batches (
    batch_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    location_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    expiry_date DATE, -- Crucial for your 'Expiring Items (30 days)' KPI
    received_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (location_id) REFERENCES locations(location_id)
);

-- Tracks every single movement (Explode, Transfer, Receive, Damage)
CREATE TABLE stock_movements (
    movement_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    from_location_id INT, -- NULL if receiving from supplier
    to_location_id INT,   -- NULL if damaged/lost
    quantity INT NOT NULL,
    movement_type ENUM('receive', 'transfer', 'explode', 'adjust', 'damage', 'sale') NOT NULL,
    user_id INT NOT NULL, -- Who did it
    movement_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id),
    FOREIGN KEY (from_location_id) REFERENCES locations(location_id),
    FOREIGN KEY (to_location_id) REFERENCES locations(location_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 4. POS & SALES (Profitability Tracking)
CREATE TABLE sales (
    sale_id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(50) NOT NULL UNIQUE,
    cashier_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cashier_id) REFERENCES users(user_id)
);

CREATE TABLE sale_items (
    sale_item_id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    -- We save these here to lock in the exact profit at the time of sale
    unit_cost_at_sale DECIMAL(10,2) NOT NULL, 
    unit_price_at_sale DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(sale_id),
    FOREIGN KEY (product_id) REFERENCES products(product_id)
);

-- 5. SYSTEM LOGS (Audit Trail)
CREATE TABLE activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_description VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);