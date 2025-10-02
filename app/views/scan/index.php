<?php
// app/views/scan/index.php
require __DIR__ . '/../layout/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

<div class="container mt-4">
  <a href="index.php?page=dashboard" class="btn btn-secondary mb-3">← Back</a>
  <h2>Scan Tagihan</h2>

  <div class="mb-3">
    <video id="video" autoplay muted playsinline
           style="width:100%;max-width:600px;border:1px solid #ccc"></video>
    <p id="status" class="mt-2 text-info">Arahkan kamera ke QR code invoice</p>
  </div>

  <div id="detailContainer"></div>
</div>

<script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
  const codeReader = new ZXing.BrowserMultiFormatReader();
  const videoElem  = document.getElementById('video');
  const statusElem = document.getElementById('status');
  const detailDiv  = document.getElementById('detailContainer');

  // state to avoid double handling while camera stays ON
  let busy = false;
  let lastText = null;
  let lastHitTs = 0;
  let currentDeviceId = null;

  // start camera once; do NOT call reset() after each scan
  codeReader.listVideoInputDevices()
    .then(devices => {
      if (!devices || devices.length === 0) throw new Error('No camera found');
      currentDeviceId = devices[devices.length - 1].deviceId; // prefer back camera
      return codeReader.decodeFromVideoDevice(currentDeviceId, videoElem, onFrame);
    })
    .catch(err => {
      statusElem.textContent = 'Tidak bisa akses kamera: ' + err.message;
    });

  function onFrame(result, err) {
    if (!result) return; // ignore frames without QR
    const text = result.getText();

    // debounce: ignore same text within 1.2s, or while loading/deciding
    const now = Date.now();
    if (busy) return;
    if (text === lastText && (now - lastHitTs) < 1200) return;

    lastText = text;
    lastHitTs = now;
    busy = true;

    statusElem.textContent = `QR terdeteksi: ${text}`;

    // extract id (supports URL with ?id=14 or plain "14")
    const m = text.match(/[\?&]id=(\d+)/);
    const invoiceId = m ? m[1] : text.replace(/\D/g,'');
    if (!invoiceId) {
      statusElem.textContent = 'QR tidak berisi ID invoice yang valid';
      busy = false;
      return;
    }
    loadInvoiceDetail(invoiceId);
  }

  function loadInvoiceDetail(id) {
    detailDiv.innerHTML = `<p class="text-info">Memuat invoice #${id}&hellip;</p>`;
    fetch(`index.php?page=scan&action=fetch&id=${encodeURIComponent(id)}`)
      .then(res => {
        if (!res.ok) throw new Error('Invoice tidak ditemukan');
        return res.text();
      })
      .then(html => {
        detailDiv.innerHTML = html;
        attachDecisionHandlers();
        // camera keeps running; allow next scan
        statusElem.textContent = 'Arahkan kamera ke QR code invoice';
        busy = false;
      })
      .catch(err => {
        detailDiv.innerHTML = `<div class="alert alert-warning">
          Tagihan #${id} tidak terdaftar.<br><small>${err.message}</small>
        </div>`;
        // clear after a moment, then allow scanning again
        setTimeout(() => {
          detailDiv.innerHTML = '';
          statusElem.textContent = 'Arahkan kamera ke QR code invoice';
          busy = false;
          lastText = null;
        }, 1500);
      });
  }

  function attachDecisionHandlers() {
    detailDiv.querySelectorAll('.btn-decision').forEach(btn => {
      btn.addEventListener('click', () => {
        const mode = btn.dataset.decision;  // 'approve' or 'reject'
        const id   = btn.dataset.id;
        btn.disabled = true;

        // while saving decision block duplicate handling
        busy = true;

        fetch('index.php?page=scan&action=decide', {
          method: 'POST',
          headers: { 'Content-Type':'application/x-www-form-urlencoded' },
          body: `invoice_id=${encodeURIComponent(id)}&decision=${encodeURIComponent(mode)}`
        })
        .then(r => r.json())
        .then(js => {
          if (!js.success) throw new Error('Gagal menyimpan keputusan');
          if (mode === 'reject') {
            detailDiv.innerHTML = `<div class="alert alert-warning">
              Dokumen dikirim kembali untuk revisi.
            </div>`;
          } else {
            detailDiv.innerHTML = `<div class="alert alert-success">
              Keputusan <strong>APPROVE</strong> tersimpan. Next: <em>${js.next || 'selesai'}</em>
            </div>`;
          }
        })
        .catch(err => {
          detailDiv.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        })
        .finally(() => {
          // after a short pause, clear detail and let camera continue scanning new ones
          setTimeout(() => {
            detailDiv.innerHTML = '';
            statusElem.textContent = 'Arahkan kamera ke QR code invoice';
            busy = false;
            lastText = null; // allow same QR again if needed
          }, 1200);
        });
      });
    });
  }
</script>
<?php
require __DIR__ . '/../layout/footer.php';