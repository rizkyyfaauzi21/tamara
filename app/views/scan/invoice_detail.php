<?php
// app/views/scan/invoice_detail.php
?>
<style>
    .uploader {
        border: 2px dashed #cfe3ff;
        border-radius: 12px;
        background: #f7fbff;
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
    }

    .uploader:hover {
        background: #f1f8ff;
        border-color: #4299e1;
    }

    .uploader .cloud {
        font-size: 40px;
        line-height: 1;
        color: #a0aec0;
    }

    .uploader .cta {
        color: #1976d2;
        font-weight: 600;
    }

    .upload-label-overlay {
        position: absolute;
        inset: 0;
        cursor: pointer;
    }

    .file-pill {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .6rem;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #edf2f7;
    }

    .file-badge {
        font-weight: 700;
        font-size: .75rem;
        padding: .15rem .45rem;
        border-radius: 6px;
        background: #e8f1ff;
        color: #0b5ed7;
        text-transform: uppercase;
    }

    .file-remove {
        border: none;
        background: #f8d7da;
        color: #a61b2b;
        width: 26px;
        height: 26px;
        border-radius: 50%;
        font-weight: 700;
        line-height: 1;
        margin-left: auto;
        cursor: pointer;
        transition: background 0.2s;
    }

    .file-remove:hover {
        filter: brightness(.95);
        background: #fc8181;
    }

    .file-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: .5rem;
    }
</style>
<?php
// tersedia dari controller: $inv, $lines, $logs, $flow, $role, $userIdView
$approved = array_column(array_filter($logs, fn($l) => $l['decision'] === 'APPROVED'), 'role');
$rejected = array_column(array_filter($logs, fn($l) => $l['decision'] === 'REJECTED'), 'role');
$lastLog = $logs ? end($logs) : null;
$lastDecision = $lastLog['decision'] ?? null;
$lastRole     = $lastLog['role'] ?? null;
$current = $inv['current_role'];

// ✅ Cek apakah Admin PCS sudah approve
$adminPcsApproved = in_array('ADMIN_PCS', $approved, true);

// ✅ Cek apakah sedang dalam status REACTIVE atau CLOSE
$isReactive = ($lastDecision === 'REACTIVE' && $current === 'KEUANGAN');
$isClosed = ($lastDecision === 'CLOSE' && $current === 'KEUANGAN');

$isRevision = !empty($inv['is_revised']) || ($lastDecision === 'REJECTED');
$revisedBy = $inv['revised_by'] ?? $lastRole ?? null;
$canEdit = $isRevision && ($current === $role);

// Reset status hasDecided jika ada revisi atau reactive
$hasDecided = false;
if ($logs && !$isReactive) {
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
        if (
            (int)$logs[$i]['created_by'] === (int)($userIdView ?? 0) &&
            $logs[$i]['role'] === $role &&
            !in_array($logs[$i]['decision'], ['REACTIVE', 'CLOSE'])
        ) {
            $hasDecided = true;
            break;
        }
    }
}

// Role bisa decide jika:
// 1. Ini giliran mereka (current === role) dan belum decide di siklus ini
// 2. Atau jika sedang dalam status REACTIVE (KEUANGAN bisa decide lagi)
$canDecide = ($current === $role && !$hasDecided) || $isReactive;

