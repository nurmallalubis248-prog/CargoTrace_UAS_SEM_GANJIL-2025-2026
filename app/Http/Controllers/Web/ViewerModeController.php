<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Masuk / keluar "Mode Viewer (Read-Only Demo)". Murni session, tidak menyentuh tabel users. */
class ViewerModeController extends Controller
{
    public function enter(Request $request): RedirectResponse
    {
        // Pengguna yang sudah login sungguhan tidak perlu (dan tidak boleh) diturunkan menjadi viewer.
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $request->session()->regenerate();
        session(['is_viewer' => true]);

        return redirect()->route('dashboard');
    }

    public function exit(Request $request): RedirectResponse
    {
        session()->forget('is_viewer');

        return redirect()->route('login');
    }
}
