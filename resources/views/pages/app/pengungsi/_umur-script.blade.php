{{-- Tanggal lahir ATAU usia (sama dengan aplikasi LOGEVA Client):
     tanggal lahir diisi -> usia dihitung otomatis & dikunci;
     tanggal lahir kosong -> usia diketik langsung. --}}
<script>
document.querySelectorAll('form').forEach(function (form) {
    var lahir = form.querySelector('input[name="tanggal_lahir"]');
    var usia = form.querySelector('input[name="usia"]');
    if (!lahir || !usia) return;

    function hitung() {
        if (!lahir.value) {
            usia.readOnly = false;
            usia.classList.remove('bg-slate-100');
            return;
        }
        var t = new Date(lahir.value + 'T00:00:00');
        var n = new Date();
        var umur = n.getFullYear() - t.getFullYear();
        if (n.getMonth() < t.getMonth() ||
            (n.getMonth() === t.getMonth() && n.getDate() < t.getDate())) umur--;
        usia.value = Math.max(0, umur);
        usia.readOnly = true;
        usia.classList.add('bg-slate-100');
    }

    lahir.addEventListener('change', hitung);
    lahir.addEventListener('input', hitung);
    hitung();
});
</script>
