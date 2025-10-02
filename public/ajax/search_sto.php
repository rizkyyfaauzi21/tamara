<?php
// public/ajax/search_sto.php
require __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
$sql = "
  SELECT id, nomor_sto, tanggal_terbit, transportir
  FROM sto
  WHERE nomor_sto LIKE :q OR transportir LIKE :q
  ORDER BY created_at DESC
  LIMIT 50
";
$stmt = $conn->prepare($sql);
$stmt->execute(['q'=>"%{$q}%"]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
