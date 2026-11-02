# Panduan memahami kode (untuk interview / code ownership)

Dosen akan memilih file/fungsi secara acak dan meminta Anda menjelaskan serta memodifikasinya. Pelajari urutan ini.

## 1. Alur satu request (hafalkan)
`routes` -> middleware (`auth`, `role`) -> `FormRequest` (validasi + authorize) -> `Controller` (tipis) -> `Service` (aturan bisnis) -> `Model` -> database.

## 2. File terpenting dan pertanyaan yang mungkin muncul
| File | Pelajari | Contoh pertanyaan dosen |
|---|---|---|
| `app/Enums/ShipmentStatus.php` | `allowedNext()` = state machine | "Bagaimana mencegah status invalid?" -> `TrackingService` memanggil `canTransitionTo()` di backend. |
| `app/Services/TrackingService.php` | urutan validasi, transaksi, `lockForUpdate` | "Apa akibatnya jika validasi dihapus?" -> status bisa loncat/mundur, event bisa bertanggal masa depan. |
| `app/Services/EtaService.php` + `config/cargotrace.php` | `classify()` | "Ubah delay threshold jadi 12/18/24 jam." -> ubah `.env` (`CARGOTRACE_DELAY_*`) atau angka di config; tes ada di `tests/Unit/EtaServiceTest.php`. |
| `app/Policies/ShipmentPolicy.php` | matriks hak akses | "Bagaimana customer dicegah melihat shipment orang lain?" -> `view()` + scope `visibleTo()` + test. |
| `app/Models/Shipment.php` | relasi, `$fillable`, scope `filter` | "Mengapa `hasMany`?" -> satu customer/carrier/lokasi punya banyak shipment (lihat `docs/ERD.md`). |
| `app/Models/TrackingEvent.php` | `booted()` memblokir update | "Mengapa event tidak boleh ditimpa?" -> riwayat historis. |
| `app/Services/WeatherService.php` | `Http::timeout`, `try/catch`, cache | "Apa yang terjadi bila API cuaca mati?" -> `available:false`, halaman tetap jalan. |
| `bootstrap/app.php` | format error JSON konsisten | "Mengapa tidak ada stack trace di API?" |
| `database/migrations/*` | foreign key, unique, index | "Mengapa ada index di kolom ini?" |

## 3. Modifikasi kecil yang sering diminta
- Ganti threshold delay: `.env` -> `CARGOTRACE_DELAY_LOW_MAX_HOURS`, `CARGOTRACE_DELAY_MEDIUM_MAX_HOURS`.
- Tambah jenis exception: tambah `case` di `app/Enums/ExceptionType.php` (validasi dan dropdown otomatis ikut).
- Tambah status baru: ubah `ShipmentStatus` (case + `allowedNext()` + `badgeClass()`).
- Ubah siapa boleh melakukan apa: `ShipmentPolicy`.

## 4. Menjalankan test sebelum demo
`php artisan test` — pastikan semua hijau, dan pahami minimal satu test per fitur.
