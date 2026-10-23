-- 1. สร้างฐานข้อมูล (ถ้ายังไม่มี) และกำหนด Collation ให้รองรับภาษาไทย
CREATE DATABASE IF NOT EXISTS food_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. เลือกใช้งานฐานข้อมูล food_db
USE food_db;

-- 3. สร้างตารางเก็บข้อมูลอาหารหลัก (Food) พร้อมคอลัมน์เก็บชื่อไฟล์รูปภาพ
CREATE TABLE IF NOT EXISTS foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_th VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. สร้างตารางเก็บวัตถุดิบ/สูตรอาหาร (Recipe) 
-- มี Foreign Key ผูกกับตาราง foods (ลบข้อมูลอาหาร รูปและสูตรจะถูกลบตามอัตโนมัติ ON DELETE CASCADE)
CREATE TABLE IF NOT EXISTS recipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    recipe_name VARCHAR(255) NOT NULL,
    quantity FLOAT NOT NULL,
    unit_name VARCHAR(50) NOT NULL,
    FOREIGN KEY (food_id) REFERENCES foods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;