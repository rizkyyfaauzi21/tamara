<?php
// app/controllers/ScanController.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../config/database.php';

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

// urutan alur (sesuai kebutuhan proses)
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
    $i    = $idxOf($last['role']);
    if ($last['decision'] === 'APPROVED') {
        $current = ($i !== null && isset($flow[$i + 1])) ? $flow[$i + 1] : null;
    } else { // REJECTED => turun satu
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

        $current = $resolveCurrent($logs);

        // sinkron ke DB jika beda
        if (($inv['current_role'] ?? null) !== $current) {
            $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
            $upd->execute([$current, $invId]);
            $inv['current_role'] = $current;
        }

        // pass ke view
        $userIdView = $userId;
        require __DIR__ . '/../views/scan/invoice_detail.php';
    } catch (Throwable $e) {
        http_response_code(500);
        echo 'Fetch error: ' . $e->getMessage();
    }
    exit;
}

/* ===================== DECIDE (JSON) ===================== */
if ($action === 'decide' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear all output buffers
    while (ob_get_level()) ob_end_clean();
    
    header('Content-Type: application/json; charset=utf-8');

    try {
        $invId  = (int)($_POST['invoice_id'] ?? 0);
        $mode   = $_POST['decision'] ?? '';
        $status = ($mode === 'approve') ? 'APPROVED' : 'REJECTED';

        // Log untuk debugging
        error_log("Invoice ID: $invId");
        error_log("Decision: $mode");
        error_log("Status: $status");
        error_log("Role: $role");
        error_log("User ID: $userId");

        if (!$invId) {
            echo json_encode(['success' => false, 'message' => 'Invoice ID tidak valid']);
            exit;
        }

        // ambil logs untuk tentukan current server-side
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
            echo json_encode(['success' => false, 'message' => 'Bukan giliran Anda untuk memutuskan. Current: ' . $current]);
            exit;
        }

        // cegah double decide oleh role yang sama di siklus yang sama
        if ($logs) {
            $last = end($logs);
            if ((int)$last['created_by'] === $userId && $last['role'] === $role) {
                echo json_encode(['success' => false, 'message' => 'Anda sudah memberi keputusan untuk siklus ini.']);
                exit;
            }
        }

        // simpan log
        $stmt = $conn->prepare("
          INSERT INTO `approval_log` (`invoice_id`,`role`,`status`,`created_by`,`created_at`)
          VALUES (?,?,?,?, NOW())
        ");
        $stmt->execute([$invId, $role, $status, $userId]);

        error_log("Approval log inserted successfully");

        // hitung next role
        $i    = $idxOf($role);
        $next = null;
        if ($i !== null) {
            if ($status === 'APPROVED') {
                if (isset($flow[$i + 1])) $next = $flow[$i + 1];      // naik satu
            } else {
                $next = ($i > 0) ? $flow[$i - 1] : $flow[0];          // turun satu
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

        // Log data yang diterima
        error_log("Data POST - no_soj: " . ($no_soj ?? 'NULL'));
        error_log("Data POST - no_mmj: " . ($no_mmj ?? 'NULL'));
        error_log("Data POST - note_admin_wilayah: " . ($note_admin_wilayah ?? 'NULL'));
        error_log("Data POST - note_perwakilan_pi: " . ($note_perwakilan_pi ?? 'NULL'));
        error_log("Data POST - note_admin_pcs: " . ($note_admin_pcs ?? 'NULL'));
        error_log("Data POST - note_keuangan: " . ($note_keuangan ?? 'NULL'));

        // Update invoice berdasarkan role
        if ($role === 'ADMIN_WILAYAH') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_admin_wilayah` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_admin_wilayah, $invId]);
            error_log("Updated ADMIN_WILAYAH note");
            
        } elseif ($role === 'PERWAKILAN_PI') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_perwakilan_pi` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_perwakilan_pi, $invId]);
            error_log("Updated PERWAKILAN_PI note");
            
        } elseif ($role === 'ADMIN_PCS') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `no_soj` = ?, 
                    `no_mmj` = ?,
                    `note_admin_pcs` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $no_soj, $no_mmj, $note_admin_pcs, $invId]);
            error_log("Updated ADMIN_PCS note and numbers");
            
        } elseif ($role === 'KEUANGAN') {
            $upd = $conn->prepare("UPDATE `invoice` 
                SET `current_role` = ?, 
                    `note_keuangan` = ?
                WHERE `id` = ?");
            $upd->execute([$next, $note_keuangan, $invId]);
            error_log("Updated KEUANGAN note");
            
        } else {
            // Role lain atau SUPERADMIN
            $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
            $upd->execute([$next, $invId]);
            error_log("Updated current_role only");
        }

        error_log("Invoice updated successfully");

        echo json_encode([
            'success' => true, 
            'next' => $next,
            'message' => 'Keputusan berhasil disimpan'
        ]);
        
    } catch (Throwable $e) {
        error_log("Error in decide action: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        echo json_encode([
            'success' => false, 
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
    exit;
}

/* ===================== UI ===================== */
require __DIR__ . '/../views/scan/index.php';