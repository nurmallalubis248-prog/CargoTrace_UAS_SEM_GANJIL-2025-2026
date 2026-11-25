# Pasang METAR bandara + kepadatan pelabuhan (AIS)

## 1. Salin file (timpa yang lama)
- app/Console/Commands/AisExportPorts.php   (baru)
- app/Http/Controllers/DashboardOverviewController.php
- resources/views/dashboard/index.blade.php
- routes/web.php                              (ada route baru: dashboard.metar)
- scripts/ais-listener.js                     (baru, buat folder `scripts` di root proyek)

Lalu: `php artisan route:clear` dan `php artisan view:clear`

## 2. METAR bandara — TANPA API key
Langsung jalan. Data dari NOAA Aviation Weather Center, di-cache 10 menit.
Kode bandara di tabel locations dipetakan ke ICAO di controller (konstanta ICAO):
SGSIA=WSSS, AEDXB=OMDB, IDCGK=WIII. Bandara baru: tambahkan pasangannya di konstanta itu,
atau isi kode lokasi langsung dengan kode ICAO 4 huruf.

## 3. Kepadatan pelabuhan — butuh API key AISStream (gratis daftar)
1. Daftar di https://aisstream.io lalu buat API key di halaman Account.
2. Di terminal, folder proyek:
   npm i ws
   php artisan ais:ports
   PowerShell:  $env:AISSTREAM_API_KEY="KUNCI_KAMU"; node scripts/ais-listener.js
   Linux/Mac:   AISSTREAM_API_KEY=KUNCI_KAMU node scripts/ais-listener.js
3. Biarkan terminal itu terbuka. Tiap 60 detik file storage/app/ais-ports.json diperbarui.
   Dashboard membacanya otomatis (data lebih lama dari 15 menit diabaikan).
4. Pelabuhan baru ditambah lewat menu Admin? Jalankan lagi `php artisan ais:ports` lalu restart listener.

Penting: key JANGAN ditaruh di file JavaScript browser / Blade. AISStream melarang koneksi dari browser.

## Cara kerja & batasan
- Kapal dihitung milik pelabuhan bila <= 30 km dari titik pelabuhan.
- Status navigasi AIS: 1 = berlabuh (dihitung "antre"), 5 = tertambat, lainnya = berlayar.
- Waspada bila >= 8 kapal berlabuh, Gangguan bila >= 20 (konstanta CONGEST_WARN / CONGEST_BAD di index.blade.php).
- "Rata-rata menunggu" dihitung sejak listener mulai berjalan, jadi baru akurat setelah beberapa jam menyala.
- Cakupan AIS gratis bergantung stasiun penerima di dekat pelabuhan; sebagian area bisa sepi/kosong.
- Ini indikator operasional, bukan data resmi otoritas pelabuhan atau NOTAM.
