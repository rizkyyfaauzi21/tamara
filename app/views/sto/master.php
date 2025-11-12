<?php
// variabel yang dipakai di view ini (datang dari controller):
// $gudangs, $stoList, $filesBySto, $filesBaseUrl
?>
<div class="container-fluid  mt-5" style="padding:0px 50px 50px 50px;" >
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Master STO</h2>
    <a href="index.php?page=dashboard" class="btn btn-secondary">Back</a>
  </div>

  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error'];
                                    unset($_SESSION['error']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['warning'])): ?>
    <div class="alert alert-warning"><?= $_SESSION['warning'];
                                      unset($_SESSION['warning']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success'];
                                      unset($_SESSION['success']); ?></div>
  <?php endif; ?>

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
  </style>

  <!-- ================= 1) Form Registrasi ================= -->
  <form id="stoForm" class="row g-3 mb-5" method="POST" enctype="multipart/form-data">
    <div class="col-md-4">
      <label class="form-label">Nomor STO</label>
      <input type="text" name="nomor_sto" class="form-control" placeholder="Masukkan Nomor STO" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Tanggal Terbit</label>
      <input type="date" name="tanggal_terbit" class="form-control" required>
    </div>
    <div class="col-md-4">
      <label class="form-label">Nama Gudang</label>

      <input
        type="text"
        name="nama_gudang"
        id="edit-nama-gudang"
        class="form-control"
        placeholder="Nama Gudang"
        value="<?= htmlspecialchars($nama_gudang) ?: '-' ?>"
        readonly>
    </div>
    <div class="col-md-4">
      <label class="form-label">Jenis Kegiatan</label>
      <select name="jenis_transaksi" class="form-control" required>
        <option value="">-- Pilih --</option>
        <option value="BONGKAR">BONGKAR</option>
        <option value="MUAT">MUAT</option>
      </select>
    </div>
    <div class="col-md-4">
      <label class="form-label">Tonase Normal (Ton)</label>
      <input type="number" step="0.01" name="tonase_normal" id="tonase_normal" class="form-control" value="0">
    </div>
    <div class="col-md-4">
      <label class="form-label">Tonase Lembur (Ton)</label>
      <input type="number" step="0.01" name="tonase_lembur" id="tonase_lembur" class="form-control" value="0">
    </div>
    <div class="col-md-4">
      <label class="form-label">Total Tonase</label>
      <input type="text" id="total_tonase" class="form-control" readonly>
    </div>
    <div class="col-md-4">
      <label class="form-label">Transportir</label>
      <input type="text" name="transportir" class="form-control" placeholder="Nama Transportir" required>
    </div>
    <div class="col-md-8">
      <label class="form-label">Keterangan</label>
      <input type="text" name="keterangan" class="form-control" placeholder="Opsional">
    </div>

    <!-- =========== Upload Multi File (baru) =========== -->
    <div class="col-12">
      <label class="form-label">Lampiran (boleh banyak)</label>

      <div id="dz-create" class="uploader p-4 text-center mb-2">
        <div class="cloud mb-2">☁️⬆️</div>
        <div class="cta">Click To Upload</div>
        <small class="text-muted d-block mt-1">
          atau drag & drop file ke sini • Maks 10MB/file • pdf, jpg, png, xls, xlsx
        </small>
      </div>

      <input id="files-create" type="file" name="files[]" class="d-none" multiple
        accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx">

      <ul id="list-create" class="file-list"></ul>
    </div>

    <div class="col-12 text-end">
      <button type="submit" class="btn btn-primary">Daftar STO</button>
    </div>
  </form>

  <!-- ================= 2) Filter Tabel ================= -->
  <div class="row g-2 mb-3">
    <div class="col-md-2"><input type="text" id="f_nomor" class="form-control filter-input" placeholder="Filter Nomor"></div>
    <div class="col-md-2"><input type="text" id="f_gudang" class="form-control filter-input" placeholder="Filter Gudang"></div>
    <div class="col-md-2"><input type="text" id="f_transaksi" class="form-control filter-input" placeholder="Filter Transaksi"></div>
    <div class="col-md-2"><input type="text" id="f_status" class="form-control filter-input" placeholder="Filter Status"></div>
    <div class="col-md-4 text-end">
      <button id="resetFilters" class="btn btn-secondary" type="button">Reset Filters</button>
    </div>
  </div>

 <!-- ================= 3) Tabel Master ================= -->
