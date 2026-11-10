<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('karyawan');
/** TODO: Ubah status baru/diproses/selesai/diambil dan status pembayaran. */
http_response_code(501);
echo 'Pembaruan status order belum diimplementasikan.';
