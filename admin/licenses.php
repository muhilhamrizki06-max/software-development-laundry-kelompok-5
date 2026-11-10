<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('admin');

/**
 * TODO:
 * - Form pilih owner, paket gratis/premium, tanggal mulai/berakhir.
 * - Buat kode lisensi acak yang tidak mudah ditebak.
 * - Simpan kode lisensi dan status pada tabel lisensi.
 * - Catat aktivitas admin dan validasi masa berlaku.
 */
http_response_code(501);
echo 'Pengelolaan lisensi belum diimplementasikan.';
