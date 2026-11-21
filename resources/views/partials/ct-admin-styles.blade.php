{{-- Gaya bersama halaman admin bernuansa pink-hijau (Pengangkut, Lokasi): hero, filter, tabel, aksi, dialog --}}
<style>
    /* ===== Kepala halaman (hero) dengan ilustrasi truk ===== */
    .ct-hero { position: relative; display: grid; grid-template-columns: auto minmax(0, 1fr); align-items: center; min-height: 11rem; margin-bottom: 1.1rem; border-radius: 28px; overflow: hidden; }
    .ct-hero .sh-title { position: relative; z-index: 2; padding: .5rem .25rem; }
    .ct-hero .sh-title .ico { width: 6.2rem; height: 6.2rem; border-radius: 9999px; box-shadow: 0 14px 28px -10px rgba(233,30,140,.55), inset 0 -6px 12px rgba(200,20,110,.25); }
    .ct-hero .sh-title .ico svg { width: 3.2rem; height: 3.2rem; }
    .ct-hero .sh-title h1 { font-size: 2.25rem; color: #3B1672; }
    .ct-hero-art { align-self: end; justify-self: end; width: 100%; max-width: 46rem; height: 11rem; min-width: 0; pointer-events: none; z-index: 1; }
    @media (max-width: 1279px) { .ct-hero-art { display: none; } }
    .ct-hero-art svg { display: block; width: 100%; height: 100%; }
    .ct-add { position: absolute; right: 1.5rem; bottom: 1rem; z-index: 3; display: inline-flex; align-items: center; gap: .55rem; height: 2.9rem; padding: 0 1.5rem; border: 0; border-radius: 9999px; color: #fff; font-weight: 800; font-size: .9rem; cursor: pointer; background: linear-gradient(90deg, #FF6FAE, #F0508F); box-shadow: 0 12px 24px -10px rgba(233,30,140,.75); transition: transform .15s, box-shadow .15s; }
    .ct-add:hover { transform: translateY(-1px); box-shadow: 0 16px 28px -10px rgba(233,30,140,.8); }
    .ct-add svg { width: 1.1rem; height: 1.1rem; }
    @media (max-width: 767px) {
        .ct-hero { display: flex; flex-direction: column; align-items: flex-start; gap: .75rem; padding-bottom: 3.9rem; }
        .ct-hero .sh-title .ico { width: 4.2rem; height: 4.2rem; } .ct-hero .sh-title .ico svg { width: 2.2rem; height: 2.2rem; }
        .ct-hero .sh-title h1 { font-size: 1.7rem; }
        .ct-hero-art { display: none; }
        .ct-hero .sh-title .ico { flex: none; }
        .ct-add { left: 1rem; right: auto; bottom: .9rem; }
    }

    /* ===== Filter ===== */
    .ct-filter { display: grid; gap: 1rem 1.1rem; align-items: end; grid-template-columns: 1fr; }
    @media (min-width: 768px)  { .ct-filter { grid-template-columns: 1fr 1fr; } }
    @media (min-width: 1280px) { .ct-filter { grid-template-columns: 1.15fr 1fr 1fr auto auto; } }
    .ct-filter, .ct-filter *, dialog.ct-dlg, dialog.ct-dlg * { box-sizing: border-box; }
    .ct-filter > * { min-width: 0; }
    .ct-filter .sh-fld, .ct-filter .sh-sel { background-color: #fff; }
    .ct-filter a.sh-btn { text-decoration: none; }
    .ct-filter .sh-btn { height: 3.1rem; }

    /* ===== Tabel ===== */
    .ct-tbl { min-width: 54rem; }
    .ct-tbl thead th { background: #FFF0F6; font-size: .92rem; padding: 1rem .75rem; }
    .ct-tbl tbody td { font-size: .92rem; padding: .85rem .75rem; border-bottom-color: #F5E6EC; }
    .ct-tbl tbody td.ct-name { font-weight: 600; color: #1B1F18; }
    .ct-tbl th.ct-c, .ct-tbl td.ct-c { text-align: center; }
    .ct-acts { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; }
    .ct-act { display: inline-flex; align-items: center; gap: .4rem; padding: .42rem .85rem; border: 0; border-radius: 9999px; font-size: .8rem; font-weight: 700; line-height: 1; cursor: pointer; transition: filter .15s, transform .15s; box-shadow: 0 6px 12px -8px rgba(0,0,0,.25); }
    .ct-act:hover { filter: brightness(.96); transform: translateY(-1px); }
    .ct-act svg { width: .95rem; height: .95rem; }
    .ct-act-detail { background: #FFE3EE; color: #E91E8C; }
    .ct-act-edit { background: #E3F0FF; color: #2A6BD1; }
    .ct-act-del { background: #FFE1E8; color: #C81E4F; }
    .ct-mode { display: inline-flex; align-items: center; gap: .35rem; padding: .15rem .6rem; border-radius: 9999px; background: #F1F8E6; color: #3F5729; font-size: .72rem; font-weight: 700; }

    /* ===== Dialog ===== */
    dialog.ct-dlg { width: min(32rem, calc(100vw - 2rem)); max-height: calc(100vh - 2rem); padding: 0; border: 0; border-radius: 26px; background: #fff; color: #2B3027; box-shadow: 0 30px 70px -20px rgba(120,20,70,.45); overflow: auto; }
    dialog.ct-dlg::backdrop { background: rgba(60, 20, 50, .38); backdrop-filter: blur(3px); }
    .ct-dlg-h { display: flex; align-items: center; gap: .8rem; padding: 1.3rem 1.5rem; background: linear-gradient(90deg, #FFE3EE, #F1F8E6); }
    .ct-dlg-h .ico { display: grid; place-items: center; width: 2.6rem; height: 2.6rem; border-radius: 9999px; color: #fff; background: linear-gradient(135deg, #FF7DB5, #F0508F); }
    .ct-dlg-h .ico svg { width: 1.4rem; height: 1.4rem; }
    .ct-dlg-h h3 { font-size: 1.15rem; font-weight: 800; color: #1B1F18; }
    .ct-dlg-x { margin-left: auto; display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border: 0; border-radius: 9999px; background: rgba(255,255,255,.8); color: #2B3027; cursor: pointer; }
    .ct-dlg-x:hover { background: #fff; color: #E91E8C; }
    .ct-dlg-x svg { width: 1.1rem; height: 1.1rem; }
    .ct-dlg-b { padding: 1.3rem 1.5rem; display: grid; gap: 1rem; }
    .ct-dlg-f { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .6rem; padding: 0 1.5rem 1.4rem; }
    .ct-dlg-f .sh-btn { height: 2.7rem; padding: 0 1.4rem; font-size: .88rem; }
    .ct-sw { display: flex; align-items: center; gap: .6rem; font-size: .9rem; font-weight: 700; cursor: pointer; }
    .ct-sw input { width: 1.1rem; height: 1.1rem; accent-color: #F0508F; }
    .ct-dl { display: grid; grid-template-columns: 9.5rem 1fr; gap: .7rem 1rem; font-size: .92rem; }
    .ct-dl dt { color: #565E4E; font-weight: 600; } .ct-dl dd { color: #1B1F18; font-weight: 700; word-break: break-word; }
    .ct-del-msg { font-size: .95rem; line-height: 1.5; } .ct-del-msg b { color: #C81E4F; }
    .sh-btn-danger { color: #fff; border: 0; background: linear-gradient(90deg, #F25C85, #D9254F); box-shadow: 0 10px 22px -10px rgba(217,37,79,.7); }
</style>
