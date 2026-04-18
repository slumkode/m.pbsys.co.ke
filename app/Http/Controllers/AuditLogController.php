<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\Shortcode;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'audit_logs', 'view');
        $visibleLogs = $this->visibleAuditLogQuery($authUser);

        return view('admin.modules.audit-logs', $this->baseViewData($request, [
            'actions' => (clone $visibleLogs)->select('action')->distinct()->orderBy('action')->pluck('action'),
            'auditUsers' => $this->auditUserOptions($authUser),
            'auditPages' => (clone $visibleLogs)
                ->whereNotNull('page_name')
                ->where('page_name', '!=', '')
                ->select('page_name')
                ->distinct()
                ->orderBy('page_name')
                ->pluck('page_name'),
        ]));
    }

    public function datatable(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'audit_logs', 'view');
        $columns = [
            0 => 'created_at',
            1 => 'user_name',
            2 => 'action',
            3 => 'auditable_type',
            4 => 'page_name',
        ];

        $baseQuery = $this->visibleAuditLogQuery($authUser);
        $totalData = (clone $baseQuery)->count();
        $limit = max(10, min((int) $request->input('length', 25), 100));
        $start = max((int) $request->input('start', 0), 0);
        $order = $columns[$request->input('order.0.column', 0)] ?? 'created_at';
        $dir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $hasRestorableFlag = Schema::hasColumn('audit_logs', 'is_restorable');
        $hasRestoreStatus = Schema::hasColumn('audit_logs', 'restored_at');

        $selectedColumns = [
            'id',
            'created_at',
            'user_id',
            'user_name',
            'action',
            'auditable_type',
            'auditable_id',
            'auditable_label',
            'page_name',
        ];

        if ($hasRestorableFlag) {
            $selectedColumns[] = 'is_restorable';
        }

        if ($hasRestoreStatus) {
            $selectedColumns[] = 'restored_at';
        }

        $filteredQuery = $this->visibleAuditLogQuery($authUser)->select($selectedColumns);
        $this->applyFilters($filteredQuery, $request);
        $totalFiltered = (clone $filteredQuery)->count();

        $logs = $filteredQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($logs as $log) {
            $detailsButton = '<button type="button" class="btn btn-default btn-sm toggle-audit-details" data-id="'.$log->id.'">View Changes</button>';
            $restoreAction = '';
            $canRestore = $hasRestorableFlag
                && (bool) ($log->is_restorable ?? false)
                && empty($log->restored_at);

            if ($canRestore && $authUser->hasPermission('audit_logs.restore')) {
                $restoreAction = '<button type="button" class="btn btn-primary btn-sm restore-audit-log" data-id="'.$log->id.'">Restore</button>';
            } elseif ($hasRestoreStatus && $log->restored_at) {
                $restoreAction = '<span class="badge badge-success">Restored</span>';
            }

            $data[] = [
                'when' => optional($log->created_at)->format('d M Y, H:i:s'),
                'user' => e($log->user_name ?: 'System'),
                'action' => e(ucwords(str_replace('_', ' ', $log->action))),
                'object' => '<div>'.e($log->auditable_type ?: 'General').'</div><small class="text-muted">'.e($log->auditable_label ?: 'No label').'</small>',
                'page' => e($log->page_name ?: 'N/A'),
                'details' => trim($detailsButton.' '.$restoreAction) ?: '<span class="text-muted">No actions</span>',
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ]);
    }

    public function details(Request $request, AuditLog $auditLog)
    {
        $authUser = $this->requireActionPermission($request, 'audit_logs', 'view');
        $auditLog = $this->resolveVisibleAuditLog($authUser, $auditLog);
        $auditLog->load('restoredBy:id,name');
        $oldValues = $this->resolvedOldValues($auditLog);
        $newValues = $this->resolvedNewValues($auditLog);
        $diffSummary = $this->buildAuditJsonDiff($oldValues, $newValues);

        return response()->json([
            'status' => true,
            'html' => view('admin.modules.partials.audit-log-details', [
                'log' => $auditLog,
                'oldValues' => $oldValues,
                'newValues' => $newValues,
                'formattedOldValues' => $diffSummary['previous_json'],
                'formattedNewValues' => $diffSummary['new_json'],
                'diffSummary' => $diffSummary,
                'changesDetected' => $diffSummary['has_changes'],
                'displayUrl' => $authUser->canViewAllAuditLogs()
                    ? ($auditLog->url ?: 'N/A')
                    : $this->maskAuditUrl($auditLog->url),
                'maskAuditUrl' => ! $authUser->canViewAllAuditLogs() && ! empty($auditLog->url),
            ])->render(),
        ]);
    }

    public function restore(Request $request, AuditLog $auditLog)
    {
        $authUser = $this->requireActionPermission($request, 'audit_logs', 'restore');
        $auditLog = $this->resolveVisibleAuditLog($authUser, $auditLog);

        if (! $auditLog->canBeRestored()) {
            return [
                'status' => false,
                'msg' => 'This audit entry is not eligible for restore.',
                'header' => 'Audit Logs',
            ];
        }

        $payload = $auditLog->restore_payload;

        try {
            DB::transaction(function () use ($request, $authUser, $auditLog, $payload) {
                $restoredSnapshot = [];

                switch ($payload['type'] ?? null) {
                    case 'user_bundle':
                        $restoredSnapshot = $this->restoreUserBundle($payload);
                        break;
                    case 'service':
                        $restoredSnapshot = $this->restoreService($payload);
                        break;
                    default:
                        abort(422, 'Unsupported restore payload.');
                }

                $auditLog->restored_at = $this->timestamp();
                $auditLog->restored_by = $authUser->id;
                $auditLog->save();

                $this->recordAudit($request, 'restored', $auditLog->auditable_type ?: 'Restore', $this->buildRestoreOldValues($auditLog, $payload), $this->buildRestoreNewValues($auditLog, $authUser, $restoredSnapshot), [
                    'page_name' => 'Audit Logs',
                    'auditable_type' => $auditLog->auditable_type,
                    'auditable_id' => $auditLog->auditable_id,
                    'auditable_label' => $auditLog->auditable_label,
                ]);
            });
        } catch (\Throwable $exception) {
            return [
                'status' => false,
                'msg' => $exception->getMessage(),
                'header' => 'Audit Logs',
            ];
        }

        return [
            'status' => true,
            'msg' => 'Deleted data restored successfully.',
            'header' => 'Audit Logs',
        ];
    }

    protected function restoreUserBundle(array $payload)
    {
        $userId = (int) ($payload['user_id'] ?? 0);
        $shortcodeIds = array_map('intval', $payload['shortcode_ids'] ?? []);
        $serviceIds = array_map('intval', $payload['service_ids'] ?? []);

        $user = User::withTrashed()->find($userId);

        if (! $user) {
            abort(422, 'The deleted user record could not be found for restore.');
        }

        if ($user->trashed()) {
            $user->restore();
        }

        if (! empty($shortcodeIds)) {
            Shortcode::withTrashed()->whereIn('id', $shortcodeIds)->restore();
        }

        if (! empty($serviceIds)) {
            Service::withTrashed()->whereIn('id', $serviceIds)->restore();
        }

        $user->refresh();

        return $this->userBundleSnapshot($userId);
    }

    protected function restoreService(array $payload)
    {
        $serviceId = (int) ($payload['service_id'] ?? 0);
        $service = Service::withTrashed()
            ->with(['shortcode.user', 'user'])
            ->find($serviceId);

        if (! $service) {
            abort(422, 'The deleted service record could not be found for restore.');
        }

        if ($service->trashed()) {
            $service->restore();
        }

        return $this->serviceSnapshot($serviceId);
    }

    protected function applyFilters($query, Request $request)
    {
        $userId = (int) $request->input('user_id');
        $userSearch = trim((string) $request->input('user_name'));
        $objectSearch = trim((string) $request->input('object'));
        $pageSearch = trim((string) $request->input('page_name'));
        $action = trim((string) $request->input('action'));
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );

        if ($userId > 0) {
            $query->where('user_id', $userId);
        } elseif ($userSearch !== '') {
            $query->where('user_name', 'LIKE', "%{$userSearch}%");
        }

        if ($objectSearch !== '') {
            $query->where(function ($builder) use ($objectSearch) {
                $builder->where('auditable_type', 'LIKE', "%{$objectSearch}%")
                    ->orWhere('auditable_label', 'LIKE', "%{$objectSearch}%");

                if (is_numeric($objectSearch)) {
                    $builder->orWhere('auditable_id', (int) $objectSearch);
                }
            });
        }

        if ($pageSearch !== '') {
            $query->where('page_name', 'LIKE', "%{$pageSearch}%");
        }

        if ($action !== '') {
            $query->where('action', $action);
        }

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }
    }

    protected function resolveDateRange($dateFrom, $dateTo)
    {
        $start = $dateFrom ? Carbon::parse($dateFrom) : null;
        $end = $dateTo ? Carbon::parse($dateTo) : null;

        if ($start && $end && $start->gt($end)) {
            [$start, $end] = [$end->copy(), $start->copy()];
        }

        return [$start, $end];
    }

    protected function visibleAuditLogQuery(User $authUser)
    {
        $query = AuditLog::query();

        if (! $authUser->canViewAllAuditLogs()) {
            $query->where('user_id', $authUser->id);
        }

        return $query;
    }

    protected function resolveVisibleAuditLog(User $authUser, AuditLog $auditLog)
    {
        abort_if(
            ! $authUser->canViewAllAuditLogs() && (int) $auditLog->user_id !== (int) $authUser->id,
            403,
            'You do not have permission to access this audit entry.'
        );

        return $auditLog;
    }

    protected function auditUserOptions(User $authUser)
    {
        $supportsSoftDeletes = Schema::hasColumn('users', 'deleted_at');
        $supportsStatus = Schema::hasColumn('users', 'status');
        $query = $supportsSoftDeletes ? User::withTrashed() : User::query();
        $visibleUserIds = (clone $this->visibleAuditLogQuery($authUser))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->filter()
            ->map(function ($userId) {
                return (int) $userId;
            })
            ->values()
            ->all();
        $columns = ['id', 'name', 'username', 'email'];

        if ($supportsStatus) {
            $columns[] = 'status';
        }

        if ($supportsSoftDeletes) {
            $columns[] = 'deleted_at';
        }

        if ($authUser->canViewAllAuditLogs()) {
            if (empty($visibleUserIds)) {
                return collect();
            }

            $query->whereIn('id', $visibleUserIds);
        } else {
            $query->where('id', $authUser->id);
        }

        return $query->orderBy('name')
            ->get($columns)
            ->map(function ($user) use ($supportsSoftDeletes) {
                $statusLabel = 'Active';

                if ($supportsSoftDeletes && ! empty($user->deleted_at)) {
                    $statusLabel = 'Deleted';
                } elseif ((int) ($user->status ?? 1) !== 1) {
                    $statusLabel = 'Inactive';
                }

                $identity = trim((string) $user->name);

                if ($user->username) {
                    $identity .= ' ('.$user->username.')';
                } elseif ($identity === '' && $user->email) {
                    $identity = $user->email;
                }

                if ($identity === '') {
                    $identity = 'User #'.$user->id;
                }

                return [
                    'id' => (int) $user->id,
                    'label' => $identity.' - '.$statusLabel,
                    'status' => $statusLabel,
                ];
            })
            ->values();
    }

    protected function resolvedOldValues(AuditLog $auditLog)
    {
        if ($auditLog->action !== 'restored') {
            return $auditLog->old_values;
        }

        $oldValues = $auditLog->old_values ?: [];

        if ($this->restoreLogHasSnapshot($oldValues)) {
            return $oldValues;
        }

        $sourceAuditLog = $this->sourceDeletedAuditLog($auditLog);

        return $sourceAuditLog && ! empty($sourceAuditLog->old_values)
            ? $sourceAuditLog->old_values
            : $oldValues;
    }

    protected function resolvedNewValues(AuditLog $auditLog)
    {
        if ($auditLog->action !== 'restored') {
            return $auditLog->new_values;
        }

        $newValues = $auditLog->new_values ?: [];

        if ($this->restoreLogHasSnapshot($newValues)) {
            return $newValues;
        }

        $snapshot = $this->restoredSnapshotForAuditLog($auditLog);

        if (empty($snapshot)) {
            return $newValues;
        }

        $newValues = array_merge($snapshot, $newValues);
        $newValues['restored_at'] = optional($auditLog->restored_at)->format('Y-m-d H:i:s');
        $newValues['restored_by'] = optional($auditLog->restoredBy)->name ?: $auditLog->user_name;

        return $newValues;
    }

    protected function buildRestoreOldValues(AuditLog $auditLog, array $payload)
    {
        return ! empty($auditLog->old_values)
            ? $auditLog->old_values
            : [
                'audit_log_id' => $auditLog->id,
                'restore_payload' => $payload,
            ];
    }

    protected function buildRestoreNewValues(AuditLog $auditLog, User $authUser, array $restoredSnapshot = [])
    {
        $newValues = $restoredSnapshot;
        $newValues['restored_at'] = optional($auditLog->restored_at)->toDateTimeString();
        $newValues['restored_by'] = $authUser->name;

        return $newValues;
    }

    protected function sourceDeletedAuditLog(AuditLog $auditLog)
    {
        $sourceAuditLogId = data_get($auditLog->old_values, 'audit_log_id');

        if (! $sourceAuditLogId) {
            return null;
        }

        return AuditLog::find($sourceAuditLogId);
    }

    protected function restoredSnapshotForAuditLog(AuditLog $auditLog)
    {
        $payload = data_get($auditLog->old_values, 'restore_payload');

        if (! is_array($payload)) {
            $payload = $auditLog->restore_payload;
        }

        switch ($payload['type'] ?? null) {
            case 'user_bundle':
                return $this->userBundleSnapshot((int) ($payload['user_id'] ?? $auditLog->auditable_id));
            case 'service':
                return $this->serviceSnapshot((int) ($payload['service_id'] ?? $auditLog->auditable_id));
            default:
                return [];
        }
    }

    protected function userBundleSnapshot($userId)
    {
        if (! $userId) {
            return [];
        }

        $user = User::withTrashed()
            ->with([
                'shortcodes.service',
                'assignedRoles.permissions',
                'customPermissions',
            ])
            ->find($userId);

        if (! $user) {
            return [];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'status' => (bool) $user->status,
            'role' => $user->primaryRoleName(),
            'custom_permissions' => $user->assignedCustomPermissionSlugs(),
            'effective_permissions' => $user->effectivePermissionSlugs(),
            'shortcodes' => $user->shortcodes->map(function ($shortcode) {
                return [
                    'id' => $shortcode->id,
                    'shortcode' => $shortcode->shortcode,
                    'group' => $shortcode->group,
                    'services' => $shortcode->service->map(function ($service) {
                        return [
                            'id' => $service->id,
                            'service_name' => $service->service_name,
                            'prefix' => $service->prefix,
                        ];
                    })->values()->all(),
                ];
            })->values()->all(),
        ];
    }

    protected function serviceSnapshot($serviceId)
    {
        if (! $serviceId) {
            return [];
        }

        $service = Service::withTrashed()
            ->with(['shortcode.user', 'user'])
            ->find($serviceId);

        if (! $service) {
            return [];
        }

        return [
            'id' => $service->id,
            'shortcode_id' => $service->shortcode_id,
            'shortcode' => optional($service->shortcode)->shortcode,
            'user_id' => $service->user_id,
            'owner_name' => optional($service->user)->name ?: optional(optional($service->shortcode)->user)->name,
            'service_name' => $service->service_name,
            'service_description' => $service->service_description,
            'prefix' => $service->prefix,
            'verification_url' => $service->verification_url,
            'callback_url' => $service->callback_url,
        ];
    }

    protected function restoreLogHasSnapshot(array $values = [])
    {
        return isset($values['id']) || isset($values['name']) || isset($values['shortcodes']);
    }

    protected function formatAuditJson($values)
    {
        if (empty($values)) {
            return null;
        }

        $json = json_encode($values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($json !== false) {
            return $json;
        }

        return json_encode([
            'message' => 'Unable to format audit data.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    protected function buildAuditJsonDiff($oldValues, $newValues)
    {
        $normalizedOldValues = $this->canonicalizeAuditValue($oldValues);
        $normalizedNewValues = $this->canonicalizeAuditValue($newValues);
        $oldJson = $this->formatAuditJson($normalizedOldValues);
        $newJson = $this->formatAuditJson($normalizedNewValues);
        $oldLines = $oldJson ? preg_split("/\\r\\n|\\r|\\n/", $oldJson) : [];
        $newLines = $newJson ? preg_split("/\\r\\n|\\r|\\n/", $newJson) : [];
        $lineMatches = $this->computeAuditLineMatches($oldLines, $newLines);

        return [
            'has_changes' => $normalizedOldValues !== $normalizedNewValues,
            'previous_json' => $oldJson,
            'new_json' => $newJson,
            'previous_lines' => $this->decorateAuditDiffLines($oldLines, $lineMatches['left'], 'removed'),
            'new_lines' => $this->decorateAuditDiffLines($newLines, $lineMatches['right'], 'added'),
        ];
    }

    protected function computeAuditLineMatches(array $leftLines, array $rightLines)
    {
        $leftCount = count($leftLines);
        $rightCount = count($rightLines);
        $dp = array_fill(0, $leftCount + 1, array_fill(0, $rightCount + 1, 0));

        for ($leftIndex = $leftCount - 1; $leftIndex >= 0; $leftIndex--) {
            for ($rightIndex = $rightCount - 1; $rightIndex >= 0; $rightIndex--) {
                if ($leftLines[$leftIndex] === $rightLines[$rightIndex]) {
                    $dp[$leftIndex][$rightIndex] = $dp[$leftIndex + 1][$rightIndex + 1] + 1;
                } else {
                    $dp[$leftIndex][$rightIndex] = max($dp[$leftIndex + 1][$rightIndex], $dp[$leftIndex][$rightIndex + 1]);
                }
            }
        }

        $matchedLeftIndexes = [];
        $matchedRightIndexes = [];
        $leftIndex = 0;
        $rightIndex = 0;

        while ($leftIndex < $leftCount && $rightIndex < $rightCount) {
            if ($leftLines[$leftIndex] === $rightLines[$rightIndex]) {
                $matchedLeftIndexes[$leftIndex] = true;
                $matchedRightIndexes[$rightIndex] = true;
                $leftIndex++;
                $rightIndex++;
                continue;
            }

            if ($dp[$leftIndex + 1][$rightIndex] >= $dp[$leftIndex][$rightIndex + 1]) {
                $leftIndex++;
            } else {
                $rightIndex++;
            }
        }

        return [
            'left' => $matchedLeftIndexes,
            'right' => $matchedRightIndexes,
        ];
    }

    protected function decorateAuditDiffLines(array $lines, array $matchedIndexes, $changedType)
    {
        $decoratedLines = [];

        foreach ($lines as $index => $line) {
            $isMatched = isset($matchedIndexes[$index]);
            $type = $isMatched ? 'context' : $changedType;

            $decoratedLines[] = [
                'number' => $index + 1,
                'type' => $type,
                'marker' => $type === 'context' ? ' ' : ($type === 'added' ? '+' : '-'),
                'text' => $line,
            ];
        }

        return $decoratedLines;
    }

    protected function canonicalizeAuditValue($value)
    {
        if (! is_array($value)) {
            return $value;
        }

        if ($this->isAssociativeArray($value)) {
            ksort($value);
            foreach ($value as $key => $childValue) {
                $value[$key] = $this->canonicalizeAuditValue($childValue);
            }

            return $value;
        }

        $normalizedItems = array_map(function ($childValue) {
            return $this->canonicalizeAuditValue($childValue);
        }, $value);

        usort($normalizedItems, function ($left, $right) {
            return strcmp(
                json_encode($left, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                json_encode($right, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
            );
        });

        return array_values($normalizedItems);
    }

    protected function isAssociativeArray(array $value)
    {
        if ($value === []) {
            return false;
        }

        return array_keys($value) !== range(0, count($value) - 1);
    }

    protected function maskAuditUrl($url)
    {
        $url = trim((string) $url);

        if ($url === '') {
            return 'N/A';
        }

        $parts = parse_url($url);

        if ($parts === false || empty($parts['host'])) {
            return 'Hidden for your access level';
        }

        $maskedUrl = '';

        if (! empty($parts['scheme'])) {
            $maskedUrl .= $parts['scheme'].'://';
        }

        $maskedUrl .= $parts['host'];

        if (! empty($parts['port'])) {
            $maskedUrl .= ':'.$parts['port'];
        }

        $maskedUrl .= '/***';

        if (! empty($parts['query'])) {
            $maskedUrl .= '?***';
        }

        return $maskedUrl;
    }
}
