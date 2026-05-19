CREATE DATABASE IF NOT EXISTS bps_antrian;
USE bps_antrian;

-- Tabel untuk menyimpan riwayat antrian selamanya
CREATE TABLE data_antrian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE NOT NULL,
    nomor_antrian VARCHAR(10) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    kode_loket VARCHAR(5) NOT NULL,
    nama_loket VARCHAR(50) NOT NULL,
    status ENUM('Menunggu', 'Selesai') DEFAULT 'Menunggu',
    waktu_ambil DATETIME DEFAULT CURRENT_TIMESTAMP,
    waktu_panggil DATETIME NULL
);

-- Tabel untuk mengontrol status layar/panel saat ini
CREATE TABLE state_layar (
    id INT PRIMARY KEY DEFAULT 1,
    layar_pst VARCHAR(10) DEFAULT '---',
    layar_ppid VARCHAR(10) DEFAULT '---',
    layar_pengaduan VARCHAR(10) DEFAULT '---',
    panggilan_nomor VARCHAR(10) DEFAULT '---',
    panggilan_loket VARCHAR(50) DEFAULT '---',
    waktu_update BIGINT DEFAULT 0
);

INSERT INTO state_layar (id) VALUES (1);

-- Tabel untuk menyimpan data admin
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Memasukkan username 'PST1372' dan password 'bpskotasolok' yang di-hash dengan MD5
INSERT INTO admin_users (username, password) 
VALUES ('PST1372', MD5('bpskotasolok'));

INSERT INTO admin_users (username, password) 
VALUES ('admin1372', MD5('bpskotasolok'));
