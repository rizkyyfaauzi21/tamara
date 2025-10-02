<?php
// app/controllers/DashboardController.php

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Ambil data user dari session
$username = $_SESSION['username'];
$role     = $_SESSION['role'];

require_once __DIR__ . '/../views/layout/header.php';
?>

<div class="dashboard-canvas">
  <div class="dashboard-grid" aria-hidden="true"></div>

  <!-- Top bar -->
  <div class="dash-bar section-pad py-3 mb-3">
    <div class="d-flex align-items-center justify-content-between">
      <div class="dash-brand">
        <div class="dash-badge" aria-hidden="true"></div>
        <div>
          <div class="fw-bold">TAMARA</div>
          <small class="text-muted">Dashboard</small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-3">
        <div class="text-end d-none d-md-block" style="color:var(--text)">
          Halo, <strong><?= htmlspecialchars($username) ?></strong>
          <span class="role-chip"><?= htmlspecialchars($role) ?></span>
        </div>
        <a href="index.php?page=logout" class="btn btn-sm btn-danger">Logout</a>
      </div>
    </div>
  </div>

  <!-- Content tiles -->
  <div class="section-pad tile-wrap">
    <h1 class="h4 mb-3" style="color:var(--text);">Dashboard</h1>

    <div class="tile-grid">

      <!-- Data STO (Sales Transport Order) -->
      <a href="index.php?page=master_sto" class="tile" style="text-decoration:none; color:inherit;">
        <div class="tile-body">
          <div class="tile-icon mb-2" aria-hidden="true">
            <!-- database icon -->
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
              <ellipse cx="12" cy="5" rx="8" ry="3" stroke="currentColor" stroke-width="1.6"></ellipse>
              <path d="M4 5v6c0 1.66 3.58 3 8 3s8-1.34 8-3V5" stroke="currentColor" stroke-width="1.6"></path>
              <path d="M4 11v6c0 1.66 3.58 3 8 3s8-1.34 8-3v-6" stroke="currentColor" stroke-width="1.6"></path>
            </svg>
          </div>
          <h5>Data STO</h5>
          <p>Akses, kelola, dan perbarui Sales Transport Order dengan mudah.</p>
        </div>
      </a>

      <!-- Monitoring Tagihan (data + status, tanpa grafik) -->
      <a href="index.php?page=report" class="tile" style="text-decoration:none; color:inherit;">
        <div class="tile-body">
          <div class="tile-icon mb-2" aria-hidden="true" style="background:linear-gradient(140deg, var(--blue), var(--green))">
            <!-- document/list icon -->
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
              <rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="1.6"/>
              <path d="M8 8h8M8 12h8M8 16h6" stroke="currentColor" stroke-width="1.6" />
            </svg>
          </div>
          <h5>Monitoring Tagihan</h5>
          <p>Pantau tagihan dan status approval secara real-time.</p>
        </div>
      </a>

      <!-- Verifikasi Tagihan via QR (approve/reject setelah scan) -->
      <a href="index.php?page=scan" class="tile" style="text-decoration:none; color:inherit;">
        <div class="tile-body">
          <div class="tile-icon mb-2" aria-hidden="true" style="background:linear-gradient(140deg, var(--green), var(--blue))">
            <!-- qr icon -->
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
              <path d="M3 3h6v6H3V3Z M15 3h6v6h-6V3Z M3 15h6v6H3v-6Z" stroke="currentColor" stroke-width="1.6"></path>
              <path d="M15 15h3m3 0v6m-6 0h3m0-6v3" stroke="currentColor" stroke-width="1.6"></path>
            </svg>
          </div>
          <h5>Verifikasi Tagihan</h5>
          <p>Scan QR untuk memeriksa detail tagihan sebelum approve atau reject.</p>
        </div>
      </a>

      <!-- Data Gudang (Superadmin only) -->
      <?php if ($role === 'SUPERADMIN'): ?>
      <a href="index.php?page=gudang" class="tile" style="text-decoration:none; color:inherit;">
        <div class="tile-body">
          <div class="tile-icon mb-2" aria-hidden="true" style="background:linear-gradient(140deg, var(--green), var(--amber))">
            <!-- warehouse icon -->
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
              <path d="M3 10l9-6 9 6v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-8Z" stroke="currentColor" stroke-width="1.6"/>
              <path d="M7 20v-6h10v6" stroke="currentColor" stroke-width="1.6"/>
            </svg>
          </div>
          <h5>Data Gudang</h5>
          <p>Kelola data gudang beserta tarifnya secara terpusat dan efisien.</p>
        </div>
      </a>
      <?php endif; ?>

    </div>
  </div>
</div>

<?php
require_once __DIR__ . '/../views/layout/footer.php';
