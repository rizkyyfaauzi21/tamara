<?php
// app/views/scan/invoice_detail.php
?>
<!-- ================= CSS kecil untuk uploader ================= -->
<style>
.uploader {
    border: 2px dashed #cfe3ff;
    border-radius: 12px;
    background: #f7fbff;
    cursor: pointer;
}

.uploader:hover {
    background: #f1f8ff;
}

.uploader .cloud {
    font-size: 40px;
    line-height: 1;
}

.uploader .cta {
    color: #1976d2;
    font-weight: 600;
}

.file-pill {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .6rem;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
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
}

.file-remove:hover {
    filter: brightness(.95);
}

.file-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: .5rem;
}

/* Label overlay yang menutupi seluruh area dropzone */
.upload-label-overlay {
    position: absolute;
    inset: 0;
    /* top:0; right:0; bottom:0; left:0 */
    cursor: pointer;
    /* tidak perlu opacity: 0; karena label tidak punya tampilan visual */
}
</style>
<?php

// ✅ Bagian 1: Tangani AJAX POST dari tombol Approve / Reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $invoice_id = $_POST['invoice_id'] ?? null;
    $decision   = $_POST['decision'] ?? null;
    $no_mmj     = trim($_POST['no_mmj'] ?? '');
    $no_soj     = trim($_POST['no_soj'] ?? '');
    $note_admin_wilayah = trim($_POST['note_admin_wilayah'] ?? '');
    $note_perwakilan_pi = trim($_POST['note_perwakilan_pi'] ?? '');
    $note_admin_pcs = trim($_POST['note_admin_pcs'] ?? '');
    $note_keuangan = trim($_POST['note_keuangan'] ?? '');
    $role       = 'ADMIN_PCS'; // nanti bisa diganti $_SESSION['role'] kalau sudah login

    if (!$invoice_id || !$decision) {
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
$approved = array_column(array_filter($logs, fn($l) => $l['decision'] === 'APPROVED'), 'role');
$rejected = array_column(array_filter($logs, fn($l) => $l['decision'] === 'REJECTED'), 'role');
$lastLog = $logs ? end($logs) : null;
$lastDecision = $lastLog['decision'] ?? null;
$lastRole     = $lastLog['role'] ?? null;
$current = $inv['current_role'];
$isRevision = ($lastDecision === 'REJECTED');

$hasDecided = false;
if ($logs) {
    for ($i = count($logs) - 1; $i >= 0; $i--) {
        // Jika user saat ini sudah pernah memutuskan
        if ((int)$logs[$i]['created_by'] === (int)($userIdView ?? 0) && $logs[$i]['role'] === $role) {
            $hasDecided = true;
            echo '<p class="text-warning">Anda sudah memberikan keputusan untuk siklus ini.</p>';
            break;
        }

        // Kalau log ini masih "menunggu keputusan" dari role tertentu
        if ($logs[$i]['decision'] === 'pending' || $logs[$i]['decision'] === null) {
            $current = $logs[$i]['role'];
            break;
        }
    }
}

if (!empty($current)) {
    echo '<p class="text-muted">Menunggu keputusan <strong>' . htmlspecialchars($current) . '</strong>.</p>';
} else {
    echo '<p class="text-success"> <strong>PROSES SELESAI.</strong></p>';
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

    <?php if (($canDecide && $current !== null) || $role === 'ADMIN_PCS' || $role === 'KEUANGAN'): ?>
    <div class="mb-4 mt-3">
        <?php if ($role === 'ADMIN_PCS'): ?>
        <!-- Hanya ADMIN_PCS yang bisa input nomor -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Nomor MMJ</label>
                <input type="text" id="no_mmj" class="form-control"
                    value="<?= htmlspecialchars($inv['no_mmj'] ?? '') ?>"
                    <?= !empty($inv['no_mmj']) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nomor SOJ</label>
                <input type="text" id="no_soj" class="form-control"
                    value="<?= htmlspecialchars($inv['no_soj'] ?? '') ?>"
                    <?= !empty($inv['no_soj']) ? 'disabled' : '' ?>>
            </div>
        </div>

        <!-- =========== Upload Multi File (baru) =========== -->
        <div class="col-12">
            <label class="form-label">Lampiran (boleh banyak)</label>

            <div id="dz-create" class="uploader p-4 text-center mb-2" role="button" tabindex="0"
                aria-label="Unggah lampiran">
                <div class="cloud mb-2">☁⬆</div>
                <div class="cta">Click To Upload</div>
                <small class="text-muted d-block mt-1">
                    atau drag & drop file ke sini • Maks 10MB/file • pdf, jpg, png, xls, xlsx
                </small>

                <!-- Label overlay: klik ke mana pun di dropzone akan memicu input di bawah -->
                <label for="files-create" class="upload-label-overlay" aria-hidden="true"></label>
            </div>

            <!-- Tetap d-none sesuai permintaan -->
            <input id="files-create" type="file" name="files[]" class="d-none" multiple
                accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx">

            <ul id="list-create" class="file-list"></ul>
        </div>

        <?php elseif ($role === 'KEUANGAN'): ?>
        <!-- KEUANGAN hanya lihat (readonly) -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label class="form-label">Nomor MMJ</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($inv['no_mmj'] ?? '-') ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nomor SOJ</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($inv['no_soj'] ?? '-') ?>" disabled>
            </div>
        </div>
        <?php endif; ?>

        <!-- ✅ INPUT CATATAN SESUAI ROLE -->
        <?php if ($canDecide): ?>
        <div class="mb-3">
            <?php if ($role === 'ADMIN_WILAYAH'): ?>
            <label class="form-label fw-bold">Catatan Admin Wilayah</label>
            <textarea id="note_role" class="form-control" rows="3"
                placeholder="Masukkan catatan Anda sebagai Admin Wilayah..."><?= htmlspecialchars($inv['note_admin_wilayah'] ?? '') ?></textarea>

            <?php elseif ($role === 'PERWAKILAN_PI'): ?>
            <label class="form-label fw-bold">Catatan Perwakilan PI</label>
            <textarea id="note_role" class="form-control" rows="3"
                placeholder="Masukkan catatan Anda sebagai Perwakilan PI..."><?= htmlspecialchars($inv['note_perwakilan_pi'] ?? '') ?></textarea>

            <?php elseif ($role === 'ADMIN_PCS'): ?>
            <label class="form-label fw-bold">Catatan Admin PCS</label>
            <textarea id="note_role" class="form-control" rows="3"
                placeholder="Masukkan catatan Anda sebagai Admin PCS..."><?= htmlspecialchars($inv['note_admin_pcs'] ?? '') ?></textarea>

            <?php elseif ($role === 'KEUANGAN'): ?>
            <label class="form-label fw-bold">Catatan Keuangan</label>
            <textarea id="note_role" class="form-control" rows="3"
                placeholder="Masukkan catatan Anda sebagai Keuangan..."><?= htmlspecialchars($inv['note_keuangan'] ?? '') ?></textarea>
            <?php endif; ?>
        </div>

        <div class="text-end">
            <button class="btn btn-success btn-decision" data-decision="approve" data-id="<?= (int)$inv['id'] ?>"
                data-role="<?= htmlspecialchars($role) ?>">
                <i class="bi bi-check-circle"></i> Approve
            </button>
            <button class="btn btn-danger btn-decision" data-decision="reject" data-id="<?= (int)$inv['id'] ?>"
                data-role="<?= htmlspecialchars($role) ?>">
                <i class="bi bi-x-circle"></i> Reject
            </button>
        </div>
        <?php endif; ?>
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

<!-- ================= SCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {

    // ---------------- Kirim keputusan (Approve/Reject) ----------------
    const buttons = document.querySelectorAll('.btn-decision');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            const decision = this.getAttribute('data-decision');
            const id = this.getAttribute('data-id');
            const role = this.getAttribute('data-role');

            const formData = new FormData();
            formData.append('invoice_id', id);
            formData.append('decision', decision);

            if (role === 'ADMIN_PCS') {
                const no_mmj = document.getElementById('no_mmj')?.value || '';
                const no_soj = document.getElementById('no_soj')?.value || '';
                formData.append('no_mmj', no_mmj);
                formData.append('no_soj', no_soj);
            }

            const noteField = document.getElementById('note_role');
            if (noteField) {
                const noteValue = noteField.value.trim();
                if (role === 'ADMIN_WILAYAH') formData.append('note_admin_wilayah', noteValue);
                else if (role === 'PERWAKILAN_PI') formData.append('note_perwakilan_pi',
                    noteValue);
                else if (role === 'ADMIN_PCS') formData.append('note_admin_pcs', noteValue);
                else if (role === 'KEUANGAN') formData.append('note_keuangan', noteValue);
            }

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

    // ---------------- Widget multi uploader (drop zone + list) ----------------
    function initMultiUploader(zoneId, inputId, listId, options = {}) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);

        // null-guard: aman jika uploader tidak dirender (tergantung role)
        if (!zone || !input || !list) return;

        const MAX_BYTES = (options.maxMB || 10) * 1024 * 1024;
        const ALLOWED = (options.allowed || ['pdf', 'png', 'jpg', 'jpeg', 'xls', 'xlsx']).map(x => x
            .toLowerCase());

        const dt = new DataTransfer();

        const extOf = (name) => (name.split('.').pop() || '').toLowerCase();
        const labelOf = (name) => {
            const ext = extOf(name);
            if (ext === 'pdf') return 'Pdf';
            if (ext === 'doc' || ext === 'docx') return 'Docx';
            if (ext === 'xls' || ext === 'xlsx') return 'Xls';
            if (ext === 'jpg' || ext === 'jpeg') return 'Jpg';
            if (ext === 'png') return 'Png';
            return ext || 'File';
        };

        function renderList() {
            list.innerHTML = '';
            Array.from(dt.files).forEach((f, idx) => {
                const li = document.createElement('li');
                li.className = 'file-pill';
                li.innerHTML = `
                    <span class="file-badge">${labelOf(f.name)}</span>
                    <span class="flex-grow-1 text-truncate">${f.name}</span>
                    <button type="button" class="file-remove" title="Hapus">&times;</button>
                `;
                li.querySelector('.file-remove').addEventListener('click', () => {
                    const newDt = new DataTransfer();
                    Array.from(dt.files).forEach((ff, i) => {
                        if (i !== idx) newDt.items.add(ff);
                    });
                    input.files = newDt.files;
                    dt.items.clear();
                    Array.from(newDt.files).forEach(ff => dt.items.add(ff));
                    renderList();
                });
                list.appendChild(li);
            });
        }

        function acceptFiles(files) {
            Array.from(files).forEach(f => {
                const ext = extOf(f.name);
                if (!ALLOWED.includes(ext)) {
                    console.warn('Tipe tidak diizinkan:', f.name);
                    return;
                }
                if (f.size > MAX_BYTES) {
                    console.warn('Kebesaran:', f.name);
                    return;
                }
                dt.items.add(f);
            });
            input.files = dt.files;
            renderList();
        }

        // tidak memakai input.click() karena input menjadi overlay transparan
        input.addEventListener('change', (e) => acceptFiles(e.target.files));

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev =>
            zone.addEventListener(ev, e => {
                e.preventDefault();
                e.stopPropagation();
            })
        );
        zone.addEventListener('drop', e => acceptFiles(e.dataTransfer.files));

        // aksesibilitas (enter/space fokus pada zone akan memfokuskan input)
        zone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                input.focus(); // fokuskan ke input (meski transparan)
            }
        });
    }

    // inisialisasi uploader: aman meski tidak dirender (null-guard)
    initMultiUploader('dz-create', 'files-create', 'list-create', {
        maxMB: 10
    });

    // ---------------- Placeholder: tombol "Daftar STO" (opsional) ----------------
    // document.getElementById('btn-daftar-sto')?.addEventListener('click', () => {
    //     // Implementasi sesuai kebutuhan (submit form lain, dsb.)
    //     alert('Tombol "Daftar STO" ditekan (placeholder).');
    // });
});
</script>