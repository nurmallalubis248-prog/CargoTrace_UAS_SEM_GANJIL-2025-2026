<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(): View
    {
        return view('admin.audit', [
            'logs' => AuditLog::with('actor')->latest()->orderByDesc('id')->paginate(25),
        ]);
    }
}