<div class="table-responsive">
  <table id="sto-table" class="table table-bordered align-middle table-striped">
    <thead>
      <tr>
        <th>No.</th>
        <th>Nomor STO</th>
        <th>Tgl Terbit</th>
        <th>Gudang</th>
        <th>Transaksi</th>
        <th>Transportir</th>
        <th>Normal</th>
        <th>Lembur</th>
        <th>Jumlah</th>
        <th>Status</th>
        <th>Lampiran</th>
        <th>Keterangan</th>
        <th>Created</th>
        <th>Aksi</th>
        <th>Pilih Nomor STO</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($stoList)): ?>
        <tr><td colspan="15" class="text-center text-muted">Belum ada STO</td></tr>
      <?php else: ?>
        <?php foreach ($stoList as $i => $s): ?>
          <?php $fcount = isset($filesBySto[$s['id']]) ? count($filesBySto[$s['id']]) : 0; ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($s['nomor_sto']) ?></td>
            <td><?= htmlspecialchars($s['tanggal_terbit']) ?></td>
            <td><?= htmlspecialchars($s['nama_gudang']) ?></td>
            <td><?= htmlspecialchars($s['jenis_transaksi']) ?></td>
            <td><?= htmlspecialchars($s['transportir']) ?></td>
            <td><?= number_format($s['tonase_normal'], 2) ?></td>
            <td><?= number_format($s['tonase_lembur'], 2) ?></td>
            <td><?= number_format($s['jumlah'], 2) ?></td>
            <td><?= htmlspecialchars($s['status']) ?></td>
            <td><?= $fcount ? "<span class='badge bg-info'>$fcount file</span>" : "<span class='text-muted'>—</span>" ?></td>
            <td><?= htmlspecialchars($s['keterangan'] ?? '') ?></td>
            <td><?= htmlspecialchars($s['created_at']) ?></td>
            <td class="text-nowrap">
              <button data-id="<?= $s['id'] ?>" class="btn btn-sm btn-warning btn-edit">Detail & Edit</button>
              <a href="?page=master_sto&delete=<?= $s['id'] ?>" class="btn btn-sm btn-danger"
                 onclick="return confirm('Hapus STO ini beserta lampiran?')">Hapus</a>
            </td>
           <td>
              <?php if ($s['status'] === 'NOT_USED'): ?>
                <?php if ($s['pilihan'] === 'DIPILIH'): ?>
                  <?php if ($_SESSION['role'] === 'KEPALA_GUDANG' || $_SESSION['role'] === 'SUPERADMIN'): ?>
                    <button class="btn btn-sm btn-success toggle-pilih" 
                            data-id="<?= $s['id'] ?>" 
                            data-next="BELUM_DIPILIH">
                      Dipilih
                    </button>
                  <?php else: ?>
                    <span class="badge bg-success">Dipilih</span>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if ($_SESSION['role'] === 'KEPALA_GUDANG' || $_SESSION['role'] === 'SUPERADMIN'): ?>
                    <button class="btn btn-sm btn-outline-primary toggle-pilih" 
                            data-id="<?= $s['id'] ?>" 
                            data-next="DIPILIH">
                      Belum Dipilih
                    </button>
                  <?php else: ?>
                    <span class="badge bg-outline-primary text-primary border border-primary">Belum Dipilih</span>
                  <?php endif; ?>
                <?php endif; ?>
              <?php else: ?>
                <span class="badge bg-secondary">Sudah Laporan</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
    <!-- ================= Kontrol Pagination ================= -->
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>Menampilkan <span id="count-showing">0</span> dari <span id="count-total">0</span> data</div>
        <div>
            <label class="me-2">Tampilkan</label>
            <select id="rowsPerPage" class="form-select form-select-sm d-inline-block" style="width:80px;">
                <option value="5">5</option>
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
            <span>data per halaman</span>
        </div>
    </div>
