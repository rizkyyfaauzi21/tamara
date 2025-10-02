<?php
// app/views/report/index.php

$months        = $months        ?? [];
$types         = $types         ?? [];
$gudangs       = $gudangs       ?? [];
$stoList       = $stoList       ?? [];
$invoices      = $invoices      ?? [];
$invoiceData   = $invoiceData   ?? [];
$invoiceLines  = $invoiceLines  ?? [];
$invoiceLineDetails = $invoiceLineDetails ?? [];

// Siapkan data STO untuk pengisian kolom (map id -> detail) dan opsi select2
$stoDataPHP = [];
$stoOpts = [];
foreach ($stoList as $s) {
  $id   = (int)$s['id'];
  $stoDataPHP[$id] = [
    'id'             => $id,
    'nomor_sto'      => $s['nomor_sto'] ?? '',
    'tanggal_terbit' => $s['tanggal_terbit'] ?? '',
    'nama_gudang'    => $s['nama_gudang'] ?? '',
    'transportir'    => $s['transportir'] ?? '',
    'tonase_normal'  => $s['tonase_normal'] ?? 0,
    'tonase_lembur'  => $s['tonase_lembur'] ?? 0,
    'keterangan'     => $s['keterangan'] ?? '',
  ];
  $stoOpts[] = ['id'=>$id,'text'=>$s['nomor_sto']];
}
?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

