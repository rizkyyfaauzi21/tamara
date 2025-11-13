<?php
// Cek login & role superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SUPERADMIN') {
    header('Location: index.php?page=dashboard');
    exit;
}

require_once __DIR__ . '/../views/layout/header.php';

// --- ACTION HANDLER ---
$action = $_GET['action'] ?? null;


if ($action === 'tambah_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        $id_user = $_POST['id'] ?? null;
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $role = $_POST['role'];
        $password = $_POST['password'] ?? '';
        $id_gudang = in_array($role, ['ADMIN_GUDANG', 'KEPALA_GUDANG']) && !empty($_POST['id_gudang'])
            ? $_POST['id_gudang']
            : null;

        // Normalisasi id_wilayah agar selalu array
        $wilayahList = isset($_POST['id_wilayah'])
            ? (is_array($_POST['id_wilayah']) ? $_POST['id_wilayah'] : [$_POST['id_wilayah']])
            : [];

        // === VALIDASI USERNAME UNIK ===
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username" . ($id_user ? " AND id != :id" : ""));
        $params = ['username' => $username];
        if ($id_user) $params['id'] = $id_user;
        $stmt->execute($params);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Username sudah digunakan!";
            header("Location: index.php?page=users");
            exit;
        }

       

        // === MODE EDIT USER ===
        if (!empty($id_user)) {
            // Update data utama
            if (!empty($password)) {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nama = :nama, username = :username, role = :role, id_gudang = :id_gudang, password = :password
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nama' => $nama,
                    'username' => $username,
                    'role' => $role,
                    'id_gudang' => $id_gudang,
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                    'id' => $id_user
                ]);
            } else {
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET nama = :nama, username = :username, role = :role, id_gudang = :id_gudang
                    WHERE id = :id
                ");
                $stmt->execute([
                    'nama' => $nama,
                    'username' => $username,
                    'role' => $role,
                    'id_gudang' => $id_gudang,
                    'id' => $id_user
                ]);
            }

            // Hapus wilayah lama
            $stmtDel = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_user = :id_user");
            $stmtDel->execute(['id_user' => $id_user]);

            // Tambah wilayah baru (jika ADMIN_WILAYAH)
            if ($role === 'ADMIN_WILAYAH' && !empty($wilayahList)) {
                $stmtWilayah = $conn->prepare("
                    INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                    VALUES (:id_user, :id_wilayah)
                ");
                foreach ($wilayahList as $wilayah_id) {
                    $stmtWilayah->execute([
                        'id_user' => $id_user,
                        'id_wilayah' => $wilayah_id
                    ]);
                }
            }

            $_SESSION['success'] = "Data user berhasil diperbarui.";

        // === MODE TAMBAH USER BARU ===
        } else {
            if (empty($password)) {
                $_SESSION['error'] = "Password wajib diisi untuk user baru!";
                header("Location: index.php?page=users");
                exit;
            }

            // Tambah data user baru
            $stmt = $conn->prepare("
                INSERT INTO users (nama, username, role, id_gudang, password)
                VALUES (:nama, :username, :role, :id_gudang, :password)
            ");
            $stmt->execute([
                'nama' => $nama,
                'username' => $username,
                'role' => $role,
                'id_gudang' => $id_gudang,
                'password' => password_hash($password, PASSWORD_DEFAULT)
            ]);

            $user_id = $conn->lastInsertId();

            // Masukkan wilayah (jika ADMIN_WILAYAH)
            if ($role === 'ADMIN_WILAYAH' && !empty($wilayahList)) {
                $stmtWilayah = $conn->prepare("
                    INSERT INTO user_admin_wilayah (id_user, id_wilayah)
                    VALUES (:id_user, :id_wilayah)
                ");
                foreach ($wilayahList as $wilayah_id) {
                    $stmtWilayah->execute([
                        'id_user' => $user_id,
                        'id_wilayah' => $wilayah_id
                    ]);
                }
            }

            $_SESSION['success'] = "User baru berhasil ditambahkan.";
        }

        $conn->commit();
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Terjadi kesalahan: " . $e->getMessage();
    }

    header("Location: index.php?page=users");
    exit;
}

// === HAPUS USER ===
if ($action === 'deleteUser' && isset($_GET['delete'])) {
    $id = $_GET['delete'];

    try {
        $conn->beginTransaction();

        // Hapus relasi wilayah
        $stmt = $conn->prepare("DELETE FROM user_admin_wilayah WHERE id_user = :id_user");
        $stmt->execute(['id_user' => $id]);

        // Hapus user utama
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

// menampilkan semua list gudang
$allGudangList = $conn->query("SELECT * FROM gudang ORDER BY nama_gudang")->fetchAll(PDO::FETCH_ASSOC);

// menampilkan list wilayah
$wilayahList = $conn->query("SELECT * FROM wilayah")->fetchAll(PDO::FETCH_ASSOC);

// menampilkan list user role admin gudang
$admin_gudangList = $conn->query("SELECT 
  u.id, 
  u.nama, 
  u.username, 
  u.role, 
  u.id_gudang, 
  g.nama_gudang 
FROM users u
LEFT JOIN gudang g ON u.id_gudang = g.id 
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
LEFT JOIN gudang g ON u.id_gudang = g.id 
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