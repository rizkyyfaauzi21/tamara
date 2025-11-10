<?php
// app/controllers/MasterStoController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

/* ------------ Konfigurasi Upload ------------ */
$UPLOAD_DIR   = realpath(__DIR__ . '/../../public') . '/uploads/sto/'; // path absolut
$UPLOAD_URL   = 'uploads/sto/';                                        // URL relatif dari /public
$MAX_MB       = 10;
$MAX_BYTES    = $MAX_MB * 1024 * 1024;
$ALLOWED_EXT  = ['pdf', 'png', 'jpg', 'jpeg', 'xls', 'xlsx'];

if (!is_dir($UPLOAD_DIR)) {
  @mkdir($UPLOAD_DIR, 0775, true);
}
if (!is_dir($UPLOAD_DIR) || !is_writable($UPLOAD_DIR)) {
  $_SESSION['error'] = 'Folder upload tidak bisa ditulis: ' . $UPLOAD_DIR;
}

/* ------------ Guard Login ------------ */
if (!isset($_SESSION['user_id'])) {
  header('Location: index.php?page=login');
  exit;
}

$userId = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

/* ------------ Hapus 1 file lampiran ------------ */
if (($_GET['action'] ?? '') === 'del_file' && !empty($_GET['file_id'])) {
  $stmt = $conn->prepare("SELECT id, stored_name FROM sto_files WHERE id=?");
  $stmt->execute([$_GET['file_id']]);
  if ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
    @unlink($UPLOAD_DIR . $f['stored_name']);
    $conn->prepare("DELETE FROM sto_files WHERE id=?")->execute([$f['id']]);
    $_SESSION['success'] = "Lampiran dihapus.";
  }
  header('Location: index.php?page=master_sto');
  exit;
}

