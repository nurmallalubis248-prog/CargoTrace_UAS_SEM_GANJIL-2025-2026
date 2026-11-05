/* CargoTrace map helpers: real-world OSM base map (country/city names), plane/truck/ship markers, mode-aware routes. */
(function () {
  window.CT = window.CT || {};
  const ICON = {
    AIR: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M21 16v-2l-8-5V3.5a1.5 1.5 0 0 0-3 0V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5z"/></svg>',
    ROAD: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M1 5h13v10H1zM14 8h4l3 3v4h-7zM5.5 16.5a2 2 0 1 0 .01 0M17.5 16.5a2 2 0 1 0 .01 0"/></svg>',
    SEA: '<svg viewBox="0 0 24 24" width="22" height="22"><path fill="currentColor" d="M3 15l2 5h14l2-5-9-3zM11 3h2v6h-2zM6 10l6-2 6 2v2l-6-2-6 2z"/></svg>'
  };
  CT.baseMap = function (id, view, zoom) {
    const m = L.map(id, {
      worldCopyJump: true, minZoom: 2, maxZoom: 18, zoomSnap: 0.5, zoomDelta: 1, wheelPxPerZoomLevel: 90,
      dragging: true, inertia: true, touchZoom: true, doubleClickZoom: true, boxZoom: true, keyboard: true, scrollWheelZoom: true,
      zoomControl: true
    }).setView(view || [15, 80], zoom || 3);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, noWrap: false,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors' }).addTo(m);
    // "Reset view" button: fit the world again after the user has wandered off.
    const Reset = L.Control.extend({ options: { position: 'topleft' }, onAdd: function () {
      const b = L.DomUtil.create('a', 'leaflet-bar leaflet-control'); b.href = '#'; b.title = 'Reset';
      b.style.cssText = 'display:block;width:30px;height:30px;line-height:30px;text-align:center;background:#fff;font-weight:700;color:#3F5729;text-decoration:none';
      b.innerHTML = '&#8962;';
      L.DomEvent.on(b, 'click', (e) => { L.DomEvent.stop(e); m.setView(view || [15, 80], zoom || 3); });
      L.DomEvent.disableClickPropagation(b); return b; } });
    m.addControl(new Reset());
    // Latitude / longitude grid (every 30 degrees) with labels, switchable from the layers control.
    const grid = L.layerGroup();
    const lab = (txt, ll) => L.marker(ll, { interactive: false, icon: L.divIcon({ className: '', html: '<span style="font:600 10px Manrope,sans-serif;color:#3F5729;background:rgba(255,255,255,.75);padding:0 3px;border-radius:3px">' + txt + '</span>', iconSize: [34, 14], iconAnchor: [17, 7] }) });
    for (let lat = -60; lat <= 60; lat += 30) {
      L.polyline([[lat, -540], [lat, 540]], { color: lat === 0 ? '#D85A82' : '#506E33', weight: lat === 0 ? 1.4 : 0.8, opacity: 0.6, dashArray: lat === 0 ? null : '4 6', interactive: false }).addTo(grid);
      for (let lng = -180; lng <= 360; lng += 120) { lab(Math.abs(lat) + '°' + (lat === 0 ? '' : (lat > 0 ? 'N' : 'S')), [lat, lng]).addTo(grid); }
    }
    for (let lng = -540; lng <= 540; lng += 30) {
      const norm = ((lng + 540) % 360) - 180; // label as -180..180
      L.polyline([[-85, lng], [85, lng]], { color: lng % 360 === 0 ? '#D85A82' : '#506E33', weight: lng % 360 === 0 ? 1.4 : 0.8, opacity: 0.6, dashArray: lng % 360 === 0 ? null : '4 6', interactive: false }).addTo(grid);
      lab(Math.abs(norm) + '°' + (norm === 0 || Math.abs(norm) === 180 ? '' : (norm > 0 ? 'E' : 'W')), [-78, lng]).addTo(grid);
    }
    grid.addTo(m);
    const ov = {}; ov[CT.tx('Coordinate grid')] = grid;
    L.control.layers(null, ov, { collapsed: true, position: 'topright' }).addTo(m);
    // Live cursor coordinates.
    const Pos = L.Control.extend({ options: { position: 'bottomleft' }, onAdd: function () {
      const d = L.DomUtil.create('div'); d.style.cssText = 'background:rgba(255,255,255,.85);padding:2px 8px;border-radius:6px;font:600 11px Manrope,sans-serif;color:#26331A';
      m.on('mousemove', (e) => { const w = m.wrapLatLng(e.latlng); d.textContent = w.lat.toFixed(4) + ', ' + w.lng.toFixed(4); }); return d; } });
    m.addControl(new Pos());
    m.getContainer().style.cursor = 'grab';
    setTimeout(() => m.invalidateSize(), 300); window.addEventListener('resize', () => m.invalidateSize()); window.addEventListener('load', () => m.invalidateSize()); // fixes grey/stuck map when the container size changes after load
    return m;
  };
  CT.bearing = function (a, b) {
    const r = Math.PI / 180, y = Math.sin((b[1] - a[1]) * r) * Math.cos(b[0] * r);
    const x = Math.cos(a[0] * r) * Math.sin(b[0] * r) - Math.sin(a[0] * r) * Math.cos(b[0] * r) * Math.cos((b[1] - a[1]) * r);
    return (Math.atan2(y, x) / r + 360) % 360;
  };
  /* Vehicle marker. Plane icon points north by default, so rotate by bearing; trucks/ships stay upright. */
  CT.vehicle = function (mode, latlng, delayed, bearing) {
    const rot = mode === 'AIR' ? (bearing || 0) : 0;
    const html = '<div class="ct-veh ' + (delayed ? 'ct-veh-late' : '') + '"><span style="display:block;transform:rotate(' + rot + 'deg)">' + (ICON[mode] || ICON.SEA) + '</span></div>';
    return L.marker(latlng, { icon: L.divIcon({ className: '', html: html, iconSize: [38, 38], iconAnchor: [19, 19] }) });
  };
  CT.routeStyle = function (mode) {
    return mode === 'AIR' ? { color: '#D85A82', weight: 3, dashArray: '2 8', lineCap: 'round' }
         : mode === 'ROAD' ? { color: '#B8741A', weight: 4 } : { color: '#506E33', weight: 3, dashArray: '10 6' };
  };
  CT.modeLabel = (m) => CT.t(m === 'AIR' ? 'modeAir' : m === 'ROAD' ? 'modeRoad' : 'modeSea');

  /* Red destination pin (like courier apps). */
  CT.pin = function (latlng) {
    const svg = '<svg viewBox="0 0 24 32" width="30" height="40"><path fill="#D85A82" stroke="#fff" stroke-width="1.5" d="M12 1C6 1 2 5.5 2 11c0 7.5 10 20 10 20s10-12.5 10-20C22 5.5 18 1 12 1z"/><circle cx="12" cy="11" r="4" fill="#fff"/></svg>';
    return L.marker(latlng, { icon: L.divIcon({ className: '', html: svg, iconSize: [30, 40], iconAnchor: [15, 40], popupAnchor: [0, -36] }) });
  };
  /* Curved flight path (quadratic arc) between two points. */
  CT.arc = function (a, b) {
    const mid = [(a[0] + b[0]) / 2, (a[1] + b[1]) / 2], d = Math.hypot(b[0] - a[0], b[1] - a[1]) * 0.18;
    const ctrl = [mid[0] + d * 0.6, mid[1] - d * 0.2], pts = [];
    for (let t = 0; t <= 1.0001; t += 0.04) {
      pts.push([(1 - t) * (1 - t) * a[0] + 2 * (1 - t) * t * ctrl[0] + t * t * b[0], (1 - t) * (1 - t) * a[1] + 2 * (1 - t) * t * ctrl[1] + t * t * b[1]]);
    }
    return pts;
  };
  /* Garis ringkas tanpa request jaringan: busur untuk udara, lurus untuk lainnya. */
  CT.quickLine = function (layer, a, b, mode, style) {
    return L.polyline(mode === 'AIR' ? CT.arc(a, b) : [a, b], style).addTo(layer);
  };
  /* Draws a route through pts. ROAD => follows real roads (OSRM public router, falls back to a straight line). */
  CT.route = function (layer, pts, mode, style) {
    if (pts.length < 2) { return; }
    if (mode === 'AIR') {
      for (let i = 0; i < pts.length - 1; i++) { L.polyline(CT.arc(pts[i], pts[i + 1]), style).addTo(layer); }
      return;
    }
    const line = L.polyline(pts, style).addTo(layer);
    if (mode !== 'ROAD') { return; }
    const coords = pts.map((p) => p[1] + ',' + p[0]).join(';');
    fetch('https://router.project-osrm.org/route/v1/driving/' + coords + '?overview=full&geometries=geojson')
      .then((r) => r.json())
      .then((j) => {
        if (j.routes && j.routes[0]) { line.setLatLngs(j.routes[0].geometry.coordinates.map((c) => [c[1], c[0]])); }
      }).catch(() => {});
  };
})();
