<?php
// app/views/scan/invoice_detail.php

// tersedia dari controller: $inv, $lines, $logs, $flow, $role, $userIdView (opsional)
$approved = array_column(array_filter($logs, fn($l) => $l['decision'] === 'APPROVED'), 'role');
$rejected = array_column(array_filter($logs, fn($l) => $l['decision'] === 'REJECTED'), 'role');
$lastLog = $logs ? end($logs) : null;
$lastDecision = $lastLog['decision'] ?? null;
$lastRole     = $lastLog['role'] ?? null;
$current = $inv['current_role'];
$isRevision = !empty($inv['is_revised']) || ($lastDecision === 'REJECTED');
$revisedBy = $inv['revised_by'] ?? $lastRole ?? null;
$canEdit = $isRevision && ($current === $role);

// Perbaiki deteksi revision dan current role
$isRevision = !empty($inv['is_revised']) || ($lastDecision === 'REJECTED');
$revisedBy = $inv['revised_by'] ?? $lastRole ?? null;
$current = $inv['current_role'];

// Reset status hasDecided jika ada revisi
$hasDecided = false;
if ($logs) {
    $lastCycleStartIndex = count($logs) - 1;
    // Jika ada revisi, cari index terakhir rejection
    if ($isRevision) {
        for ($i = count($logs) - 1; $i >= 0; $i--) {
            if ($logs[$i]['decision'] === 'REJECTED') {
                $lastCycleStartIndex = $i;
                break;
            }
        }
    }
    
    // Cek keputusan hanya dari titik revisi terakhir
    for ($i = $lastCycleStartIndex; $i < count($logs); $i++) {
        if ((int)$logs[$i]['created_by'] === (int)($userIdView ?? 0) && 
            $logs[$i]['role'] === $role) {
            $hasDecided = true;
            break;
        }
    }
}

// Role bisa decide jika:
// 1. Ini giliran mereka (current === role) dan belum decide di siklus ini
// 2. Atau jika sedang revisi dan mereka adalah role yang harus merevisi
$canDecide = ($current === $role && !$hasDecided);

