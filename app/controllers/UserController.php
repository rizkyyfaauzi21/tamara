<?php
// Cek login & role superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SUPERADMIN') {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once __DIR__ . '/../views/layout/header.php';

// --- ACTION HANDLER ---
$action = $_GET['action'] ?? null;



//
if ($action === 'tambah_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $tambah_user = $_POST['tambah_user'];

    try {
        $conn->beginTransaction();

        // === MODE EDIT USER ===
        if (!empty($_POST['id'])) {
            $id_user = $_POST['id'];
            $id_gudang = null;

            if (in_array($_POST['role'], ['ADMIN_GUDANG', 'KEPALA_GUDANG'])) {
                $id_gudang = !empty($_POST['id_gudang']) ? $_POST['id_gudang'] : null;
            }

            // --- Update data user utama ---
            if (!empty($_POST['password'])) {
                // Jika password diisi → update semuanya
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nama = :nama, username = :username, role = :role, id_gudang = :id_gudang, password = :password 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nama' => $_POST['nama'],
                    'username' => $_POST['username'],
                    'role' => $_POST['role'],
                    'id_gudang' => $id_gudang,
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'id' => $id_user
                ]);
            } else {
                // Password tidak diubah
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nama = :nama, username = :username, role = :role, id_gudang = :id_gudang 
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nama' => $_POST['nama'],
                    'username' => $_POST['username'],
                    'role' => $_POST['role'],
                    'id_gudang' => $id_gudang,
                    'id' => $id_user
                ]);
            }

            // --- Jika role ADMIN_WILAYAH, perbarui data wilayahnya ---
            if ($_POST['role'] === 'ADMIN_WILAYAH') {
                // Hapus data wilayah lama
                $stmtDel = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_user = :id_user");
                $stmtDel->execute(['id_user' => $id_user]);

                // Insert ulang wilayah baru
                if (!empty($_POST['id_wilayah']) && is_array($_POST['id_wilayah'])) {
                    $stmtWilayah = $conn->prepare("
                        INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                        VALUES (:id_user, :id_wilayah)
                    ");
                    foreach ($_POST['id_wilayah'] as $wilayah_id) {
                        $stmtWilayah->execute([
                            'id_user' => $id_user,
                            'id_wilayah' => $wilayah_id
                        ]);
                    }
                }
            } else {
                // Jika bukan ADMIN_WILAYAH, pastikan datanya tidak nyangkut
                $stmtDel = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_user = :id_user");
                $stmtDel->execute(['id_user' => $id_user]);
            }

            $conn->commit();
            $_SESSION['success'] = "Data user berhasil diperbarui.";

        // === MODE TAMBAH USER BARU ===
        } else {
            if (empty($_POST['password'])) {
                $_SESSION['error'] = "Password wajib diisi untuk user baru!";
                header("Location: index.php?page=users");
                exit;
            }

            $id_gudang = null;
            if (in_array($_POST['role'], ['ADMIN_GUDANG', 'KEPALA_GUDANG'])) {
                $id_gudang = !empty($_POST['id_gudang']) ? $_POST['id_gudang'] : null;
            }

            // Insert user utama
            $stmt = $conn->prepare("
                INSERT INTO users (nama, username, role, id_gudang, password)
                VALUES (:nama, :username, :role, :id_gudang, :password)
            ");
            $stmt->execute([
                'nama' => $_POST['nama'],
                'username' => $_POST['username'],
                'role' => $_POST['role'],
                'id_gudang' => $id_gudang,
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT)
            ]);

            $user_id = $conn->lastInsertId();

            // Jika ADMIN_WILAYAH, masukkan ke tabel relasi
            if ($_POST['role'] === 'ADMIN_WILAYAH' && !empty($_POST['id_wilayah']) && is_array($_POST['id_wilayah'])) {
                $stmtWilayah = $conn->prepare("
                    INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                    VALUES (:id_user, :id_wilayah)
                ");
                foreach ($_POST['id_wilayah'] as $wilayah_id) {
                    $stmtWilayah->execute([
                        'id_user' => $user_id,
                        'id_wilayah' => $wilayah_id
                    ]);
                }
            }

            $conn->commit();
            $_SESSION['success'] = "User baru berhasil ditambahkan.";
        }

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header("Location: index.php?page=users");
    exit;
}

if ($action === 'deleteUser' && isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $conn->beginTransaction();

        // Hapus dari tabel relasi dulu
        $stmt = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_user = :id_user");
        $stmt->execute(['id_user' => $id]);

        // Baru hapus user utama
        $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $conn->commit();
        $_SESSION['success'] = "User berhasil dihapus.";
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Gagal menghapus user: " . $e->getMessage();
    }

    header("Location: index.php?page=users");
    exit;
}


// untuk menampilkan list gudang yang belum memiliki admin gudang atau kepala gudang buat di select saat menambahkan admin gudang / kepala gudang
$gudangList = $conn->query("SELECT g.*
  FROM gudang g
  LEFT JOIN users u ON g.id = u.id_gudang
    AND u.role IN ('admin_gudang', 'kepala_gudang')
  GROUP BY g.id
  HAVING COUNT(DISTINCT u.role) < 2;")->fetchAll(PDO::FETCH_ASSOC);

// untuk menampilkan list wilayah yang belum memiliki admin wilayah buat di select saat menambahkan admin wilayah
$wilayahList = $conn->query("  SELECT w.*
  FROM wilayah w
  LEFT JOIN user_admin_wilayah uaw ON w.id = uaw.id_wilayah
  LEFT JOIN users u ON uaw.id_user = u.id AND u.role = 'admin_wilayah'
  WHERE u.id IS NULL
")->fetchAll(PDO::FETCH_ASSOC);

// menampilkan list user role admin gudang
$admin_gudangList = $conn->query("SELECT 
  u.id, 
  u.nama, 
  u.username, 
  u.role, 
  u.id_gudang, 
  g.nama_gudang 
FROM users u
JOIN gudang g ON u.id_gudang = g.id 
WHERE u.role = 'ADMIN_GUDANG';")->fetchAll(PDO::FETCH_ASSOC);

// menampilkan list user role kepala gudang
$kepala_gudangList = $conn->query("SELECT 
  u.id, 
  u.nama, 
  u.username, 
  u.role, 
  u.id_gudang, 
  g.nama_gudang 
FROM users u
JOIN gudang g ON u.id_gudang = g.id 
WHERE u.role = 'KEPALA_GUDANG';")->fetchAll(PDO::FETCH_ASSOC);

$admin_wilayahList = $conn->query("
    SELECT 
        u.id,
        u.nama,
        u.username,
        u.role,
        GROUP_CONCAT(w.wilayah SEPARATOR ', ') AS wilayah_ditangani,
        GROUP_CONCAT(w.id SEPARATOR ',') AS id_wilayah_ditangani
    FROM users u
    LEFT JOIN user_admin_wilayah uaw ON u.id = uaw.id_user
    LEFT JOIN wilayah w ON uaw.id_wilayah = w.id
    WHERE u.role NOT IN ('ADMIN_GUDANG', 'KEPALA_GUDANG')
    GROUP BY u.id, u.nama, u.username, u.role
")->fetchAll(PDO::FETCH_ASSOC);


// Load view
require_once __DIR__ . '/../views/users/index.php';
require_once __DIR__ . '/../views/layout/footer.php';