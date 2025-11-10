<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Saat modal dibuka, aktifkan Select2
    $('#tambahUserModal').on('shown.bs.modal', function () {
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
</body>
</html>
