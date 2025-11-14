<?php
// app/views/scan/index.php
require __DIR__ . '/../layout/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

<div class="container mt-4">
    <a href="index.php?page=dashboard" class="btn btn-secondary mb-3">← Back</a>
    <h2>Scan Tagihan</h2>

    <div class="mb-3">
        <video id="video" autoplay muted playsinline style="width:100%;max-width:600px;border:1px solid #ccc"></video>
        <p id="status" class="mt-2 text-info">Arahkan kamera ke QR code invoice</p>
    </div>

    <div id="detailContainer"></div>
</div>

<script src="https://unpkg.com/@zxing/library@0.18.6/umd/index.min.js"></script>
<script>
    const codeReader = new ZXing.BrowserMultiFormatReader();
    const videoElem = document.getElementById('video');
    const statusElem = document.getElementById('status');
    const detailDiv = document.getElementById('detailContainer');

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

        statusElem.textContent = `QR terdeteksi`;

        // extract id (supports URL with ?id=14 or plain "14")
        const m = text.match(/[\?&]id=(\d+)/);
        const invoiceId = m ? m[1] : text.replace(/\D/g, '');
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
                console.log("✅ HTML invoice detail loaded");
                detailDiv.innerHTML = html;

                // ========== PENTING ========== 
                // Inisialisasi uploader SETELAH HTML dimasukkan
                if (
                    document.getElementById('dz-create') &&
                    document.getElementById('files-create') &&
                    document.getElementById('list-create')
                ) {
                    console.log("Init uploader inside AJAX-loaded invoice");
                    initMultiUploader('dz-create', 'files-create', 'list-create', {
                        maxMB: 10
                    });
                }

                attachDecisionHandlers();
                attachCloseReactiveHandlers();

                statusElem.textContent = 'Arahkan kamera ke QR code invoice';
                busy = false;
            })

            .catch(err => {
                detailDiv.innerHTML = `<div class="alert alert-warning">
                Tagihan ini tidak dapat diakses karena tidak sesuai dengan wilayah Anda.
            </div>`;
                setTimeout(() => {
                    detailDiv.innerHTML = '';
                    statusElem.textContent = 'Arahkan kamera ke QR code invoice';
                    busy = false;
                    lastText = null;
                }, 1500);
            });
    }

    function attachDecisionHandlers() {
        console.log("🔗 attachDecisionHandlers() aktif");

        detailDiv.querySelectorAll('.btn-decision').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.decision; // 'approve' or 'reject'
                const id = btn.dataset.id;
                const role = btn.dataset.role;

                console.log('=== DECISION CLICKED ===');
                console.log('Mode:', mode);
                console.log('Invoice ID:', id);
                console.log('Role:', role);

                // Ambil nomor SOJ dan MMJ (khusus ADMIN_PCS)
                const no_soj_input = document.getElementById("no_soj");
                const no_mmj_input = document.getElementById("no_mmj");
                const no_soj = no_soj_input && !no_soj_input.disabled ? no_soj_input.value.trim() : "";
                const no_mmj = no_mmj_input && !no_mmj_input.disabled ? no_mmj_input.value.trim() : "";

                // ✅ Ambil catatan sesuai role
                const note_field = document.getElementById("note_role");
                const note_value = note_field ? note_field.value.trim() : "";

                console.log('No SOJ:', no_soj);
                console.log('No MMJ:', no_mmj);
                console.log('Note:', note_value);

                // ✅ Validasi input sebelum kirim
                if (role === "ADMIN_PCS" && mode === "approve") {
                    if (!no_mmj || !no_soj) {
                        alert("⚠️ Harap isi Nomor MMJ dan Nomor SOJ sebelum melakukan approve!");
                        return;
                    }
                }

                // ✅ Validasi catatan untuk REJECT dari KEUANGAN (terutama saat REACTIVE)
                if (role === "KEUANGAN" && mode === "reject") {
                    if (!note_value) {
                        alert("⚠️ Harap isi catatan revisi sebelum melakukan reject!");
                        return;
                    }
                }

                // Konfirmasi
                let confirmMsg = mode === 'approve' ?
                    'Apakah Anda yakin ingin APPROVE invoice ini?' :
                    'Apakah Anda yakin ingin REJECT invoice ini?';

                // Pesan khusus untuk KEUANGAN REJECT (revisi)
                if (role === "KEUANGAN" && mode === "reject") {
                    confirmMsg = 'Invoice akan dikirim ke ADMIN PCS untuk revisi. Lanjutkan?';
                }

                if (!confirm(confirmMsg)) {
                    return;
                }

                btn.disabled = true;
                busy = true;

                // ✅ Siapkan FormData untuk mengirim file juga
                const formData = new FormData();
                formData.append('invoice_id', id);
                formData.append('decision', mode);
                formData.append('no_mmj', no_mmj);
                formData.append('no_soj', no_soj);

                // ✅ Tambahkan catatan sesuai role
                if (role === 'ADMIN_WILAYAH') {
                    formData.append('note_admin_wilayah', note_value);
                } else if (role === 'PERWAKILAN_PI') {
                    formData.append('note_perwakilan_pi', note_value);
                } else if (role === 'ADMIN_PCS') {
                    formData.append('note_admin_pcs', note_value);

                    // === FIX FILE UPLOAD (pakai dt.files, bukan input.files) ===
                    const fileInput = document.getElementById('files-create');

                    let filesToAppend = [];

                    if (fileInput && fileInput._dt && fileInput._dt.files.length > 0) {
                        filesToAppend = fileInput._dt.files; // ambil dari DataTransfer
                    }

                    for (let i = 0; i < filesToAppend.length; i++) {
                        formData.append('files[]', filesToAppend[i]);
                    }
                } else if (role === 'KEUANGAN') {
                    formData.append('note_keuangan', note_value);
                }

                console.log('Data yang akan dikirim:', {
                    invoice_id: id,
                    decision: mode,
                    no_mmj: no_mmj,
                    no_soj: no_soj,
                    note: note_value,
                    files_count: role === 'ADMIN_PCS' && document.getElementById('files-create')
                        ?.files?.length || 0
                });

                fetch('index.php?page=scan&action=decide', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => {
                        console.log('Response status:', r.status);
                        return r.json();
                    })
                    .then(js => {
                        console.log("Respon dari server:", js);
                        if (!js.success) throw new Error(js.message || 'Gagal menyimpan keputusan');

                        if (mode === 'reject') {
                            detailDiv.innerHTML = `<div class="alert alert-warning">
                            <strong>✓ Invoice dikirim untuk revisi ke ${js.next || 'role sebelumnya'}.</strong>
                            ${note_value ? '<br><small>Catatan: ' + note_value + '</small>' : ''}
                        </div>`;
                        } else {
                            detailDiv.innerHTML = `<div class="alert alert-success">
                            <strong>✓ Keputusan APPROVE tersimpan.</strong><br>
                            Next: <em>${js.next || 'SELESAI'}</em>
                            ${note_value ? '<br><small>Catatan: ' + note_value + '</small>' : ''}
                        </div>`;
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        detailDiv.innerHTML = `<div class="alert alert-danger">
                        <strong>✗ Error:</strong> ${err.message}
                    </div>`;
                        btn.disabled = false;
                    })
                    .finally(() => {
                        setTimeout(() => {
                            detailDiv.innerHTML = '';
                            statusElem.textContent = 'Arahkan kamera ke QR code invoice';
                            busy = false;
                            lastText = null;
                        }, 2500);
                    });
            });
        });
    }

    // ✅ Handler untuk CLOSE & REACTIVE
    function attachCloseReactiveHandlers() {
        console.log("🔗 attachCloseReactiveHandlers() aktif");

        // Handler untuk tombol CLOSE
        const btnClose = detailDiv.querySelector('#btnClose');
        if (btnClose) {
            btnClose.addEventListener('click', () => {
                const id = btnClose.dataset.id;

                console.log('=== CLOSE CLICKED ===');
                console.log('Invoice ID:', id);

                if (!confirm('Apakah Anda yakin ingin MENUTUP invoice ini? Invoice akan selesai.')) {
                    return;
                }

                btnClose.disabled = true;
                busy = true;

                const formData = new FormData();
                formData.append('invoice_id', id);

                // Ambil catatan keuangan
                const note_field = document.getElementById("note_role");
                const note_value = note_field ? note_field.value.trim() : "";
                if (note_value) {
                    formData.append('note_keuangan', note_value);
                }

                console.log('Data CLOSE:', {
                    invoice_id: id,
                    note_keuangan: note_value
                });

                fetch('index.php?page=scan&action=close', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => {
                        console.log('Response status:', r.status);
                        return r.json();
                    })
                    .then(js => {
                        console.log("Respon CLOSE dari server:", js);
                        if (!js.success) throw new Error(js.message || 'Gagal menutup invoice');

                        detailDiv.innerHTML = `<div class="alert alert-success">
                    <strong>✓ Invoice berhasil ditutup (CLOSE)</strong><br>
                    <small>Invoice sudah selesai diproses</small>
                </div>`;
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        detailDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>✗ Error:</strong> ${err.message}
                </div>`;
                        btnClose.disabled = false;
                    })
                    .finally(() => {
                        setTimeout(() => {
                            detailDiv.innerHTML = '';
                            statusElem.textContent = 'Arahkan kamera ke QR code invoice';
                            busy = false;
                            lastText = null;
                        }, 2500);
                    });
            });
        }

        // Handler untuk tombol REACTIVE
        const btnReactive = detailDiv.querySelector('#btnReactive');
        if (btnReactive) {
            btnReactive.addEventListener('click', () => {
                const id = btnReactive.dataset.id;

                console.log('=== REACTIVE CLICKED ===');
                console.log('Invoice ID:', id);

                if (!confirm('Apakah Anda yakin ingin mengaktifkan kembali invoice ini untuk revisi?')) {
                    return;
                }

                btnReactive.disabled = true;
                busy = true;

                const formData = new FormData();
                formData.append('invoice_id', id);

                console.log('Data REACTIVE:', {
                    invoice_id: id
                });

                fetch('index.php?page=scan&action=reactive', {
                        method: 'POST',
                        body: formData
                    })
                    .then(r => {
                        console.log('Response status:', r.status);
                        return r.json();
                    })
                    .then(js => {
                        console.log("Respon REACTIVE dari server:", js);
                        if (!js.success) throw new Error(js.message || 'Gagal mengaktifkan kembali invoice');

                        detailDiv.innerHTML = `<div class="alert alert-success">
                    <strong>✓ Invoice berhasil diaktifkan kembali</strong><br>
                    <small>Current role: ${js.current_role}</small>
                </div>`;
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        detailDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>✗ Error:</strong> ${err.message}
                </div>`;
                        btnReactive.disabled = false;
                    })
                    .finally(() => {
                        setTimeout(() => {
                            detailDiv.innerHTML = '';
                            statusElem.textContent = 'Arahkan kamera ke QR code invoice';
                            busy = false;
                            lastText = null;
                        }, 2500);
                    });
            });
        }
    }
</script>
<?php
require __DIR__ . '/../layout/footer.php';
?>