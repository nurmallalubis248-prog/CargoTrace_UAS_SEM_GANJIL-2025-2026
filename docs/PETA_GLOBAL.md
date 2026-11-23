# Peta Global CargoTrace (CesiumJS)

Halaman: **menu "Peta Global"** (`/map`). Dari halaman detail pengiriman ada tombol **3D globe** (`/map?shipment=ID`).

## Yang sudah ada di halaman ini

| Fitur | Sumber | Perlu key? |
|---|---|---|
| Globe 3D, peta 2D, tampilan Columbus (2,5D) | CesiumJS (CDN) | Tidak |
| Zoom dari tampilan dunia sampai tingkat jalan/bangunan (tombol, roda mouse, tombol "Tingkat jalan / bangunan") | CesiumJS | Tidak |
| Jalan | OpenStreetMap | Tidak |
| Satelit | Esri World Imagery | Tidak (lihat catatan lisensi) |
| Satelit + label jalan | Cesium ion (Bing Aerial with Labels) | `CESIUM_ION_TOKEN` |
| Topografi / pegunungan (peta) | OpenTopoMap | Tidak |
| Medan 3D (gunung, lembah) | Cesium World Terrain | `CESIUM_ION_TOKEN` |
| Bangunan 3D | Cesium OSM Buildings | `CESIUM_ION_TOKEN` |
| Rumah/gedung fotorealistik 3D | Google Photorealistic 3D Tiles | `GOOGLE_MAPS_API_KEY` |
| Tanda laut (pelabuhan, pelampung, dll.) | OpenSeaMap | Tidak |
| Pelabuhan, bandara, gudang | Tabel `locations` (data sistem) | Tidak |
| Posisi pengiriman, rute asal-posisi-tujuan, panel detail | Endpoint `dashboard.stats` (dibatasi per peran) | Tidak |

Fitur yang butuh key otomatis **nonaktif** (tombolnya abu-abu + catatan di atas peta) bila key kosong. Tidak ada data pengganti/rekaan.

## Konfigurasi `.env`

```
CESIUM_ION_TOKEN=          # https://ion.cesium.com -> Access Tokens (gratis untuk akun dasar)
CESIUM_VERSION=1.124       # versi CesiumJS dari CDN cesium.com
GOOGLE_MAPS_API_KEY=       # Google Cloud: aktifkan "Map Tiles API" + penagihan
```

Setelah mengubah `.env`: `php artisan config:clear`.

Kedua key dipakai **di browser** (CesiumJS berjalan di sisi klien), jadi:
- Cesium ion: buat token khusus, beri hanya akses aset yang dipakai (Bing Aerial 3, World Terrain, OSM Buildings), dan batasi **Allowed URLs** ke domain Anda.
- Google: batasi key ke **HTTP referrers** domain Anda dan hanya ke **Map Tiles API**.

## Batasan yang jujur

- Halaman ini menampilkan **posisi terakhir yang tercatat di sistem** (tabel `shipments`/`tracking_events`) dan menyegarkannya tiap 15 detik. Ini **bukan** pelacakan GPS langsung: GPS HP kurir / perangkat kendaraan adalah bagian 2 (belum dikerjakan).
- Google Photorealistic 3D Tiles hanya mencakup kota-kota tertentu dan memerlukan akun Google Cloud dengan penagihan. Di luar cakupan, globe memakai peta dasar yang dipilih.
- Medan 3D, OSM Buildings, dan satelit ion butuh internet + token. Tanpa token, satelit memakai Esri World Imagery; **periksa syarat penggunaan Esri** sebelum dipakai di produksi (untuk tugas/demo kampus umumnya tidak masalah, untuk komersial gunakan Cesium ion atau Google).
- Server tile OpenStreetMap dan OpenTopoMap punya kebijakan pemakaian wajar; untuk lalu lintas tinggi gunakan penyedia tile sendiri.
- Tampilan 2D / Columbus tidak menampilkan Google 3D Tiles (hanya mode 3D).
- Terjemahan teks baru tersedia dalam Indonesia dan Inggris; bahasa lain menampilkan teks Inggris.
- Tampilan dan koneksi ke layanan eksternal belum diuji di server Anda; coba dengan `php artisan serve`, buka `/map`, lalu cek konsol browser (F12) bila ada layer yang tidak muncul.

## Pengujian

`php artisan test --filter=GlobeMapTest` (halaman butuh login, key diteruskan ke browser, layer berkey nonaktif saat key kosong, parameter `shipment` hanya angka, menu tersedia).
