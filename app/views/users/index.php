<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="align-items-center w-100">
            <div class="mb-2">
                <h2>Master User</h2>
            </div>
            <!-- Tombol Tambah User -->
            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#tambahUserModal">
                    Tambah User
                </button>
            </div>

        </div>

        <!-- Tombol Back ke Dashboard -->
        <a href="index.php?page=dashboard" class="btn btn-secondary">Back</a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success'];
                                            unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error'];
                                        unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <h4>Role: Admin Gudang</h4>

    <!-- Tabel admin_ gudang -->
    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
                <th>Gudang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($admin_gudangList) && is_array($admin_gudangList)): ?>
                <?php foreach ($admin_gudangList as $i => $g): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($g['nama']) ?></td>
                        <td><?= htmlspecialchars($g['username']) ?></td>
                        <td><?= htmlspecialchars($g['role']) ?></td>
                        <td><?= htmlspecialchars($g['nama_gudang']) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="openEditModal(
                                <?= $g['id'] ?>,
                                '<?= htmlspecialchars($g['nama']) ?>',
                                '<?= htmlspecialchars($g['username']) ?>',
                                '<?= htmlspecialchars($g['role']) ?>',
                                '<?= htmlspecialchars($g['id_gudang'] ?? '') ?>',
                                ''
                            )">
                                Edit
                            </button>
                            <a href="index.php?page=users&action=deleteUser&delete=<?= $g['id'] ?>"
                                class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Belum ada data admin gudang</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>


    <h4>Role: Kepala Gudang</h4>

    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
                <th>Gudang</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($kepala_gudangList) && is_array($kepala_gudangList)): ?>
                <?php foreach ($kepala_gudangList as $i => $g): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($g['nama']) ?></td>
                        <td><?= htmlspecialchars($g['username']) ?></td>
                        <td><?= htmlspecialchars($g['role']) ?></td>
                        <td><?= htmlspecialchars($g['nama_gudang']) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-warning"
                                onclick="openEditModal(
                                <?= $g['id'] ?>,
                                '<?= htmlspecialchars($g['nama']) ?>',
                                '<?= htmlspecialchars($g['username']) ?>',
                                '<?= htmlspecialchars($g['role']) ?>',
                                '<?= htmlspecialchars($g['id_gudang'] ?? '') ?>',
                                ''
                            )">
                                Edit
                            </button>

                            <a href="index.php?page=users&action=deleteUser&delete=<?= $g['id'] ?>"
                                class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Belum ada data kepala gudang</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>



    <h4>Role: Lainnya</h4>


    <!-- Tabel role lainnya -->
    <table class="table table-bordered mb-5">
        <thead>
            <tr>
                <th>#</th>
                <th>Nama</th>
                <th>Username</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($admin_wilayahList) && is_array($admin_wilayahList)): ?>
                <?php foreach ($admin_wilayahList as $i => $g): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($g['nama']) ?></td>
                        <td><?= htmlspecialchars($g['username']) ?></td>
                        <td><?= htmlspecialchars($g['role']) ?></td>

                        <td>
                           <button type="button" class="btn btn-sm btn-warning"
                            onclick="openEditModal(
                                <?= $g['id'] ?>,
                                '<?= htmlspecialchars($g['nama']) ?>',
                                '<?= htmlspecialchars($g['username']) ?>',
                                '<?= htmlspecialchars($g['role']) ?>',
                                '',
                                '<?= htmlspecialchars($g['id_wilayah_ditangani'] ?? '') ?>'
                            )">
                            Edit
                           </button>



                            <a href="index.php?page=users&action=deleteUser&delete=<?= $g['id'] ?>"
                                class="btn btn-sm btn-danger" onclick="return confirm('Hapus user ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" class="text-center">Belum ada data admin wilayah</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>


    <!-- Modal Tambah User -->
    <div class="modal fade" id="tambahUserModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="tambahUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="tambahUserModalLabel">Tambah User Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form method="POST" action="index.php?page=users&action=tambah_user">
                    <input type="hidden" name="id" id="user_id">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama" class="form-label">Nama</label>
                                <input type="text" class="form-control" name="nama" id="nama" placeholder="Masukkan Nama" required>
                            </div>

                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" name="username" id="username" placeholder="Masukkan Username" required>
                            </div>

                            <div class="col-md-6">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" name="role" id="role" required>
                                    <option value="">-- Pilih Role --</option>
                                    <option value="ADMIN_GUDANG">Admin Gudang</option>
                                    <option value="KEPALA_GUDANG">Kepala Gudang</option>
                                    <option value="ADMIN_WILAYAH">Admin Wilayah</option>
                                    <option value="PERWAKILAN_PI">Perwakilan PI</option>
                                    <option value="ADMIN_PCS">Admin PCS</option>
                                    <option value="KEUANGAN">Keuangan</option>
                                    <option value="SUPERADMIN">Superadmin</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="id_gudang" class="form-label">Gudang</label>
                                <select class="form-control" name="id_gudang" id="id_gudang">
                                    <option value="">-- Pilih Gudang --</option>
                                    <?php foreach ($gudangList as $g): ?>
                                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="id_wilayah" class="form-label">Wilayah</label>
                                <select class="form-control" name="id_wilayah[]" id="id_wilayah" multiple>
                                    <?php foreach ($wilayahList as $w): ?>
                                        <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['wilayah']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>


                            <div class="col-md-6">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" name="password" id="password" placeholder="Masukkan Password" >
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="tambah_user">Simpan</button>
                    </div>
                </form>

            </div>
        </div>
    </div>


