-- =====================================================
-- DATABASE: IDEA FOUNDATION (FINAL VERSION)
-- DESCRIPTION: Book Archive System (View/Download)
-- AUTHOR ROLES: Admin, Employee
-- STORAGE: Cloudflare R2 Ready
-- =====================================================

CREATE DATABASE IF NOT EXISTS idea_foundation_db
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE idea_foundation_db;

-- 1. خشتەی بەکارهێنەرانی سیستم (Users - Admin/Employee)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') DEFAULT 'employee',
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_role (role)
) ENGINE=InnoDB;

-- 2. خشتەی پۆلە سەرەکییەکان (Categories)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_cat_active (is_active)
) ENGINE=InnoDB;

-- 3. خشتەی ژێر-پۆلەکان (Subcategories)
CREATE TABLE subcategories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_subcat_active (is_active)
) ENGINE=InnoDB;

-- 4. خشتەی نووسەران (Authors)
CREATE TABLE authors (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    bio TEXT,
    image VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT(name)
) ENGINE=InnoDB;

-- 5. خشتەی سەرەکی کتێبەکان (Books)
CREATE TABLE books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    short_description TEXT,
    long_description TEXT,
    category_id INT NULL,
    subcategory_id INT NULL,
    file_key VARCHAR(500), -- Key for Cloudflare R2
    view_count INT DEFAULT 0,
    thumbnail VARCHAR(255),
    youtube_url VARCHAR(255) NULL,
    download_count INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(150),
    meta_description VARCHAR(255),
    created_by INT NULL, -- ئەو بەکارهێنەرەی زیادی کردووە
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_book_active_feat (is_active, is_featured),
    INDEX idx_book_slug (slug),
    FULLTEXT(title, short_description)
) ENGINE=InnoDB;

-- 6. پەیوەندی کتێب و نووسەر (Book Authors - Many to Many)
CREATE TABLE book_authors (
    book_id INT NOT NULL,
    author_id INT NOT NULL,
    role ENUM('author', 'translator', 'editor') DEFAULT 'author',
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. تایبەتمەندییە وردەکانی کتێب (Book Specifications)
CREATE TABLE book_specifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL, 
    spec_name VARCHAR(100) NOT NULL,
    spec_value TEXT NOT NULL,
    group VARCHAR(100) NULL,
    is_visible TINYINT(1) DEFAULT 1,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_spec_book (book_id)
) ENGINE=InnoDB;

-- 8. لۆگی داونلۆد و بینین بۆ خەڵک (Public Logs)
CREATE TABLE logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    action_type ENUM('view', 'download') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    INDEX idx_logs_ip_time (ip_address, created_at),
    INDEX idx_logs_ip_action_time (ip_address, action_type, created_at)
) ENGINE=InnoDB;

-- 9. لۆگی چالاکی ئەدمین و فەرمانبەران (Admin/Employee Action Logs)
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action_type ENUM('create', 'update', 'delete', 'login') NOT NULL,
    description TEXT,
    table_name VARCHAR(50),
    record_id INT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 10. خشتەی ڕێکخستنەکان (Settings)
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 11. ڕێگەپێدان بۆ فەرمانبەران (Employee Permissions)
CREATE TABLE IF NOT EXISTS employee_permissions (
    user_id INT NOT NULL,
    resource ENUM('categories','subcategories','authors','books') NOT NULL,
    can_create TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    PRIMARY KEY (user_id, resource),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- VIEWەکان بۆ ئاسانکاری و ئاماری داشبۆرد
-- =====================================================

-- 1. بینینی وردەکاری کتێب لەگەڵ ناوی نووسەر و پۆلەکان (بۆ React)
CREATE OR REPLACE VIEW full_book_details AS
SELECT
    b.*,
    c.name as category_name,
    s.name as subcategory_name,
    u.full_name as uploader_name,
    GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') as author_names
FROM books b
LEFT JOIN categories c ON b.category_id = c.id
LEFT JOIN subcategories s ON b.subcategory_id = s.id
LEFT JOIN users u ON b.created_by = u.id
LEFT JOIN book_authors ba ON b.id = ba.book_id
LEFT JOIN authors a ON ba.author_id = a.id
GROUP BY b.id;

-- 2. ئاماری گشتی بۆ داشبۆرد (Dashboard Stats)
CREATE OR REPLACE VIEW dashboard_summary AS
SELECT
    (SELECT COUNT(*) FROM books WHERE is_active = 1) as total_active_books,
    (SELECT COUNT(*) FROM logs WHERE action_type = 'download') as total_downloads,
    (SELECT COUNT(DISTINCT ip_address) FROM logs) as unique_visitors,
    (SELECT COUNT(*) FROM authors WHERE is_active = 1) as total_authors,
    (SELECT COUNT(*) FROM logs WHERE DATE(created_at) = CURDATE() AND action_type = 'download') as downloads_today;

-- =====================================================
-- داتای نموونە (Sample Data)
-- =====================================================

-- تێبینی: پاسوۆردەکە "admin123"ە کە بە Hashed دانراوە
INSERT INTO users (full_name, email, password, role) VALUES
('Main Admin', 'admin@idea.foundation', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ali Employee', 'ali@idea.foundation', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employee');

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Idea Foundation'),
('contact_email', 'info@idea.foundation'),
('maintenance_mode', '0');
