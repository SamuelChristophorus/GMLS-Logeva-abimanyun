{{--
    Input tanggal berformat Indonesia untuk SEMUA <input type="date"> (dipasang di layout dashboard & admin).

    - Tampil   : hh/bb/tttt, kalender berbahasa Indonesia (Januari, Senin, ...).
    - Dikirim  : tetap YYYY-MM-DD (nilai input asli), jadi controller & validasi tidak perlu diubah.
    - min / max / required / disabled pada input asli tetap berlaku.
    - Di HP memakai pemilih tanggal bawaan HP (mengikuti bahasa HP).
    - Jika CDN gagal dimuat, input tanggal bawaan browser tetap dipakai.
--}}
@once
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
<style>
    .flatpickr-day.selected, .flatpickr-day.selected:hover { background: #dc2626; border-color: #dc2626; }
    .flatpickr-day.today { border-color: #dc2626; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 700; }
</style>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/id.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.flatpickr) return;

    document.querySelectorAll('input[type="date"]').forEach(function (el) {
        var fp = flatpickr(el, {
            locale: 'id',
            dateFormat: 'Y-m-d',   // yang dikirim ke server
            altInput: true,
            altFormat: 'd/m/Y',    // yang dilihat & diketik pengguna
            allowInput: true,      // boleh diketik langsung, mis. tanggal lahir 25/07/1958
            minDate: el.min || null,
            maxDate: el.max || null,
        });

        var alt = fp.altInput;
        if (!alt) return; // HP: pemilih bawaan, tidak ada altInput
        alt.placeholder = 'hh/bb/tttt';
        alt.inputMode = 'numeric';
        alt.autocomplete = 'off';
        alt.required = el.required;   // agar pesan "wajib diisi" browser tetap muncul
        alt.disabled = el.disabled;

        // Ikuti perubahan disabled dari Alpine (contoh: form donasi)
        new MutationObserver(function () { alt.disabled = el.disabled; })
            .observe(el, { attributes: true, attributeFilter: ['disabled'] });
    });
});
</script>
@endonce
