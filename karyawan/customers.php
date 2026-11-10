<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('karyawan');
/** TODO: Tambah dan cari pelanggan. Semua query wajib dibatasi owner_id. */
http_response_code(501);
echo 'Data pelanggan belum diimplementasikan.';
