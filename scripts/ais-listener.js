/**
 * Listener AIS -> kepadatan pelabuhan.  Jalankan dari folder proyek:
 *   npm i ws
 *   php artisan ais:ports
 *   AISSTREAM_API_KEY=xxxx node scripts/ais-listener.js      (Windows PowerShell: $env:AISSTREAM_API_KEY="xxxx"; node scripts/ais-listener.js)
 * Menulis storage/app/ais-ports.json tiap 60 detik. Biarkan terminal ini tetap terbuka saat demo.
 * AISStream tidak boleh dipanggil dari browser, karena itu dijalankan di server/komputermu seperti ini.
 */
const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

const KEY = process.env.AISSTREAM_API_KEY;
if (!KEY) { console.error('Set AISSTREAM_API_KEY dulu.'); process.exit(1); }

const CONFIG = path.join(__dirname, '..', 'storage', 'app', 'ais-ports-config.json');
const OUT = path.join(__dirname, '..', 'storage', 'app', 'ais-ports.json');
const RADIUS_KM = 30;        // kapal dihitung milik pelabuhan jika berada <= 30 km dari titik pelabuhan
const BOX_DEG = 0.4;         // kotak langganan AIS +/- 0.4 derajat (~44 km) di sekitar tiap pelabuhan
const STALE_MS = 45 * 60 * 1000;

const ports = JSON.parse(fs.readFileSync(CONFIG, 'utf8'));
if (!ports.length) { console.error('Tidak ada pelabuhan. Jalankan: php artisan ais:ports'); process.exit(1); }

const rad = (d) => d * Math.PI / 180;
function km(a, b, c, d) {
  const x = Math.sin(rad(c - a) / 2) ** 2 + Math.cos(rad(a)) * Math.cos(rad(c)) * Math.sin(rad(d - b) / 2) ** 2;
  return 12742 * Math.asin(Math.sqrt(x));
}
const vessels = new Map();   // mmsi -> { port, status, sog, seen, anchorSince }

function onPosition(m) {
  const pr = m.Message && m.Message.PositionReport; if (!pr) { return; }
  const meta = m.MetaData || {}; const lat = meta.latitude, lng = meta.longitude; const id = meta.MMSI || pr.UserID;
  if (id == null || lat == null || lng == null) { return; }
  let best = null, bd = RADIUS_KM;
  for (const p of ports) { const d = km(lat, lng, p.lat, p.lng); if (d <= bd) { bd = d; best = p; } }
  if (!best) { vessels.delete(id); return; }
  const prev = vessels.get(id); const status = pr.NavigationalStatus;   // standar AIS: 0 berlayar, 1 berlabuh (at anchor), 5 tertambat (moored)
  const now = Date.now();
  vessels.set(id, { port: best.id, status: status, sog: pr.Sog, seen: now,
    anchorSince: status === 1 ? ((prev && prev.status === 1 && prev.anchorSince) || now) : null });
}

function snapshot() {
  const now = Date.now(); const res = {};
  for (const p of ports) { res[p.id] = { anchored: 0, moored: 0, underway: 0, total: 0, waits: [] }; }
  for (const [id, v] of vessels) {
    if (now - v.seen > STALE_MS) { vessels.delete(id); continue; }
    const r = res[v.port]; if (!r) { continue; }
    r.total++;
    if (v.status === 1) { r.anchored++; r.waits.push((now - v.anchorSince) / 60000); }
    else if (v.status === 5) { r.moored++; } else { r.underway++; }
  }
  const out = {};
  for (const k of Object.keys(res)) {
    const r = res[k];
    // waktu tunggu = rata-rata lama kapal terlihat berlabuh SEJAK listener ini memantau (estimasi, bukan data resmi pelabuhan)
    out[k] = { anchored: r.anchored, moored: r.moored, underway: r.underway, total: r.total,
      avg_wait_min: r.waits.length ? Math.round(r.waits.reduce((a, b) => a + b, 0) / r.waits.length) : null };
  }
  const tmp = OUT + '.tmp';
  fs.writeFileSync(tmp, JSON.stringify({ updated_at: new Date().toISOString(), ports: out })); fs.renameSync(tmp, OUT);
  console.log(new Date().toISOString().slice(11, 19), 'kapal terpantau:', vessels.size, '| ditulis', path.basename(OUT));
}
setInterval(snapshot, 60000);

let backoff = 3000;
function connect() {
  const ws = new WebSocket('wss://stream.aisstream.io/v0/stream');   // permessage-deflate aktif secara bawaan di 'ws'
  ws.on('open', () => {
    backoff = 3000;
    ws.send(JSON.stringify({ APIKey: KEY,
      BoundingBoxes: ports.map((p) => [[p.lat - BOX_DEG, p.lng - BOX_DEG], [p.lat + BOX_DEG, p.lng + BOX_DEG]]),
      FilterMessageTypes: ['PositionReport'] }));
    console.log('Terhubung ke AISStream,', ports.length, 'pelabuhan dipantau.');
  });
  ws.on('message', (d) => { try { const m = JSON.parse(d.toString()); if (m.error) { console.error('AISStream:', m.error); return; } onPosition(m); } catch (e) { /* abaikan */ } });
  ws.on('close', () => { console.log('Terputus, mencoba lagi dalam', backoff / 1000, 'dtk'); setTimeout(connect, backoff); backoff = Math.min(backoff * 2, 60000); });
  ws.on('error', (e) => console.error('WS error:', e.message));
}
connect();