</div>
<script>
   function openEditModal(id, nama, username, role, idGudang = '', idWilayahList = '') {
    document.getElementById('user_id').value = id;
    document.getElementById('nama').value = nama;
    document.getElementById('username').value = username;
    document.getElementById('role').value = role;
    document.getElementById('id_gudang').value = idGudang || '';

    // Reset pilihan wilayah
    const wilayahSelect = document.getElementById('id_wilayah');
    for (let opt of wilayahSelect.options) opt.selected = false;

    // Kalau role = ADMIN_WILAYAH, tandai wilayah yang dimiliki
    if (role === 'ADMIN_WILAYAH' && idWilayahList) {
        idWilayahList.split(',').forEach(id => {
            const option = wilayahSelect.querySelector(`option[value="${id}"]`);
            if (option) option.selected = true;
        });
    }

    // Kosongkan password saat edit
    document.getElementById('password').value = '';

    // 🔥 Tambahkan trigger ini
    document.getElementById('role').dispatchEvent(new Event('change'));

    // Ubah label modal
    document.getElementById('tambahUserModalLabel').textContent = 'Edit User';
    var modal = new bootstrap.Modal(document.getElementById('tambahUserModal'));
    modal.show();
}


</script>



<script>
    const tambahUserModal = document.getElementById('tambahUserModal');
tambahUserModal.addEventListener('hidden.bs.modal', function() {
    const form = tambahUserModal.querySelector('form');
    form.reset();

    document.getElementById('tambahUserModalLabel').textContent = 'Tambah User';
    document.getElementById('user_id').value = '';

    // ✅ Tambahkan reset tampilan field role terkait
    document.getElementById('id_gudang').closest('.col-md-6').style.display = 'none';
    document.getElementById('id_wilayah').closest('.col-md-6').style.display = 'none';

    document.getElementById('id_gudang').required = false;
    document.getElementById('id_wilayah').required = false;
});


</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const gudangField = document.getElementById('id_gudang').closest('.col-md-6'); // ambil container <div> dari dropdown gudang

        // sembunyikan dulu saat halaman dimuat
        gudangField.style.display = 'none';

        // event listener ketika role berubah
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;

            // tampilkan hanya jika role = ADMIN_GUDANG atau KEPALA_GUDANG
            if (selectedRole === 'ADMIN_GUDANG' || selectedRole === 'KEPALA_GUDANG') {
                gudangField.style.display = 'block';
                document.getElementById('id_gudang').required = true;
            } else {
                gudangField.style.display = 'none';
                document.getElementById('id_gudang').required = false;
                document.getElementById('id_gudang').value = ''; // reset nilai
            }
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const wilayahField = document.getElementById('id_wilayah').closest('.col-md-6'); // ambil container <div> dari dropdown wilayah

        // sembunyikan dulu saat halaman dimuat
        wilayahField.style.display = 'none';

        // event listener ketika role berubah
        roleSelect.addEventListener('change', function() {
            const selectedRole = this.value;

            // tampilkan hanya jika role = ADMIN_WILAYAH
            if (selectedRole === 'ADMIN_WILAYAH') {
                wilayahField.style.display = 'block';
                document.getElementById('id_wilayah').required = true;
            } else {
                wilayahField.style.display = 'none';
                document.getElementById('id_wilayah').required = false;
                document.getElementById('id_wilayah').value = ''; // reset nilai
            }
        });
    });
</script>