</div>


<!-- ================= 4) Modal Edit ================= -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form id="editForm" class="modal-content" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-header">
        <h5 class="modal-title">Edit STO</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nomor STO</label>
            <input type="text" name="nomor_sto" id="edit-nomor" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Tanggal Terbit</label>
            <input type="date" name="tanggal_terbit" id="edit-tanggal" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Nama Gudang</label>
            <input
              type="text"
              name="nama_gudang"
              id="edit-gudang"
              class="form-control"
              placeholder="Nama Gudang"
              value="<?= htmlspecialchars($nama_gudang) ?: '-' ?>"
              readonly>
          </div>
          <div class="col-md-6">
            <label class="form-label">Jenis Transaksi</label>
            <select name="jenis_transaksi" id="edit-jenis" class="form-control" required>
              <option value="BONGKAR">BONGKAR</option>
              <option value="MUAT">MUAT</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Tonase Normal</label>
            <input type="number" step="0.01" name="tonase_normal" id="edit-normal" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Tonase Lembur</label>
            <input type="number" step="0.01" name="tonase_lembur" id="edit-lembur" class="form-control">
          </div>
          <div class="col-md-4">
            <label class="form-label">Transportir</label>
            <input type="text" name="transportir" id="edit-transportir" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label">Keterangan</label>
            <input type="text" name="keterangan" id="edit-keterangan" class="form-control">
          </div>
          <div class="col-md-3">
            <label class="form-label">Status</label>
            <select name="status" id="edit-status" class="form-control">
              <option value="USED">USED</option>
              <option value="NOT_USED">NOT_USED</option>
            </select>
          </div>
        

          <!-- Upload tambahan di EDIT -->
          <div class="col-12">
            <label class="form-label">Tambah Lampiran</label>

            <div id="dz-edit" class="uploader p-4 text-center mb-2">
              <div class="cloud mb-2">☁️⬆️</div>
              <div class="cta">Click To Upload</div>
              <small class="text-muted d-block mt-1">atau drag & drop file ke sini • Maks 10MB/file • pdf, jpg, png, xls, xlsx</small>
            </div>

            <input id="files-edit" type="file" name="edit_files[]" class="d-none" multiple
              accept=".pdf,.png,.jpg,.jpeg,.xls,.xlsx">

            <ul id="list-edit" class="file-list"></ul>
            <div class="form-text">Lampiran baru akan ditambahkan. Lampiran lama ada di bawah.</div>
          </div>

          <!-- Daftar lampiran yang sudah ada -->
          <div class="col-12">
            <label class="form-label d-block">Lampiran Saat Ini</label>
            <ul id="current-files" class="list-group small"></ul>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- ================= Script ================= -->