// ✅ Status pesan untuk user
if ($isClosed && !$isReactive && $role === 'KEUANGAN') {
    echo '<div class="alert alert-success mb-3">
        <i class="bi bi-check-circle"></i> <strong>Invoice sudah ditutup (CLOSE).</strong> 
        Anda dapat mengaktifkan kembali jika perlu revisi.
    </div>';
} elseif ($isReactive && $role === 'KEUANGAN') {
    echo '<div class="alert alert-warning mb-3">
        <i class="bi bi-arrow-clockwise"></i> <strong>Invoice dalam status REACTIVE.</strong> 
        Anda dapat melakukan revisi (reject) atau menutup (close) invoice ini.
    </div>';
} elseif ($hasDecided && !$isReactive) {
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
            <?php if ($isReactive && $r === 'KEUANGAN'): ?>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-arrow-clockwise"></i> <?= htmlspecialchars($r) ?> (REACTIVE)
                </span>
            <?php elseif ($isClosed && $r === 'KEUANGAN'): ?>
                <span class="badge bg-success">
                    <i class="bi bi-check-circle"></i> <?= htmlspecialchars($r) ?> (CLOSED)
                </span>
            <?php elseif ($isRevision): ?>
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
            <div><i class="bi bi-exclamation-triangle"></i> Dokumen direvisi oleh
                <strong><?= htmlspecialchars($revisedBy) ?></strong>
            </div>
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

    <!-- ✅ SECTION FILE LAMPIRAN -->
    <?php if (!empty($invoiceFiles ?? [])): ?>
        <div class="mb-4">
            <h6 class="mb-3">File Lampiran</h6>
            <div class="list-group">
                <?php foreach ($invoiceFiles as $file): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-file-earmark"></i>
                            <span><?= htmlspecialchars($file['filename']) ?></span>
                            <small class="text-muted">
                                (<?= number_format($file['size_bytes'] / 1024, 2) ?> KB)
                            </small>
                        </div>
                        <?php
                        $baseUrl = rtrim($uploadUrl ?? 'uploads/invoice/', '/') . '/';
                        $fileUrl = htmlspecialchars($baseUrl . $file['stored_name']);
                        ?>
                        <div class="">

                            <a href="download/download.php?file=<?= urlencode($file['stored_name']) ?>"
                                class="btn btn-sm btn-outline-success">
                                <i class="bi bi-download"></i> Download
                            </a>


                            <a href="<?= $fileUrl ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i> Lihat
                            </a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ✅ FORM INPUT & TOMBOL ACTION -->
    <?php if ($role === 'KEUANGAN'): ?>

        <?php if ($isClosed && !$isReactive): ?>
            <!-- ✅ Invoice sudah CLOSE: tampilkan tombol REACTIVE -->
            <div class="mb-4 mt-3">
                <button class="btn btn-warning"
                    id="btnReactive"
                    data-id="<?= (int)$inv['id'] ?>">
                    <i class="bi bi-arrow-clockwise"></i> Aktifkan Kembali (Reactive)
                </button>
            </div>

        <?php elseif ($current === 'KEUANGAN' && !$hasDecided): ?>
            <!-- ✅ KEUANGAN sedang giliran: tampilkan form -->
            <div class="mb-4 mt-3">
                <!-- ✅ SOJ dan MMJ hanya tampil jika Admin PCS sudah approve -->
                <?php if ($adminPcsApproved): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nomor MMJ</label>
                            <input type="text" id="no_mmj" class="form-control"
                                value="<?= htmlspecialchars($inv['no_mmj'] ?? '') ?>" disabled>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor SOJ</label>
                            <input type="text" id="no_soj" class="form-control"
                                value="<?= htmlspecialchars($inv['no_soj'] ?? '') ?>" disabled>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-3">
                        <i class="bi bi-info-circle"></i> Nomor SOJ dan MMJ akan ditampilkan setelah Admin PCS melakukan approve.
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">
                        Catatan Keuangan
                        <?php if ($isReactive || $isRevision): ?>
                            <small class="text-warning">(Revisi - Wajib diisi jika reject)</small>
                        <?php endif; ?>
                    </label>
                    <textarea id="note_role" class="form-control" rows="3"
                        placeholder="<?= ($isReactive || $isRevision) ? 'Jelaskan alasan revisi jika akan reject...' : 'Masukkan catatan Anda...' ?>"><?= htmlspecialchars($inv['note_keuangan'] ?? '') ?></textarea>
                    <?php if ($isReactive || $isRevision): ?>
                        <small class="form-text text-muted">
                            <i class="bi bi-info-circle"></i> Catatan ini akan dikirim ke ADMIN PCS jika Anda klik REJECT.
                        </small>
                    <?php endif; ?>
                </div>

                <div class="text-end">
                    <button class="btn btn-danger btn-decision"
                        data-decision="reject"
                        data-id="<?= (int)$inv['id'] ?>"
                        data-role="KEUANGAN">
                        <i class="bi bi-x-circle"></i> Reject (Turun ke Admin PCS)
                    </button>

                    <button class="btn btn-success"
                        id="btnClose"
                        data-id="<?= (int)$inv['id'] ?>">
                        <i class="bi bi-check-circle"></i> Close (Selesai)
                    </button>
                </div>
            </div>

        <?php else: ?>
            <!-- Kondisi: KEUANGAN belum giliran atau sudah memberikan keputusan -->
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                <?php if ($hasDecided): ?>
                    Anda sudah memberikan keputusan untuk siklus ini.
                <?php else: ?>
                    Invoice sedang diproses oleh: <strong><?= htmlspecialchars($current) ?></strong>
                <?php endif; ?>
            </div>
        <?php endif; ?>



    <?php elseif ($canDecide || $canEdit || ($role === 'ADMIN_PCS' && $adminPcsApproved)): ?>

        <!-- Form untuk role lain (ADMIN_WILAYAH, PERWAKILAN_PI, ADMIN_PCS) -->
        <div class="mb-4 mt-3">
            <!-- Form fields section -->
            <?php if ($role === 'ADMIN_PCS'): ?>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor MMJ</label>
                        <input type="text" id="no_mmj" class="form-control"
                            value="<?= htmlspecialchars($inv['no_mmj'] ?? '') ?>"
                            <?= (!$canEdit && !$canDecide) ? 'disabled' : '' ?>>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nomor SOJ</label>
                        <input type="text" id="no_soj" class="form-control"
                            value="<?= htmlspecialchars($inv['no_soj'] ?? '') ?>"
                            <?= (!$canEdit && !$canDecide) ? 'disabled' : '' ?>>
                    </div>
                </div>

                <!-- Upload Multi File (ADMIN_PCS) — SELALU RENDER untuk ADMIN_PCS -->
                <?php if ($canDecide): ?>
                    <div class="col-12 mb-3">
                        <label class="form-label">Lampiran (boleh banyak)</label>

                        <div id="dz-create" class="uploader p-4 text-center mb-2" role="button" tabindex="0"
                            aria-label="Unggah lampiran">
                            <div class="cloud mb-2">☁⬆</div>
                            <div class="cta">Click To Upload</div>
                            <small class="text-muted d-block mt-1">
                                atau drag & drop file ke sini • Maks 10MB/file • pdf, jpg, png, xls, xlsx
                            </small>
                            <!-- <label for="files-create" class="upload-label-overlay" aria-hidden="true"></label> -->
                            <div class="upload-label-overlay"></div>

                        </div>

                        <input id="files-create" type="file" name="files[]" class="d-none" multiple
                            accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx">

                        <ul id="list-create" class="file-list"></ul>
                    <?php endif; ?>

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
                            placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"><?= htmlspecialchars($inv['note_admin_wilayah'] ?? '') ?></textarea>

                    <?php elseif ($role === 'PERWAKILAN_PI'): ?>
                        <label class="form-label fw-bold">
                            Catatan Perwakilan PI
                            <?php if ($canEdit): ?>
                                <small class="text-muted">(Revisi)</small>
                            <?php endif; ?>
                        </label>
                        <textarea id="note_role" class="form-control" rows="3"
                            placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"><?= htmlspecialchars($inv['note_perwakilan_pi'] ?? '') ?></textarea>

                    <?php elseif ($role === 'ADMIN_PCS'): ?>
                        <label class="form-label fw-bold">
                            Catatan Admin PCS
                            <?php if ($canEdit): ?>
                                <small class="text-muted">(Revisi)</small>
                            <?php endif; ?>
                        </label>
                        <textarea id="note_role" class="form-control" rows="3"
                            placeholder="<?= $canEdit ? 'Tambahkan catatan revisi Anda...' : 'Masukkan catatan Anda...' ?>"><?= htmlspecialchars($inv['note_admin_pcs'] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>

                <!-- Action buttons -->
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
                                    <?php elseif ($log['decision'] === 'REJECTED'): ?>
                                        <span class="badge bg-danger">Rejected</span>
                                    <?php elseif ($log['decision'] === 'CLOSE'): ?>
                                        <span class="badge bg-primary">Closed</span>
                                    <?php elseif ($log['decision'] === 'REACTIVE'): ?>
                                        <span class="badge bg-warning">Reactivated</span>
                                    <?php endif; ?>
                                </span>
                                <small class="text-muted"><?= htmlspecialchars($log['created_at']) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
        </div>