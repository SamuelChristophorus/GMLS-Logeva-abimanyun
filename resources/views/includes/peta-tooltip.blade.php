{{--
    Kartu ringkasan saat titik posko di-hover (dipakai peta publik & peta panel admin).
    Pemakaian (setelah leaflet.js dimuat):
        @include('includes.peta-tooltip')
        TeaPeta.pasang(map, marker, htmlKartu, urlDetail, radiusTitik);

    - Hover  : kartu muncul, otomatis memilih sisi (atas/bawah/kanan/kiri) yang ruangnya cukup
               sehingga tidak terpotong tepi peta.
    - Klik   : membuka halaman detail.
    - HP     : ketuk pertama tampilkan kartu, ketuk kedua buka detail.
--}}
@once
<style>
    .leaflet-tooltip.tea-tooltip {
        padding: 0; border: 0; border-radius: 14px; overflow: hidden;
        box-shadow: 0 12px 32px -8px rgba(15, 23, 42, .35); white-space: normal;
    }
    .leaflet-tooltip-top.tea-tooltip::before    { border-top-color: #fff; }
    .leaflet-tooltip-bottom.tea-tooltip::before { border-bottom-color: #fff; }
    .leaflet-tooltip-right.tea-tooltip::before  { border-right-color: #fff; }
    .leaflet-tooltip-left.tea-tooltip::before   { border-left-color: #fff; }

    /* Lencana "!" di pojok titik untuk posko yang butuh bantuan */
    .tea-seru {
        display: inline-flex; align-items: center; justify-content: center;
        width: 16px; height: 16px; border-radius: 9999px; border: 2px solid #fff;
        font-size: 9px; font-weight: 900; line-height: 1; color: #fff;
        box-shadow: 0 1px 4px rgba(15, 23, 42, .4);
    }
    .tea-seru-segera { background: #dc2626; }
    .tea-seru-pantau { background: #f59e0b; color: #451a03; }
</style>

<script>
window.TeaPeta = (function () {
    const JARAK = 10;                                   // jarak minimal kartu ke tepi peta (px)
    const tanpaHover = window.matchMedia('(hover: none)').matches;
    const terakhirDiketuk = new WeakMap();              // per peta

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    /**
     * Pilih sisi titik yang ruangnya cukup: atas -> bawah -> kanan -> kiri.
     * r = jari-jari titik (px); tooltipAnchor ikon harus [0, -r].
     */
    function posisikan(map, marker, tooltip, r) {
        const el = tooltip.getElement();
        if (!el) return;

        const titik = map.latLngToContainerPoint(marker.getLatLng());
        const peta  = map.getSize();
        const w = el.offsetWidth, h = el.offsetHeight;
        const pasHorizontal = titik.x - w / 2 >= JARAK && titik.x + w / 2 <= peta.x - JARAK;
        const pasVertikal   = titik.y - h / 2 >= JARAK && titik.y + h / 2 <= peta.y - JARAK;

        let arah = 'top', offset = [0, 0];
        if (pasHorizontal && titik.y - r - h - JARAK >= 0) {
            arah = 'top';
        } else if (pasHorizontal && titik.y + r + h + JARAK <= peta.y) {
            arah = 'bottom'; offset = [0, 2 * r];
        } else if (titik.x + r + w + JARAK <= peta.x) {
            arah = 'right';  offset = [r, r];
        } else {
            arah = 'left';   offset = [-r, r];
        }

        // Kanan/kiri: geser naik/turun bila kartu melewati tepi atas/bawah
        if ((arah === 'right' || arah === 'left') && !pasVertikal) {
            const atas = titik.y - h / 2;
            offset[1] += atas < JARAK ? JARAK - atas : (peta.y - JARAK) - (titik.y + h / 2);
        }

        if (tooltip.options.direction !== arah || tooltip.options.offset.toString() !== offset.toString()) {
            tooltip.options.direction = arah;
            tooltip.options.offset = offset;
            tooltip.update();
        }
    }

    /** Pasang kartu hover + klik-ke-detail pada satu titik. */
    function pasang(map, marker, html, url, r = 16) {
        marker.bindTooltip(html, { direction: 'top', className: 'tea-tooltip', opacity: 1 });
        marker.on('tooltipopen', (e) => posisikan(map, marker, e.tooltip, r));

        // Keyboard: Tab ke titik lalu Enter -> Leaflet meneruskannya sebagai 'click'
        marker.on('click', () => {
            if (tanpaHover && terakhirDiketuk.get(map) !== marker) {
                terakhirDiketuk.set(map, marker);
                marker.openTooltip();
                return;
            }
            window.location.href = url;
        });

        if (!map._teaKlikPeta) {
            map.on('click', () => terakhirDiketuk.delete(map));
            map._teaKlikPeta = true;
        }
    }

    return { esc, pasang, posisikan };
})();
</script>
@endonce
