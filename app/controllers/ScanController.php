<?php
// app/controllers/ScanController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

/* ------------ Konfigurasi Upload ------------ */
$UPLOAD_DIR   = realpath(__DIR__ . '/../../public') . '/uploads/invoice/';
$UPLOAD_URL   = 'uploads/invoice/';
$MAX_MB       = 10;
$MAX_BYTES    = $MAX_MB * 1024 * 1024;
$ALLOWED_EXT  = ['pdf', 'png', 'jpg', 'jpeg', 'xls', 'xlsx'];

if (!is_dir($UPLOAD_DIR)) {
    @mkdir($UPLOAD_DIR, 0775, true);
}
if (!is_dir($UPLOAD_DIR) || !is_writable($UPLOAD_DIR)) {
    $_SESSION['error'] = 'Folder upload tidak bisa ditulis: ' . $UPLOAD_DIR;
}

/* ------------ Helper: normalize upload array ------------ */
function normalize_upload_array(array $arr): array
{
    if (!is_array($arr['name'])) {
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $k) {
            $arr[$k] = [$arr[$k]];
        }
    }
    return $arr;
}

/* ------------ Helper: simpan files ke disk & DB ------------ */
function save_invoice_files(PDO $conn, int $invoiceId, string $field, string $dir, array $allowedExt, int $maxBytes, array &$debugErrors): int
{
    if (empty($_FILES[$field])) return 0;

    $files = normalize_upload_array($_FILES[$field]);
    $saved = 0;

    foreach ($files['name'] as $i => $origName) {
        if (!$origName || trim($origName) === '') continue;

        $err  = $files['error'][$i];
        $tmp  = $files['tmp_name'][$i];
        $size = (int)$files['size'][$i];

        if ($err !== UPLOAD_ERR_OK) {
            $debugErrors[] = "$origName: upload error code $err";
            continue;
        }

        if ($size > $maxBytes) {
            $debugErrors[] = "$origName: melebihi batas {$maxBytes} bytes";
            continue;
        }

        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            $debugErrors[] = "$origName: ekstensi tidak diizinkan ($ext)";
            continue;
        }

        if (!is_writable($dir)) {
            $debugErrors[] = "$origName: folder upload tidak writable ($dir)";
            continue;
        }

        $stored = uniqid('inv_', true) . '.' . $ext;
        if (!@move_uploaded_file($tmp, $dir . $stored)) {
            $debugErrors[] = "$origName: gagal move_uploaded_file()";
            continue;
        }

        $mime = function_exists('mime_content_type') ? @mime_content_type($dir . $stored) : null;

        $ins = $conn->prepare("
            INSERT INTO invoice_files (invoice_id, filename, stored_name, mime, size_bytes, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ins->execute([$invoiceId, $origName, $stored, $mime, $size]);
        $saved++;
    }
    return $saved;
}

/* ------------ Auth Check ------------ */
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$stmt   = $conn->prepare("SELECT `role` FROM `users` WHERE `id`=?");
$stmt->execute([$userId]);
$user   = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('HTTP/1.1 403 Forbidden');
    exit('User not found');
}
$role = $user['role'];

$allowed = [
    'ADMIN_WILAYAH',
    'PERWAKILAN_PI',
    'ADMIN_PCS',
    'KEUANGAN',
    'SUPERADMIN'
];

if (!in_array($role, $allowed, true)) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// urutan alur approval
$flow = [
    'ADMIN_WILAYAH',
    'PERWAKILAN_PI',
    'ADMIN_PCS',
    'KEUANGAN'
];

$idxOf = function (string $r) use ($flow) {
    $i = array_search($r, $flow, true);
    return $i === false ? null : $i;
};

$action = $_GET['action'] ?? 'form';

// util: cari current dari logs
$resolveCurrent = function (array $logs) use ($flow, $idxOf) {
    $current = 'ADMIN_WILAYAH';
    if (!$logs) return $current;
    $last = end($logs);
    
    // ✅ Skip jika status CLOSE atau REACTIVE
    if (in_array($last['decision'], ['CLOSE', 'REACTIVE'])) {
        // Jika CLOSE atau REACTIVE, current_role tetap di role terakhir yang melakukan aksi
        return $last['role'];
    }
    
    $i = $idxOf($last['role']);
    if ($last['decision'] === 'APPROVED') {
        $current = ($i !== null && isset($flow[$i + 1])) ? $flow[$i + 1] : null;
    } else { // REJECTED
        if ($i !== null) $current = ($i > 0) ? $flow[$i - 1] : $flow[0];
    }
    return $current;
};

