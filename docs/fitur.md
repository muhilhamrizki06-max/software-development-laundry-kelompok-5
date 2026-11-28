# Daftar Fitur & Status Implementasi

Pemetaan kebutuhan dari proposal terhadap implementasi aktual saat ini:

## Umum
- **FR-01 Login/logout:** Diimplementasikan (`auth/login.php`, `auth/logout.php`) dengan `password_verify` dan regenerasi session ID.
- **FR-02 Pembatasan akses owner/karyawan:** Diimplementasikan di sidebar (`public/includes/header.php`) dan verifikasi sisi server pada seluruh halaman `owner/*` dan `ai/ai.php`.
- **FR-03 Registrasi owner & FR-04 Ubah/reset password:** Belum diimplementasikan (saat ini pembuatan akun baru dikelola oleh Owner untuk karyawan di `owner/employees.php`).

## Owner
- **FR-05 Dashboard:** Diimplementasikan (`karyawan/index.php`) menampilkan omzet hari ini, estimasi laba, grafik 7 hari terakhir, dan ringkasan peringatan.
- **FR-06 Laporan omzet, pengeluaran, dan laba:** Diimplementasikan (`owner/reports.php`, `owner/export_csv.php`) dengan filter status/periode/pencarian dan ekspor CSV.
- **FR-07 Pengelolaan layanan dan harga:** Diimplementasikan (`owner/settings.php`) bersama pengaturan profil toko & footer struk.
- **FR-08 Pengelolaan akun karyawan:** Diimplementasikan (`owner/employees.php`) untuk tambah, aktif/nonaktifkan, dan hapus karyawan.
- **FR-09 Pencatatan pengeluaran:** Diimplementasikan (`owner/expenses.php`).
- **FR-10 Pemantauan aktivitas:** Tercakup sebagian melalui riwayat shift kasir (`karyawan/cash.php`) dan riwayat mutasi stok (`karyawan/stock.php`).

## Karyawan / Operasional
- **FR-11 Input order, hitung total, cetak nota:** Diimplementasikan (`karyawan/orders.php`, `karyawan/nota.php`) termasuk paket kecepatan, parfum, DP, dan cetak struk.
- **FR-12 Pembaruan status order dan pembayaran:** Diimplementasikan (`karyawan/nota.php`) via AJAX update status dan pelunasan tagihan yang terhubung ke shift kas.
- **FR-13 Pengelolaan pelanggan:** Pelanggan baru dapat ditambahkan langsung saat input order (`karyawan/orders.php`).
- **FR-14 Pencarian order:** Diimplementasikan (`karyawan/nota.php`, `owner/reports.php`) via filter server dan live search di tabel.
- **FR-15 Pemberitahuan cucian siap diambil:** Diimplementasikan melalui tombol **WA Siap Ambil** (`wa.me`) di `karyawan/nota.php`.
- **Tambahan Operasional:** Shift & kas harian (`karyawan/cash.php`) serta manajemen stok bahan (`karyawan/stock.php`).

## Lisensi dan AI
- **FR-16 & FR-17 Penerbitan & validasi kode lisensi oleh admin:** Belum ada modul `admin/`; perpindahan paket Basic/Premium dan toggle gateway pesan sementara dikelola langsung melalui `owner/license.php` untuk keperluan demo.
- **FR-18 Pembukaan fitur berdasarkan paket:** Diimplementasikan di `ai/ai.php` (prediksi & draf broadcast terkunci pada paket Basic, terbuka pada Premium).
- **FR-19 Peringatan penurunan omzet:** Diimplementasikan di `karyawan/index.php` dan `ai/ai.php` (membandingkan total omzet 7 hari terakhir dengan 7 hari sebelumnya) beserta deteksi order mendekati/melewati estimasi selesai.
- **FR-20 s.d. FR-23 Saran/prediksi AI, draf broadcast, dan riwayat insight:** Diimplementasikan versi awal (`ai/ai.php`) dengan proyeksi 30 hari dari rata-rata harian, generator template draf broadcast, simulasi kirim, dan penyimpanan riwayat ke tabel `ai_insight`.
