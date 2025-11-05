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
    // 'ADMIN_GUDANG',
    // 'KEPALA_GUDANG',
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
    // 'ADMIN_GUDANG',
    // 'KEPALA_GUDANG',
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

        if ($role === 'ADMIN_WILAYAH') {
            $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM user_admin_wilayah uaw
        JOIN gudang g ON g.id_wilayah = uaw.id_wilayah
        WHERE uaw.id_user = ? AND g.id = ?
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
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    try {
        $invId  = (int)($_POST['invoice_id'] ?? 0);
        $mode   = $_POST['decision'] ?? '';
        $status = ($mode === 'approve') ? 'APPROVED' : 'REJECTED';

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
        if ($current !== $role) {
            echo json_encode(['success' => false, 'message' => 'Bukan giliran Anda untuk memutuskan.']);
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

        // pastikan hanya admin wilayah yang sesuai bisa approve invoice
        if ($role === 'ADMIN_WILAYAH') {
            $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM user_admin_wilayah uaw
        JOIN gudang g ON g.id_wilayah = uaw.id_wilayah
        JOIN invoice i ON i.gudang_id = g.id
        WHERE uaw.id_user = ? AND i.id = ?
    ");
            $stmt->execute([$userId, $invId]);
            $allowed = $stmt->fetchColumn();

            if (!$allowed) {
                echo json_encode(['success' => false, 'message' => 'Anda bukan admin wilayah untuk invoice ini.']);
                exit;
            }
        }


        // simpan log
        $stmt = $conn->prepare("
          INSERT INTO `approval_log` (`invoice_id`,`role`,`status`,`created_by`,`created_at`)
          VALUES (?,?,?,?, NOW())
        ");
        $stmt->execute([$invId, $role, $status, $userId]);

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

        $no_soj = $_POST['no_soj'] ?? null;
        $no_mmj = $_POST['no_mmj'] ?? null;


        // update posisi (gunakan backtick)
        $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
        $upd->execute([$next, $invId]); // $next bisa null saat selesai

        if ($role === 'ADMIN_PCS') {
            $no_soj = $_POST['no_soj'] ?? null;
            $no_mmj = $_POST['no_mmj'] ?? null;
            $upd = $conn->prepare("UPDATE `invoice` 
        SET `current_role` = ?, 
            `no_soj` = COALESCE(?, `no_soj`), 
            `no_mmj` = COALESCE(?, `no_mmj`)
        WHERE `id` = ?");
            $upd->execute([$next, $no_soj, $no_mmj, $invId]);
        } else {
            $upd = $conn->prepare("UPDATE `invoice` SET `current_role` = ? WHERE `id` = ?");
            $upd->execute([$next, $invId]);
        }


        echo json_encode(['success' => true, 'next' => $next]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ===================== UI ===================== */
require __DIR__ . '/../views/scan/index.php';