if ($hasDecided) {
    echo '<p class="text-warning">Anda sudah memberikan keputusan untuk siklus ini.</p>';
} elseif (!empty($current)) {
    if ($current === $role) {
        echo '<p class="text-primary"><strong>Giliran Anda untuk memberikan keputusan.</strong></p>';
    } else {
        echo '<p class="text-muted">Menunggu keputusan <strong>' . htmlspecialchars($current) . '</strong>.</p>';
    }
} else {
    echo '<p class="text-success"><strong>PROSES SELESAI.</strong></p>';
}
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
            <div><i class="bi bi-exclamation-triangle"></i> Dokumen direvisi oleh <strong><?= htmlspecialchars($revisedBy) ?></strong></div>
            <?php if ($canEdit): ?>
                <div class="mt-2">
                    <strong>Petunjuk:</strong>
                    <ol class="mb-0">
                        <li>Periksa catatan revisi dari <?= htmlspecialchars($revisedBy) ?></li>
                        <li>Lakukan perbaikan yang diperlukan</li>
                        <li>Klik "Approve" untuk melanjutkan ke tahap berikutnya</li>
                    </ol>
                </div>
            <?php else: ?>
                <div>Menunggu revisi dari <strong><?= htmlspecialchars($current) ?></strong></div>
            <?php endif; ?>
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
                <th>No</th>
                <th>STO</th>
                <th>Tanggal</th>
                <th class="text-end">Normal</th>
                <th class="text-end">Lembur</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($lines as $i => $ln): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($ln['nomor_sto']) ?></td>
                    <td><?= htmlspecialchars($ln['tanggal_terbit']) ?></td>
                    <td class="text-end"><?= number_format($ln['tonase_normal']) ?></td>
                    <td class="text-end"><?= number_format($ln['tonase_lembur']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ✅ SECTION CATATAN DARI SETIAP ROLE -->
    <div class="mb-4">
        <h6 class="mb-3">Catatan dari Setiap Role</h6>
        
        <div class="row">
            <!-- Catatan Admin Wilayah -->
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="bi bi-person-badge"></i> Admin Wilayah
                        </h6>
                        <p class="card-text small mb-0">
                            <?= !empty($inv['note_admin_wilayah']) 
                                ? nl2br(htmlspecialchars($inv['note_admin_wilayah'])) 
                                : '<em class="text-muted">Belum ada catatan</em>' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Catatan Perwakilan PI -->
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="bi bi-person-check"></i> Perwakilan PI
                        </h6>
                        <p class="card-text small mb-0">
                            <?= !empty($inv['note_perwakilan_pi']) 
                                ? nl2br(htmlspecialchars($inv['note_perwakilan_pi'])) 
                                : '<em class="text-muted">Belum ada catatan</em>' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Catatan Admin PCS -->
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="bi bi-clipboard-check"></i> Admin PCS
                        </h6>
                        <p class="card-text small mb-0">
                            <?= !empty($inv['note_admin_pcs']) 
                                ? nl2br(htmlspecialchars($inv['note_admin_pcs'])) 
                                : '<em class="text-muted">Belum ada catatan</em>' ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Catatan Keuangan -->
            <div class="col-md-6 mb-3">
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="bi bi-cash-stack"></i> Keuangan
                        </h6>
                        <p class="card-text small mb-0">
                            <?= !empty($inv['note_keuangan']) 
                                ? nl2br(htmlspecialchars($inv['note_keuangan'])) 
                                : '<em class="text-muted">Belum ada catatan</em>' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($canDecide || $canEdit): ?>
        <div class="mb-4 mt-3">
            <!-- Form fields section -->
            <?php if ($role === 'ADMIN_PCS'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor MMJ</label>
                        <input type="text" id="no_mmj" class="form-control"
                            value="<?= htmlspecialchars($inv['no_mmj'] ?? '') ?>"
                            <?= (!$canEdit && !$canDecide && !empty($inv['no_mmj'])) ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor SOJ</label>
                        <input type="text" id="no_soj" class="form-control"
                            value="<?= htmlspecialchars($inv['no_soj'] ?? '') ?>"
                            <?= (!$canEdit && !$canDecide && !empty($inv['no_soj'])) ? 'disabled' : '' ?>>
                    </div>
                </div>

            <?php endif; ?>

            <!-- ✅ INPUT CATATAN SESUAI ROLE -->
            <div class="mb-3">
                <?php if ($role === 'ADMIN_WILAYAH'): ?>
                    <label class="form-label fw-bold">
                        Catatan Admin Wilayah
                        <?php if ($canEdit): ?>
                            <small class="text-muted">(Revisi)</small>
                        <?php endif; ?>
                    </label>
                    <textarea id="note_role" class="form-control" rows="3" 
                        placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"
                    ><?= htmlspecialchars($inv['note_admin_wilayah'] ?? '') ?></textarea>

                <?php elseif ($role === 'PERWAKILAN_PI'): ?>
                    <label class="form-label fw-bold">
                        Catatan Perwakilan PI
                        <?php if ($canEdit): ?>
                            <small class="text-muted">(Revisi)</small>
                        <?php endif; ?>
                    </label>
                    <textarea id="note_role" class="form-control" rows="3" 
                        placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"
                    ><?= htmlspecialchars($inv['note_perwakilan_pi'] ?? '') ?></textarea>
                
                <?php elseif ($role === 'ADMIN_PCS'): ?>
                    <label class="form-label fw-bold">
                        Catatan Admin PCS
                        <?php if ($canEdit): ?>
                            <small class="text-muted">(Revisi)</small>
                        <?php endif; ?>
                    </label>
                    <textarea id="note_role" class="form-control" rows="3" 
                        placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"
                    ><?= htmlspecialchars($inv['note_admin_pcs'] ?? '') ?></textarea>
                
                <?php elseif ($role === 'KEUANGAN'): ?>
                    <label class="form-label fw-bold">
                        Catatan Keuangan
                        <?php if ($canEdit): ?>
                            <small class="text-muted">(Revisi)</small>
                        <?php endif; ?>
                     </label>
                    <textarea id="note_role" class="form-control" rows="3" 
                        placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"
                    ><?= htmlspecialchars($inv['note_keuangan'] ?? '') ?></textarea>
                <?php endif; ?>
            </div>

            <!-- Action buttons - tampilkan sesuai kondisi -->
            <div class="text-end">
                <?php if ($canDecide): ?>
                    <button class="btn btn-success btn-decision" data-decision="approve" data-id="<?= (int)$inv['id'] ?>"
                        data-role="<?= htmlspecialchars($role) ?>">
                        <i class="bi bi-check-circle"></i> 
                        <?= $isRevision && $current === $role ? 'Approve Revisi' : 'Approve' ?>
                    </button>
                    <?php if ($role !== 'ADMIN_WILAYAH'): ?> 
                        <button class="btn btn-danger btn-decision" data-decision="reject" data-id="<?= (int)$inv['id'] ?>"
                            data-role="<?= htmlspecialchars($role) ?>">
                            <i class="bi bi-x-circle"></i> Reject
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Riwayat -->
    <?php if ($logs): ?>
        <h6 class="mt-4">Riwayat Keputusan</h6>
        <ul class="list-group">
            <?php foreach ($logs as $log): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>
                        <strong><?= htmlspecialchars($log['role']) ?></strong>
                        &mdash; 
                        <?php if ($log['decision'] === 'APPROVED'): ?>
                            <span class="badge bg-success">Approved</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Rejected</span>
                        <?php endif; ?>
                    </span>
                    <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn-decision');
    
    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const decision = this.getAttribute('data-decision');
            const id = this.getAttribute('data-id');
            const role = this.getAttribute('data-role');
            
            // Ambil nilai input sesuai role
            let formData = new FormData();
            formData.append('invoice_id', id);
            formData.append('decision', decision);
            
            // Ambil nomor MMJ & SOJ jika role ADMIN_PCS
            if (role === 'ADMIN_PCS') {
                const no_mmj = document.getElementById('no_mmj')?.value || '';
                const no_soj = document.getElementById('no_soj')?.value || '';
                formData.append('no_mmj', no_mmj);
                formData.append('no_soj', no_soj);
            }
            
            // Ambil catatan sesuai role
            const noteField = document.getElementById('note_role');
            if (noteField) {
                const noteValue = noteField.value.trim();
                if (role === 'ADMIN_WILAYAH') {
                    formData.append('note_admin_wilayah', noteValue);
                } else if (role === 'PERWAKILAN_PI') {
                    formData.append('note_perwakilan_pi', noteValue);
                } else if (role === 'ADMIN_PCS') {
                    formData.append('note_admin_pcs', noteValue);
                } else if (role === 'KEUANGAN') {
                    formData.append('note_keuangan', noteValue);
                }
            }
            
            // Kirim ke server
            fetch('index.php?page=scan&action=decide', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Keputusan berhasil disimpan!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(err => {
                alert('Network error: ' + err.message);
            });
        });
    });
});
</script>