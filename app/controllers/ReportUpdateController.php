<?php
// app/controllers/ReportUpdateController.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';
if (!isset($_SESSION['user_id'])) { header('Location:index.php?page=login'); exit; }

$invoiceId = (int)($_GET['id'] ?? 0);
if (!$invoiceId){ $_SESSION['error']='Invoice ID tidak valid.'; header('Location:index.php?page=report'); exit; }

$bulan       = $_POST['bulan'] ?? '';
$jp          = $_POST['jenis_pupuk'] ?? '';
$gid         = (int)($_POST['gudang_id'] ?? 0);
$jtrans      = $_POST['jenis_transaksi'] ?? '';
$uraian      = $_POST['uraian_pekerjaan'] ?? '';
$tn          = (float)($_POST['tarif_normal'] ?? 0);
$tl          = (float)($_POST['tarif_lembur'] ?? 0);
$newIds      = array_map('intval', $_POST['sto_ids'] ?? []);

$conn->beginTransaction();
try {
  // header
  $upd=$conn->prepare("UPDATE invoice SET bulan=?,jenis_pupuk=?,gudang_id=?,jenis_transaksi=?,uraian_pekerjaan=?,tarif_normal=?,tarif_lembur=? WHERE id=?");
  $upd->execute([$bulan,$jp,$gid,$jtrans,$uraian,$tn,$tl,$invoiceId]);

  // ambil sto lama
  $oldStmt=$conn->prepare("SELECT sto_id FROM invoice_line WHERE invoice_id=?");
  $oldStmt->execute([$invoiceId]);
  $oldIds = array_map('intval',$oldStmt->fetchAll(PDO::FETCH_COLUMN));

  // hapus lines lama
  $conn->prepare("DELETE FROM invoice_line WHERE invoice_id=?")->execute([$invoiceId]);

  // insert lines baru
  $ins=$conn->prepare("INSERT INTO invoice_line (invoice_id, sto_id) VALUES (?,?)");
  foreach($newIds as $sid){ $ins->execute([$invoiceId,$sid]); }

  // hitung diff status
  $toRelease = array_values(array_diff($oldIds, $newIds)); // jadi NOT_USED
  $toUse     = array_values(array_diff($newIds, $oldIds)); // jadi USED

  if ($toRelease) {
    $in = implode(',', array_fill(0,count($toRelease),'?'));
    $conn->prepare("UPDATE sto SET status='NOT_USED' WHERE id IN ($in)")->execute($toRelease);
  }
  if ($toUse) {
    $in = implode(',', array_fill(0,count($toUse),'?'));
    $conn->prepare("UPDATE sto SET status='USED' WHERE id IN ($in)")->execute($toUse);
  }

  $conn->commit();
  $_SESSION['success']="Invoice #{$invoiceId} berhasil di-update.";
} catch(Exception $e){
  $conn->rollBack();
  $_SESSION['error']="Gagal update invoice: ".$e->getMessage();
}
header('Location:index.php?page=report'); exit;