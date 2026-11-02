<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /** The page is a shell: numbers, charts and map markers are loaded via Fetch from /dashboard/stats (database data). */
    public function index(): View
    {
        return view('dashboard.index');
    }
}
