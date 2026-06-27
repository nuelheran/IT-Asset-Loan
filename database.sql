-- ============================================================
-- IT ASSET LOAN - Database Schema
-- Sistem Peminjaman Aset IT
-- ============================================================

CREATE DATABASE IF NOT EXISTS it_asset_loan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE it_asset_loan;

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nik VARCHAR(30) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    role ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: categories  (kategori aset: Laptop, Monitor, Proyektor, dst)
-- ------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: assets  (master data aset IT)
-- ------------------------------------------------------------
CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    category_id INT NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(150) DEFAULT NULL,
    specification TEXT DEFAULT NULL,
    purchase_date DATE DEFAULT NULL,
    condition_status ENUM('good','minor_damage','major_damage') NOT NULL DEFAULT 'good',
    status ENUM('available','on_loan','maintenance','retired') NOT NULL DEFAULT 'available',
    photo VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: loans  (transaksi peminjaman & pengembalian)
-- ------------------------------------------------------------
CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_code VARCHAR(50) UNIQUE NOT NULL,
    asset_id INT NOT NULL,
    user_id INT NOT NULL,
    purpose TEXT NOT NULL,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE DEFAULT NULL,
    condition_on_loan ENUM('good','minor_damage','major_damage') NOT NULL DEFAULT 'good',
    condition_on_return ENUM('good','minor_damage','major_damage') DEFAULT NULL,
    status ENUM('pending','approved','rejected','active','returned','overdue') NOT NULL DEFAULT 'pending',
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    rejection_reason VARCHAR(255) DEFAULT NULL,
    return_notes TEXT DEFAULT NULL,
    received_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table: loan_logs  (riwayat perubahan status peminjaman)
-- ------------------------------------------------------------
CREATE TABLE loan_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    status VARCHAR(30) NOT NULL,
    note VARCHAR(255) DEFAULT NULL,
    action_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(id) ON DELETE CASCADE,
    FOREIGN KEY (action_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default users
-- password untuk semua akun di bawah ini: "password123"
-- (hash di-generate dengan password_hash bcrypt — lihat seed_password.php jika ingin generate ulang)
INSERT INTO users (nik, name, email, password, department, phone, role, status) VALUES
('ADM001', 'Administrator', 'admin@company.com', '$2y$10$lBdbS5kZmIn.rVlWtmhp7efH6kaNROXtdG6YsZDCRL4EhSpp909ne', 'IT Department', '081200000001', 'admin', 'active'),
('EMP001', 'Budi Santoso', 'budi.santoso@company.com', '$2y$10$lBdbS5kZmIn.rVlWtmhp7efH6kaNROXtdG6YsZDCRL4EhSpp909ne', 'Finance', '081200000002', 'employee', 'active'),
('EMP002', 'Siti Aminah', 'siti.aminah@company.com', '$2y$10$lBdbS5kZmIn.rVlWtmhp7efH6kaNROXtdG6YsZDCRL4EhSpp909ne', 'Marketing', '081200000003', 'employee', 'active');

-- Categories
INSERT INTO categories (name, description) VALUES
('Laptop', 'Laptop dan notebook untuk kebutuhan kerja'),
('Monitor', 'Monitor eksternal'),
('Proyektor', 'Proyektor untuk presentasi/meeting'),
('Printer', 'Printer dan scanner'),
('Aksesoris', 'Mouse, keyboard, headset, dan lainnya'),
('Networking', 'Router, switch, modem');

-- Assets
INSERT INTO assets (asset_code, name, category_id, brand, serial_number, specification, purchase_date, condition_status, status) VALUES
('AST-0001', 'Laptop Lenovo ThinkPad E14', 1, 'Lenovo', 'SN-LNV-2023-001', 'Intel i5, RAM 8GB, SSD 256GB', '2023-01-15', 'good', 'available'),
('AST-0002', 'Laptop Dell Latitude 5420', 1, 'Dell', 'SN-DELL-2023-002', 'Intel i7, RAM 16GB, SSD 512GB', '2023-03-10', 'good', 'available'),
('AST-0003', 'Monitor LG 24 inch', 2, 'LG', 'SN-LG-2022-010', '24 inch Full HD IPS', '2022-11-05', 'good', 'available'),
('AST-0004', 'Proyektor Epson EB-X06', 3, 'Epson', 'SN-EPS-2021-004', '3600 lumens, XGA resolution', '2021-08-20', 'good', 'available'),
('AST-0005', 'Printer Canon Pixma G2010', 4, 'Canon', 'SN-CN-2022-007', 'Inkjet print, scan, copy', '2022-05-12', 'good', 'available');

-- Sample loan transaction (riwayat contoh)
INSERT INTO loans (loan_code, asset_id, user_id, purpose, loan_date, due_date, return_date, condition_on_loan, condition_on_return, status, approved_by, approved_at, received_by) VALUES
('LOAN-20260101-0001', 5, 2, 'Mencetak dokumen laporan keuangan bulanan', '2026-01-05', '2026-01-12', '2026-01-11', 'good', 'good', 'returned', 1, '2026-01-04 09:00:00', 1);

INSERT INTO loan_logs (loan_id, status, note, action_by) VALUES
(1, 'pending', 'Pengajuan peminjaman dibuat', 2),
(1, 'approved', 'Disetujui oleh admin', 1),
(1, 'active', 'Aset diserahkan ke peminjam', 1),
(1, 'returned', 'Aset dikembalikan dalam kondisi baik', 1);
