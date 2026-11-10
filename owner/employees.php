<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('owner');
/** TODO: CRUD akun karyawan. Pastikan owner_id selalu berasal dari sesi owner. */
http_response_code(501);
echo 'Manajemen karyawan belum diimplementasikan.';
