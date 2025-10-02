<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Master Gudang</h2>
        <!-- Tombol Back ke Dashboard -->
        <a href="index.php?page=dashboard" class="btn btn-secondary">Back</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- ===================================== -->
    <!-- 1. KELOLA NAMA GUDANG -->
    <!-- ===================================== -->
    <h4>Kelola Nama Gudang</h4>
    <form method="POST" class="row g-3 mb-4" action="index.php?page=gudang&action=nama">
        <input type="hidden" name="id" id="edit-nama-id">

        <!-- Nama Gudang -->
        <div class="col-md-4">
            <input type="text" name="nama_gudang" id="edit-nama-gudang" class="form-control" placeholder="Nama Gudang" required>
        </div>

        <!-- Tombol Simpan -->
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Simpan</button>
        </div>
    </form>

    <!-- Tabel Nama Gudang -->
    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Gudang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($gudangList) && is_array($gudangList)): ?>
                <?php foreach ($gudangList as $i => $g): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($g['nama_gudang']) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="editNamaGudang(<?= $g['id'] ?>,'<?= htmlspecialchars($g['nama_gudang']) ?>')">Edit</button>
                            <a href="index.php?page=gudang&action=nama&delete=<?= $g['id'] ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Hapus nama gudang ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Belum ada data gudang</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- ===================================== -->
    <!-- 2. KELOLA TARIF GUDANG -->
    <!-- ===================================== -->
    <h4>Kelola Tarif Gudang</h4>
    <form method="POST" class="row g-3 mb-4" action="index.php?page=gudang&action=tarif">
        <input type="hidden" name="id" id="edit-tarif-id">

        <!-- Pilih Gudang -->
        <div class="col-md-3">
            <select name="gudang_id" id="edit-tarif-gudang" class="form-control" required>
                <option value="">-- Pilih Gudang --</option>
                <?php if (!empty($gudangList) && is_array($gudangList)): ?>
                    <?php foreach ($gudangList as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Jenis Transaksi -->
        <div class="col-md-2">
            <select name="jenis_transaksi" id="edit-jenis" class="form-control" required>
                <option value="BONGKAR">Bongkar</option>
                <option value="MUAT">Muat</option>
            </select>
        </div>

        <!-- Tarif Normal -->
        <div class="col-md-2">
            <input type="number" step="0.01" name="tarif_normal" id="edit-normal" class="form-control" placeholder="Tarif Normal" required>
        </div>

        <!-- Tarif Lembur -->
        <div class="col-md-2">
            <input type="number" step="0.01" name="tarif_lembur" id="edit-lembur" class="form-control" placeholder="Tarif Lembur" required>
        </div>

        <!-- Tombol Simpan -->
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100">Simpan</button>
        </div>
    </form>

    <!-- Tabel Tarif Gudang -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama Gudang</th>
                <th>Jenis Transaksi</th>
                <th>Tarif Normal</th>
                <th>Tarif Lembur</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($tarifList) && is_array($tarifList)): ?>
                <?php foreach ($tarifList as $i => $t): ?>
                    <tr>
                        <td><?= $i+1 ?></td>
                        <td><?= htmlspecialchars($t['nama_gudang']) ?></td>
                        <td><?= htmlspecialchars($t['jenis_transaksi']) ?></td>
                        <td><?= number_format($t['tarif_normal'], 2) ?></td>
                        <td><?= number_format($t['tarif_lembur'], 2) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="editTarif(<?= $t['id'] ?>, <?= $t['gudang_id'] ?>, '<?= $t['jenis_transaksi'] ?>', <?= $t['tarif_normal'] ?>, <?= $t['tarif_lembur'] ?>)">Edit</button>
                            <a href="index.php?page=gudang&action=tarif&delete=<?= $t['id'] ?>" 
                               class="btn btn-sm btn-danger" onclick="return confirm('Hapus tarif ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">Belum ada tarif gudang</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function editNamaGudang(id, nama) {
    document.getElementById('edit-nama-id').value = id;
    document.getElementById('edit-nama-gudang').value = nama;
}

function editTarif(id, gudang_id, jenis, normal, lembur) {
    document.getElementById('edit-tarif-id').value = id;
    document.getElementById('edit-tarif-gudang').value = gudang_id;
    document.getElementById('edit-jenis').value = jenis;
    document.getElementById('edit-normal').value = normal;
    document.getElementById('edit-lembur').value = lembur;
}
</script>
