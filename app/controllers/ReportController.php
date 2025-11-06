<?php
// app/controllers/ReportController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php?page=login');
  exit;
}

$userId = (int) $_SESSION['user_id'];

// Ambil data gudang user + tarif dari tabel gudang_tarif
$stmt = $conn->prepare("
  SELECT 
    u.id_gudang,
    g.nama_gudang,
    gt.tarif_normal,
    gt.tarif_lembur
  FROM users u
  LEFT JOIN gudang g ON u.id_gudang = g.id
  LEFT JOIN gudang_tarif gt ON g.id = gt.gudang_id
  WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set variabel gudang & tarif
$gudang_id     = !empty($user['id_gudang']) ? (int) $user['id_gudang'] : null;
$nama_gudang   = $user['nama_gudang'] ?? '-';
$tarif_normal  = $user['tarif_normal'] ?? 0;
$tarif_lembur  = $user['tarif_lembur'] ?? 0;


// Jika user bukan bagian dari gudang, beri peringatan tapi jangan hentikan
if ($gudang_id === null) {
  $_SESSION['warning'] = "User bukan bagian dari gudang manapun. Beberapa fitur mungkin terbatas.";
}

// --- Master Data ---
$months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 
            'August', 'September', 'October', 'November', 'December'];
$types  = ['BONGKAR', 'MUAT'];

// --- STO yang siap diinvoice ---
$stoList = $conn->query("
  SELECT s.id, s.nomor_sto, s.tanggal_terbit, s.keterangan, s.transportir,
         s.tonase_normal, s.tonase_lembur, s.jenis_transaksi, g.nama_gudang, g.id AS gudang_id
  FROM sto s
  JOIN gudang g ON s.gudang_id = g.id
  WHERE s.status = 'NOT_USED' 
    AND s.pilihan = 'DIPILIH'
  ORDER BY s.tanggal_terbit DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Invoice Header ---
$invoices = $conn->query("
  SELECT i.*, g.nama_gudang
  FROM invoice i
  JOIN gudang g ON i.gudang_id = g.id
  ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Data untuk view / JS ---
$invoiceData        = [];
$invoiceLines       = [];
$invoiceLineDetails = [];

foreach ($invoices as $inv) {
  $id = (int) $inv['id'];

  // Header data
  $invoiceData[$id] = [
    'bulan'            => $inv['bulan'],
    'jenis_pupuk'      => $inv['jenis_pupuk'],
    'gudang_id'        => $inv['gudang_id'],
    'jenis_transaksi'  => $inv['jenis_transaksi'],
    'uraian_pekerjaan' => $inv['uraian_pekerjaan'],
    'tarif_normal'     => $inv['tarif_normal'],
    'tarif_lembur'     => $inv['tarif_lembur'],
  ];

  // STO ID per invoice
  $stmt = $conn->prepare("SELECT sto_id FROM invoice_line WHERE invoice_id = ? ORDER BY id");
  $stmt->execute([$id]);
  $invoiceLines[$id] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

  // Detail STO per invoice (supaya tetap tampil meski sudah USED)
  $stmt2 = $conn->prepare("
    SELECT s.id, s.nomor_sto, s.tanggal_terbit, s.transportir, 
           s.tonase_normal, s.tonase_lembur, g.nama_gudang, s.keterangan
    FROM invoice_line il
    JOIN sto s ON il.sto_id = s.id
    JOIN gudang g ON s.gudang_id = g.id
    WHERE il.invoice_id = ?
    ORDER BY il.id
  ");
  $stmt2->execute([$id]);
  $invoiceLineDetails[$id] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../views/layout/header.php';
require_once __DIR__ . '/../views/report/index.php';
require_once __DIR__ . '/../views/layout/footer.php';
