<?php


// ✅ Bagian 1: Tangani AJAX POST dari tombol Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $id_invoice = $_POST['id_invoice'] ?? null;
    $decision   = $_POST['decision'] ?? null;
    $no_mmj     = trim($_POST['no_mmj'] ?? '');
    $no_soj     = trim($_POST['no_soj'] ?? '');
    $role       = 'ADMIN_PCS'; // nanti bisa diganti $_SESSION['role'] kalau sudah login

    if (!$id_invoice || !$decision) {
        echo json_encode([
            'success' => false,
            'message' => 'Data tidak lengkap (ID invoice atau keputusan tidak ada).'
        ]);
        exit;
    }

    // ✅ Validasi khusus ADMIN_PCS saat approve
    if ($role === 'ADMIN_PCS' && $decision === 'approve') {
        if (empty($no_mmj) || empty($no_soj)) {
            echo json_encode([
                'success' => false,
                'message' => 'Nomor MMJ dan SOJ wajib diisi sebelum melakukan approve.'
            ]);
            exit;
        }
    }

    // TODO: logika update database, misalnya:
    // update status invoice, insert log, dst...

    echo json_encode([
        'success' => true,
        'next' => 'KEUANGAN',
        'message' => 'Keputusan berhasil disimpan.'
    ]);
    exit;
}




// tersedia dari controller: $inv, $lines, $logs, $flow, $role, $userIdView (opsional)
$approved = array_column(array_filter($logs, fn($l)=>$l['decision']==='APPROVED'), 'role');
$rejected = array_column(array_filter($logs, fn($l)=>$l['decision']==='REJECTED'), 'role');
$lastLog = $logs ? end($logs) : null;
$lastDecision = $lastLog['decision'] ?? null;
$lastRole     = $lastLog['role'] ?? null;
$current = $inv['current_role'];
$isRevision = ($lastDecision === 'REJECTED');

$hasDecided = false;
if ($logs) {
  for ($i = count($logs)-1; $i>=0; $i--) {
    if ((int)$logs[$i]['created_by'] === (int)($userIdView ?? 0) && $logs[$i]['role'] === $role) {
      $hasDecided = true;
      break;
    }
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

  <?php if ($isRevision): ?>
    <div class="alert alert-warning">
      Dokumen direvisi oleh <strong><?= htmlspecialchars($lastRole) ?></strong>.
      Alur kembali ke <strong><?= htmlspecialchars($current) ?></strong>.
    </div>
  <?php endif; ?>

  <p class="mb-2">
    <strong>Bulan</strong>: <?= htmlspecialchars($inv['bulan']) ?><br>
    <strong>Jenis</strong>: <?= htmlspecialchars($inv['jenis_transaksi']) ?><br>
    <strong>Pupuk</strong>: <?= htmlspecialchars($inv['jenis_pupuk']) ?><br>
    <strong>Gudang</strong>: <?= htmlspecialchars($inv['nama_gudang']) ?><br>
    <strong>Dibuat</strong>: <?= htmlspecialchars($inv['created_at']) ?>
  </p>

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


  <?php if ($canDecide && $current !== null): ?>
  <div class="mb-4 mt-3">

    <?php if ($role === 'ADMIN_PCS'): ?>
      <!-- Hanya ADMIN_PCS yang bisa input -->
      <div class="row mb-3">
        <div class="col-md-6">
          <label for="no_mmj" class="form-label">Nomor MMJ</label>
          <input type="text" id="no_mmj" class="form-control" 
                 placeholder="Masukkan nomor MMJ"
                 value="<?= htmlspecialchars($inv['no_mmj'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label for="no_soj" class="form-label">Nomor SOJ</label>
          <input type="text" id="no_soj" class="form-control" 
                 placeholder="Masukkan nomor SOJ"
                 value="<?= htmlspecialchars($inv['no_soj'] ?? '') ?>">
        </div>
      </div>

    <?php elseif ($role === 'KEUANGAN'): ?>
      <!-- KEUANGAN hanya lihat (readonly) -->
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">Nomor MMJ</label>
          <input type="text" class="form-control" 
                 value="<?= htmlspecialchars($inv['no_mmj'] ?? '-') ?>" disabled>
        </div>
        <div class="col-md-6">
          <label class="form-label">Nomor SOJ</label>
          <input type="text" class="form-control" 
                 value="<?= htmlspecialchars($inv['no_soj'] ?? '-') ?>" disabled>
        </div>
      </div>
    <?php endif; ?>

    
      <div class="text-end">
        <button class="btn btn-success btn-decision"
                data-decision="approve"
                data-id="<?= (int)$inv['id'] ?>"
                data-role="<?= htmlspecialchars($role) ?>">
          Approve
        </button>
        <button class="btn btn-danger btn-decision"
                data-decision="reject"
                data-id="<?= (int)$inv['id'] ?>"
                data-role="<?= htmlspecialchars($role) ?>">
          Reject
        </button>
      </div>
    

  </div>

<?php elseif ($hasDecided): ?>
  <p class="text-warning">Anda sudah memberikan keputusan untuk siklus ini.</p>

<?php elseif ($current !== null): ?>
  <p class="text-muted">Menunggu keputusan <strong><?= htmlspecialchars($current) ?></strong>.</p>

<?php else: ?>
  <div class="alert alert-success mb-0">Proses selesai.</div>
<?php endif; ?>


<!-- Riwayat -->
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
