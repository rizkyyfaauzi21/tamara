<?php
// public/ajax/get_tarif.php
require __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$g  = $_GET['gudang_id']       ?? null;
$jt = $_GET['jenis_transaksi'] ?? null;
if (!$g || !$jt) {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("
  SELECT tarif_normal, tarif_lembur
    FROM gudang_tarif
   WHERE gudang_id      = :g
     AND jenis_transaksi = :jt
   LIMIT 1
");
$stmt->execute(['g'=>$g,'jt'=>$jt]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: []);
