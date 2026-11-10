<?php
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_role('owner');
require_once __DIR__ . '/analytics.php';
/** TODO: Ambil omzet mingguan milik owner, jalankan detectRevenueDrop(), simpan insight. */
http_response_code(501);
echo 'Integrasi peringatan omzet dengan database belum diimplementasikan.';
