# CargoTrace: pengiriman otomatis + hapus globe & Midtrans

Timpa file di proyekmu dengan file yang namanya sama (struktur folder sama persis).

## A. File yang DITIMPA
    app/Services/SimulationService.php
    app/Services/TrackingService.php
    app/Services/MidtransService.php                     (tinggal class kosong, aman)
    app/Console/Commands/SimulateShipments.php
    app/Enums/ShipmentStatus.php
    app/Http/Controllers/Web/ShipmentController.php
    app/Http/Controllers/Web/PaymentController.php
    config/cargotrace.php
    config/services.php
    routes/web.php
    resources/views/layouts/app.blade.php                (menu globe dihapus)
    resources/views/shipments/show.blade.php             (tombol 3D globe + script bayar dihapus)
    resources/views/payments/_card.blade.php             (kartu tarif/status, tanpa tombol bayar)
    resources/views/auth/_landing.blade.php              (teks Midtrans/QRIS diganti)

## B. File yang DIHAPUS
    app/Http/Controllers/Web/MapController.php
    resources/views/map/globe.blade.php                  (lalu hapus folder resources/views/map)

## C. .env
Hapus: MIDTRANS_SERVER_KEY, MIDTRANS_CLIENT_KEY, MIDTRANS_IS_PRODUCTION
Tambah:
    CARGOTRACE_SIMULATION=true
    CARGOTRACE_SIMULATION_MODE=time
    CARGOTRACE_SIMULATION_TIME_SCALE=1        # 144 = perjalanan 3 hari selesai +-30 menit (demo)
    CARGOTRACE_SIMULATION_AUTO_BOOK_MINUTES=1
    CARGOTRACE_SIMULATION_AUTO_DELIVER=true
    CARGOTRACE_SIMULATION_SPEED_KMH=50

## D. Jalankan
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan schedule:work        (terminal ini harus tetap terbuka; ini yang menggerakkan pengiriman)

Tes sekali jalan: php artisan cargotrace:simulate

## Cara kerja pengiriman otomatis
- Posisi = (sekarang - berangkat) / (tiba - berangkat), dicari sepanjang rute, disimpan sebagai tracking_event
  (dan current_latitude/current_longitude di tabel shipments). Peta hanya membaca dari database.
- Berangkat/tiba diambil dari expected_departure_at dan expected_arrival_at pengiriman.
- Status naik sendiri: CREATED -> BOOKED -> PICKED_UP -> IN_TRANSIT -> ARRIVED -> DELIVERED, tanpa konfirmasi customer.
- Pengiriman lama yang jadwalnya sudah lewat langsung diselesaikan pada putaran pertama.
- Rute jalan asli (OSRM) hanya untuk carrier transport_mode ROAD dan butuh internet; selain itu garis lengkung.

## Catatan
- Tabel payments, halaman akuntansi, dan laporan TIDAK dihapus; pendapatan baru akan Rp 0.
- Teks landing yang diganti tampil berbahasa Inggris di bahasa lain sampai public/js/ct-dict.js diperbarui.
- Folder public/ (js/css) belum saya lihat; kalau ada tautan ke /map di sana, hapus juga.
