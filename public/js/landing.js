/* CargoTrace – perilaku landing page tamu: navbar, link aktif, animasi muncul, tombol ke atas, dan pembuka popup login/daftar. */
(function () {
    'use strict';
    var root = document.documentElement;
    root.classList.add('lp-js');

    var nav = document.getElementById('lp-nav');
    var topBtn = document.getElementById('lp-top');

    function onScroll() {
        var y = window.pageYOffset || root.scrollTop;
        if (nav) { nav.classList.toggle('is-scrolled', y > 60); }
        if (topBtn) { topBtn.classList.toggle('is-on', y > 600); }
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    if (topBtn) { topBtn.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); }); }

    /* Animasi muncul saat di-scroll (tanpa IntersectionObserver: langsung tampil) */
    var items = Array.prototype.slice.call(document.querySelectorAll('.lp-reveal'));
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
        }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
        items.forEach(function (el) { io.observe(el); });
    } else {
        items.forEach(function (el) { el.classList.add('is-in'); });
    }

    /* Menu aktif mengikuti section yang sedang dilihat */
    var links = Array.prototype.slice.call(document.querySelectorAll('.lp-links [data-lp-link]'));
    var sections = links.map(function (a) { return document.getElementById(a.getAttribute('data-lp-link')); });
    function setActive() {
        var line = (window.innerHeight * 0.35), current = 0;
        sections.forEach(function (s, i) { if (s && s.getBoundingClientRect().top <= line) { current = i; } });
        links.forEach(function (a, i) { a.classList.toggle('is-active', i === current); });
    }
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();

    /* Tombol di landing yang membuka popup login / daftar (popup-nya dibuat oleh partials/auth-pill).
       Memakai data-lp-open agar tidak ganda dengan listener data-open-reg milik auth-pill. */
    document.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('[data-lp-open]') : null;
        if (!b) { return; }
        var m = document.getElementById('reg-modal-' + b.getAttribute('data-lp-open'));
        if (!m) { return; }
        m.hidden = false;
        document.body.style.overflow = 'hidden';
        var f = m.querySelector('input'); if (f) { f.focus(); }
    });
})();
