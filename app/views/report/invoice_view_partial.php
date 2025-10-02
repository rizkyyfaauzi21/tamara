<?php
// app/views/report/invoice_view_partial.php
// tersedia setelah controller:
//   $inv, $lines, $totalNorm, $totalLemb, $totalAll, $qrImage
?>
<style>
  .modal-body { position: relative; padding-top:1.5rem; }
  .qr-code {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 100px;
    height: 100px;
  }
  .info-header td { vertical-align: top; padding: .25rem 1rem; font-size: 14px; }
  .table-print { width:100%; border-collapse: collapse; font-size:13px; margin:1rem 0; }
  .table-print th, .table-print td {
    border:1px solid #000; padding:6px;
  }
  .table-print th { background:#f8f8f8; text-align:center; }
  .table-print .c { text-align:center; }
  .table-print .r { text-align:right; }
  .table-print tr.tot td { border-top:2px solid #000; font-weight:bold; }
  .footer-totals { width:100%; font-size:13px; margin-top:1rem; }
  .footer-totals td { padding:4px 8px; }
  .footer-totals .r { text-align:right; }
  @media print {
    .modal-backdrop, body > :not(.modal) { display:none !important; }
    .modal { position: static !important; display:block !important; }
  }
</style>

<div class="modal-header border-0">
  <h5 class="modal-title">Detail Invoice #<?= $inv['id'] ?></h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
  <!-- QR di kanan atas -->
  <img src="<?= htmlspecialchars($qrImage) ?>" class="qr-code" alt="QR Invoice">

  <!-- Info header -->
  <table class="info-header w-100">
    <tr>
      <td>
        <strong>BULAN</strong>         : <?= htmlspecialchars($inv['bulan']) ?><br>
        <strong>JENIS KEGIATAN</strong>: <?= htmlspecialchars($inv['jenis_transaksi']) ?><br>
        <strong>TARIF NORMAL</strong>  : Rp <?= number_format($inv['tarif_normal'],0,',','.') ?><br>
        <strong>TARIF LEMBUR</strong>  : Rp <?= number_format($inv['tarif_lembur'],0,',','.') ?>
      </td>
      <td>
        <strong>JENIS PUPUK</strong>     : <?= htmlspecialchars($inv['jenis_pupuk']) ?><br>
        <strong>GUDANG</strong>           : <?= htmlspecialchars($inv['nama_gudang']) ?><br>
        <strong>URAIAN PEKERJAAN</strong> : <?= htmlspecialchars($inv['uraian_pekerjaan']) ?><br>
        <strong>DIBUAT PADA</strong>      : <?= $inv['created_at'] ?>
      </td>
    </tr>
  </table>

  <!-- Tabel detail 18 baris -->
  <table class="table-print">
    <thead>
      <tr>
        <th rowspan="2">NO</th>
        <th rowspan="2">NOMOR<br>SALES TRANSPORT ORDER</th>
        <th rowspan="2">TANGGAL<br>TERBIT</th>
        <th rowspan="2">TRANSPORTIR</th>
        <th colspan="3">TONASE BONGKAR</th>
      </tr>
      <tr>
        <th>NORMAL</th><th>LEMBUR</th><th>JUMLAH</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($lines as $i => $ln): 
        $sub = $ln['tonase_normal'] * $inv['tarif_normal']
             + $ln['tonase_lembur'] * $inv['tarif_lembur'];
      ?>
      <tr>
        <td class="c"><?= $i+1 ?></td>
        <td><?= htmlspecialchars($ln['nomor_sto']) ?></td>
        <td class="c"><?= date('d m Y',strtotime($ln['tanggal_terbit'])) ?></td>
        <td><?= htmlspecialchars($ln['transportir']) ?></td>
        <td class="r"><?= number_format($ln['tonase_normal'],3,',','.') ?></td>
        <td class="r"><?= number_format($ln['tonase_lembur'],3,',','.') ?></td>
        <td class="r"><?= number_format($sub,3,',','.') ?></td>
      </tr>
      <?php endforeach; ?>

      <?php for($j = count($lines)+1; $j <= 10; $j++): ?>
      <tr>
        <td class="c"><?= $j ?></td>
        <td></td><td></td><td></td>
        <td></td><td></td><td></td>
      </tr>
      <?php endfor; ?>

      <tr class="tot">
        <td colspan="4" class="r">TOTAL</td>
        <td class="r"><?= number_format($totalNorm,3,',','.') ?></td>
        <td class="r"><?= number_format($totalLemb,3,',','.') ?></td>
        <td class="r"><?= number_format($totalAll,3,',','.') ?></td>
      </tr>
    </tbody>
  </table>

  <!-- Footer grand totals -->
  <table class="footer-totals">
    <tr>
      <td>TOTAL BONGKAR NORMAL :</td>
      <td class="r">Rp <?= number_format($totalNorm,2,',','.') ?></td>
    </tr>
    <tr>
      <td>TOTAL BONGKAR LEMBUR :</td>
      <td class="r">Rp <?= number_format($totalLemb,2,',','.') ?></td>
    </tr>
    <tr>
      <td>TOTAL :</td>
      <td class="r">Rp <?= number_format($totalAll,2,',','.') ?></td>
    </tr>
  </table>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
  <button type="button" class="btn btn-primary" onclick="window.print()">Print</button>
</div>
