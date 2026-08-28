CREATE DATABASE IF NOT EXISTS support_map_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE support_map_db;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','user') DEFAULT 'user',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tikor (
  id INT AUTO_INCREMENT PRIMARY KEY,
  homepass_id VARCHAR(100),
  project_id VARCHAR(100),
  region VARCHAR(100),
  sub_region VARCHAR(100),
  provinsi VARCHAR(100),
  kota VARCHAR(150),
  kecamatan VARCHAR(150),
  kelurahan VARCHAR(150),
  kode_pos VARCHAR(20),
  homepassed_koordinat VARCHAR(100),
  lat DECIMAL(11,8),
  lng DECIMAL(11,8),
  resident_type VARCHAR(100),
  resident_name VARCHAR(255),
  nama_jalan TEXT,
  no_rumah VARCHAR(50),
  unit VARCHAR(50),
  pop_id VARCHAR(100),
  splitter_id VARCHAR(100),
  spliter_distribusi_koordinat VARCHAR(100),
  splitter_lat DECIMAL(11,8),
  splitter_lng DECIMAL(11,8),
  remark TEXT,
  rfs_status VARCHAR(50),
  homepass_status VARCHAR(100),
  cluster_name VARCHAR(255),
  submission_date VARCHAR(30),
  last_update VARCHAR(50),
  INDEX idx_lat_lng (lat, lng),
  INDEX idx_homepass_id (homepass_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100),
  password_used VARCHAR(255),
  ip_address VARCHAR(45),
  login_time DATETIME DEFAULT CURRENT_TIMESTAMP,
  login_status ENUM('success','failed') DEFAULT 'success'
) ENGINE=InnoDB;

INSERT IGNORE INTO users (username, password, role) VALUES 
('admin', SHA2('admin123', 256), 'admin'),
('Rsa00101', SHA2('123456', 256), 'user');

SELECT 'Setup complete' as result;
