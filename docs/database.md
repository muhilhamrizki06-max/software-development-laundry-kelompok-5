# Desain Database

Database: `laundria2` (MySQL/MariaDB InnoDB, charset `utf8mb4`).

Skema lengkap tersedia di `database/schema.sql` dengan 13 tabel:

1. `users`: akun `owner` dan `employee` beserta status `active`.
2. `settings`: konfigurasi tunggal outlet (`id = 1`), mencakup paket aktif (`basic`/`premium`), status gateway pesan, dan identitas outlet pada nota.
3. `customers`: data pelanggan dan persetujuan (`consent`) menerima pesan promo.
4. `services`: master layanan laundry beserta satuan (`kg`/`pcs`/`m2`) dan tarif.
5. `orders`: header transaksi/nota, total tagihan, total terbayar (`paid_amount`), status cucian, estimasi selesai (`due_date`), dan metode bayar awal.
6. `order_items`: rincian layanan dan jumlah per order.
7. `order_payments`: log setiap pembayaran/DP/pelunasan order beserta metode dan `shift_id` (untuk rekonsiliasi kas tunai).
8. `cash_shifts`: sesi buka/tutup shift kasir, modal awal, kas sistem, kas fisik, dan selisih.
9. `cash_movements`: arus kas manual (`in`/`out`) di luar transaksi order selama shift berjalan.
10. `expenses`: pencatatan biaya pengeluaran usaha.
11. `inventory_items`: daftar bahan/perlengkapan laundry dan batas stok minimum.
12. `stock_movements`: riwayat mutasi stok masuk/keluar beserta `before_qty` dan `after_qty`.
13. `ai_insight`: riwayat draf broadcast dan insight yang disimpan dari modul Analisis & AI.

> Catatan: Implementasi saat ini beroperasi sebagai single-outlet (single-tenant). Apabila dikembangkan menjadi multi-outlet/SaaS sesuai proposal awal, perlu penambahan `owner_id` beserta validasi kepemilikan tenant di setiap query.
