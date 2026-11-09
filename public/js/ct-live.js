/* CargoTrace live route (Shopee-style): the route follows real roads for ROAD shipments (OSRM), great-circle for SEA/AIR,
   the part already travelled is drawn solid ("bekas jalan"), and the vehicle keeps moving toward the ETA. */
(function () {
  window.CT = window.CT || {};
  const R = 6371000, RAD = Math.PI / 180;
  /* Jam simulasi bersama: semua kendaraan memakai waktu yang sama, jadi tidak lompat saat pengiriman terpilih berganti. */
  const SIM = { t0: Date.now(), w0: performance.now(), speed: 6000 };
  CT.simNow = () => SIM.t0 + (performance.now() - SIM.w0) * SIM.speed;
  CT.setSimSpeed = (v) => { SIM.t0 = CT.simNow(); SIM.w0 = performance.now(); SIM.speed = v; };
  const hav = (a, b) => {
    const dLa = (b[0] - a[0]) * RAD, dLn = (b[1] - a[1]) * RAD;
    const x = Math.sin(dLa / 2) ** 2 + Math.cos(a[0] * RAD) * Math.cos(b[0] * RAD) * Math.sin(dLn / 2) ** 2;
    return 2 * R * Math.asin(Math.min(1, Math.sqrt(x)));
  };
  function greatCircle(a, b, n) {
    const p1 = a[0] * RAD, l1 = a[1] * RAD, p2 = b[0] * RAD, l2 = b[1] * RAD;
    const d = 2 * Math.asin(Math.min(1, Math.sqrt(Math.sin((p2 - p1) / 2) ** 2 + Math.cos(p1) * Math.cos(p2) * Math.sin((l2 - l1) / 2) ** 2)));
    if (d < 1e-9) { return [a, b]; }
    const out = [];
    for (let i = 0; i <= n; i++) {
      const f = i / n, A = Math.sin((1 - f) * d) / Math.sin(d), B = Math.sin(f * d) / Math.sin(d);
      const x = A * Math.cos(p1) * Math.cos(l1) + B * Math.cos(p2) * Math.cos(l2);
      const y = A * Math.cos(p1) * Math.sin(l1) + B * Math.cos(p2) * Math.sin(l2);
      const z = A * Math.sin(p1) + B * Math.sin(p2);
      out.push([Math.atan2(z, Math.hypot(x, y)) / RAD, Math.atan2(y, x) / RAD]);
    }
    return out;
  }
  function unwrap(pts) {   // keep longitudes continuous so a route across the 180th meridian is not drawn across the whole map
    const out = [[pts[0][0], pts[0][1]]];
    for (let i = 1; i < pts.length; i++) {
      let lng = pts[i][1]; const prev = out[i - 1][1];
      while (lng - prev > 180) { lng -= 360; }
      while (lng - prev < -180) { lng += 360; }
      out.push([pts[i][0], lng]);
    }
    return out;
  }
  function osrm(wps) {   // public OSRM demo server; result cached in localStorage; null on any failure
    const key = 'ct_route:' + wps.map((p) => p[0].toFixed(3) + ',' + p[1].toFixed(3)).join('|');
    try { const c = localStorage.getItem(key); if (c) { return Promise.resolve(JSON.parse(c)); } } catch (e) { /* storage unavailable */ }
    const url = 'https://router.project-osrm.org/route/v1/driving/' + wps.map((p) => p[1] + ',' + p[0]).join(';') + '?overview=full&geometries=geojson';
    const ac = new AbortController(), to = setTimeout(() => ac.abort(), 9000);
    return fetch(url, { signal: ac.signal }).then((r) => r.json()).then((j) => {
      clearTimeout(to);
      if (!(j.routes && j.routes[0])) { return null; }
      const pts = j.routes[0].geometry.coordinates.map((c) => [c[1], c[0]]);
      try { localStorage.setItem(key, JSON.stringify(pts)); } catch (e) { /* quota */ }
      return pts;
    }).catch(() => { clearTimeout(to); return null; });
  }
  async function buildPath(mode, wps) {
    if (mode === 'ROAD') { const r = await osrm(wps); if (r && r.length > 1) { return { pts: r, real: true }; } }
    let pts = [];
    for (let i = 0; i < wps.length - 1; i++) {
      const seg = mode === 'AIR' ? CT.arc(wps[i], wps[i + 1]) : greatCircle(wps[i], wps[i + 1], Math.max(8, Math.min(160, Math.round(hav(wps[i], wps[i + 1]) / 80000))));
      pts = pts.concat(i ? seg.slice(1) : seg);
    }
    return { pts: unwrap(pts), real: false };
  }
  const prep = (pts) => { const c = [0]; for (let i = 1; i < pts.length; i++) { c.push(c[i - 1] + hav(pts[i - 1], pts[i])); } return c; };
  function at(pts, cum, d) {
    if (d >= cum[cum.length - 1]) { return { i: pts.length - 1, p: pts[pts.length - 1] }; }
    let lo = 0, hi = cum.length - 1;
    while (hi - lo > 1) { const mid = (lo + hi) >> 1; if (cum[mid] <= d) { lo = mid; } else { hi = mid; } }
    const f = (d - cum[lo]) / ((cum[hi] - cum[lo]) || 1);
    return { i: lo, p: [pts[lo][0] + (pts[hi][0] - pts[lo][0]) * f, pts[lo][1] + (pts[hi][1] - pts[lo][1]) * f] };
  }

  /* Speed selector (live / simulation) + "show vehicle" button, once per map. */
  CT.liveControl = function (map) {
    if (map.__ctLive) { return map.__ctLive; }
    const st = { speed: SIM.speed, pos: null };
    const C = L.Control.extend({ options: { position: 'bottomright' }, onAdd: function () {
      const box = L.DomUtil.create('div', 'leaflet-bar');
      box.style.cssText = 'background:#fff;padding:4px 8px;display:flex;gap:6px;align-items:center;font:600 11px Manrope,sans-serif;color:#26331A;border-radius:8px';
      const sel = document.createElement('select');
      sel.title = CT.tx('Simulation speed'); sel.style.cssText = 'font:inherit;border:1px solid #C5CBBB;border-radius:6px;padding:1px 4px';
      [[1, 'Live'], [600, '\u00d7600'], [6000, '\u00d76000'], [60000, '\u00d760000']].forEach((o) => { const e = document.createElement('option'); e.value = o[0]; e.textContent = o[1]; sel.appendChild(e); });
      sel.value = String(SIM.speed);
      sel.onchange = () => { st.speed = Number(sel.value); CT.setSimSpeed(st.speed); };
      const loc = document.createElement('button');
      loc.type = 'button'; loc.title = CT.tx('Show vehicle'); loc.textContent = '\u25ce';
      loc.style.cssText = 'font-size:17px;line-height:1;border:0;background:transparent;cursor:pointer;color:#E0394F';
      loc.onclick = () => { if (st.pos) { map.setView(st.pos, Math.max(map.getZoom(), 8)); } };
      box.appendChild(document.createTextNode(CT.tx('Speed') + ' ')); box.appendChild(sel); box.appendChild(loc);
      L.DomEvent.disableClickPropagation(box); L.DomEvent.disableScrollPropagation(box);
      return box;
    } });
    map.addControl(new C()); map.__ctLive = st; return st;
  };

  /* s = { from, here, dest, mode, delayed, done, lastAt(ms), eta(ms), popup, fit } -> { stop() } */
  CT.liveRoute = function (map, layer, s) {
    const ctl = { dead: false, raf: 0, stop: function () { this.dead = true; cancelAnimationFrame(this.raf); } };
    layer.clearLayers();
    const raw = [s.from || s.here, s.here, s.dest];
    const wps = raw.filter((p, i) => i === 0 || hav(p, raw[i - 1]) > 300);
    if (wps.length < 2) { return ctl; }
    buildPath(s.mode, wps).then((res) => {
      if (ctl.dead) { return; }
      const pts = res.pts, cum = prep(pts), total = cum[cum.length - 1] || 1;
      let i0 = 0, best = Infinity;
      for (let i = 0; i < pts.length; i++) { const d = hav(pts[i], s.here); if (d < best) { best = d; i0 = i; } }
      const p0 = cum[i0] / total;
      const strong = { color: '#E0394F', weight: 5, opacity: 0.95, lineCap: 'round', lineJoin: 'round', interactive: false };
      L.polyline(pts, { color: '#ffffff', weight: 9, opacity: 0.9, interactive: false }).addTo(layer);
      L.polyline(pts.slice(i0), Object.assign({}, strong, { opacity: 0.35, dashArray: res.real ? null : '8 10' })).addTo(layer);   // still to go
      L.polyline(pts.slice(0, i0 + 1), strong).addTo(layer);                                                                       // already travelled
      const moved = L.polyline([], strong).addTo(layer);                                                                           // travelled since the last scan
      L.circleMarker(pts[0], { radius: 7, color: '#26331A', weight: 2, fillColor: '#506E33', fillOpacity: 1 }).addTo(layer);
      CT.pin(pts[pts.length - 1]).addTo(layer);
      const veh = CT.vehicle(s.mode, pts[i0], s.delayed, 0, s.state).addTo(layer);
      if (s.popup) { veh.bindPopup(s.popup); }
      veh.bindTooltip('', { permanent: true, direction: 'top', offset: [0, -24], className: 'ct-bubble', interactive: false });
      if (s.fit) { map.fitBounds(L.latLngBounds(pts), { padding: [40, 40] }); }

      const ctrl = CT.liveControl(map), lastAt = s.lastAt || Date.now();
      let simNow = Date.now(), prev = performance.now(), lastDraw = 0, lastTxt = '';
      const frame = (ts) => {
        if (ctl.dead) { return; }
        ctl.raf = requestAnimationFrame(frame);
        if (ts - lastDraw < 66 || document.hidden) { return; }      // about 15 fps
        simNow = CT.simNow(); lastDraw = ts;
        let p = p0;
        if (s.eta && s.eta > lastAt) { p = p0 + (1 - p0) * Math.min(1, Math.max(0, (simNow - lastAt) / (s.eta - lastAt))); }
        if (s.arrived) { p = 1; } else if (!s.done) { p = Math.max(p0, Math.min(p, 0.995)); }
        const cur = at(pts, cum, p * total);
        veh.setLatLng(cur.p); ctrl.pos = cur.p;
        moved.setLatLngs([pts[i0]].concat(pts.slice(i0 + 1, cur.i + 1), [cur.p]));
        const b = CT.bearing(cur.p, pts[Math.min(pts.length - 1, cur.i + 3)]), el = veh.getElement(), sp = el && el.querySelector('span');
        if (sp) { sp.style.transform = s.mode === 'AIR' ? 'rotate(' + b + 'deg)' : 'scaleX(' + (b > 180 ? -1 : 1) + ')'; }
        const km = Math.max(0, (1 - p) * total / 1000);
        const txt = s.arrived ? CT.tx('Arrived') + ' \u00b7 ' + CT.tx('waiting for payment')
            : (s.done ? CT.tx('Delivered') : CT.tx('In transit')) + ' \u00b7 ' + (km >= 10 ? Math.round(km) : km.toFixed(1)) + ' km ' + CT.tx('remaining') + (res.real ? '' : ' (' + CT.tx('estimate') + ')');
        if (txt !== lastTxt) { veh.setTooltipContent(txt); lastTxt = txt; }
      };
      ctl.raf = requestAnimationFrame(frame);
    });
    return ctl;
  };
  /* Semua kendaraan yang masih berjalan bergerak bersamaan menuju tujuan (posisi diekstrapolasi dari scan terakhir ke ETA).
     items = [{ marker, from, here, dest, mode, lastAt, eta }] -> { stop() } */
  CT.liveFleet = function (map, items) {
    const ctl = { dead: false, raf: 0, stop: function () { this.dead = true; cancelAnimationFrame(this.raf); } };
    CT.liveControl(map);
    const run = [];
    items.forEach((s) => {
      const raw = [s.from || s.here, s.here, s.dest];
      const wps = raw.filter((p, i) => i === 0 || hav(p, raw[i - 1]) > 300);
      if (wps.length < 2) { return; }
      buildPath(s.mode, wps).then((res) => {
        if (ctl.dead) { return; }
        const cum = prep(res.pts), total = cum[cum.length - 1] || 1;
        let i0 = 0, best = Infinity;
        for (let i = 0; i < res.pts.length; i++) { const d = hav(res.pts[i], s.here); if (d < best) { best = d; i0 = i; } }
        run.push({ s: s, pts: res.pts, cum: cum, total: total, p0: cum[i0] / total, lastAt: s.lastAt || Date.now() });
      });
    });
    let lastDraw = 0;
    const frame = (ts) => {
      if (ctl.dead) { return; }
      ctl.raf = requestAnimationFrame(frame);
      if (ts - lastDraw < 66 || document.hidden) { return; }
      lastDraw = ts;
      const now = CT.simNow();
      run.forEach((r) => {
        const s = r.s;
        let p = r.p0;
        if (s.eta && s.eta > r.lastAt) { p = r.p0 + (1 - r.p0) * Math.min(1, Math.max(0, (now - r.lastAt) / (s.eta - r.lastAt))); }
        p = Math.max(r.p0, Math.min(p, 1));
        const cur = at(r.pts, r.cum, p * r.total);
        s.marker.setLatLng(cur.p);
        const b = CT.bearing(cur.p, r.pts[Math.min(r.pts.length - 1, cur.i + 3)]), el = s.marker.getElement(), sp = el && el.querySelector('span');
        if (sp) { sp.style.transform = s.mode === 'AIR' ? 'rotate(' + b + 'deg)' : 'scaleX(' + (b > 180 ? -1 : 1) + ')'; }
      });
    };
    ctl.raf = requestAnimationFrame(frame);
    return ctl;
  };
})();
