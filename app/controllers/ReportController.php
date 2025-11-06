<?php
// app/controllers/ReportController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) { 
    header('Location: index.php?page=login'); 
    exit; 
}

// Master data
$months  = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$types   = ['BONGKAR','MUAT'];
$gudangs = $conn->query("SELECT id, nama_gudang FROM gudang ORDER BY nama_gudang")->fetchAll(PDO::FETCH_ASSOC);

// HANYA STO yang DIPILIH dan NOT_USED untuk pembuatan invoice
$stoList = $conn->query("
  SELECT s.id, s.nomor_sto, s.tanggal_terbit, s.keterangan, s.transportir,
         s.tonase_normal, s.tonase_lembur, s.jenis_transaksi, g.nama_gudang, g.id as gudang_id
  FROM sto s
  JOIN gudang g ON s.gudang_id=g.id
  WHERE s.status='NOT_USED' 
    AND s.pilihan='DIPILIH'
  ORDER BY s.tanggal_terbit DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Invoice header
$invoices = $conn->query("
  SELECT i.*, g.nama_gudang
  FROM invoice i
  JOIN gudang g ON i.gudang_id=g.id
  ORDER BY i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk view/JS
$invoiceData         = [];   // header per invoice
$invoiceLines        = [];   // array sto_id per invoice (lama)
$invoiceLineDetails  = [];   // detail STO per invoice (untuk modal edit)

foreach ($invoices as $inv) {
  $id = (int)$inv['id'];
  $invoiceData[$id] = [
    'bulan'            => $inv['bulan'],
    'jenis_pupuk'      => $inv['jenis_pupuk'],
    'gudang_id'        => $inv['gudang_id'],
    'jenis_transaksi'  => $inv['jenis_transaksi'],
    'uraian_pekerjaan' => $inv['uraian_pekerjaan'],
    'tarif_normal'     => $inv['tarif_normal'],
    'tarif_lembur'     => $inv['tarif_lembur'],
  ];

  // hanya ID
  $stmt = $conn->prepare("SELECT sto_id FROM invoice_line WHERE invoice_id=? ORDER BY id");
  $stmt->execute([$id]);
  $invoiceLines[$id] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

  // detail untuk modal edit (supaya STO yang sudah USED tetap bisa tampil)
  $stmt2 = $conn->prepare("
    SELECT s.id, s.nomor_sto, s.tanggal_terbit, s.transportir, s.tonase_normal, s.tonase_lembur,
           g.nama_gudang, s.keterangan
    FROM invoice_line il
    JOIN sto s ON il.sto_id=s.id
    JOIN gudang g ON s.gudang_id=g.id
    WHERE il.invoice_id = ?
    ORDER BY il.id
  ");
  $stmt2->execute([$id]);
  $invoiceLineDetails[$id] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/../views/layout/header.php';
require_once __DIR__ . '/../views/report/index.php';
require_once __DIR__ . '/../views/layout/footer.php';