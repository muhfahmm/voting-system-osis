<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require '../../../db/db.php';

// Statistik Token Siswa
$qStatTotal = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token");
$statTotal = (int)mysqli_fetch_assoc($qStatTotal)['total'];

$qStatUsed = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token WHERE status_token = 'sudah'");
$statUsed = (int)mysqli_fetch_assoc($qStatUsed)['total'];

$statUnused = $statTotal - $statUsed;

// Query parameter: page & search
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE token LIKE '%$search%'";
}

$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_buat_token $whereClause");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = max(1, ceil($totalData / $limit));

$queryTokens = mysqli_query($db, "
    SELECT id, token, status_token
    FROM tb_buat_token 
    $whereClause
    ORDER BY id DESC 
    LIMIT $limit OFFSET $offset
");

$tokens = [];
while ($row = mysqli_fetch_assoc($queryTokens)) {
    $tokens[] = [
        'id'           => (int)$row['id'],
        'token'        => $row['token'],
        'status_token' => $row['status_token']
    ];
}

echo json_encode([
    'status'      => 'success',
    'timestamp'   => date('H:i:s'),
    'statTotal'   => $statTotal,
    'statUsed'    => $statUsed,
    'statUnused'  => $statUnused,
    'page'        => $page,
    'limit'       => $limit,
    'offset'      => $offset,
    'totalData'   => $totalData,
    'totalPages'  => $totalPages,
    'tokens'      => $tokens
], JSON_UNESCAPED_UNICODE);
exit;

