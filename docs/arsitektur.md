# Arsitektur Sistem

Mengikuti proposal, aplikasi menggunakan arsitektur tiga lapis:
1. Presentasi: HTML, CSS, JavaScript pada browser.
2. Aplikasi: PHP Native untuk autentikasi, hak akses, lisensi, operasional, laporan, dan analisis.
3. Data: MySQL.

Layanan eksternal:
- API AI untuk penjelasan, saran, dan draf pesan dari ringkasan agregat.
- WhatsApp/SMS opsional untuk pengiriman pesan; bukan bagian wajib implementasi awal.

Alur:
Browser -> PHP -> MySQL -> PHP -> Browser.
Untuk insight premium, modul analisis menyiapkan ringkasan agregat lalu mengirimkannya ke API AI.
