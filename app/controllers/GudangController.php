<?php
// Cek login & role superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SUPERADMIN') {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once __DIR__ . '/../views/layout/header.php';

// --- ACTION HANDLER ---
$action = $_GET['action'] ?? null;

// ==========================
// 1. KELOLA NAMA GUDANG
// ==========================
if ($action === 'nama' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_gudang'];

    if (!empty($_POST['id'])) {
        // Update nama gudang
        $stmt = $conn->prepare("UPDATE gudang SET nama_gudang = :nama WHERE id = :id");
        $stmt->execute([
            'nama' => $nama,
            'id' => $_POST['id']
        ]);
        $_SESSION['success'] = "Nama gudang berhasil diperbarui.";
    } else {
        // Tambah nama gudang
        $stmt = $conn->prepare("INSERT INTO gudang (nama_gudang) VALUES (:nama)");
        $stmt->execute(['nama' => $nama]);
        $_SESSION['success'] = "Nama gudang baru berhasil ditambahkan.";
    }

    header("Location: index.php?page=gudang");
    exit;
}

if ($action === 'nama' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM gudang WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $_SESSION['success'] = "Nama gudang berhasil dihapus.";
    header("Location: index.php?page=gudang");
    exit;
}

// ==========================
// 2. KELOLA TARIF GUDANG
// ==========================
if ($action === 'tarif' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $gudang_id = $_POST['gudang_id'];
    $jenis_transaksi = $_POST['jenis_transaksi'];
    $tarif_normal = $_POST['tarif_normal'];
    $tarif_lembur = $_POST['tarif_lembur'];

    if (!empty($_POST['id'])) {
        // Update tarif
        $stmt = $conn->prepare("UPDATE gudang_tarif 
            SET gudang_id = :gudang_id,
                jenis_transaksi = :jenis_transaksi,
                tarif_normal = :tarif_normal,
                tarif_lembur = :tarif_lembur
            WHERE id = :id");
        $stmt->execute([
            'gudang_id' => $gudang_id,
            'jenis_transaksi' => $jenis_transaksi,
            'tarif_normal' => $tarif_normal,
            'tarif_lembur' => $tarif_lembur,
            'id' => $_POST['id']
        ]);
        $_SESSION['success'] = "Tarif gudang berhasil diperbarui.";
    } else {
        // Tambah tarif baru
        $stmt = $conn->prepare("INSERT INTO gudang_tarif 
            (gudang_id, jenis_transaksi, tarif_normal, tarif_lembur)
            VALUES (:gudang_id, :jenis_transaksi, :tarif_normal, :tarif_lembur)");
        $stmt->execute([
            'gudang_id' => $gudang_id,
            'jenis_transaksi' => $jenis_transaksi,
            'tarif_normal' => $tarif_normal,
            'tarif_lembur' => $tarif_lembur
        ]);
        $_SESSION['success'] = "Tarif gudang baru berhasil ditambahkan.";
    }

    header("Location: index.php?page=gudang");
    exit;
}

if ($action === 'tarif' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM gudang_tarif WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $_SESSION['success'] = "Tarif gudang berhasil dihapus.";
    header("Location: index.php?page=gudang");
    exit;
}

// ==========================
// AMBIL DATA UNTUK VIEW
// ==========================
$gudangList = $conn->query("SELECT * FROM gudang ORDER BY nama_gudang ASC")->fetchAll(PDO::FETCH_ASSOC);
$tarifList = $conn->query("
    SELECT gt.*, g.nama_gudang 
    FROM gudang_tarif gt
    JOIN gudang g ON gt.gudang_id = g.id
    ORDER BY gt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Load view
require_once __DIR__ . '/../views/gudang/index.php';
require_once __DIR__ . '/../views/layout/footer.php';
?>
