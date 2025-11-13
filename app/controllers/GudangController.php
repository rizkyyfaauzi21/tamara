<?php
// Cek login & role superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SUPERADMIN') {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once __DIR__ . '/../views/layout/header.php';

// --- ACTION HANDLER ---
$action = $_GET['action'] ?? null;




if ($action === 'wilayah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $wilayah = trim($_POST['wilayah']);
    $id_user = !empty($_POST['id_user']) ? (int)$_POST['id_user'] : null;
    $id_wilayah = isset($_POST['id']) ? (int)$_POST['id'] : null; // untuk edit
    $isEdit = !empty($id_wilayah); // cek apakah ini update atau insert

    try {
        $conn->beginTransaction();

        // =========================================
        // 1️⃣ MODE INSERT BARU
        // =========================================
        if (!$isEdit) {
            $stmt = $conn->prepare("INSERT INTO wilayah (wilayah) VALUES (:wilayah)");
            $stmt->execute(['wilayah' => $wilayah]);

            $id_wilayah = $conn->lastInsertId();

            // Jika admin wilayah dipilih
            if (!empty($id_user)) {
                $stmtRelasi = $conn->prepare("
                    INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                    VALUES (:id_user, :id_wilayah)
                ");
                $stmtRelasi->execute([
                    'id_user' => $id_user,
                    'id_wilayah' => $id_wilayah
                ]);
                $_SESSION['success'] = "Wilayah baru berhasil ditambahkan dan dikaitkan dengan admin wilayah.";
            } else {
                $_SESSION['success'] = "Wilayah baru berhasil ditambahkan tanpa admin wilayah.";
            }
        }

        // =========================================
        // 2️⃣ MODE EDIT DATA WILAYAH
        // =========================================
        else {
            // Update nama wilayah
            $stmt = $conn->prepare("UPDATE wilayah SET wilayah = :wilayah WHERE id = :id");
            $stmt->execute([
                'wilayah' => $wilayah,
                'id' => $id_wilayah
            ]);

            // Cek apakah sudah punya relasi
            $check = $conn->prepare("SELECT id FROM user_admin_wilayah WHERE id_wilayah = :id_wilayah");
            $check->execute(['id_wilayah' => $id_wilayah]);
            $existingRelasi = $check->fetch(PDO::FETCH_ASSOC);

            // Jika admin wilayah dipilih
            if (!empty($id_user)) {
                if ($existingRelasi) {
                    // Update relasi yang sudah ada
                    $update = $conn->prepare("
                        UPDATE user_admin_wilayah 
                        SET id_user = :id_user 
                        WHERE id_wilayah = :id_wilayah
                    ");
                    $update->execute([
                        'id_user' => $id_user,
                        'id_wilayah' => $id_wilayah
                    ]);
                } else {
                    // Tambahkan relasi baru
                    $insert = $conn->prepare("
                        INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                        VALUES (:id_user, :id_wilayah)
                    ");
                    $insert->execute([
                        'id_user' => $id_user,
                        'id_wilayah' => $id_wilayah
                    ]);
                }
                $_SESSION['success'] = "Wilayah berhasil diperbarui dan dikaitkan dengan admin wilayah.";
            } else {
                // Jika dikosongkan, hapus relasi yang lama
                if ($existingRelasi) {
                    $delete = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_wilayah = :id_wilayah");
                    $delete->execute(['id_wilayah' => $id_wilayah]);
                }
                $_SESSION['success'] = "Wilayah berhasil diperbarui tanpa admin wilayah.";
            }
        }

        $conn->commit();

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Gagal menyimpan perubahan wilayah: " . $e->getMessage();
    }

    header("Location: index.php?page=gudang");
    exit;
}



// ==================================================
// 3️⃣ HAPUS WILAYAH + RELASI
// ==================================================
// pastikan ini berada di tempat yang menangani action 'admin_wilayah'
if (($action ?? '') === 'admin_wilayah') {

  // ==== DELETE WILAYAH + RELASI ====
if (isset($_GET['delete_wilayah'])) {

    $id_wilayah = (int) $_GET['delete_wilayah'];

    try {
        $conn->beginTransaction();

        // Hapus relasi jika ada
        $stmtRel = $conn->prepare("
            DELETE FROM user_admin_wilayah
            WHERE id_wilayah = ?
        ");
        $stmtRel->execute([$id_wilayah]);

        // Hapus wilayah
        $stmtWil = $conn->prepare("
            DELETE FROM wilayah
            WHERE id = ?
        ");
        $stmtWil->execute([$id_wilayah]);

        $conn->commit();
        $_SESSION['success'] = "Wilayah berhasil dihapus.";

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Gagal menghapus wilayah: " . $e->getMessage();
    }

    header("Location: index.php?page=gudang&action=admin_wilayah");
    exit;
}

}




// ==========================
// 2. KELOLA NAMA GUDANG
// ==========================
if ($action === 'nama' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama_gudang'];

    if (!empty($_POST['id'])) {
        // Update nama gudang
        $stmt = $conn->prepare("UPDATE gudang SET nama_gudang = :nama, id_wilayah = :id_wilayah WHERE id = :id");
        $stmt->execute([
            'nama' => $nama,
            'id_wilayah' => $_POST['id_wilayah'],
            'id' => $_POST['id']
        ]);
        $_SESSION['success'] = "Data gudang berhasil diperbarui.";
    } else {
        // Tambah nama gudang
        $stmt = $conn->prepare("INSERT INTO gudang (nama_gudang, id_wilayah) VALUES (:nama, :id_wilayah)");
        $stmt->execute(['nama' => $nama, 'id_wilayah' => $_POST['id_wilayah']]);
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
// 3. KELOLA TARIF GUDANG
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
$wilayahList = $conn->query("SELECT * FROM wilayah ORDER BY wilayah ASC")->fetchAll(PDO::FETCH_ASSOC);
$admin_wilayahList = $conn->query("SELECT * FROM users where role = 'ADMIN_WILAYAH';")->fetchAll(PDO::FETCH_ASSOC);
// $gudangList = $conn->query("
//     SELECT g.id, g.nama_gudang, g.id_wilayah 
//     FROM gudang g 
    
//     ORDER BY g.id DESC
// ")->fetchAll(PDO::FETCH_ASSOC);
$gudangList = $conn->query("
    SELECT g.id, g.nama_gudang, g.id_wilayah, w.wilayah 
    FROM gudang g 
    LEFT JOIN wilayah w ON g.id_wilayah = w.id
    ORDER BY g.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$tarifList = $conn->query("
    SELECT gt.*, g.nama_gudang 
    FROM gudang_tarif gt
    JOIN gudang g ON gt.gudang_id = g.id
    ORDER BY gt.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

$user_admin_wilayah = $conn->query("
    SELECT 
        w.id AS id_wilayah,
        w.wilayah,
        u.id AS id_user,
        u.nama,
        u.role,
        uaw.id AS id_relasi
    FROM wilayah AS w
    LEFT JOIN user_admin_wilayah AS uaw ON w.id = uaw.id_wilayah
    LEFT JOIN users AS u ON uaw.id_user = u.id
    ORDER BY w.wilayah ASC
")->fetchAll(PDO::FETCH_ASSOC);


// Load view
require_once __DIR__ . '/../views/gudang/index.php';
require_once __DIR__ . '/../views/layout/footer.php';