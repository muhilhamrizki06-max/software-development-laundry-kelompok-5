# Laundria — Sistem Manajemen Laundry

Aplikasi manajemen operasional laundry berbasis web menggunakan PHP Native dan MySQL (`mysqli`).

## Teknologi

- PHP Native (PHP 8+)
- MySQL / MariaDB (InnoDB)
- HTML, CSS, JavaScript (Vanilla)
- Modul Analisis & Draf Broadcast (rule-based pada versi saat ini; siap diintegrasikan dengan API AI eksternal)

## Struktur Project

- `auth/`: Autentikasi (`login.php`, `logout.php`)
- `config/`: Koneksi database dan helper keamanan (`koneksi.php`)
- `database/`: Skema dan data awal database (`schema.sql`)
- `karyawan/`: Fitur operasional (dashboard, input order, daftar nota, shift & kas harian, manajemen stok)
- `owner/`: Fitur pemilik (pengeluaran, laporan & ekspor CSV, manajemen karyawan, lisensi, pengaturan toko & tarif)
- `ai/`: Modul analisis tren omzet, deteksi keterlambatan, dan draf broadcast (`ai.php`)
- `public/`: Aset CSS, JavaScript, serta komponen layout (`includes/header.php`, `footer.php`, `ui.php`)
- `docs/`: Dokumentasi project

## Instalasi

1. Clone repository ke folder `htdocs` XAMPP.
2. Jalankan Apache dan MySQL di XAMPP.
3. Import `database/schema.sql` melalui phpMyAdmin atau MySQL CLI (akan membuat database `laundria2` beserta data demo).
4. Sesuaikan host, user, password, dan nama database di `config/koneksi.php` bila diperlukan.
5. Akses aplikasi melalui browser (misal `http://localhost/software-development-laundry-kelompok-5/`).
