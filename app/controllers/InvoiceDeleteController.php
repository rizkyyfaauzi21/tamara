<?php
// app/controllers/InvoiceDeleteController.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: index.php?page=login');
  exit;
}

$invoiceId = (int)($_GET['id'] ?? 0);
if (!$invoiceId) {
  $_SESSION['error'] = 'Invoice ID tidak valid.';
  header('Location: index.php?page=report');
  exit;
}

try {
  $conn->beginTransaction();

  // Ambil daftar STO yang terhubung dulu (sebelum baris dihapus)
  $q = $conn->prepare("SELECT sto_id FROM invoice_line WHERE invoice_id = ?");
  $q->execute([$invoiceId]);
  $stoIds = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));

  // Hapus lines
  $delLines = $conn->prepare("DELETE FROM invoice_line WHERE invoice_id = ?");
  $delLines->execute([$invoiceId]);

  // Hapus header
  $delInv = $conn->prepare("DELETE FROM invoice WHERE id = ?");
  $delInv->execute([$invoiceId]);

  // Kembalikan status STO menjadi NOT_USED
  if ($stoIds) {
    $in  = implode(',', array_fill(0, count($stoIds), '?'));
    $upd = $conn->prepare("UPDATE sto SET status='NOT_USED' WHERE id IN ($in)");
    $upd->execute($stoIds);
  }

  $conn->commit();
  $_SESSION['success'] = "Invoice #{$invoiceId} berhasil dihapus.";
} catch (Throwable $e) {
  if ($conn->inTransaction()) $conn->rollBack();
  $_SESSION['error'] = 'Gagal menghapus invoice: '.$e->getMessage();
}

header('Location: index.php?page=report');
exit;