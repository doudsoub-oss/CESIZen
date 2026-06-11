<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when(
                $request->filled('action'),
                fn ($q) => $q->where('action', $request->string('action'))
            )
            ->when(
                $request->filled('user_id'),
                fn ($q) => $q->where('user_id', $request->integer('user_id'))
            )
            ->when(
                $request->filled('type'),
                fn ($q) => $q->where('auditable_type', $request->string('type'))
            )
            ->when(
                $request->filled('from'),
                fn ($q) => $q->whereDate('created_at', '>=', $request->date('from'))
            )
            ->when(
                $request->filled('to'),
                fn ($q) => $q->whereDate('created_at', '<=', $request->date('to'))
            )
            ->latest('created_at')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('admin/audit-logs/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->string('action')->toString() ?: null,
                'user_id' => $request->filled('user_id') ? $request->integer('user_id') : null,
                'type' => $request->string('type')->toString() ?: null,
                'from' => $request->string('from')->toString() ?: null,
                'to' => $request->string('to')->toString() ?: null,
            ],
            'actionOptions' => $this->actionOptions(),
            'typeOptions' => $this->typeOptions(),
        ]);
    }

    /**
     * Distinct actions present in the trail, for the filter dropdown.
     *
     * @return array<int, string>
     */
    private function actionOptions(): array
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->all();
    }

    /**
     * Distinct auditable types, exposed as short class basenames for display.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return AuditLog::query()
            ->whereNotNull('auditable_type')
            ->distinct()
            ->orderBy('auditable_type')
            ->pluck('auditable_type')
            ->map(fn (string $type) => [
                'value' => $type,
                'label' => class_basename($type),
            ])
            ->all();
    }
}
