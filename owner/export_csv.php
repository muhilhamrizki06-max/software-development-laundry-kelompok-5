<?php
require_once '../config/koneksi.php';
cek_login();

$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? 'all';
$period = $_GET['period'] ?? 'all';

$where = ["1=1"];
$params = [];
$types = "";

if ($q !== '') {
    $where[] = "(o.id LIKE ? OR c.name LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like;
    $types .= "ss";
}
if ($status !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $status;
    $types .= "s";
}
if ($period !== 'all') {
    $where[] = "DATE_FORMAT(o.created_at, '%Y-%m') = ?";
    $params[] = $period;
    $types .= "s";
}

$sql = "SELECT o.id, c.name, o.amount, o.paid_amount, o.status, o.payment, o.created_at, o.due_date
        FROM orders o JOIN customers c ON o.customer_id = c.id
        WHERE " . implode(" AND ", $where) . " ORDER BY o.created_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== "") $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=laporan-laundria-' . $period . '.csv');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($out, ['ID Order', 'Pelanggan', 'Total (Rp)', 'Dibayar (Rp)', 'Status', 'Pembayaran', 'Tanggal Masuk', 'Estimasi Selesai']);

while ($row = $res->fetch_assoc()) {
    fputcsv($out, [$row['id'], $row['name'], $row['amount'], $row['paid_amount'], $row['status'], $row['payment'], $row['created_at'], $row['due_date']]);
}
fclose($out);