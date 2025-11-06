<?php
// Jika form login dikirim (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    error_log("Login attempt for user: $username");

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
  <div class="auth-grid" aria-hidden="true"></div>

  <div class="login-card <?php echo isset($_SESSION['error']) ? 'shake' : '' ?>">
    <div class="brand">
      <h2>TAMARA</h2>
    </div>
    <p class="subtitle">Login ke dashboard</p>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-modern mb-3 py-2 px-3" role="alert">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="index.php?page=login" id="loginForm" novalidate>
    <!-- Username -->
    <div class="field input-wrap mb-3">
      
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

        <button type="button" class="toggle-pass" aria-label="Tampilkan/sembunyikan password" tabindex="0">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Z" stroke="currentColor" stroke-width="1.6"/>
            <circle cx="12" cy="12" r="3.2" stroke="currentColor" stroke-width="1.6"/>
        </svg>
        </button>
    </div>

    <div class="meta">
        <div class="form-check" style="user-select:none">
        <input class="form-check-input" type="checkbox" value="" id="remember" checked>
        <label class="form-check-label" for="remember">Ingat saya</label>
        </div>
        <span class="tiny"><a href="#" onclick="event.preventDefault();">Lupa password?</a></span>
    </div>

    <button type="submit" class="btn btn-gradient w-100 mt-3 py-2 fw-semibold" id="loginBtn">
        <span class="btn-text">Login</span>
        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
    </button>
    </form>

    <div class="tiny mt-3 text-center" style="color:#94a3b8">
      © <script>document.write(new Date().getFullYear())</script> IT - PT Petrokopindo Cipta Selaras • All rights reserved
    </div>
  </div>
</div>

<script>
(() => {
  const pass  = document.getElementById('password');
  const toggle= document.querySelector('.toggle-pass');
  const form  = document.getElementById('loginForm');
  const btn   = document.getElementById('loginBtn');
  const txt   = btn.querySelector('.btn-text');
  const spn   = btn.querySelector('.spinner-border');

  // Toggle eye (click + keyboard)
  const toggleEye = () => {
    const type = pass.getAttribute('type') === 'password' ? 'text' : 'password';
    pass.setAttribute('type', type);
    toggle.style.opacity = type === 'text' ? 1 : .85;
  };
  toggle.addEventListener('click', toggleEye);
  toggle.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleEye(); }
  });

  // Submit: client validation, loading state, shake on invalid
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) {
      e.preventDefault(); e.stopPropagation();
      const card = document.querySelector('.login-card');
      card.classList.add('shake');
      setTimeout(() => card.classList.remove('shake'), 450);
      return;
    }
    btn.disabled = true;
    txt.textContent = 'Memproses...';
    spn.classList.remove('d-none');
  }, false);
})();
</script>

<?php
require_once __DIR__ . '/../views/layout/footer.php';