/* ------------ AJAX GET satu STO (+ files) ------------ */
if (($_GET['action'] ?? '') === 'get' && !empty($_GET['id'])) {
  $stmt = $conn->prepare("
      SELECT s.*, g.nama_gudang
      FROM sto s
      LEFT JOIN gudang g ON s.gudang_id = g.id
      WHERE s.id = :id
    ");
  $stmt->execute(['id' => $_GET['id']]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  $fs = $conn->prepare("
      SELECT id, filename, stored_name, size_bytes
      FROM sto_files WHERE sto_id=? ORDER BY id
    ");
  $fs->execute([$_GET['id']]);
  $row['files'] = $fs->fetchAll(PDO::FETCH_ASSOC);

  header('Content-Type: application/json');
  echo json_encode($row);
  exit;
}

/* ------------ Helper: simpan files ke disk & DB ------------ */
function normalize_upload_array(array $arr): array
{
  // pastikan selalu berupa array-of-files
  if (!is_array($arr['name'])) { // fallback kalau name tanpa []
    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
      $arr[$k] = [$arr[$k]];
    }
  }
  return $arr;
}

function save_uploaded_files(PDO $conn, int $stoId, string $field, string $dir, array $allowedExt, int $maxBytes, array &$debugErrors): int
{
  if (empty($_FILES[$field])) return 0;

  $files = normalize_upload_array($_FILES[$field]);
  $saved = 0;

  foreach ($files['name'] as $i => $origName) {
    // skip kalau kosong
    if (!$origName || trim($origName) === '') continue;

    $err  = $files['error'][$i];
    $tmp  = $files['tmp_name'][$i];
    $size = (int)$files['size'][$i];

    // cek error bawaan PHP
    if ($err !== UPLOAD_ERR_OK) {
      $debugErrors[] = "$origName: upload error code $err";
      continue;
    }

    // batas ukuran
    if ($size > $maxBytes) {
      $debugErrors[] = "$origName: melebihi batas {$maxBytes} bytes";
      continue;
    }

    // ekstensi
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
      $debugErrors[] = "$origName: ekstensi tidak diizinkan ($ext)";
      continue;
    }

    // pastikan folder writable
    if (!is_writable($dir)) {
      $debugErrors[] = "$origName: folder upload tidak writable ($dir)";
      continue;
    }

    // pindahkan
    $stored = uniqid('sto_', true) . '.' . $ext;
    if (!@move_uploaded_file($tmp, $dir . $stored)) {
      $debugErrors[] = "$origName: gagal move_uploaded_file()";
      continue;
    }

    // mime (opsional)
    $mime = function_exists('mime_content_type') ? @mime_content_type($dir . $stored) : null;

    // simpan DB
    $ins = $conn->prepare("
          INSERT INTO sto_files (sto_id, filename, stored_name, mime, size_bytes, created_at)
          VALUES (:sto_id, :fn, :sn, :mime, :sz, NOW())
        ");
    $ins->execute([
      'sto_id' => $stoId,
      'fn'     => $origName,
      'sn'     => $stored,
      'mime'   => $mime,
      'sz'     => $size,
    ]);
    $saved++;
  }
  return $saved;
}

/* ------------ Toggle Pilihan (AJAX) ------------ */
if (($_GET['action'] ?? '') === 'toggle_pilih' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $stoId = $_POST['sto_id'] ?? null;
  $newPilihan = $_POST['pilihan'] ?? null;

  if ($stoId && in_array($newPilihan, ['DIPILIH', 'BELUM_DIPILIH'], true)) {
    // Pastikan hanya STO dengan status 'NOT_USED' yang bisa diubah
    $stmt = $conn->prepare("UPDATE sto SET pilihan = :pilihan WHERE id = :id AND status = 'NOT_USED'");
    $stmt->execute([':pilihan' => $newPilihan, ':id' => $stoId]);

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
  }

  header('Content-Type: application/json');
  echo json_encode(['success' => false, 'message' => 'Invalid request']);
  exit;
}

$stmt = $conn->prepare("
  SELECT id_gudang 
  FROM users 
  WHERE id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user['id_gudang']) {
  $_SESSION['warning'] = "User tidak bisa menambah STO karena bukan bagian dari gudang manapun.";
}


$gudang_id = isset($user['id_gudang']) && $user['id_gudang'] !== null
  ? (int) $user['id_gudang']
  : null;

/* ------------ Update STO (AJAX) ------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'update')) {
  $upd = $conn->prepare("
      UPDATE sto SET
        nomor_sto       = :nomor_sto,
        tanggal_terbit  = :tanggal_terbit,
        gudang_id       = :gudang_id,
        jenis_transaksi = :jenis_transaksi,
        tonase_normal   = :tonase_normal,
        tonase_lembur   = :tonase_lembur,
        transportir     = :transportir,
        keterangan      = :keterangan,
        status          = :status,
        pilihan         = :pilihan
      WHERE id = :id
    ");
  $upd->execute([
    'nomor_sto'       => $_POST['nomor_sto'],
    'tanggal_terbit'  => $_POST['tanggal_terbit'],
    'gudang_id'       => $gudang_id,
    'jenis_transaksi' => $_POST['jenis_transaksi'],
    'tonase_normal'   => $_POST['tonase_normal'],
    'tonase_lembur'   => $_POST['tonase_lembur'],
    'transportir'     => $_POST['transportir'],
    'keterangan'      => $_POST['keterangan'] ?: null,
    'status'          => $_POST['status'],
    'pilihan'         => $_POST['pilihan'],
    'id'              => $_POST['id'],
  ]);

  // upload tambahan edit_files[]
  $debug = [];
  $saved = save_uploaded_files($conn, (int)$_POST['id'], 'edit_files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

  header('Content-Type: application/json');
  echo json_encode([
    'success' => true,
    'saved'   => $saved,
    'errors'  => $debug,
  ]);
  exit;
}

/* ------------ Delete STO ------------ */
if (isset($_GET['delete'])) {
  // hapus fisik lampiran
  $fs = $conn->prepare("SELECT stored_name FROM sto_files WHERE sto_id=?");
  $fs->execute([$_GET['delete']]);
  foreach ($fs->fetchAll(PDO::FETCH_ASSOC) as $f) {
    @unlink($UPLOAD_DIR . $f['stored_name']);
  }
  $conn->prepare("DELETE FROM sto_files WHERE sto_id=?")->execute([$_GET['delete']]);
  $conn->prepare("DELETE FROM sto WHERE id=?")->execute([$_GET['delete']]);

  $_SESSION['success'] = "STO terhapus.";
  header('Location: index.php?page=master_sto');
  exit;
}

/* ------------ Insert STO baru (dengan files[]) ------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
  // duplikat
  $chk = $conn->prepare("SELECT id FROM sto WHERE nomor_sto=?");
  $chk->execute([$_POST['nomor_sto']]);
  if ($chk->rowCount()) {
    $_SESSION['error'] = "Nomor STO sudah terdaftar!";
    header('Location: index.php?page=master_sto');
    exit;
  }

  $ins = $conn->prepare("
    INSERT INTO sto (
      nomor_sto,tanggal_terbit,keterangan,
      gudang_id,jenis_transaksi,transportir,
      tonase_normal,tonase_lembur,status,pilihan,created_at
    ) VALUES (
      :nomor_sto,:tanggal_terbit,:keterangan,
      :gudang_id,:jenis_transaksi,:transportir,
      :tonase_normal,:tonase_lembur,'NOT_USED','BELUM_DIPILIH',NOW()
    )
  ");

  $ins->execute([
    'nomor_sto'       => $_POST['nomor_sto'],
    'tanggal_terbit'  => $_POST['tanggal_terbit'],
    'keterangan'      => $_POST['keterangan'] ?: null,
    'gudang_id'       => $gudang_id,
    'jenis_transaksi' => $_POST['jenis_transaksi'],
    'transportir'     => $_POST['transportir'],
    'tonase_normal'   => $_POST['tonase_normal'],
    'tonase_lembur'   => $_POST['tonase_lembur'],
  ]);
  $newId = (int)$conn->lastInsertId();

  // simpan lampiran baru
  $debug = [];
  $saved = save_uploaded_files($conn, $newId, 'files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

  if ($debug) {
    $_SESSION['error'] = 'Sebagian lampiran gagal diunggah: ' . implode(' | ', $debug);
  }
  if ($saved > 0) {
    $_SESSION['success'] = "STO berhasil didaftarkan. Lampiran tersimpan: $saved file.";
  } else {
    $_SESSION['success'] = "STO berhasil didaftarkan.";
  }
  header('Location: index.php?page=master_sto');
  exit;
}

/* ------------ Data untuk View ------------ */
if ($userId) {
  $stmt = $conn->prepare("
        SELECT g.nama_gudang 
        FROM users u 
        JOIN gudang g ON u.id_gudang = g.id 
        WHERE u.id = ?
    ");
  $stmt->execute([$userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($row) {
    $nama_gudang = $row['nama_gudang'];
  }
}
$stmt = $conn->prepare("
  SELECT s.*, g.nama_gudang,
         (s.tonase_normal + s.tonase_lembur) AS jumlah
  FROM sto s
  JOIN gudang g ON s.gudang_id = g.id
  JOIN users u ON u.id_gudang = g.id
  WHERE u.id = :user_id
  ORDER BY s.created_at DESC
");
$stmt->execute(['user_id' => $userId]);
$stoList = $stmt->fetchAll(PDO::FETCH_ASSOC);
// map files per STO
$filesBySto = [];
if ($stoList) {
  $ids = array_column($stoList, 'id');
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $fs  = $conn->prepare("
      SELECT id, sto_id, filename, stored_name, size_bytes
      FROM sto_files WHERE sto_id IN ($in)
    ");
  $fs->execute($ids);
  foreach ($fs->fetchAll(PDO::FETCH_ASSOC) as $f) {
    $filesBySto[$f['sto_id']][] = $f;
  }
}

$filesBaseUrl = $UPLOAD_URL;

/* ------------ Render ------------ */
require_once __DIR__ . '/../views/layout/header.php';
require_once __DIR__ . '/../views/sto/master.php';
require_once __DIR__ . '/../views/layout/footer.php';