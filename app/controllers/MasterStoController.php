<?php
// app/controllers/MasterStoController.php
if (session_status()===PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

/* ------------ Konfigurasi Upload ------------ */
$UPLOAD_DIR   = realpath(__DIR__ . '/../../public') . '/uploads/sto/'; // path absolut
$UPLOAD_URL   = 'uploads/sto/';                                        // URL relatif dari /public
$MAX_MB       = 10;
$MAX_BYTES    = $MAX_MB * 1024 * 1024;
$ALLOWED_EXT  = ['pdf','png','jpg','jpeg','xls','xlsx'];

if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}
if (!is_dir($UPLOAD_DIR) || !is_writable($UPLOAD_DIR)) {
    $_SESSION['error'] = 'Folder upload tidak bisa ditulis: ' . $UPLOAD_DIR;
}

/* ------------ Guard Login ------------ */
if (!isset($_SESSION['user_id'])) { header('Location: index.php?page=login'); exit; }

/* ------------ Hapus 1 file lampiran ------------ */
if (($_GET['action'] ?? '') === 'del_file' && !empty($_GET['file_id'])) {
    $stmt = $conn->prepare("SELECT id, stored_name FROM sto_files WHERE id=?");
    $stmt->execute([$_GET['file_id']]);
    if ($f = $stmt->fetch(PDO::FETCH_ASSOC)) {
        @unlink($UPLOAD_DIR . $f['stored_name']);
        $conn->prepare("DELETE FROM sto_files WHERE id=?")->execute([$f['id']]);
        $_SESSION['success'] = "Lampiran dihapus.";
    }
    header('Location: index.php?page=master_sto'); exit;
}

