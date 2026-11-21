{{-- Menu titik tiga + konfirmasi hapus --}}
<script>
(function () {
    // Menu titik tiga: dibuka sebagai panel "fixed" supaya tidak terpotong oleh tabel yang bisa di-scroll.
    var open = null;
    function close() { if (open) { open.removeAttribute('open'); var m = open.querySelector('.sh-menu'); if (m) { m.hidden = true; } open = null; } }
    document.querySelectorAll('.sh-kebab').forEach(function (d) {
        d.addEventListener('toggle', function () {
            var m = d.querySelector('.sh-menu'); if (!m) { return; }
            if (d.open) {
                if (open && open !== d) { close(); }
                var r = d.querySelector('summary').getBoundingClientRect();
                m.hidden = false;
                var w = m.offsetWidth, h = m.offsetHeight;
                m.style.left = Math.max(8, Math.min(window.innerWidth - w - 8, r.right - w)) + 'px';
                m.style.top = (r.bottom + h + 8 > window.innerHeight ? Math.max(8, r.top - h - 6) : r.bottom + 6) + 'px';
                open = d;
            } else { m.hidden = true; if (open === d) { open = null; } }
        });
    });
    document.addEventListener('click', function (e) { if (open && !open.contains(e.target)) { close(); } });
    window.addEventListener('scroll', close, true);
    window.addEventListener('resize', close);

    // Konfirmasi hapus (ikut bahasa yang dipilih)
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            var msg = f.getAttribute('data-confirm'); if (window.CT && CT.tx) { msg = CT.tx(msg); }
            if (!window.confirm(msg)) { e.preventDefault(); }
        });
    });
})();
</script>
