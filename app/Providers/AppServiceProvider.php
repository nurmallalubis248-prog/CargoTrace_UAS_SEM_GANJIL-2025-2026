<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // @writable ... @endwritable: isi hanya tampil bila BUKAN Mode Viewer (dipakai untuk tombol Tambah/Edit/Simpan/Hapus).
        Blade::if('writable', fn () => session('is_viewer') !== true);

        // Lapis pengaman kedua: pengguna virtual Mode Viewer hanya boleh melihat (policy view/viewAny), tidak boleh
        // create/update/delete/addTracking/manageExceptions. Efeknya @can('update', ...) dst. otomatis menyembunyikan tombolnya.
        Gate::before(function ($user, string $ability) {
            if ($user instanceof User && ! $user->exists && session('is_viewer') === true) {
                return in_array($ability, ['view', 'viewAny'], true) ? null : false;
            }

            return null;
        });
    }
}