<div class="container mt-4">
  <a href="index.php?page=dashboard" class="btn btn-secondary mb-3">← Back</a>
  <h2>Laporan STO</h2>

  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <!-- FORM CREATE -->
  <form id="frm-create" method="POST" action="index.php?page=report_generate">
    <div class="row gy-3">
      <div class="col-md-3">
        <label>Bulan</label>
        <select name="bulan" class="form-control" required>
          <?php foreach($months as $m): ?>
            <option value="<?= $m ?>"><?= $m ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label>Jenis Pupuk</label>
        <input name="jenis_pupuk" class="form-control" placeholder="Masukkan jenis pupuk..." required>
      </div>
      <div class="col-md-3">
        <label>Gudang</label>
        <select id="sel-gudang-new" name="gudang_id" class="form-control" required>
          <option value="">-- Pilih --</option>
          <?php foreach($gudangs as $g): ?>
            <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label>Jenis Kegiatan</label>
        <select id="sel-trans-new" name="jenis_transaksi" class="form-control" required>
          <option value="">-- Pilih --</option>
          <?php foreach($types as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-4">
        <label>Uraian Pekerjaan</label>
        <input name="uraian_pekerjaan" class="form-control" placeholder="Misal: Bongkar Pupuk" required>
      </div>
      <div class="col-md-4">
        <label>Tarif Normal (Rp)</label>
        <input id="fld-normal-new" name="tarif_normal" class="form-control" readonly>
      </div>
      <div class="col-md-4">
        <label>Tarif Lembur (Rp)</label>
        <input id="fld-lembur-new" name="tarif_lembur" class="form-control" readonly>
      </div>
    </div>

    <hr>

    <div class="table-responsive mb-3">
      <table class="table table-bordered" id="tbl-sto-new">
        <thead class="table-light">
          <tr>
            <th style="width:40px;">No</th>
            <th>Nomor STO</th>
            <th>Tanggal Terbit</th>
            <th>Nama Gudang</th>
            <th>Transportir</th>
            <th>Tonase Normal</th>
            <th>Tonase Lembur</th>
            <th>Jumlah</th>
            <th>Keterangan</th>
            <th style="width:40px;"></th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
      <button type="button" id="btn-add-new" class="btn btn-sm btn-primary">+ Tambah Baris</button>
    </div>

    <button type="submit" class="btn btn-success">Generate Invoice & QR</button>
  </form>

  <hr>

  <!-- daftar invoice -->
  <h4>Daftar Invoice</h4>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>#</th><th>ID</th><th>Bulan</th><th>Pupuk</th><th>Gudang</th>
        <th>Transaksi</th><th>Uraian Pekerjaan</th><th>Dibuat Pada</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($invoices): foreach($invoices as $i=>$inv): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= $inv['id'] ?></td>
          <td><?= htmlspecialchars($inv['bulan']) ?></td>
          <td><?= htmlspecialchars($inv['jenis_pupuk']) ?></td>
          <td><?= htmlspecialchars($inv['nama_gudang']) ?></td>
          <td><?= htmlspecialchars($inv['jenis_transaksi']) ?></td>
          <td><?= htmlspecialchars($inv['uraian_pekerjaan']) ?></td>
          <td><?= $inv['created_at'] ?></td>
          <td class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-primary btn-view" data-id="<?= $inv['id'] ?>">View</button>
            <button type="button" class="btn btn-sm btn-warning btn-edit" data-id="<?= $inv['id'] ?>">Edit</button>
            <a href="index.php?page=invoice_delete&id=<?= $inv['id'] ?>"
              class="btn btn-sm btn-danger"
              onclick="return confirm('Hapus invoice #<?= $inv['id'] ?>?')">Hapus</a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr>
          <td colspan="9" class="text-center text-muted">Belum ada invoice yang dibuat.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal detail -->
<div class="modal fade" id="modalInvoice" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content" id="modalInvoiceContent"></div>
  </div>
</div>

<!-- Modal EDIT -->
<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <form id="frm-edit" class="modal-content" method="POST">
      <div class="modal-header">
        <h5 class="modal-title">Edit Invoice</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit-invoice-id" name="invoice_id" />
        <div class="row gy-3">
          <div class="col-md-3">
            <label>Bulan</label>
            <select id="edit-bulan" name="bulan" class="form-control" required>
              <?php foreach($months as $m): ?>
                <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label>Jenis Pupuk</label>
            <input id="edit-jp" name="jenis_pupuk" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label>Gudang</label>
            <select id="edit-gdg" name="gudang_id" class="form-control" required>
              <option value="">-- Pilih --</option>
              <?php foreach($gudangs as $g): ?>
                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama_gudang']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label>Jenis Transaksi</label>
            <select id="edit-trans" name="jenis_transaksi" class="form-control" required>
              <option value="">-- Pilih --</option>
              <?php foreach($types as $t): ?>
                <option value="<?= $t ?>"><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-4">
            <label>Uraian Pekerjaan</label>
            <input id="edit-uraian" name="uraian_pekerjaan" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label>Tarif Normal (Rp)</label>
            <input id="edit-tn" name="tarif_normal" class="form-control" readonly>
          </div>
          <div class="col-md-4">
            <label>Tarif Lembur (Rp)</label>
            <input id="edit-tl" name="tarif_lembur" class="form-control" readonly>
          </div>
        </div>

        <hr>

        <div class="table-responsive mb-3">
          <table class="table table-bordered" id="tbl-sto-edit">
            <thead class="table-light">
              <tr>
                <th style="width:40px;">No</th>
                <th>Nomor STO</th>
                <th>Tanggal Terbit</th>
                <th>Nama Gudang</th>
                <th>Transportir</th>
                <th>Tonase Normal</th>
                <th>Tonase Lembur</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th style="width:40px;"></th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
          <button type="button" id="btn-add-edit" class="btn btn-sm btn-primary">+ Tambah Baris</button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-success" type="submit">Simpan Perubahan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // Data dari PHP
  const stoData            = <?= json_encode($stoDataPHP         ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const stoOpts            = <?= json_encode($stoOpts            ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const invoiceData        = <?= json_encode($invoiceData        ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const invoiceLines       = <?= json_encode($invoiceLines       ?? [], JSON_UNESCAPED_UNICODE) ?>;
  const invoiceLineDetails = <?= json_encode($invoiceLineDetails ?? [], JSON_UNESCAPED_UNICODE) ?>;

  // Utils
  function buildSelectData(extraRows){
    const base = stoOpts.slice();
    const has  = new Set(base.map(x=>x.id));
    (extraRows||[]).forEach(r=>{
      const rid = parseInt(r.id);
      if(!has.has(rid)){
        base.push({id: rid, text: (r.nomor_sto || r.text || '')});
        if(!stoData[rid]) stoData[rid] = r;
      }
    });
    return base;
  }
  function renumber($tbody){
    $tbody.find('tr').each((i,tr)=>{
      $(tr).find('td.no').text(i+1);
      $(tr).find('.rm').toggle(i>0);
    });
  }

  // CREATE
  function addRowCreate($tbody, selectData, selected=null, detail=null){
    const $r = $(`<tr>
      <td class="no"></td>
      <td><select name="sto_ids[]" class="form-control sto-sel" required><option></option></select></td>
      <td class="tgl"></td><td class="gdg"></td><td class="trp"></td>
      <td class="norm"></td><td class="lemb"></td><td class="jml"></td><td class="ket"></td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rm">–</button></td>
    </tr>`);
    $tbody.append($r);
    renumber($tbody);

    const sel = $r.find('.sto-sel').select2({
      data: selectData, placeholder:'Cari Nomor STO…', allowClear:true, width:'100%'
    })
    .on('select2:select', e=>{
      const id = e.params.data.id;
      const d  = stoData[id] || {};
      $r.find('.tgl').text(d.tanggal_terbit || d.tanggal || '');
      $r.find('.gdg').text(d.nama_gudang || '');
      $r.find('.trp').text(d.transportir || '');
      $r.find('.norm').text(d.tonase_normal || '');
      $r.find('.lemb').text(d.tonase_lembur || '');
      const j = (parseFloat(d.tonase_normal||0)+parseFloat(d.tonase_lembur||0));
      $r.find('.jml').text(isNaN(j)?'':j);
      $r.find('.ket').text(d.keterangan || '');
    })
    .on('select2:clear change', ()=>{
      if(!sel.val()){
        $r.find('.tgl,.gdg,.trp,.norm,.lemb,.jml,.ket').text('');
      }
    });

    if (selected){
      sel.val(String(selected)).trigger('change');
      const d = detail || stoData[selected] || {};
      $r.find('.tgl').text(d.tanggal_terbit || d.tanggal || '');
      $r.find('.gdg').text(d.nama_gudang || '');
      $r.find('.trp').text(d.transportir || '');
      $r.find('.norm').text(d.tonase_normal || '');
      $r.find('.lemb').text(d.tonase_lembur || '');
      const j = (parseFloat(d.tonase_normal||0)+parseFloat(d.tonase_lembur||0));
      $r.find('.jml').text(isNaN(j)?'':j);
      $r.find('.ket').text(d.keterangan || '');
    }
    $r.find('.rm').click(()=>{ $r.remove(); renumber($tbody); });
  }

  function bindTarifNew(){
    const g=$('#sel-gudang-new').val(), t=$('#sel-trans-new').val();
    if(!g||!t) return;
    $.getJSON('ajax/get_tarif.php',{gudang_id:g,jenis_transaksi:t})
     .done(d=>{ $('#fld-normal-new').val(d.tarif_normal); $('#fld-lembur-new').val(d.tarif_lembur); });
  }
  $('#sel-gudang-new,#sel-trans-new').on('change', bindTarifNew);
  $('#tbl-sto-new tbody').empty();
  addRowCreate($('#tbl-sto-new tbody'), buildSelectData([]));
  $('#btn-add-new').click(()=> addRowCreate($('#tbl-sto-new tbody'), buildSelectData([])));

  // VIEW
  $(document).on('click','.btn-view',function(e){
    e.preventDefault();
    const id=$(this).data('id');
    $.get('index.php?page=invoice_view_partial&id='+id, html=>{
      $('#modalInvoiceContent').html(html);
      const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalInvoice'));
      m.show();
    });
  });

  // ===== EDIT =====
  function getSelectedIds($tbody){
    const s = new Set();
    $tbody.find('select.sto-sel').each(function(){
      const v = $(this).val();
      if (v) s.add(parseInt(v));
    });
    return s;
  }

  function rebuildOneSelect($sel, baseData, selectedIds){
    const keep = $sel.val() ? parseInt($sel.val()) : null;

    const data = baseData.map(o => ({
      id: o.id,
      text: o.text,
      disabled: (selectedIds.has(o.id) && o.id !== keep)
    }));

    // aman destroy kalau memang sudah select2
    $sel.off('select2:select select2:clear change');
    if ($sel.hasClass('select2-hidden-accessible')) {
      $sel.select2('destroy');
    }

    $sel.empty().append('<option></option>');
    $sel.select2({
      data,
      placeholder: 'Cari Nomor STO…',
      allowClear: true,
      width: '100%',
      // PENTING: supaya dropdown muncul di atas modal, bukan di body
      dropdownParent: $('#modalEdit')
    });

    if (keep) $sel.val(String(keep)).trigger('change');

    const $row = $sel.closest('tr');

    $sel.on('select2:select', e=>{
      const id = e.params.data.id;
      const d  = stoData[id] || {};
      $row.find('.tgl').text(d.tanggal_terbit || d.tanggal || '');
      $row.find('.gdg').text(d.nama_gudang || '');
      $row.find('.trp').text(d.transportir || '');
      $row.find('.norm').text(d.tonase_normal || '');
      $row.find('.lemb').text(d.tonase_lembur || '');
      const j = (parseFloat(d.tonase_normal||0)+parseFloat(d.tonase_lembur||0));
      $row.find('.jml').text(isNaN(j)?'':j);
      $row.find('.ket').text(d.keterangan || '');
      refreshAll($row.closest('tbody'), baseData);
    }).on('select2:clear change', ()=>{
      if (!$sel.val()){
        $row.find('.tgl,.gdg,.trp,.norm,.lemb,.jml,.ket').text('');
        refreshAll($row.closest('tbody'), baseData);
        // Biar user langsung bisa pilih lagi
        setTimeout(()=> { $sel.select2('open'); }, 0);
      }
    });
  }

  function refreshAll($tbody, baseData){
    const selectedIds = getSelectedIds($tbody);
    $tbody.find('select.sto-sel').each(function(){
      rebuildOneSelect($(this), baseData, selectedIds);
    });
  }

  function addRowEdit($tbody, baseData, selected=null, detail=null){
    const $r = $(`<tr>
      <td class="no"></td>
      <td><select name="sto_ids[]" class="form-control sto-sel"><option></option></select></td>
      <td class="tgl"></td><td class="gdg"></td><td class="trp"></td>
      <td class="norm"></td><td class="lemb"></td><td class="jml"></td><td class="ket"></td>
      <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rm">–</button></td>
    </tr>`);
    $tbody.append($r);
    renumber($tbody);

    rebuildOneSelect($r.find('.sto-sel'), baseData, getSelectedIds($tbody));

    if (selected){
      $r.find('.sto-sel').val(String(selected)).trigger('change');
      const d = detail || stoData[selected] || {};
      $r.find('.tgl').text(d.tanggal_terbit || d.tanggal || '');
      $r.find('.gdg').text(d.nama_gudang || '');
      $r.find('.trp').text(d.transportir || '');
      $r.find('.norm').text(d.tonase_normal || '');
      $r.find('.lemb').text(d.tonase_lembur || '');
      const j = (parseFloat(d.tonase_normal||0)+parseFloat(d.tonase_lembur||0));
      $r.find('.jml').text(isNaN(j)?'':j);
      $r.find('.ket').text(d.keterangan || '');
      refreshAll($tbody, baseData);
    }

    $r.find('.rm').on('click', ()=>{
      $r.remove(); renumber($tbody);
      refreshAll($tbody, baseData);
    });
  }

  // Klik Edit (delegasi) – pastikan modal tampil
  $(document).on('click','.btn-edit',function(e){
    e.preventDefault();
    e.stopPropagation();

    const id = Number($(this).data('id'));
    const hdr = (invoiceData && (invoiceData[id] || invoiceData[String(id)])) || null;
    if (!hdr) { alert('Data invoice tidak ditemukan. Reload halaman.'); return; }

    // header
    $('#edit-invoice-id').val(id);
    $('#edit-bulan').val(hdr.bulan || '');
    $('#edit-jp').val(hdr.jenis_pupuk || '');
    $('#edit-gdg').val(hdr.gudang_id || '').trigger('change');
    $('#edit-trans').val(hdr.jenis_transaksi || '').trigger('change');
    $('#edit-uraian').val(hdr.uraian_pekerjaan || '');
    $('#edit-tn').val(hdr.tarif_normal || '');
    $('#edit-tl').val(hdr.tarif_lembur || '');

    // rows
    const lines  = (invoiceLines && (invoiceLines[id] || invoiceLines[String(id)])) || [];
    const detail = (invoiceLineDetails && (invoiceLineDetails[id] || invoiceLineDetails[String(id)])) || [];

    const $tb = $('#tbl-sto-edit tbody'); $tb.empty();
    const baseData = buildSelectData(detail);

    if (detail.length){
      detail.forEach(d=> addRowEdit($tb, baseData, d.id, d));
    } else if (lines.length){
      lines.forEach(sid=> addRowEdit($tb, baseData, sid, {}));
    } else {
      addRowEdit($tb, baseData);
    }

    $('#btn-add-edit').off('click').on('click', ()=> addRowEdit($tb, baseData));
    $('#frm-edit').attr('action','index.php?page=report_update&id='+id);

    const m = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEdit'));
    m.show();
  });

  // tarif di edit
  function bindTarifEdit(){
    const g=$('#edit-gdg').val(), t=$('#edit-trans').val();
    if(!g||!t) return;
    $.getJSON('ajax/get_tarif.php',{gudang_id:g,jenis_transaksi:t})
     .done(d=>{ $('#edit-tn').val(d.tarif_normal); $('#edit-tl').val(d.tarif_lembur); });
  }
  $('#edit-gdg,#edit-trans').on('change', bindTarifEdit);
});
</script>