/* ===================== FETCH ===================== */
if ($action === 'fetch') {
    try {
        $invId = (int)($_GET['id'] ?? 0);

        $stmt = $conn->prepare("
          SELECT i.*, g.`nama_gudang`
          FROM `invoice` i
          JOIN `gudang` g ON i.`gudang_id` = g.`id`
          WHERE i.`id` = ?
        ");
        $stmt->execute([$invId]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($role === 'ADMIN_WILAYAH') {
            $stmt = $conn->prepare("
                SELECT COUNT(*)
                FROM user_admin_wilayah
                WHERE id_user = ?
                  AND id_wilayah = (
                      SELECT id_wilayah FROM gudang WHERE id = ?
                  )
            ");
            $stmt->execute([$userId, $inv['gudang_id']]);
            $allowed = $stmt->fetchColumn();

            if (!$allowed) {
                http_response_code(403);
                exit('Anda bukan admin wilayah untuk invoice ini.');
            }
        }

        if (!$inv) {
            http_response_code(404);
            exit('Invoice tidak ditemukan');
        }

        $stmt = $conn->prepare("
          SELECT il.*, s.`nomor_sto`, s.`tanggal_terbit`, s.`tonase_normal`, s.`tonase_lembur`
          FROM `invoice_line` il
          JOIN `sto` s ON il.`sto_id` = s.`id`
          WHERE il.`invoice_id` = ?
          ORDER BY il.`id`
        ");
        $stmt->execute([$invId]);
        $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
          SELECT `id`, `role`, `status` AS `decision`, `created_by`, `created_at`
          FROM `approval_log`
          WHERE `invoice_id` = ?
          ORDER BY `created_at` ASC, `id` ASC
        ");
        $stmt->execute([$invId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("
          SELECT `id`, `filename`, `stored_name`, `mime`, `size_bytes`, `created_at`
          FROM `invoice_files`
          WHERE `invoice_id` = ?
          ORDER BY `created_at` ASC, `id` ASC
        ");
        $stmt->execute([$invId]);
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $current = $resolveCurrent($logs);

        // Sinkron current_role di DB
        if (($inv['current_role'] ?? null) !== $current) {
            $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
            $upd->execute([$current, $invId]);
            $inv['current_role'] = $current;
        }

        $userIdView = $userId;
        $invoiceFiles = $files ?? [];
        $uploadUrl = $UPLOAD_URL;
        require __DIR__ . '/../views/scan/invoice_detail.php';
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Fetch error: ' . $e->getMessage();
    }
    exit;
}

/* ===================== DECIDE (JSON) ===================== */
if ($action === 'decide' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $invId  = (int)($_POST['invoice_id'] ?? 0);
        $mode   = $_POST['decision'] ?? '';
        $status = ($mode === 'approve') ? 'APPROVED' : 'REJECTED';

        error_log("Invoice ID: $invId | Decision: $mode | Status: $status | Role: $role | User ID: $userId");

        if (!$invId) {
            echo json_encode(['success' => false, 'message' => 'Invoice ID tidak valid']);
            exit;
        }

        $stmt = $conn->prepare("
          SELECT `id`, `role`, `status` AS `decision`, `created_by`, `created_at`
          FROM `approval_log`
          WHERE `invoice_id` = ?
          ORDER BY `created_at` ASC, `id` ASC
        ");
        $stmt->execute([$invId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $current = $resolveCurrent($logs);

        error_log("Current role: $current");

        if ($current !== $role) {
            echo json_encode(['success' => false, 'message' => 'Bukan giliran Anda. Current: ' . $current]);
            exit;
        }

        // ✅ Cegah double decide - kecuali jika sedang REACTIVE
        $lastLog = $logs ? end($logs) : null;
        $isReactive = ($lastLog && $lastLog['decision'] === 'REACTIVE' && $lastLog['role'] === 'KEUANGAN');
        
        if ($logs && !$isReactive) {
            $last = end($logs);
            if ((int)$last['created_by'] === $userId && $last['role'] === $role && !in_array($last['decision'], ['CLOSE', 'REACTIVE'])) {
                echo json_encode(['success' => false, 'message' => 'Anda sudah memberi keputusan untuk siklus ini.']);
                exit;
            }
        }

        // Simpan log
        $stmt = $conn->prepare("
          INSERT INTO `approval_log` (`invoice_id`,`role`,`status`,`created_by`,`created_at`)
          VALUES (?,?,?,?, NOW())
        ");
        $stmt->execute([$invId, $role, $status, $userId]);

        error_log("Approval log inserted successfully");

        // Hitung next role
        $i = $idxOf($role);
        $next = null;
        if ($i !== null) {
            if ($status === 'APPROVED') {
                if (isset($flow[$i + 1])) $next = $flow[$i + 1];
            } else {
                $next = ($i > 0) ? $flow[$i - 1] : $flow[0];
            }
        }

        error_log("Next role: " . ($next ?? 'NULL'));

        // Ambil data dari POST
        $no_soj = !empty($_POST['no_soj']) ? trim($_POST['no_soj']) : null;
        $no_mmj = !empty($_POST['no_mmj']) ? trim($_POST['no_mmj']) : null;
        $note_admin_wilayah = !empty($_POST['note_admin_wilayah']) ? trim($_POST['note_admin_wilayah']) : null;
        $note_perwakilan_pi = !empty($_POST['note_perwakilan_pi']) ? trim($_POST['note_perwakilan_pi']) : null;
        $note_admin_pcs = !empty($_POST['note_admin_pcs']) ? trim($_POST['note_admin_pcs']) : null;
        $note_keuangan = !empty($_POST['note_keuangan']) ? trim($_POST['note_keuangan']) : null;

        // Update invoice berdasarkan role
        if ($role === 'ADMIN_WILAYAH') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_admin_wilayah` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_admin_wilayah, $invId]);
        } elseif ($role === 'PERWAKILAN_PI') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_perwakilan_pi` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_perwakilan_pi, $invId]);
        } elseif ($role === 'ADMIN_PCS') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `no_soj` = ?, 
                    `no_mmj` = ?,
                    `note_admin_pcs` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $no_soj, $no_mmj, $note_admin_pcs, $invId]);
        } elseif ($role === 'KEUANGAN') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_keuangan` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_keuangan, $invId]);
        } else {
            $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
            $upd->execute([$next, $invId]);
        }

        error_log("Invoice updated successfully");

        // Simpan file jika ada (khusus ADMIN_PCS)
        $uploadErrors = [];
        if ($role === 'ADMIN_PCS' && !empty($_FILES['files'])) {
            $savedCount = save_invoice_files($conn, $invId, 'files', $UPLOAD_DIR, $ALLOWED_EXT, $MAX_BYTES, $uploadErrors);
            error_log("Files saved: $savedCount");
        }

        $message = 'Keputusan berhasil disimpan';
        if (!empty($uploadErrors)) {
            $message .= '. Peringatan: ' . implode(', ', $uploadErrors);
        }

        echo json_encode([
            'success' => true,
            'next' => $next,
            'message' => $message
        ]);
    } catch (Throwable $e) {
        error_log("Error in decide action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

/* ===================== CLOSE (JSON) ===================== */
if ($action === 'close' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $invId = (int)($_POST['invoice_id'] ?? 0);

        error_log("Close Invoice ID: $invId | Role: $role | User ID: $userId");

        if (!$invId) {
            echo json_encode(['success' => false, 'message' => 'Invoice ID tidak valid']);
            exit;
        }

        if ($role !== 'KEUANGAN') {
            echo json_encode(['success' => false, 'message' => 'Hanya KEUANGAN yang dapat menutup invoice']);
            exit;
        }

        $stmt = $conn->prepare("
          SELECT `id`, `role`, `status` AS `decision`, `created_by`, `created_at`
          FROM `approval_log`
          WHERE `invoice_id` = ?
          ORDER BY `created_at` ASC, `id` ASC
        ");
        $stmt->execute([$invId]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $current = $resolveCurrent($logs);

        error_log("Current role: $current");

        if ($current !== 'KEUANGAN') {
            echo json_encode(['success' => false, 'message' => 'Bukan giliran KEUANGAN. Current: ' . $current]);
            exit;
        }

        // ✅ Cegah double close - kecuali jika sedang REACTIVE
        $lastLog = $logs ? end($logs) : null;
        $isReactive = ($lastLog && $lastLog['decision'] === 'REACTIVE');
        
        if ($logs && !$isReactive) {
            $last = end($logs);
            if ($last['role'] === 'KEUANGAN' && $last['decision'] === 'CLOSE') {
                echo json_encode(['success' => false, 'message' => 'Invoice sudah ditutup sebelumnya']);
                exit;
            }
        }

        $note_keuangan = !empty($_POST['note_keuangan']) ? trim($_POST['note_keuangan']) : null;

        // ✅ Update invoice: current_role tetap KEUANGAN (tidak NULL)
        $upd = $conn->prepare("
            UPDATE `invoice` 
            SET `note_keuangan` = ?
            WHERE `id` = ?
        ");
        $upd->execute([$note_keuangan, $invId]);

        error_log("Invoice closed successfully, current_role tetap KEUANGAN");

        // Simpan log CLOSE
        $stmt = $conn->prepare("
            INSERT INTO `approval_log` (`invoice_id`, `role`, `status`, `created_by`, `created_at`)
            VALUES (?, 'KEUANGAN', 'CLOSE', ?, NOW())
        ");
        $stmt->execute([$invId, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Invoice berhasil ditutup (CLOSE)',
            'current_role' => 'KEUANGAN'
        ]);
    } catch (Throwable $e) {
        error_log("Error in close action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

/* ===================== REACTIVE (JSON) ===================== */
if ($action === 'reactive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $invId = (int)($_POST['invoice_id'] ?? 0);

        error_log("Reactive Invoice ID: $invId | Role: $role | User ID: $userId");

        if (!$invId) {
            echo json_encode(['success' => false, 'message' => 'Invoice ID tidak valid']);
            exit;
        }

        if ($role !== 'KEUANGAN') {
            echo json_encode(['success' => false, 'message' => 'Hanya KEUANGAN yang dapat mengaktifkan kembali invoice']);
            exit;
        }

        // Ambil invoice dan status terakhir
        $stmt = $conn->prepare("SELECT `current_role` FROM `invoice` WHERE `id` = ?");
        $stmt->execute([$invId]);
        $inv = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inv) {
            echo json_encode(['success' => false, 'message' => 'Invoice tidak ditemukan']);
            exit;
        }

        // Ambil status terakhir dari approval log
        $stmt = $conn->prepare("
            SELECT `role`, `status`
            FROM `approval_log`
            WHERE `invoice_id` = ?
            ORDER BY `created_at` DESC, `id` DESC
            LIMIT 1
        ");
        $stmt->execute([$invId]);
        $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lastLog) {
            echo json_encode(['success' => false, 'message' => 'Tidak ada history approval']);
            exit;
        }

        // ✅ Invoice bisa direaktivasi jika:
        // 1. Status terakhir adalah CLOSE
        // 2. current_role adalah KEUANGAN
        if ($lastLog['status'] !== 'CLOSE') {
            echo json_encode(['success' => false, 'message' => 'Invoice tidak dalam status CLOSE, tidak bisa direaktivasi']);
            exit;
        }

        if ($inv['current_role'] !== 'KEUANGAN') {
            echo json_encode(['success' => false, 'message' => 'Hanya invoice yang sudah di-CLOSE oleh KEUANGAN yang bisa direaktivasi']);
            exit;
        }

        // ✅ REACTIVE: current_role tetap KEUANGAN, status berubah jadi REACTIVE
        error_log("REACTIVE: Invoice akan tetap di KEUANGAN dengan status REACTIVE");

        // Simpan log REACTIVE
        $stmt = $conn->prepare("
            INSERT INTO `approval_log` (`invoice_id`, `role`, `status`, `created_by`, `created_at`)
            VALUES (?, 'KEUANGAN', 'REACTIVE', ?, NOW())
        ");
        $stmt->execute([$invId, $userId]);

        echo json_encode([
            'success' => true,
            'message' => 'Invoice berhasil diaktifkan kembali. Anda dapat melakukan revisi atau close lagi.',
            'current_role' => 'KEUANGAN'
        ]);
    } catch (Throwable $e) {
        error_log("Error in reactive action: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

/* ===================== UI ===================== */
require __DIR__ . '/../views/scan/index.php';