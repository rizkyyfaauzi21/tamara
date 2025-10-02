<?php
// app/controllers/ReportGenerateController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php?page=login');
  exit;
}

// 1) Ambil & validasi input
$bulan            = trim($_POST['bulan']            ?? '');
$jenis_pupuk      = trim($_POST['jenis_pupuk']      ?? '');
$gudang_id        = (int)($_POST['gudang_id']       ?? 0);
$jenis_transaksi  = trim($_POST['jenis_transaksi']  ?? '');
$uraian_pekerjaan = trim($_POST['uraian_pekerjaan'] ?? '');
$tarif_normal     = (float)($_POST['tarif_normal']  ?? 0);
$tarif_lembur     = (float)($_POST['tarif_lembur']  ?? 0);
$sto_ids          = $_POST['sto_ids']               ?? [];

if (
  !$bulan || !$jenis_pupuk || !$gudang_id || !$jenis_transaksi ||
  !$uraian_pekerjaan || $tarif_normal <= 0 || $tarif_lembur <= 0 ||
  !is_array($sto_ids) || count($sto_ids) === 0
) {
  $_SESSION['error'] = 'Semua field wajib diisi, dan pilih minimal 1 STO.';
  header('Location: index.php?page=report');
  exit;
}

// 2) Hitung total berdasarkan tonase × tarif
$totalBN = 0.0;
$totalBL = 0.0;

try {
  $stmtSto = $conn->prepare("SELECT tonase_normal, tonase_lembur FROM sto WHERE id = :id");
  foreach ($sto_ids as $sid) {
    $sid = (int)$sid;
    if (!$sid) continue;
    $stmtSto->execute(['id' => $sid]);
    if ($row = $stmtSto->fetch(PDO::FETCH_ASSOC)) {
      $totalBN += ((float)$row['tonase_normal']) * $tarif_normal;
      $totalBL += ((float)$row['tonase_lembur']) * $tarif_lembur;
    }
  }
  $grandTotal = $totalBN + $totalBL;

  // 3) Simpan: header + lines + set STO USED (pakai transaksi)
  $conn->beginTransaction();

  // Header
  $insInv = $conn->prepare("
    INSERT INTO invoice
      (bulan, jenis_pupuk, gudang_id, jenis_transaksi, uraian_pekerjaan,
       tarif_normal, tarif_lembur,
       total_bongkar_normal, total_bongkar_lembur, total, created_at)
    VALUES
      (:bulan, :jenis_pupuk, :gudang_id, :jenis_transaksi, :uraian_pekerjaan,
       :tarif_normal, :tarif_lembur,
       :tbn, :tbl, :tot, NOW())
  ");
  $insInv->execute([
    'bulan'            => $bulan,
    'jenis_pupuk'      => $jenis_pupuk,
    'gudang_id'        => $gudang_id,
    'jenis_transaksi'  => $jenis_transaksi,
    'uraian_pekerjaan' => $uraian_pekerjaan,
    'tarif_normal'     => $tarif_normal,
    'tarif_lembur'     => $tarif_lembur,
    'tbn'              => $totalBN,
    'tbl'              => $totalBL,
    'tot'              => $grandTotal,
  ]);
  $invoiceId = (int)$conn->lastInsertId();

  // Lines
  $insLine = $conn->prepare("INSERT INTO invoice_line (invoice_id, sto_id) VALUES (:iid, :sid)");
  $stoIdsInt = [];
  foreach ($sto_ids as $sid) {
    $sid = (int)$sid;
    if (!$sid) continue;
    $insLine->execute(['iid' => $invoiceId, 'sid' => $sid]);
    $stoIdsInt[] = $sid;
  }

  // Tandai STO sebagai USED
  if ($stoIdsInt) {
    $in = implode(',', array_fill(0, count($stoIdsInt), '?'));
    $upd = $conn->prepare("UPDATE sto SET status='USED' WHERE id IN ($in)");
    $upd->execute($stoIdsInt);
  }

  $conn->commit();
  $_SESSION['success'] = "Invoice #{$invoiceId} berhasil dibuat!";
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  $_SESSION['error'] = 'Gagal membuat invoice: '.$e->getMessage();
}

header('Location: index.php?page=report');
exit;