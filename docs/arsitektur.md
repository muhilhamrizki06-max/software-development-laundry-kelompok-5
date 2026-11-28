# Arsitektur Sistem

Aplikasi menggunakan arsitektur tiga lapis berbasis PHP Native:
1. **Presentasi:** HTML, CSS (`public/assets/css/style.css`), dan JavaScript (`public/assets/js/app.js`) pada browser, termasuk mode cetak struk (`@media print`).
2. **Aplikasi:** PHP Native untuk autentikasi, pembatasan akses berbasis peran (`owner` vs `employee`), proteksi CSRF, transaksi order/kas/stok, ekspor CSV, dan modul analisis.
3. **Data:** MySQL / MariaDB (`laundria2`) via ekstensi `mysqli` dengan prepared statements dan transaksi database (`begin_transaction` / `commit` / `rollback`).

Integrasi & Layanan:
- **WhatsApp Nota & Siap Ambil:** menggunakan tautan `https://wa.me/...` untuk mengirim rincian nota atau notifikasi cucian siap diambil langsung dari browser kasir.
- **Modul AI & Broadcast (`ai/ai.php`):** pada implementasi saat ini menggunakan analisis agregat SQL (tren omzet 7 hari, deteksi keterlambatan, rata-rata harian 30 hari) dan generator template draf pesan, serta simulasi pengiriman broadcast. Integrasi API AI eksternal dan gateway WhatsApp otomatis disiapkan untuk tahap pengembangan berikutnya.

Alur utama:
`Browser -> PHP (Session + CSRF + Role Check) -> MySQL (mysqli) -> PHP -> Browser`
