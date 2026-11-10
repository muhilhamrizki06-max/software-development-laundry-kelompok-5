CREATE DATABASE IF NOT EXISTS laundry_ai;
USE laundry_ai;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM("admin", "owner", "karyawan") NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE lisensi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_lisensi VARCHAR(100) NOT NULL UNIQUE,
    status ENUM("aktif", "nonaktif") DEFAULT "aktif",
    tanggal_kedaluwarsa DATE
);

CREATE TABLE pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telepon VARCHAR(20),
    alamat TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE layanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_layanan VARCHAR(100) NOT NULL,
    harga DECIMAL(12,2) NOT NULL,
    estimasi_hari INT DEFAULT 1
);

CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(50) NOT NULL UNIQUE,
    id_pelanggan INT NOT NULL,
    id_karyawan INT,
    tanggal DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM("baru", "diproses", "selesai", "diambil") DEFAULT "baru",
    total DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id),
    FOREIGN KEY (id_karyawan) REFERENCES users(id)
);

CREATE TABLE transaksi_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_transaksi INT NOT NULL,
    id_layanan INT NOT NULL,
    berat DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (id_transaksi) REFERENCES transaksi(id),
    FOREIGN KEY (id_layanan) REFERENCES layanan(id)
);

CREATE TABLE pengeluaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keterangan VARCHAR(255) NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL,
    tanggal DATE NOT NULL
);

CREATE TABLE ai_insight (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jenis VARCHAR(100),
    hasil TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
