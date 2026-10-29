<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require '../../../db/db.php';

// Statistik Token Guru
$qStatTotal = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru");
$statTotal = (int)(mysqli_fetch_assoc($qStatTotal)['total'] ?? 0);

$qStatUsed = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) as total 
    FROM tb_voter v
    JOIN tb_kode_guru g ON v.nama_voter = g.kode
");
$statUsed = (int)(mysqli_fetch_assoc($qStatUsed)['total'] ?? 0);

$statUnused = $statTotal - $statUsed;

// Query parameter: page & search
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$whereClause = '';
if ($search !== '') {
    $whereClause = "WHERE g.kode LIKE '%$search%'";
}

$totalQuery = mysqli_query($db, "SELECT COUNT(*) as total FROM tb_kode_guru g $whereClause");
$totalRow = mysqli_fetch_assoc($totalQuery);
$totalData = isset($totalRow['total']) ? (int)$totalRow['total'] : 0;
$totalPages = max(1, ceil($totalData / $limit));

$queryTokens = mysqli_query($db, "
    SELECT 
        g.id,
        g.kode,
        CASE 
            WHEN v.id IS NOT NULL THEN 'sudah'
            ELSE 'belum'
        END AS status_penggunaan
    FROM tb_kode_guru g
    LEFT JOIN tb_voter v ON g.kode = v.nama_voter
    $whereClause
    ORDER BY g.id DESC
    LIMIT $limit OFFSET $offset
");

$tokens = [];
while ($row = mysqli_fetch_assoc($queryTokens)) {
    $tokens[] = [
        'id'                => (int)$row['id'],
        'kode'              => $row['kode'],
        'status_penggunaan' => $row['status_penggunaan']
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

