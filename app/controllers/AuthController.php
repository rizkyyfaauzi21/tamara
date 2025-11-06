<?php
// Jika form login dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Ambil user dari database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cek password
    if ($user && password_verify($password, $user['password'])) {
        // Simpan session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect ke dashboard
        header('Location: index.php?page=dashboard');
        exit;
    } else {
        // Simpan error
        $_SESSION['error'] = 'Username atau password salah!';
        header('Location: index.php?page=login');
        exit;
    }
}

// Tampilkan form login
require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="auth-canvas">
  <!-- Wrapper -->
  <div class="auth-wrap">

    <!-- Brand (logo PCS + TAMARA) -->
    <div class="auth-brand brand-row">
      <img class="brand-pcs" src="assets/img/pcs.png" alt="PT Petrokopindo Cipta Selaras">
      <span class="brand-divider" aria-hidden="true"></span>
      <img class="brand-app" src="assets/img/tamara-logo.svg" alt="Tamara">
      <span class="brand-text">TAMARA</span>
    </div>

    <!-- Panel 2 kolom -->
    <div class="auth-panel auth-split <?php echo isset($_SESSION['error']) ? 'shake' : '' ?>">
      <!-- Kiri: Form -->
      <div class="auth-left auth-left--login">
        <h1 class="auth-title">Masuk ke Dashboard</h1>
        <p class="lead">Masukkan kredensial Anda</p>

        <?php if (isset($_SESSION['error'])): ?>
          <div class="alert alert-modern mb-3 py-2 px-3" role="alert">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="index.php?page=login" id="loginForm" novalidate>
          <!-- Username -->
          <div class="field mb-4">
            <label for="username" class="field-label">Username</label>
            <input
              type="text"
              class="form-control"
              id="username"
              name="username"
              placeholder="Masukkan Username"
              required
              autocomplete="username"
              autofocus
              aria-label="Username">
          </div>

          <!-- Password -->
          <div class="field input-wrap mb-2">
            <label for="password" class="field-label">Password</label>
            <input
              type="password"
              class="form-control"
              id="password"
              name="password"
              placeholder="Masukkan Password"
              required
              autocomplete="current-password"
              minlength="4"
              aria-label="Password">
          </div>

          <!-- Checkbox tampilkan/sembunyikan password -->
          <div class="showpass-row">
            <input type="checkbox" id="showpass" class="form-check-input">
            <label for="showpass" class="form-check-label">Tampilkan password</label>
          </div>

          <!-- Tombol utama -->
          <button type="submit" class="btn-continue mt-2" id="loginBtn">
            <span class="btn-text">Lanjutkan</span>
            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
          </button>

          <!-- Footer kecil di bawah tombol -->
          <div class="tiny mt-3 text-center">
            © <span id="y"></span> PT Petrokopindo Cipta Selaras
            <span class="d-block">Pergudangan & Pengantongan - IT</span>
          </div>
          <script>document.getElementById('y').textContent = new Date().getFullYear()</script>
        </form>
      </div>

      <!-- Kanan: gambar full-bleed -->
      <aside class="auth-right auth-hero">
        <img class="auth-hero__img" src="assets/img/login.jpg" alt="" loading="lazy" decoding="async">
      </aside>
    </div>
  </div>
</div>

<script>
(() => {
  const pass  = document.getElementById('password');
  const form  = document.getElementById('loginForm');
  const btn   = document.getElementById('loginBtn');
  const txt   = btn.querySelector('.btn-text');
  const spn   = btn.querySelector('.spinner-border');
  const show  = document.getElementById('showpass');

  // Checkbox show/hide password
  show.addEventListener('change', () => {
    pass.type = show.checked ? 'text' : 'password';
  });

  // Submit: validasi client, loading state, shake on invalid
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) {
      e.preventDefault(); e.stopPropagation();
      const panel = document.querySelector('.auth-panel');
      panel.classList.add('shake');
      setTimeout(() => panel.classList.remove('shake'), 450);
      return;
    }
    btn.disabled = true;
    txt.textContent = 'Memproses...';
    spn.classList.remove('d-none');
  }, false);

  // Pulihkan tombol jika ada error dari server (sesudah reload)
  if (<?php echo isset($_SESSION['error']) ? 'true' : 'false'; ?>) {
    btn.disabled = false; spn.classList.add('d-none'); txt.textContent = 'Lanjutkan';
  }
})();
</script>

<?php
require_once __DIR__ . '/../views/layout/footer.php';

