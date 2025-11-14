<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


<!-- Script untuk multiple select saad memilih wilayah -->
<script>
    $(document).ready(function() {
        // Saat modal dibuka, aktifkan Select2
        $('#tambahUserModal').on('shown.bs.modal', function() {
            $('#id_wilayah').select2({
                dropdownParent: $('#tambahUserModal'),
                placeholder: "-- Pilih Wilayah --",
                theme: 'bootstrap-5',
                allowClear: true,
                width: '100%'
            });
        });
    });
</script>


<!-- ================= SCRIPT upload file di role admin pcs ================= -->
<script>
   
    function initMultiUploader(zoneId, inputId, listId, options = {}) {
        const zone = document.getElementById(zoneId);
        const input = document.getElementById(inputId);
        const list = document.getElementById(listId);

        if (!zone || !input || !list) {
            console.log("Uploader element not found:", {
                zone,
                input,
                list
            });
            return;
        }

        const MAX_BYTES = (options.maxMB || 10) * 1024 * 1024;
        const ALLOWED = (options.allowed || ['pdf', 'png', 'jpg', 'jpeg', 'xls', 'xlsx'])
            .map(x => x.toLowerCase());

        // DataTransfer sebagai sumber kebenaran (tidak wajib dipindahkan ke input.files)
        const dt = new DataTransfer();
        // expose supaya bagian lain (submit) bisa mengambil file
        input._dt = dt;

        const extOf = name => (name.split('.').pop() || '').toLowerCase();
        const labelOf = name => extOf(name).toUpperCase();

        function renderList() {
            list.innerHTML = '';
            Array.from(dt.files).forEach((f, idx) => {
                const li = document.createElement('li');
                li.className = 'file-pill';
                li.innerHTML = `
                <span class="file-badge">${labelOf(f.name)}</span>
                <span class="flex-grow-1 text-truncate">${f.name}</span>
                <button type="button" class="file-remove" aria-label="Hapus">&times;</button>
            `;
                li.querySelector('.file-remove').onclick = () => {
                    const tmp = new DataTransfer();
                    Array.from(dt.files).forEach((ff, i) => {
                        if (i !== idx) tmp.items.add(ff);
                    });
                    dt.items.clear();
                    Array.from(tmp.files).forEach(ff => dt.items.add(ff));
                    // update exposed dt
                    input._dt = dt;
                    renderList();
                };
                list.appendChild(li);
            });
        }

        function acceptFiles(files) {
            Array.from(files).forEach(f => {
                const ext = extOf(f.name);
                if (!ALLOWED.includes(ext)) {
                    alert(`Format tidak diizinkan: ${f.name}`);
                    return;
                }
                if (f.size > MAX_BYTES) {
                    alert(`Ukuran terlalu besar: ${f.name}`);
                    return;
                }
                // avoid duplicates by name+size (optional)
                const exists = Array.from(dt.files).some(x => x.name === f.name && x.size === f.size);
                if (!exists) dt.items.add(f);
            });

            // update exposed dt
            input._dt = dt;

            // render list from dt
            renderList();

            // allow re-select same file later
            try {
                input.value = '';
            } catch (e) {
                /* ignore */ }
        }

       

        zone.onclick = () => input.click();
        input.onchange = (e) => acceptFiles(e.target.files);

        // drag / drop
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            zone.addEventListener(ev, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });
        zone.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                acceptFiles(e.dataTransfer.files);
            }
        });

        // keyboard accessibility: Enter/Space open dialog
        zone.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                input.click();
            }
        });
    }
</script>
</body>

</html>