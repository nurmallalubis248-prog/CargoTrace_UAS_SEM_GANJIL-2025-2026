<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * "Mode Viewer (Read-Only Demo)": pengunjung masuk tanpa akun database, cukup lewat session('is_viewer').
 *
 * Cara kerjanya (hanya aktif bila session('is_viewer') === true):
 *  1. Pengunjung diberi pengguna VIRTUAL (objek User di memori, tidak pernah disimpan ke tabel `users`), sehingga
 *     middleware `auth`, `verified`, `role:admin`, policy, dan auth()->user()->name tetap berjalan tanpa error.
 *  2. Semua request selain GET/HEAD diblokir, begitu juga route *.create, *.edit, dan reports.pdf (unduh PDF)
 *     (kecuali viewer.enter / viewer.exit).
 *
 * Middleware ini didaftarkan di grup `web` dan diberi prioritas SEBELUM `auth` (lihat bootstrap/app.php).
 */
class ReadOnlyViewer
{
    public const MESSAGE = 'Mode Viewer: Anda hanya dapat melihat data.';
    public const VIEWER_NAME = 'Pengunjung (Viewer)';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || $request->session()->get('is_viewer') !== true) {
            return $next($request);
        }

        Auth::setUser(self::viewerUser());

        // Keluar (dan masuk ulang) harus selalu bisa, walaupun berupa POST.
        if ($request->routeIs('viewer.exit', 'viewer.enter')) {
            return $next($request);
        }

        $readOnlyMethod = in_array($request->getRealMethod(), ['GET', 'HEAD'], true)
            && in_array($request->getMethod(), ['GET', 'HEAD'], true);

        // reports.pdf memang GET, tetapi mengunduh file (dan menulis log audit), jadi ikut dikunci untuk viewer.
        if (! $readOnlyMethod || $request->routeIs('*.create', '*.edit', 'reports.pdf')) {
            return $this->deny($request);
        }

        return $next($request);
    }

    /** Pengguna sementara: tidak ada di database (exists = false), jadi tidak mungkin tersimpan/ter-update. */
    public static function viewerUser(): User
    {
        $user = new User();
        $user->forceFill([
            'id' => 0,
            'name' => self::VIEWER_NAME,
            'email' => 'viewer@demo.local',
            'role' => User::ROLE_ADMIN, // hanya agar semua menu Admin & Operator terlihat; penulisan tetap diblokir
            'email_verified_at' => now(),
        ]);

        return $user;
    }

    protected function deny(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => self::MESSAGE], 403);
        }

        return redirect()->back(302, [], route('dashboard'))->with('error', self::MESSAGE);
    }
}
