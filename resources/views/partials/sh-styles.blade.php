{{-- Gaya bersama halaman bernuansa pink-hijau (Pengiriman & Laporan) --}}
<style>
    /* Tombol "Pengiriman baru" sudah ada di kepala halaman (sesuai desain), jadi tombol di topbar disembunyikan di halaman ini */
    header a.btn-accent { display: none !important; }

    .sh-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
    .sh-title { display: flex; align-items: center; gap: 1rem; }
    .sh-title .ico { display: grid; place-items: center; width: 4.1rem; height: 4.1rem; border-radius: 1.2rem; color: #fff; background: linear-gradient(135deg, #FF7DB5, #F0508F); box-shadow: 0 10px 22px -8px rgba(233,30,140,.55); }
    .sh-title .ico svg { width: 2.2rem; height: 2.2rem; }
    .sh-title h1 { font-size: 2rem; font-weight: 800; line-height: 1.1; color: #1B1F18; letter-spacing: -.01em; }
    .sh-title p { margin-top: .25rem; font-size: .9rem; color: #3F463A; }
    .sh-new { display: inline-flex; align-items: center; gap: .6rem; padding: .85rem 1.7rem; border-radius: 9999px; color: #fff; font-weight: 800; font-size: 1rem; background: linear-gradient(90deg, #FF6FAE, #F0508F); box-shadow: 0 12px 24px -10px rgba(233,30,140,.65); transition: transform .15s, box-shadow .15s; }
    .sh-new:hover { transform: translateY(-1px); box-shadow: 0 16px 28px -10px rgba(233,30,140,.75); }
    .sh-new svg { width: 1.35rem; height: 1.35rem; }

    .sh-panel { background: rgba(255,255,255,.82); border: 1px solid rgba(255,255,255,.95); border-radius: 26px; box-shadow: 0 14px 40px -18px rgba(233,30,140,.28); padding: 1.4rem 1.5rem; }
    .sh-filters { display: grid; gap: 1rem 1.1rem; }
    .sh-row { display: grid; gap: 1rem 1.1rem; grid-template-columns: repeat(1, minmax(0, 1fr)); }
    @media (min-width: 640px)  { .sh-row-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); } .sh-row-5 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .sh-row-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); } .sh-row-5 { grid-template-columns: repeat(5, minmax(0, 1fr)); } .sh-row-3 { grid-template-columns: 1fr 1fr auto auto; align-items: end; } }
    .sh-lbl { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 700; color: #1B1F18; }
    .sh-fld, .sh-sel, .sh-date { width: 100%; height: 2.9rem; border-radius: 1rem; border: 1px solid #F6D2E1; background: #fff; padding: 0 1rem; font-size: .88rem; color: #2B3027; transition: border-color .15s, box-shadow .15s; }
    .sh-fld:focus, .sh-sel:focus, .sh-date:focus { outline: none; border-color: #F0508F; box-shadow: 0 0 0 3px rgba(240,80,143,.18); }
    .sh-fld::placeholder { color: #9AA290; }
    .sh-search { position: relative; }
    .sh-search svg { position: absolute; left: .95rem; top: 50%; width: 1.15rem; height: 1.15rem; transform: translateY(-50%); color: #2B3027; pointer-events: none; }
    .sh-search .sh-fld { padding-left: 2.7rem; }
    .sh-sel { appearance: none; -webkit-appearance: none; padding-right: 2.4rem; cursor: pointer; background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23E91E8C' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><path d='m6 9 6 6 6-6'/></svg>"); background-repeat: no-repeat; background-position: right .85rem center; background-size: 1.1rem; }
    .sh-sel-green { background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23506E33' stroke-width='2.4' stroke-linecap='round' stroke-linejoin='round'><path d='m6 9 6 6 6-6'/></svg>"); }
    .sh-date { color: #2B3027; }
    .sh-btn { display: inline-flex; align-items: center; justify-content: center; gap: .55rem; height: 3rem; padding: 0 1.9rem; border-radius: 9999px; font-weight: 800; font-size: .95rem; white-space: nowrap; transition: transform .15s, box-shadow .15s; cursor: pointer; }
    .sh-btn svg { width: 1.15rem; height: 1.15rem; }
    .sh-btn-apply { color: #fff; border: 0; background: linear-gradient(90deg, #FF6FAE, #F0508F); box-shadow: 0 10px 22px -10px rgba(233,30,140,.7); }
    .sh-btn-reset { color: #3F5729; background: #fff; border: 1.5px solid #86A85D; }
    .sh-btn:hover { transform: translateY(-1px); }

    .sh-table-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .sh-table-head h2 { display: flex; align-items: center; gap: .75rem; font-size: 1.15rem; font-weight: 800; color: #1B1F18; }
    .sh-table-head h2 small { font-size: .85rem; font-weight: 500; color: #3F463A; }
    .sh-table-head .ico { display: grid; place-items: center; width: 2.1rem; height: 2.1rem; border-radius: 9999px; color: #fff; background: linear-gradient(135deg, #FF7DB5, #F0508F); }
    .sh-table-head .ico svg { width: 1.1rem; height: 1.1rem; }
    .sh-per { display: flex; align-items: center; gap: .6rem; font-size: .85rem; color: #2B3027; }
    .sh-per .sh-sel { width: auto; min-width: 6.6rem; height: 2.4rem; border-radius: .8rem; border-color: #E7E9E2; padding-right: 2rem; }
    .sh-arrow { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; border-radius: .8rem; background: #fff; border: 1px solid #E7E9E2; color: #2B3027; }
    .sh-arrow svg { width: 1.1rem; height: 1.1rem; }
    a.sh-arrow:hover { border-color: #F0508F; color: #E91E8C; }
    .sh-arrow-off { opacity: .45; }

    .sh-tbl-wrap { overflow-x: auto; border-radius: 1.1rem; }
    .sh-tbl { width: 100%; min-width: 72rem; border-collapse: separate; border-spacing: 0; }
    .sh-tbl thead th { background: #FFE3EE; padding: .8rem .75rem; text-align: left; font-size: .8rem; font-weight: 800; color: #1B1F18; line-height: 1.2; white-space: nowrap; }
    .sh-tbl thead th:first-child { border-radius: 1.1rem 0 0 1.1rem; padding-left: 1rem; }
    .sh-tbl thead th:last-child { border-radius: 0 1.1rem 1.1rem 0; }
    .sh-tbl tbody td { padding: .8rem .75rem; font-size: .8rem; color: #2B3027; border-bottom: 1px solid #F3E4EB; vertical-align: middle; line-height: 1.25; }
    .sh-tbl tbody td:first-child { padding-left: 1rem; }
    .sh-tbl tbody tr:last-child td { border-bottom: 0; }
    .sh-tbl tbody tr:hover td { background: rgba(255,227,238,.35); }
    .sh-num { font-weight: 800; color: #1B1F18; font-size: .82rem; }
    .sh-sub { font-size: .76rem; color: #565E4E; }
    .sh-mono { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .sh-badge { display: inline-flex; align-items: center; gap: .45rem; padding: .35rem .8rem .35rem .65rem; border-radius: 9999px; font-size: .76rem; font-weight: 700; line-height: 1.1; }
    .sh-badge i { flex: none; width: .8rem; height: .8rem; border-radius: 9999px; }
    .sh-b-green  { background: #DDF3C8; color: #2F6B12; } .sh-b-green i  { background: #22B14C; }
    .sh-b-blue   { background: #D6EBFF; color: #1D5FA8; } .sh-b-blue i   { background: #2D8CF0; }
    .sh-b-orange { background: #FFEBD0; color: #B45309; } .sh-b-orange i { background: #F59E0B; }
    .sh-b-red    { background: #FFE4EA; color: #C81E4F; font-weight: 800; } .sh-b-red i { background: #E11D48; }
    .sh-b-grey   { background: #E9EAE6; color: #565E4E; } .sh-b-grey i   { background: #8A9080; }
    .sh-b-sky    { background: #D8F0F7; color: #0B6E86; } .sh-b-sky i    { background: #0EA5C4; }
    .sh-b-rose   { background: #F6E6EA; color: #9B3A52; } .sh-b-rose i   { background: #C0526E; }
    .sh-goods { font-weight: 800; color: #1B1F18; font-size: .82rem; line-height: 1.25; }
    .sh-more { display: inline-block; margin-left: .4rem; padding: .05rem .5rem; border-radius: 9999px; background: #FFE3EE; color: #E91E8C; font-size: .7rem; font-weight: 800; vertical-align: middle; }
    .sh-late { font-weight: 800; color: #E11D48; }
    .sh-detail { display: inline-flex; align-items: center; gap: .45rem; padding: .45rem .95rem; border-radius: 9999px; background: #FFE3EE; color: #E91E8C; font-weight: 800; font-size: .78rem; transition: background .15s; }
    .sh-detail:hover { background: #FFD0E3; }
    .sh-detail svg { width: 1rem; height: 1rem; }
    .sh-kebab { display: inline-block; vertical-align: middle; }
    .sh-kebab summary { list-style: none; cursor: pointer; display: grid; place-items: center; width: 2rem; height: 2rem; border-radius: 9999px; color: #2B3027; }
    .sh-kebab summary::-webkit-details-marker { display: none; }
    .sh-kebab summary:hover { background: #FFE3EE; color: #E91E8C; }
    .sh-kebab summary svg { width: 1.2rem; height: 1.2rem; }
    .sh-menu { position: fixed; z-index: 2000; min-width: 9rem; padding: .4rem; border-radius: 1rem; background: #fff; box-shadow: 0 16px 36px -10px rgba(0,0,0,.28); }
    .sh-menu a, .sh-menu button { display: block; width: 100%; text-align: left; padding: .55rem .8rem; border-radius: .7rem; font-size: .85rem; font-weight: 700; color: #2B3027; }
    .sh-menu a:hover { background: #FFF0F6; color: #E91E8C; }
    .sh-menu button { color: #C81E4F; } .sh-menu button:hover { background: #FFE4EA; }
    .sh-empty { padding: 3rem 1rem; text-align: center; color: #565E4E; font-size: .95rem; }

    .sh-foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap; margin-top: 1rem; padding-top: .9rem; border-top: 1px solid #F3E4EB; font-size: .85rem; font-weight: 600; color: #1B1F18; }
    .sh-pager { display: flex; align-items: center; gap: .45rem; }
    .sh-pg { display: grid; place-items: center; min-width: 2.3rem; height: 2.3rem; padding: 0 .5rem; border-radius: .75rem; background: #fff; border: 1px solid #E7E9E2; font-size: .85rem; font-weight: 700; color: #2B3027; }
    .sh-pg svg { width: 1.05rem; height: 1.05rem; }
    a.sh-pg:hover { border-color: #F0508F; color: #E91E8C; }
    .sh-pg-on { background: linear-gradient(135deg, #FF6FAE, #F0508F); border-color: transparent; color: #fff; box-shadow: 0 8px 16px -8px rgba(233,30,140,.7); }
    .sh-pg-dots { border-color: transparent; background: transparent; }
    .sh-pg-off { opacity: .45; }
</style>