/* ------------ AJAX GET satu STO (+ files) ------------ */
if (($_GET['action'] ?? '') === 'get' && !empty($_GET['id'])) {
    $stmt = $conn->prepare("
      SELECT s.*, g.nama_gudang
      FROM sto s
      JOIN gudang g ON s.gudang_id = g.id
      WHERE s.id = :id
    ");
    $stmt->execute(['id'=>$_GET['id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $fs = $conn->prepare("
      SELECT id, filename, stored_name, size_bytes
      FROM sto_files WHERE sto_id=? ORDER BY id
    ");
    $fs->execute([$_GET['id']]);
    $row['files'] = $fs->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($row); exit;
}

/* ------------ Helper: simpan files ke disk & DB ------------ */
function normalize_upload_array(array $arr): array {
    // pastikan selalu berupa array-of-files
    if (!is_array($arr['name'])) { // fallback kalau name tanpa []
        foreach (['name','type','tmp_name','error','size'] as $k) {
            $arr[$k] = [$arr[$k]];
        }
    }
    return $arr;
}

function save_uploaded_files(PDO $conn, int $stoId, string $field, string $dir, array $allowedExt, int $maxBytes, array &$debugErrors): int {
    if (empty($_FILES[$field])) return 0;

    $files = normalize_upload_array($_FILES[$field]);
    $saved = 0;

    foreach ($files['name'] as $i => $origName) {
        // skip kalau kosong
        if (!$origName || trim($origName)==='') continue;

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

/* ------------ Update STO (AJAX) ------------ */
if ($_SERVER['REQUEST_METHOD']==='POST' && (($_POST['action'] ?? '')==='update')) {
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
        status          = :status
      WHERE id = :id
    ");
    $upd->execute([
      'nomor_sto'       => $_POST['nomor_sto'],
      'tanggal_terbit'  => $_POST['tanggal_terbit'],
      'gudang_id'       => $_POST['gudang_id'],
      'jenis_transaksi' => $_POST['jenis_transaksi'],
      'tonase_normal'   => $_POST['tonase_normal'],
      'tonase_lembur'   => $_POST['tonase_lembur'],
      'transportir'     => $_POST['transportir'],
      'keterangan'      => $_POST['keterangan'] ?: null,
      'status'          => $_POST['status'],
      'id'              => $_POST['id'],
    ]);

    // upload tambahan edit_files[]
    $debug = [];
    $saved = save_uploaded_files($conn, (int)$_POST['id'], 'edit_files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

    header('Content-Type: application/json');
    echo json_encode([
      'success' => true,
      'saved'   => $saved,
      'errors'  => $debug, // kirimkan biar kelihatan di network tab kalau ada masalah
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

  $_SESSION['success']="STO terhapus.";
  header('Location: index.php?page=master_sto'); exit;
}

/* ------------ Insert STO baru (dengan files[]) ------------ */
// if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['action'])) {
//   // duplikat
//   $chk = $conn->prepare("SELECT id FROM sto WHERE nomor_sto=?");
//   $chk->execute([$_POST['nomor_sto']]);
//   if ($chk->rowCount()) {
//     $_SESSION['error']="Nomor STO sudah terdaftar!";
//     header('Location: index.php?page=master_sto'); exit;
//   }

//   $ins = $conn->prepare("
//     INSERT INTO sto (
//       nomor_sto,tanggal_terbit,keterangan,
//       gudang_id,jenis_transaksi,transportir,
//       tonase_normal,tonase_lembur,status,created_at
//     ) VALUES (
//       :nomor_sto,:tanggal_terbit,:keterangan,
//       :gudang_id,:jenis_transaksi,:transportir,
//       :tonase_normal,:tonase_lembur,'NOT_USED',NOW()
//     )
//   ");
//   $ins->execute([
//     'nomor_sto'       => $_POST['nomor_sto'],
//     'tanggal_terbit'  => $_POST['tanggal_terbit'],
//     'keterangan'      => $_POST['keterangan'] ?: null,
//     'gudang_id'       => $_POST['gudang_id'],
//     'jenis_transaksi' => $_POST['jenis_transaksi'],
//     'transportir'     => $_POST['transportir'],
//     'tonase_normal'   => $_POST['tonase_normal'],
//     'tonase_lembur'   => $_POST['tonase_lembur'],
//   ]);
//   $newId = (int)$conn->lastInsertId();

//   // simpan lampiran baru
//   $debug = [];
//   $saved = save_uploaded_files($conn, $newId, 'files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

//   if ($debug) {
//     $_SESSION['error'] = 'Sebagian lampiran gagal diunggah: ' . implode(' | ', $debug);
//   }
//   if ($saved > 0) {
//     $_SESSION['success'] = "STO berhasil didaftarkan. Lampiran tersimpan: $saved file.";
//   } else {
//     $_SESSION['success'] = "STO berhasil didaftarkan.";
//   }
//   header('Location: index.php?page=master_sto'); exit;
// }

if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['action'])) {
    // DEBUG sementara: lihat payload di error log (hapus setelah verifikasi)
    error_log("MASTER_STO POST: " . print_r($_POST, true));
    error_log("MASTER_STO FILES: " . print_r($_FILES, true));

    // normalisasi input (menerima scalar atau array)
    $jenis_raw       = $_POST['jenis_transaksi'] ?? [];
    $ton_normal_raw  = $_POST['tonase_normal'] ?? [];
    $ton_lembur_raw  = $_POST['tonase_lembur'] ?? [];
    $transportir_raw = $_POST['transportir'] ?? [];
    $ket_raw         = $_POST['keterangan'] ?? [];

    if (!is_array($jenis_raw))       $jenis_raw       = [$jenis_raw];
    if (!is_array($ton_normal_raw))  $ton_normal_raw  = [$ton_normal_raw];
    if (!is_array($ton_lembur_raw))  $ton_lembur_raw  = [$ton_lembur_raw];
    if (!is_array($transportir_raw)) $transportir_raw = [$transportir_raw];
    if (!is_array($ket_raw))         $ket_raw         = [$ket_raw];

    // bangun baris kegiatan dan hitung agregat
    $rows = [];
    $sum_normal = 0.0;
    $sum_lembur = 0.0;
    $jenis_names = [];
    $transportir_list = [];

    $count = max(count($jenis_raw), count($ton_normal_raw), count($ton_lembur_raw), count($transportir_raw), count($ket_raw));
    for ($i = 0; $i < $count; $i++) {
        $j = trim((string)($jenis_raw[$i] ?? ''));
        $tn = (float) str_replace(',', '.', ($ton_normal_raw[$i] ?? 0));
        $tl = (float) str_replace(',', '.', ($ton_lembur_raw[$i] ?? 0));
        $tr = trim((string)($transportir_raw[$i] ?? ''));
        $kt = trim((string)($ket_raw[$i] ?? ''));

        // skip baris kosong (tidak ada jenis + tonase 0)
        if ($j === '' && $tn == 0.0 && $tl == 0.0) continue;

        $rows[] = [
            'jenis' => $j,
            'tonase_normal' => $tn,
            'tonase_lembur' => $tl,
            'transportir' => $tr,
            'keterangan' => $kt,
        ];

        $sum_normal += $tn;
        $sum_lembur += $tl;
        if ($j !== '') $jenis_names[] = $j;
        if ($tr !== '') $transportir_list[] = $tr;
    }

    if (empty($rows)) {
        $_SESSION['error'] = 'Minimal satu kegiatan harus diisi.';
        header('Location: index.php?page=master_sto'); exit;
    }

    // gabungkan jenis & transportir menjadi string (disimpan di tabel sto)
    $jenis_join = implode(', ', array_unique($jenis_names));
    $transportir_join = implode(', ', array_unique($transportir_list));

    // Cek duplikat nomor_sto
    $chk = $conn->prepare("SELECT id FROM sto WHERE nomor_sto=?");
    $chk->execute([ (is_array($_POST['nomor_sto'] ?? null) ? ($_POST['nomor_sto'][0] ?? '') : ($_POST['nomor_sto'] ?? '')) ]);
    if ($chk->rowCount()) {
        $_SESSION['error'] = "Nomor STO sudah terdaftar!";
        header('Location: index.php?page=master_sto'); exit;
    }

    // Simpan 1 record STO dengan agregat kegiatan
    $ins = $conn->prepare("
      INSERT INTO sto (
        nomor_sto, tanggal_terbit, keterangan,
        gudang_id, jenis_transaksi, transportir,
        tonase_normal, tonase_lembur, status, created_at
      ) VALUES (
        :nomor_sto, :tanggal_terbit, :keterangan,
        :gudang_id, :jenis_transaksi, :transportir,
        :tonase_normal, :tonase_lembur, 'NOT_USED', NOW()
      )
    ");
    $ins->execute([
      'nomor_sto'      => is_array($_POST['nomor_sto']) ? ($_POST['nomor_sto'][0] ?? '') : ($_POST['nomor_sto'] ?? ''),
      'tanggal_terbit' => is_array($_POST['tanggal_terbit']) ? ($_POST['tanggal_terbit'][0] ?? '') : ($_POST['tanggal_terbit'] ?? ''),
      'keterangan'     => is_array($_POST['keterangan']) ? ($_POST['keterangan'][0] ?? null) : ($_POST['keterangan'] ?? null),
      'gudang_id'      => is_array($_POST['gudang_id']) ? ($_POST['gudang_id'][0] ?? '') : ($_POST['gudang_id'] ?? ''),
      'jenis_transaksi'=> $jenis_join,
      'transportir'    => $transportir_join,
      'tonase_normal'  => $sum_normal,
      'tonase_lembur'  => $sum_lembur,
    ]);
    $newId = (int)$conn->lastInsertId();

    // Simpan lampiran (files[])
    $debug = [];
    $saved = save_uploaded_files($conn, $newId, 'files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

    // Simpan detail kegiatan sebagai JSON di kolom keterangan_kegiatan (pastikan kolom ada: TEXT/JSON)
    try {
        $updJson = $conn->prepare("UPDATE sto SET keterangan_kegiatan = :json WHERE id = :id");
        $updJson->execute(['json' => json_encode($rows, JSON_UNESCAPED_UNICODE), 'id' => $newId]);
    } catch (PDOException $e) {
        // catat error tapi jangan crash; beritahu user
        error_log("Gagal menyimpan keterangan_kegiatan JSON: " . $e->getMessage());
        // jangan set $_SESSION['error'] dengan pesan raw di production
    }

    $_SESSION['success'] = "STO berhasil didaftarkan dengan " . count($rows) . " kegiatan.";
    if ($debug) $_SESSION['error'] = implode(' | ', $debug);

    header('Location: index.php?page=master_sto'); exit;
}
/* ------------ Insert STO baru (dengan banyak kegiatan + files[]) ------------ */
// if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['action'])) {
//     // Cek duplikat
//     $chk = $conn->prepare("SELECT id FROM sto WHERE nomor_sto=?");
//     $chk->execute([$_POST['nomor_sto']]);
//     if ($chk->rowCount()) {
//         $_SESSION['error'] = "Nomor STO sudah terdaftar!";
//         header('Location: index.php?page=master_sto');
//         exit;
//     }

//     // Simpan data utama STO
//     $insSto = $conn->prepare("
//         INSERT INTO sto (nomor_sto, tanggal_terbit, keterangan, gudang_id, status, created_at)
//         VALUES (:nomor_sto, :tanggal_terbit, :keterangan, :gudang_id, 'NOT_USED', NOW())
//     ");
//     $insSto->execute([
//         'nomor_sto'      => $_POST['nomor_sto'],
//         'tanggal_terbit' => $_POST['tanggal_terbit'],
//         'keterangan'     => $_POST['keterangan'] ?: null,
//         'gudang_id'      => $_POST['gudang_id'],
//     ]);
//     $stoId = (int)$conn->lastInsertId();

//     // Pastikan semua field kegiatan ada
//     $jenis_transaksi = $_POST['jenis_transaksi'] ?? [];
//     $tonase_normal   = $_POST['tonase_normal'] ?? [];
//     $tonase_lembur   = $_POST['tonase_lembur'] ?? [];
//     $transportir     = $_POST['transportir'] ?? [];
//     $ketKegiatan     = $_POST['keterangan_kegiatan'] ?? [];

//     // Simpan setiap kegiatan
//     if ($jenis_transaksi) {
//         $insKegiatan = $conn->prepare("
//             INSERT INTO sto_kegiatan (sto_id, jenis_transaksi, tonase_normal, tonase_lembur, transportir, keterangan)
//             VALUES (?, ?, ?, ?, ?, ?)
//         ");
//         for ($i = 0; $i < count($jenis_transaksi); $i++) {
//             $insKegiatan->execute([
//                 $stoId,
//                 $jenis_transaksi[$i],
//                 $tonase_normal[$i] ?? 0,
//                 $tonase_lembur[$i] ?? 0,
//                 $transportir[$i] ?? '',
//                 $ketKegiatan[$i] ?? null,
//             ]);
//         }
//     }

//     // Simpan lampiran
//     $debug = [];
//     $saved = save_uploaded_files($conn, $stoId, 'files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $debug);

//     $_SESSION['success'] = "STO berhasil didaftarkan dengan " . count($jenis_transaksi) . " kegiatan.";
//     if ($debug) $_SESSION['error'] = implode(' | ', $debug);

//     header('Location: index.php?page=master_sto');
//     exit;
// }


/* ------------ Data untuk View ------------ */
$gudangs = $conn->query("SELECT id,nama_gudang FROM gudang ORDER BY nama_gudang")->fetchAll(PDO::FETCH_ASSOC);

$stoList = $conn->query("
  SELECT s.*, g.nama_gudang,
         (s.tonase_normal + s.tonase_lembur) AS jumlah
    FROM sto s
    JOIN gudang g ON s.gudang_id=g.id
   ORDER BY s.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// map files per STO
$filesBySto = [];
if ($stoList) {
    $ids = array_column($stoList, 'id');
    $in  = implode(',', array_fill(0,count($ids),'?'));
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
require_once __DIR__.'/../views/layout/header.php';
require_once __DIR__.'/../views/sto/master.php';
require_once __DIR__.'/../views/layout/footer.php';
