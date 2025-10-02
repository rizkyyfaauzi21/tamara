<?php
// tersedia dari controller: $inv, $lines, $logs, $flow, $role, $userIdView (opsional)

// --- ringkas status dari log ---
$approved = array_column(array_filter($logs, fn($l)=>$l['decision']==='APPROVED'), 'role');
$rejected = array_column(array_filter($logs, fn($l)=>$l['decision']==='REJECTED'), 'role');

// log TERAKHIR (paling mutakhir)
$lastLog = $logs ? end($logs) : null;
$lastDecision = $lastLog['decision'] ?? null;
$lastRole     = $lastLog['role']     ?? null;

// current role sudah dihitung di controller & sudah disinkronkan ke DB
$current = $inv['current_role']; // bisa null jika sudah selesai di KEUANGAN

// isRevision hanya jika entry terakhir REJECT
$isRevision = ($lastDecision === 'REJECTED');

// boleh menekan tombol?
// - hanya jika posisinya sama dengan current
// - dan (belum pernah memutuskan di siklus ini) → dicek sederhana: cek entry terakhir oleh user ini dan role sama
$hasDecided = false;
if ($logs) {
  for ($i = count($logs)-1; $i>=0; $i--) {
    if ((int)$logs[$i]['created_by'] === (int)($userIdView ?? 0) && $logs[$i]['role'] === $role) {
      $hasDecided = true;
      break;
    }
    // berhenti jika ketemu keputusan oleh role lain; cukup untuk membatasi "siklus"
    if ($logs[$i]['role'] !== $role) break;
  }
}
$canDecide = ($current === $role) && !$hasDecided;
?>
<div class="card mb-3 p-3">
  <h5>
    Invoice #<?= htmlspecialchars($inv['id']) ?>
    &mdash; posisi:
    <strong><?= htmlspecialchars($current ?? 'SELESAI') ?></strong>
  </h5>

  <!-- indikator flow -->
  <div class="mb-3">
    <?php foreach ($flow as $r): ?>
      <?php if ($isRevision): ?>
        <span class="badge bg-warning"><?= htmlspecialchars($r) ?></span>
      <?php else: ?>
        <?php if (in_array($r, $approved, true)): ?>
          <span class="badge bg-success"><?= htmlspecialchars($r) ?></span>
        <?php elseif ($r === $current): ?>
          <span class="badge bg-primary"><?= htmlspecialchars($r) ?></span>
        <?php else: ?>
          <span class="badge bg-secondary"><?= htmlspecialchars($r) ?></span>
        <?php endif; ?>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <!-- pesan revisi: hanya saat log terakhir REJECT -->
  <?php if ($isRevision): ?>
    <div class="alert alert-warning">
      Dokumen direvisi oleh <strong><?= htmlspecialchars($lastRole) ?></strong>.
      Alur kembali satu tingkat ke <strong><?= htmlspecialchars($current) ?></strong>.
      Silakan perbaiki & teruskan kembali.
    </div>
  <?php endif; ?>

  <!-- header -->
  <p class="mb-2">
    <strong>Bulan</strong>: <?= htmlspecialchars($inv['bulan']) ?><br>
    <strong>Jenis</strong>: <?= htmlspecialchars($inv['jenis_transaksi']) ?><br>
    <strong>Pupuk</strong>: <?= htmlspecialchars($inv['jenis_pupuk']) ?><br>
    <strong>Gudang</strong>: <?= htmlspecialchars($inv['nama_gudang']) ?><br>
    <strong>Dibuat</strong>: <?= htmlspecialchars($inv['created_at']) ?>
  </p>

  <!-- tabel lines -->
  <table class="table table-sm table-bordered mb-3">
    <thead class="table-light">
      <tr>
        <th>No</th><th>STO</th><th>Tanggal</th>
        <th class="text-end">Normal</th><th class="text-end">Lembur</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($lines as $i => $ln): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($ln['nomor_sto']) ?></td>
        <td><?= htmlspecialchars($ln['tanggal_terbit']) ?></td>
        <td class="text-end"><?= number_format($ln['tonase_normal']) ?></td>
        <td class="text-end"><?= number_format($ln['tonase_lembur']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- tombol -->
  <?php if ($canDecide && $current !== null): ?>
    <div class="text-end mb-3">
      <?php if ($role === 'ADMIN_GUDANG'): ?>
        <button class="btn btn-primary btn-decision"
                data-decision="approve"
                data-id="<?= (int)$inv['id'] ?>">
          Serahkan
        </button>
      <?php else: ?>
        <button class="btn btn-success btn-decision"
                data-decision="approve"
                data-id="<?= (int)$inv['id'] ?>">
          Approve
        </button>
        <button class="btn btn-danger btn-decision"
                data-decision="reject"
                data-id="<?= (int)$inv['id'] ?>">
          Reject
        </button>
      <?php endif; ?>
    </div>
  <?php elseif ($hasDecided): ?>
    <p class="text-warning">Anda sudah memberikan keputusan untuk siklus ini.</p>
  <?php elseif ($current !== null): ?>
    <p class="text-muted">Menunggu keputusan <strong><?= htmlspecialchars($current) ?></strong>.</p>
  <?php else: ?>
    <div class="alert alert-success mb-0">Proses selesai.</div>
  <?php endif; ?>

  <!-- riwayat -->
  <?php if ($logs): ?>
    <h6>Riwayat</h6>
    <ul class="list-group">
      <?php foreach ($logs as $log): ?>
        <li class="list-group-item d-flex justify-content-between">
          <span>
            <strong><?= htmlspecialchars($log['role']) ?></strong>
            &mdash; <?= ucfirst(strtolower($log['decision'])) ?>
          </span>
          <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>