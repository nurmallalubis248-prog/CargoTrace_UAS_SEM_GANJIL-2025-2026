/* CargoTrace – Peta global (CesiumJS): globe 3D + peta 2D, satelit, jalan, medan, bangunan 3D, pelabuhan & bandara.
   Data pengiriman = posisi terakhir yang TERCATAT di sistem (dashboard.stats). Tidak ada animasi/ekstrapolasi palsu di halaman ini.
   Fitur yang butuh token/API key otomatis dinonaktifkan bila key di .env kosong. */
(function () {
  'use strict';
  var cfg = window.CT_GLOBE || {};
  var $ = function (id) { return document.getElementById(id); };
  var tx = function (s) { return (window.CT && typeof window.CT.tx === 'function') ? window.CT.tx(s) : s; };

  function note(msg, level) {
    var ul = $('gl-notes'); if (!ul) { return; }
    var li = document.createElement('li'); li.textContent = tx(msg); if (level) { li.className = level; }
    ul.appendChild(li);
  }

  if (!window.Cesium) { $('gl-loading').hidden = true; $('gl-fallback').hidden = false; return; }
  var C = window.Cesium;
  var hasIon = !!cfg.ionToken, hasGoogle = !!cfg.googleKey;
  if (hasIon) { C.Ion.defaultAccessToken = cfg.ionToken; }

  /* ---------- Ikon (SVG) ---------- */
  var GLYPH = {
    AIR: '<path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    SEA: '<path d="M3 17l2 4h14l2-4-9-3zM12 14V4l5 4"/>',
    ROAD: '<path d="M3 16V6h11v10M14 9h4l3 3v4h-7M7 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
    PORT: '<path d="M12 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM12 8v13M6 14a6 6 0 0 0 12 0M5 12h3M16 12h3"/>',
    AIRPORT: '<path d="M17.8 19.2L16 11l3.5-3.5C21 6 21.5 4 21 3c-1-.5-3 0-4.5 1.5L13 8 4.8 6.2c-.5-.1-.9.1-1.1.5l-.3.5c-.2.5-.1 1 .3 1.3L9 12l-2 3H4l-1 1 3 2 2 3 1-1v-3l3-2 3.5 5.3c.3.4.8.5 1.3.3l.5-.2c.4-.3.6-.7.5-1.2z"/>',
    WAREHOUSE: '<path d="M3 10l9-6 9 6v10H3zM8 20v-6h8v6"/>'
  };
  var iconCache = {};
  function badge(bg, glyphKey, size) {
    var key = bg + '|' + glyphKey + '|' + size;
    if (iconCache[key]) { return iconCache[key]; }
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 40 40">'
      + '<circle cx="20" cy="20" r="18" fill="' + bg + '" stroke="#fff" stroke-width="3"/>'
      + '<g transform="translate(8 8)" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">' + (GLYPH[glyphKey] || GLYPH.SEA) + '</g></svg>';
    iconCache[key] = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    return iconCache[key];
  }

  /* ---------- Viewer ---------- */
  var viewer = new C.Viewer('gl-cesium', {
    baseLayer: false, baseLayerPicker: false, geocoder: false, homeButton: false, sceneModePicker: false,
    navigationHelpButton: false, animation: false, timeline: false, fullscreenButton: false, infoBox: false,
    selectionIndicator: true, shouldAnimate: false
  });
  viewer.scene.globe.enableLighting = false;
  viewer.scene.globe.showGroundAtmosphere = true;
  viewer.scene.screenSpaceCameraController.minimumZoomDistance = 30;   // dekat sampai tingkat jalan/bangunan
  viewer.camera.setView({ destination: C.Cartesian3.fromDegrees(105, 5, 24000000) });
  viewer.scene.renderError.addEventListener(function (s, err) { note('Map rendering error: ' + (err && err.message ? err.message : err), 'err'); });
  var loadingEl = $('gl-loading');
  var hideLoading = function () { if (loadingEl) { loadingEl.hidden = true; } };
  viewer.scene.globe.tileLoadProgressEvent.addEventListener(function (n) { if (n === 0) { hideLoading(); } });
  setTimeout(hideLoading, 8000);

  /* ---------- Peta dasar ---------- */
  var BASE = {
    'ion-aerial': { needsIon: true, make: function () { return C.ImageryLayer.fromProviderAsync(C.IonImageryProvider.fromAssetId(3)); } },
    esri: { make: function () { return new C.ImageryLayer(new C.UrlTemplateImageryProvider({
        url: 'https://services.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', maximumLevel: 19,
        credit: 'Imagery © Esri, Maxar, Earthstar Geographics' })); } },
    osm: { make: function () { return new C.ImageryLayer(new C.OpenStreetMapImageryProvider({ url: 'https://tile.openstreetmap.org/', credit: '© OpenStreetMap contributors' })); } },
    topo: { make: function () { return new C.ImageryLayer(new C.UrlTemplateImageryProvider({
        url: 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', subdomains: ['a', 'b', 'c'], maximumLevel: 17,
        credit: '© OpenStreetMap contributors, SRTM | OpenTopoMap (CC-BY-SA)' })); } }
  };
  var baseLayer = null, baseSel = $('gl-base');
  function setBase(key) {
    var def = BASE[key]; if (!def) { return; }
    if (baseLayer) { viewer.imageryLayers.remove(baseLayer, true); baseLayer = null; }
    baseLayer = def.make();
    viewer.imageryLayers.add(baseLayer, 0);
  }
  if (!hasIon) {
    var opt = baseSel.querySelector('option[value="ion-aerial"]'); if (opt) { opt.disabled = true; }
    baseSel.value = 'esri';
    note('Cesium ion token is not set: ion satellite imagery, 3D terrain and OSM Buildings are disabled. Set CESIUM_ION_TOKEN in .env.', '');
  }
  setBase(baseSel.value);
  baseSel.addEventListener('change', function () { setBase(baseSel.value); });

  /* ---------- Tanda laut (OpenSeaMap) ---------- */
  var seaLayer = viewer.imageryLayers.addImageryProvider(new C.UrlTemplateImageryProvider({
    url: 'https://tiles.openseamap.org/seamark/{z}/{x}/{y}.png', maximumLevel: 18, credit: 'Seamarks © OpenSeaMap contributors' }));
  $('gl-sea').addEventListener('change', function (e) { seaLayer.show = e.target.checked; });

  /* ---------- Medan 3D & bangunan 3D ---------- */
  var terrainOn = false;
  function setTerrain(on) {
    if (!hasIon) { return Promise.resolve(); }
    var p = on ? C.createWorldTerrainAsync({ requestVertexNormals: true }) : Promise.resolve(new C.EllipsoidTerrainProvider());
    return p.then(function (prov) {
      viewer.terrainProvider = prov; viewer.scene.globe.depthTestAgainstTerrain = on; terrainOn = on; drawRoutes();
    }).catch(function (e) { note('3D terrain could not be loaded: ' + (e && e.message ? e.message : e), 'err'); $('gl-terrain').checked = false; });
  }
  $('gl-terrain').addEventListener('change', function (e) { setTerrain(e.target.checked); });

  var bld = { osm: null, google: null }, bldMode = 'off';
  function applyBuildings() {
    if (bld.osm) { bld.osm.show = bldMode === 'osm'; }
    var is3D = viewer.scene.mode === C.SceneMode.SCENE3D;
    var googleOn = bldMode === 'google' && is3D;
    if (bld.google) { bld.google.show = googleOn; }
    viewer.scene.globe.show = !googleOn;      // Google 3D Tiles menggantikan permukaan globe
  }
  function setBuildings(mode) {
    bldMode = mode;
    var job = Promise.resolve();
    if (mode === 'osm' && !bld.osm) {
      job = C.createOsmBuildingsAsync().then(function (ts) { bld.osm = ts; viewer.scene.primitives.add(ts); });
    } else if (mode === 'google' && !bld.google) {
      C.GoogleMaps.defaultApiKey = cfg.googleKey;
      job = C.createGooglePhotorealistic3DTileset().then(function (ts) { bld.google = ts; viewer.scene.primitives.add(ts); });
    }
    return job.then(applyBuildings).catch(function (e) {
      note('Could not load the 3D buildings layer: ' + (e && e.message ? e.message : e), 'err');
      bldMode = 'off'; document.querySelector('input[name="gl-bld"][value="off"]').checked = true; applyBuildings();
    });
  }
  document.querySelectorAll('input[name="gl-bld"]').forEach(function (r) { r.addEventListener('change', function () { if (r.checked) { setBuildings(r.value); } }); });
  if (!hasIon) {
    ['gl-terrain', 'gl-bld-osm-l'].forEach(function (id) { var el = $(id); el.classList.add('off'); var inp = el.tagName === 'INPUT' ? el : el.querySelector('input'); if (inp) { inp.disabled = true; } });
    $('gl-terrain-l').classList.add('off');
  }
  if (!hasGoogle) {
    $('gl-bld-google-l').classList.add('off'); $('gl-bld-google-l').querySelector('input').disabled = true;
    note('Google Maps API key is not set: Photorealistic 3D Tiles are disabled. Set GOOGLE_MAPS_API_KEY in .env.', '');
  }

  /* ---------- Mode tampilan 3D / 2D / Columbus ---------- */
  var sceneBtns = document.querySelectorAll('#gl-scene button');
  function paintScene() {
    var m = viewer.scene.mode, key = m === C.SceneMode.SCENE2D ? '2D' : (m === C.SceneMode.COLUMBUS_VIEW ? 'CV' : '3D');
    sceneBtns.forEach(function (b) { b.classList.toggle('on', b.dataset.scene === key); });
  }
  sceneBtns.forEach(function (b) {
    b.addEventListener('click', function () {
      var s = b.dataset.scene;
      if (s === '2D') { viewer.scene.morphTo2D(1); } else if (s === 'CV') { viewer.scene.morphToColumbusView(1); } else { viewer.scene.morphTo3D(1); }
    });
  });
  viewer.scene.morphComplete.addEventListener(function () { paintScene(); applyBuildings(); });

  /* ---------- Entitas ---------- */
  var shipDS = new C.CustomDataSource('shipments'), locDS = new C.CustomDataSource('locations'), routeDS = new C.CustomDataSource('routes');
  viewer.dataSources.add(routeDS); viewer.dataSources.add(locDS); viewer.dataSources.add(shipDS);
  var ships = new Map(), locs = new Map(), routes = new Map();
  var modeOn = { AIR: true, SEA: true, ROAD: true }, showAllRoutes = false, selectedId = null;
  var layerOn = { PORT: true, AIRPORT: true, WAREHOUSE: false };

  var colorFor = function (p) { return p.status === 'DELIVERED' ? '#D85A82' : (p.delayed ? '#E5484D' : (p.status === 'ARRIVED' ? '#2F80ED' : '#506E33')); };
  var LOC_COLOR = { PORT: '#1F6FB2', AIRPORT: '#7A4FC2', WAREHOUSE: '#B8741A' };
  var cart = function (lat, lng, h) { return C.Cartesian3.fromDegrees(Number(lng), Number(lat), h || 0); };
  var ok = function (v) { return v !== null && v !== undefined && isFinite(Number(v)); };

  function upsertShip(p) {
    if (!ok(p.latitude) || !ok(p.longitude)) { return; }
    var pos = cart(p.latitude, p.longitude), img = badge(colorFor(p), p.mode, 44), rec = ships.get(p.id);
    if (!rec) {
      var ent = shipDS.entities.add({
        id: 'ship-' + p.id, name: p.shipment_number, position: pos,
        billboard: { image: img, scale: 0.85, verticalOrigin: C.VerticalOrigin.CENTER, heightReference: C.HeightReference.CLAMP_TO_GROUND,
          disableDepthTestDistance: Number.POSITIVE_INFINITY, scaleByDistance: new C.NearFarScalar(2e5, 1.1, 2e7, 0.55) },
        label: { text: p.shipment_number, font: '600 12px sans-serif', fillColor: C.Color.WHITE, outlineColor: C.Color.fromCssColorString('#26331A'), outlineWidth: 3,
          style: C.LabelStyle.FILL_AND_OUTLINE, pixelOffset: new C.Cartesian2(0, -30), heightReference: C.HeightReference.CLAMP_TO_GROUND,
          disableDepthTestDistance: Number.POSITIVE_INFINITY, distanceDisplayCondition: new C.DistanceDisplayCondition(0, 4000000) }
      });
      rec = { entity: ent, data: p }; ships.set(p.id, rec);
    } else {
      rec.entity.position = pos; rec.entity.billboard.image = img; rec.entity.label.text = p.shipment_number; rec.data = p;
    }
    rec.entity.show = !!modeOn[p.mode];
  }

  function locVisible(l) { return !!layerOn[l.type]; }
  function upsertLoc(l) {
    if (!ok(l.latitude) || !ok(l.longitude)) { return; }
    var rec = locs.get(l.id), pos = cart(l.latitude, l.longitude);
    if (!rec) {
      var ent = locDS.entities.add({
        id: 'loc-' + l.id, name: l.name, position: pos,
        billboard: { image: badge(LOC_COLOR[l.type] || '#6b7a5c', l.type, 36), scale: 0.8, verticalOrigin: C.VerticalOrigin.CENTER, heightReference: C.HeightReference.CLAMP_TO_GROUND,
          disableDepthTestDistance: Number.POSITIVE_INFINITY, scaleByDistance: new C.NearFarScalar(1.5e6, 1.0, 1.5e7, 0.5) },
        label: { text: l.name, font: '600 11px sans-serif', fillColor: C.Color.WHITE, outlineColor: C.Color.fromCssColorString('#0b1d33'), outlineWidth: 3, style: C.LabelStyle.FILL_AND_OUTLINE,
          pixelOffset: new C.Cartesian2(0, 24), heightReference: C.HeightReference.CLAMP_TO_GROUND, disableDepthTestDistance: Number.POSITIVE_INFINITY,
          distanceDisplayCondition: new C.DistanceDisplayCondition(0, 2500000) }
      });
      rec = { entity: ent, data: l }; locs.set(l.id, rec);
    } else { rec.entity.position = pos; rec.data = l; }
    rec.entity.show = locVisible(l);
  }
  function paintLocLayers() { locs.forEach(function (r) { r.entity.show = locVisible(r.data); }); }
  [['gl-ports', 'PORT'], ['gl-airports', 'AIRPORT'], ['gl-wh', 'WAREHOUSE']].forEach(function (x) {
    $(x[0]).addEventListener('change', function (e) { layerOn[x[1]] = e.target.checked; paintLocLayers(); });
  });
  document.querySelectorAll('input[data-mode]').forEach(function (c) {
    c.addEventListener('change', function () { modeOn[c.dataset.mode] = c.checked; ships.forEach(function (r) { r.entity.show = !!modeOn[r.data.mode]; }); drawRoutes(); });
  });
  $('gl-routes').addEventListener('change', function (e) { showAllRoutes = e.target.checked; drawRoutes(); });

  /* Garis rute: asal -> posisi terakhir -> tujuan (geodesik). Untuk pengiriman terpilih (atau semua bila dicentang). */
  function drawRoutes() {
    routeDS.entities.removeAll(); routes.clear();
    ships.forEach(function (rec, id) {
      var p = rec.data, want = (showAllRoutes && modeOn[p.mode]) || id === selectedId;
      if (!want || !ok(p.dest_lat) || !ok(p.dest_lng)) { return; }
      var pts = [];
      if (ok(p.origin_lat) && ok(p.origin_lng)) { pts.push(cart(p.origin_lat, p.origin_lng)); }
      pts.push(cart(p.latitude, p.longitude)); pts.push(cart(p.dest_lat, p.dest_lng));
      var sel = id === selectedId, col = C.Color.fromCssColorString(colorFor(p));
      var lineCol = sel ? col : col.withAlpha(0.6);
      // Garis yang menempel ke medan (clampToGround) hanya mendukung material warna polos; tanpa medan 3D dipakai garis putus-putus.
      routeDS.entities.add({ polyline: { positions: pts, width: sel ? 4 : 2, arcType: C.ArcType.GEODESIC, clampToGround: terrainOn,
        material: terrainOn ? lineCol : new C.PolylineDashMaterialProperty({ color: lineCol, dashLength: 14 }) } });
      if (sel) {
        routeDS.entities.add({ position: cart(p.dest_lat, p.dest_lng), point: { pixelSize: 11, color: C.Color.fromCssColorString('#E5484D'), outlineColor: C.Color.WHITE, outlineWidth: 2,
          heightReference: C.HeightReference.CLAMP_TO_GROUND, disableDepthTestDistance: Number.POSITIVE_INFINITY } });
      }
    });
  }

  /* ---------- Kamera ---------- */
  function flyToPoint(lat, lng, range, pitchDeg) {
    var bs = new C.BoundingSphere(cart(lat, lng), 1);
    viewer.camera.flyToBoundingSphere(bs, { duration: 2.2, offset: new C.HeadingPitchRange(0, C.Math.toRadians(pitchDeg === undefined ? -35 : pitchDeg), range) });
  }
  var worldView = function () { viewer.camera.flyTo({ destination: C.Cartesian3.fromDegrees(105, 5, 24000000), duration: 1.8 }); };
  $('gl-world').addEventListener('click', worldView);
  $('gl-zin').addEventListener('click', function () { viewer.camera.zoomIn(Math.max(50, viewer.camera.positionCartographic.height * 0.5)); });
  $('gl-zout').addEventListener('click', function () { viewer.camera.zoomOut(Math.max(50, viewer.camera.positionCartographic.height * 0.8)); });
  $('gl-street').addEventListener('click', function () {
    var rec = selectedId !== null ? ships.get(selectedId) : null, lrec = (!rec && selectedLoc !== null) ? locs.get(selectedLoc) : null;
    if (rec) { flyToPoint(rec.data.latitude, rec.data.longitude, 450, -35); }
    else if (lrec) { flyToPoint(lrec.data.latitude, lrec.data.longitude, 450, -35); }
    else { note('Select a shipment, port or airport first, then use "Street / building level".', ''); }
  });

  var readEl = $('gl-read'), lastRead = 0;
  viewer.scene.postRender.addEventListener(function () {
    var now = Date.now(); if (now - lastRead < 500) { return; } lastRead = now;
    var c = viewer.camera.positionCartographic; if (!c) { return; }
    var h = c.height, txt = h >= 100000 ? Math.round(h / 1000).toLocaleString() + ' km' : (h >= 1000 ? (h / 1000).toFixed(1) + ' km' : Math.round(h) + ' m');
    readEl.textContent = tx('Altitude') + ' ' + txt + ' · ' + C.Math.toDegrees(c.latitude).toFixed(3) + '°, ' + C.Math.toDegrees(c.longitude).toFixed(3) + '°';
  });

  /* ---------- Panel detail ---------- */
  var panel = $('gl-panel'), body = $('gl-panel-body'), selectedLoc = null;
  function el(tag, text, cls) { var e = document.createElement(tag); if (text !== undefined) { e.textContent = text; } if (cls) { e.className = cls; } return e; }
  function dl(rows) {
    var d = document.createElement('dl');
    rows.forEach(function (r) { if (r[1] === null || r[1] === undefined || r[1] === '') { return; } d.appendChild(el('dt', tx(r[0]))); d.appendChild(el('dd', String(r[1]))); });
    return d;
  }
  function km(a, b) {
    var R = 6371, t = Math.PI / 180, dLat = (b[0] - a[0]) * t, dLng = (b[1] - a[1]) * t;
    var h = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a[0] * t) * Math.cos(b[0] * t) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return 2 * R * Math.asin(Math.min(1, Math.sqrt(h)));
  }
  var MODE = { AIR: 'Air', SEA: 'Sea', ROAD: 'Road' };
  function showShip(p) {
    body.textContent = '';
    body.appendChild(el('h3', p.shipment_number));
    var sub = el('p', '', 'sub'); sub.appendChild(el('span', tx(p.status), 'gl-badge' + (p.delayed ? ' late' : ''))); if (p.delayed) { sub.appendChild(document.createTextNode(' ' + tx('Delayed'))); }
    body.appendChild(sub);
    var rows = [['Mode', tx(MODE[p.mode] || p.mode)], ['Position', p.label || tx('Unknown location')],
      ['Coordinates', Number(p.latitude).toFixed(4) + ', ' + Number(p.longitude).toFixed(4)], ['Last recorded', p.updated_at]];
    if (p.eta_ts) { rows.push(['ETA', new Date(Number(p.eta_ts)).toUTCString().replace(' GMT', ' UTC')]); }
    if (ok(p.dest_lat) && ok(p.dest_lng)) { rows.push(['Distance to destination', Math.round(km([Number(p.latitude), Number(p.longitude)], [Number(p.dest_lat), Number(p.dest_lng)])).toLocaleString() + ' km (' + tx('straight line') + ')']); }
    body.appendChild(dl(rows));
    var acts = el('div', '', 'acts');
    var open = el('a', tx('Open shipment'), 'pri'); open.href = p.url; acts.appendChild(open);
    var fly = el('button', tx('Street / building level')); fly.type = 'button'; fly.addEventListener('click', function () { flyToPoint(p.latitude, p.longitude, 450, -35); }); acts.appendChild(fly);
    body.appendChild(acts); panel.hidden = false;
  }
  function showLoc(l) {
    body.textContent = '';
    body.appendChild(el('h3', l.name));
    body.appendChild(el('p', (l.code || '') + (l.country ? ' · ' + l.country : ''), 'sub'));
    var rows = [['Type', tx(l.type === 'PORT' ? 'Port' : (l.type === 'AIRPORT' ? 'Airport' : (l.type === 'WAREHOUSE' ? 'Warehouse' : l.type)))],
      ['Active inbound', l.active_in], ['Active outbound', l.active_out], ['Delayed', l.delayed], ['Open exceptions', l.open_exceptions],
      ['Coordinates', Number(l.latitude).toFixed(4) + ', ' + Number(l.longitude).toFixed(4)]];
    if (l.ais && l.ais.anchored !== undefined) { rows.push(['Vessels waiting (AIS)', l.ais.anchored]); }
    body.appendChild(dl(rows));
    var acts = el('div', '', 'acts');
    var fly = el('button', tx('Street / building level'), 'pri'); fly.type = 'button'; fly.addEventListener('click', function () { flyToPoint(l.latitude, l.longitude, 900, -40); }); acts.appendChild(fly);
    body.appendChild(acts); panel.hidden = false;
  }
  function closePanel() { panel.hidden = true; viewer.selectedEntity = undefined; }
  $('gl-panel-x').addEventListener('click', closePanel);

  viewer.selectedEntityChanged.addEventListener(function (e) {
    selectedId = null; selectedLoc = null;
    if (e && e.id && String(e.id).indexOf('ship-') === 0) { selectedId = Number(String(e.id).slice(5)); var r = ships.get(selectedId); if (r) { showShip(r.data); } }
    else if (e && e.id && String(e.id).indexOf('loc-') === 0) { selectedLoc = Number(String(e.id).slice(4)); var q = locs.get(selectedLoc); if (q) { showLoc(q.data); } }
    else { panel.hidden = true; }
    drawRoutes();
  });

  function selectShip(id, fly) {
    var rec = ships.get(id); if (!rec) { return false; }
    viewer.selectedEntity = rec.entity;
    if (fly) { flyToPoint(rec.data.latitude, rec.data.longitude, 1500000, -60); }
    return true;
  }
  $('gl-search').addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') { return; }
    var q = e.target.value.trim().toLowerCase(); if (!q) { return; }
    var hit = null; ships.forEach(function (r) { if (!hit && String(r.data.shipment_number).toLowerCase().indexOf(q) !== -1) { hit = r; } });
    if (hit) { selectShip(hit.data.id, true); } else { note('No shipment with a recorded position matches "' + e.target.value + '".', ''); }
  });

  /* ---------- Muat data (pengiriman dari dashboard.stats, lokasi dari dashboard.locations) ---------- */
  function getJson(url) {
    return fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) { throw new Error('HTTP ' + r.status); } return r.json(); });
  }
  var firstLoad = true;
  function refreshShips() {
    return getJson(cfg.statsUrl).then(function (d) {
      var list = Array.isArray(d.positions) ? d.positions : [], seen = {};
      list.forEach(function (p) { seen[p.id] = true; upsertShip(p); });
      ships.forEach(function (rec, id) { if (!seen[id]) { shipDS.entities.remove(rec.entity); ships.delete(id); } });
      drawRoutes();
      if (selectedId !== null && ships.get(selectedId)) { showShip(ships.get(selectedId).data); }
      $('gl-updated').textContent = tx('Last refreshed') + ' ' + new Date().toLocaleTimeString() + ' · ' + list.length + ' ' + tx('shipments');
      if (firstLoad) {
        firstLoad = false;
        if (cfg.focusShipmentId && ships.get(cfg.focusShipmentId)) { selectShip(cfg.focusShipmentId, true); }
        else if (!list.length) { note('No shipments with a recorded position yet.', ''); }
      }
    }).catch(function (e) { note('Shipment positions could not be loaded: ' + e.message, 'err'); });
  }
  function loadLocations() {
    return getJson(cfg.locationsUrl).then(function (rows) { (rows || []).forEach(upsertLoc); })
      .catch(function (e) { note('Ports and airports could not be loaded: ' + e.message, 'err'); });
  }
  loadLocations().then(refreshShips);
  var every = Math.max(5, Number(cfg.refreshSeconds) || 15) * 1000;
  setInterval(function () { if (!document.hidden) { refreshShips(); } }, every);
  document.addEventListener('ct:lang', function () { ships.forEach(function (r) { if (r.data.id === selectedId) { showShip(r.data); } }); });
})();
