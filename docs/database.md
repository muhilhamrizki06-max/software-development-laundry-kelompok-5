# Desain Database

Database: `laundry_ai` (MySQL/InnoDB).

Delapan tabel sesuai proposal:
1. `users`: akun owner, karyawan, dan admin.
2. `lisensi`: paket gratis/premium dan masa berlaku per owner.
3. `pelanggan`: pelanggan milik owner.
4. `layanan`: jenis layanan dan harga milik owner.
5. `transaksi`: nota/order.
6. `transaksi_detail`: rincian layanan per order.
7. `pengeluaran`: pengeluaran usaha.
8. `ai_insight`: riwayat peringatan, saran, prediksi, dan broadcast.

Import skema dari `database/schema.sql`.

Penting: foreign key saja tidak menggantikan pemeriksaan tenant. Setiap query aplikasi harus memvalidasi bahwa record yang diakses benar-benar milik `owner_id` pada sesi aktif.
