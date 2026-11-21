<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $date = fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
        $actor = (string) $request->query('actor', '');
        $filters = [
            'date_from' => $date($request->query('date_from')),
            'date_to' => $date($request->query('date_to')),
            'actor' => ($actor === 'system' || ctype_digit($actor)) ? $actor : '',
            'action' => trim((string) $request->query('action', '')),
            'entity' => trim((string) $request->query('entity', '')),
            'per_page' => in_array((int) $request->query('per_page'), [10, 25, 50], true) ? (int) $request->query('per_page') : 10,
        ];

        $logs = AuditLog::with('actor')
            ->when($filters['date_from'] !== '', fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['actor'] === 'system', fn ($q) => $q->whereNull('user_id'))
            ->when(ctype_digit($filters['actor']), fn ($q) => $q->where('user_id', (int) $filters['actor']))
            ->when($filters['action'] !== '', fn ($q) => $q->where('action', $filters['action']))
            ->when($filters['entity'] !== '', fn ($q) => $q->where('entity_type', $filters['entity']))
            ->latest()->orderByDesc('id')
            ->paginate($filters['per_page'])
            ->withQueryString();

        return view('admin.audit', [
            'logs' => $logs,
            'filters' => $filters,
            'actors' => User::whereIn('id', AuditLog::query()->whereNotNull('user_id')->distinct()->select('user_id'))->orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'entities' => AuditLog::query()->distinct()->orderBy('entity_type')->pluck('entity_type'),
        ]);
    }
}