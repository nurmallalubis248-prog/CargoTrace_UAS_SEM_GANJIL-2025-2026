<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShipmentFilterRequest;
use App\Models\Carrier;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    public function index(ShipmentFilterRequest $request, ReportService $reports): View
    {
        $filters = $request->validated();

        return view('reports.index', [
            'report' => $reports->build($request->user(), $filters),
            'filters' => $filters,
            'carriers' => Carrier::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function pdf(ShipmentFilterRequest $request, ReportService $reports, AuditService $audit): Response
    {
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::class;
        if (! class_exists($pdf)) {
            return back()->with('error', 'The PDF engine is not installed yet. Run: composer require barryvdh/laravel-dompdf');
        }

        $filters = $request->validated();
        $lang = $filters['lang'] ?? null;
        unset($filters['lang']);
        if ($lang !== null) {
            app()->setLocale($lang);
        }
        $audit->log($request->user(), 'report.downloaded', $request->user(), ['filters' => $filters]);

        return $pdf::loadView('reports.pdf', [
            'report' => $reports->build($request->user(), $filters),
            'filters' => $filters,
            'user' => $request->user(),
        ])->setPaper('a4', 'landscape')->download('cargotrace-report-'.now()->format('Ymd-His').'.pdf');
    }
}
