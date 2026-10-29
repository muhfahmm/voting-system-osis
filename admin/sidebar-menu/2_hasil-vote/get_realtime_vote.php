<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['login'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require '../../../db/db.php';

// Target siswa dari tb_kelas
$dataKelas = [];
$resultKelas = mysqli_query($db, "SELECT nama_kelas, jumlah_siswa FROM tb_kelas ORDER BY nama_kelas ASC");
while ($row = mysqli_fetch_assoc($resultKelas)) {
    $dataKelas[$row['nama_kelas']] = (int)$row['jumlah_siswa'];
}
$total_siswa = array_sum($dataKelas);

// Perolehan suara masing-masing kandidat
$query = mysqli_query($db, "
    SELECT k.nomor_kandidat, k.nama_ketua, k.nama_wakil, k.foto_ketua, k.foto_wakil, COUNT(v.id) AS total_suara
    FROM tb_kandidat k
    LEFT JOIN tb_vote_log v ON k.nomor_kandidat = v.nomor_kandidat
    GROUP BY k.nomor_kandidat, k.nama_ketua, k.nama_wakil
    ORDER BY k.nomor_kandidat ASC
");

$kandidatList = [];
$labels = [];
$dataVotes = [];

while ($row = mysqli_fetch_assoc($query)) {
    $kandidatList[] = [
        'nomor_kandidat' => (int)$row['nomor_kandidat'],
        'nama_ketua'     => $row['nama_ketua'],
        'nama_wakil'     => $row['nama_wakil'],
        'foto_ketua'     => $row['foto_ketua'],
        'foto_wakil'     => $row['foto_wakil'],
        'total_suara'    => (int)$row['total_suara']
    ];
    $labels[] = "Paslon #" . $row['nomor_kandidat'] . " (" . $row['nama_ketua'] . ")";
    $dataVotes[] = (int)$row['total_suara'];
}

// Total Suara Masuk
$totalQuery = mysqli_query($db, "SELECT COUNT(*) AS total FROM tb_vote_log");
$totalVotes = (int)mysqli_fetch_assoc($totalQuery)['total'];

// Leader / Paslon Terunggul
$leader = null;
if (!empty($kandidatList)) {
    $sortedList = $kandidatList;
    usort($sortedList, function ($a, $b) {
        if ($b['total_suara'] === $a['total_suara']) {
            return $a['nomor_kandidat'] <=> $b['nomor_kandidat'];
        }
        return $b['total_suara'] <=> $a['total_suara'];
    });

    if ($sortedList[0]['total_suara'] > 0) {
        $lead = $sortedList[0];
        $leaderPercent = $totalVotes > 0 ? round(($lead['total_suara'] / $totalVotes) * 100, 1) : 0;
        $leader = [
            'nomor_kandidat' => $lead['nomor_kandidat'],
            'nama_ketua'     => $lead['nama_ketua'],
            'nama_wakil'     => $lead['nama_wakil'],
            'foto_ketua'     => $lead['foto_ketua'],
            'foto_wakil'     => $lead['foto_wakil'],
            'total_suara'    => $lead['total_suara'],
            'persentase'     => $leaderPercent
        ];
    }
}

// Hitung persentase untuk masing-masing kandidat di list
foreach ($kandidatList as &$k) {
    $k['persentase'] = $totalVotes > 0 ? round(($k['total_suara'] / $totalVotes) * 100, 2) : 0;
}
unset($k);

// Partisipasi Siswa
$totalVotesSiswaQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) AS total 
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'siswa'
");
$totalVotesSiswa = (int)mysqli_fetch_assoc($totalVotesSiswaQuery)['total'];

// Partisipasi Guru
$totalVotesGuruQuery = mysqli_query($db, "
    SELECT COUNT(DISTINCT v.id) AS total 
    FROM tb_voter v
    JOIN tb_vote_log l ON v.id = l.voter_id
    WHERE v.role = 'guru'
");
$totalVotesGuru = (int)mysqli_fetch_assoc($totalVotesGuruQuery)['total'];

$totalSiswaTarget = $total_siswa;
$guruResult = mysqli_query($db, "SELECT COUNT(*) AS total_guru FROM tb_kode_guru");
$totalGuruTarget = (int)mysqli_fetch_assoc($guruResult)['total_guru'];
$totalPartisipasi = $totalSiswaTarget + $totalGuruTarget;

$percentSiswa = $totalSiswaTarget > 0 ? round(($totalVotesSiswa / $totalSiswaTarget) * 100, 1) : 0;
$percentGuru = $totalGuruTarget > 0 ? round(($totalVotesGuru / $totalGuruTarget) * 100, 1) : 0;

echo json_encode([
    'status' => 'success',
    'timestamp' => date('H:i:s'),
    'total_votes' => $totalVotes,
    'total_partisipasi' => $totalPartisipasi,
    'total_siswa_target' => $totalSiswaTarget,
    'total_votes_siswa' => $totalVotesSiswa,
    'percent_siswa' => $percentSiswa,
    'sisa_siswa' => max(0, $totalSiswaTarget - $totalVotesSiswa),
    'total_guru_target' => $totalGuruTarget,
    'total_votes_guru' => $totalVotesGuru,
    'percent_guru' => $percentGuru,
    'sisa_guru' => max(0, $totalGuruTarget - $totalVotesGuru),
    'leader' => $leader,
    'kandidat' => $kandidatList,
    'chart' => [
        'labels' => $labels,
        'data' => $dataVotes
    ]
], JSON_UNESCAPED_UNICODE);
exit;