<script>
  document.addEventListener('DOMContentLoaded', () => {

    // total tonase (form create)
    const n = document.getElementById('tonase_normal'),
      l = document.getElementById('tonase_lembur'),
      t = document.getElementById('total_tonase');

    function updTotal() {
      t.value = ((parseFloat(n.value) || 0) + (parseFloat(l.value) || 0)).toFixed(2)
    }
    n.addEventListener('input', updTotal);
    l.addEventListener('input', updTotal);
    updTotal();

    // filter tabel (client side)
    document.querySelectorAll('.filter-input').forEach(inp => {
      inp.addEventListener('input', () => {
        const cols = {
          'f_nomor': 1,
          'f_gudang': 3,
          'f_transaksi': 4,
          'f_status': 9
        };
        const tb = document.querySelector('#sto-table tbody');
        Array.from(tb.rows).forEach(r => {
          let show = true;
          for (const id in cols) {
            const val = document.getElementById(id).value.toLowerCase();
            if (val && !r.cells[cols[id]].innerText.toLowerCase().includes(val)) {
              show = false;
              break;
            }
          }
          r.style.display = show ? '' : 'none';
        });
      });
    });
    document.getElementById('resetFilters').addEventListener('click', () => {
      document.querySelectorAll('.filter-input').forEach(i => i.value = '');
      document.querySelectorAll('.filter-input').forEach(i => i.dispatchEvent(new Event('input')));
    });

    // ========= Widget multi uploader (drop zone + list) =========
    function initMultiUploader(zoneId, inputId, listId, options = {}) {
      const zone = document.getElementById(zoneId);
      const input = document.getElementById(inputId);
      const list = document.getElementById(listId);
      const MAX_BYTES = (options.maxMB || 10) * 1024 * 1024;
      const ALLOWED = (options.allowed || ['pdf', 'png', 'jpg', 'jpeg', 'xls', 'xlsx']).map(x => x.toLowerCase());

      const dt = new DataTransfer(); // buffer file

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

      zone.addEventListener('click', () => input.click());
      input.addEventListener('change', (e) => acceptFiles(e.target.files));

      ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev =>
        zone.addEventListener(ev, e => {
          e.preventDefault();
          e.stopPropagation();
        })
      );
      zone.addEventListener('drop', e => acceptFiles(e.dataTransfer.files));
    }

    // inisialisasi widget upload
    initMultiUploader('dz-create', 'files-create', 'list-create', {
      maxMB: 10
    });
    initMultiUploader('dz-edit', 'files-edit', 'list-edit', {
      maxMB: 10
    });

    // ========= Modal Edit =========
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    document.getElementById('sto-table').addEventListener('click', e => {
      if (!e.target.classList.contains('btn-edit')) return;
      const id = e.target.dataset.id;
      fetch(`index.php?page=master_sto&action=get&id=${id}`)
        .then(r => r.json())
        .then(s => {
          document.getElementById('edit-id').value = s.id;
          document.getElementById('edit-nomor').value = s.nomor_sto;
          document.getElementById('edit-tanggal').value = s.tanggal_terbit;
          document.getElementById('edit-gudang').value = s.nama_gudang;
          document.getElementById('edit-jenis').value = s.jenis_transaksi;
          document.getElementById('edit-normal').value = s.tonase_normal;
          document.getElementById('edit-lembur').value = s.tonase_lembur;
          document.getElementById('edit-transportir').value = s.transportir;
          document.getElementById('edit-keterangan').value = s.keterangan ?? '';
          document.getElementById('edit-status').value = s.status;

          // const pilihanEl = document.getElementById('edit-pilihan');
          // if (pilihanEl) pilihanEl.value = s.pilihan;


          // render file existing
          const ul = document.getElementById('current-files');
          ul.innerHTML = '';
          (s.files || []).forEach(f => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `
            <span>${f.filename} <small class="text-muted">(${(f.size_bytes/1024).toFixed(1)} KB)</small></span>
            <span>
              <a class="btn btn-sm btn-outline-primary me-2" target="_blank"
                 href="<?= htmlspecialchars($filesBaseUrl) ?>${f.stored_name}">Lihat</a>
              <a class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('Hapus lampiran ini?')"
                 href="index.php?page=master_sto&action=del_file&file_id=${f.id}">Hapus</a>
            </span>`;
            ul.appendChild(li);
          });

          editModal.show();
        });
    });

    // submit edit (AJAX)
    document.getElementById('editForm').addEventListener('submit', e => {
      e.preventDefault();
      const data = new FormData(e.target);
      fetch('index.php?page=master_sto', {
          method: 'POST',
          body: data
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            editModal.hide();
            window.location.reload();
          }
        });
    });

    // ========= Tombol Pilih / Belum Dipilih =========
    document.querySelectorAll('.toggle-pilih').forEach(btn => {
      btn.addEventListener('click', async e => {
        const id = btn.dataset.id;
        const next = btn.dataset.next;

        const res = await fetch('index.php?page=master_sto&action=toggle_pilih', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `sto_id=${id}&pilihan=${next}`
        });

        const data = await res.json();
        if (data.success) {
          location.reload();
        } else {
          alert('Gagal memperbarui status pilihan!');
        }
      });
    });

    // ========= Pagination Client-Side =========
    const table = document.querySelector("table.table-striped");
    const rows = Array.from(table.querySelectorAll("tbody tr"));
    const countShowing = document.getElementById("count-showing");
    const countTotal = document.getElementById("count-total");
    const rowsPerPageSelect = document.getElementById("rowsPerPage");

    // filter hanya baris data yang BUKAN empty state
    const validRows = rows.filter(row => !row.textContent.includes("Belum ada invoice"));
    let totalRows = validRows.length;

    const paginationContainer = document.createElement("div");
    paginationContainer.className = "d-flex justify-content-center";
    let pagination;

    let rowsPerPage = parseInt(rowsPerPageSelect.value);
    let totalPages = Math.ceil(totalRows / rowsPerPage);
    let currentPage = 1;

    // update total data di label
    countTotal.textContent = totalRows;
    countShowing.textContent = totalRows === 0 ? 0 : Math.min(rowsPerPage, totalRows);

    function showPage(page) {
      currentPage = page;
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;

      validRows.forEach((row, index) => {
        row.style.display = index >= start && index < end ? "" : "none";
      });

      // update label jumlah data
      if (totalRows === 0) {
        countShowing.textContent = 0;
        countTotal.textContent = 0;
      } else {
          const showing = Math.min(end, totalRows);
          countShowing.textContent = showing;
          countTotal.textContent = totalRows;
      }

      // tombol aktif
      document.querySelectorAll(".pagination .page-item").forEach(btn => btn.classList.remove("active"));
        const activeBtn = document.querySelector(`.pagination .page-item[data-page="${page}"]`);
        if (activeBtn) activeBtn.classList.add("active");

        // disable tombol prev/next
        const prev = document.getElementById("prevPage");
        const next = document.getElementById("nextPage");
        if (prev && next) {
            prev.classList.toggle("disabled", currentPage === 1);
            next.classList.toggle("disabled", currentPage === totalPages);
          }
        }

        function buildPagination() {
            if (pagination) pagination.remove();
            pagination = document.createElement("ul");
            pagination.className = "pagination justify-content-center mt-3";

            totalPages = Math.ceil(totalRows / rowsPerPage);

            // prev
            const prevLi = document.createElement("li");
            prevLi.className = "page-item disabled";
            prevLi.id = "prevPage";
            prevLi.innerHTML = `<a class="page-link" href="#">← Prev</a>`;
            pagination.appendChild(prevLi);

            // page number
            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement("li");
                li.className = "page-item";
                li.dataset.page = i;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener("click", () => showPage(i));
                pagination.appendChild(li);
            }

            // next
            const nextLi = document.createElement("li");
            nextLi.className = "page-item";
            nextLi.id = "nextPage";
            nextLi.innerHTML = `<a class="page-link" href="#">Next →</a>`;
            pagination.appendChild(nextLi);

            // event prev/next
            prevLi.addEventListener("click", e => {
                e.preventDefault();
                if (currentPage > 1) showPage(currentPage - 1);
            });
            nextLi.addEventListener("click", e => {
                e.preventDefault();
                if (currentPage < totalPages) showPage(currentPage + 1);
            });

            table.insertAdjacentElement("afterend", pagination);
            showPage(1);
        }

        // event ubah jumlah data per halaman
        rowsPerPageSelect.addEventListener("change", function() {
            rowsPerPage = parseInt(this.value);
            buildPagination();
        });

        // tampilkan pagination hanya jika ada data
        if (totalRows > 0) {
            buildPagination();
        } else {
            // sembunyikan pagination dan ubah label
            countShowing.textContent = 0;
            countTotal.textContent = 0;
        }
  });
</script>