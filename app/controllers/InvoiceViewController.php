<?php
// app/controllers/InvoiceViewController.php

if (session_status()===PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__.'/../../config/database.php';

// pastikan user logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ambil data header invoice
$stmt = $conn->prepare("
  SELECT 
    i.*, 
    g.nama_gudang 
  FROM invoice i
  JOIN gudang g ON i.gudang_id = g.id
  WHERE i.id = ?
");
$stmt->execute([$id]);
$inv = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$inv) {
    http_response_code(404);
    exit('Invoice not found');
}

// ambil baris‐baris invoice
$stmt2 = $conn->prepare("
  SELECT 
    il.*, 
    s.nomor_sto, 
    s.tanggal_terbit, 
    s.transportir, 
    s.tonase_normal, 
    s.tonase_lembur
  FROM invoice_line il
  JOIN sto s ON il.sto_id = s.id
  WHERE il.invoice_id = ?
  ORDER BY il.id ASC
");
$stmt2->execute([$id]);
$lines = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// hitung grand totals
$totalNorm = 0;
$totalLemb = 0;
foreach ($lines as $ln) {
    $totalNorm += $ln['tonase_normal'] * $inv['tarif_normal'];
    $totalLemb += $ln['tonase_lembur'] * $inv['tarif_lembur'];
}
$totalAll = $totalNorm + $totalLemb;

// bangun data untuk QR (link ke halaman view sendiri)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'];
$base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$qrData = "{$scheme}://{$host}{$base}/index.php?page=report&action=invoice_print&id={$inv['id']}";

// generate QR dengan phpqrcode
require_once __DIR__.'/../../vendor/phpqrcode/qrlib.php';
// kita generate ke string base64
ob_start();
QRcode::png($qrData, null, QR_ECLEVEL_L, 4);
$imageString = ob_get_clean();
$qrImage = 'data:image/png;base64,'. base64_encode($imageString);

// render partial
require __DIR__.'/../views/report/invoice_view_partial.php';
