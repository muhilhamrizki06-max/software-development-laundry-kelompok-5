<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('karyawan');
/**
 * TODO: Form order: pilih/tambah pelanggan, pilih beberapa layanan, qty,
 * hitung total di server, simpan transaksi + transaksi_detail dalam satu
 * database transaction, lalu sediakan nota.
 */
http_response_code(501);
echo 'Form input order belum diimplementasikan.';
