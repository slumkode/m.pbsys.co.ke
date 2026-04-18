<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Service;
use App\Models\ServiceAccountKeyword;
use App\Models\Shortcode;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function baseViewData(Request $request, array $data = [])
    {
        $authUser = $request->user();

        return array_merge([
            'authUser' => $authUser,
            'user' => $authUser,
            'userimg' => $authUser ? $authUser->accessEntries()->where('access_name', 'thumbnail')->first() : null,
            'permissionCatalog' => User::permissionCatalog(),
            'permissionDefinitions' => User::permissionDefinitions(),
        ], $data);
    }

    protected function requirePagePermission(Request $request, $permission)
    {
        return $this->requireActionPermission($request, $permission, 'view');
    }

    protected function requireActionPermission(Request $request, $page, $action = 'view')
    {
        $authUser = $request->user();
        $slug = User::normalizePermissionSlug($page.'.'.$action);

        if ($page === 'profile' || $slug === 'profile.view' || $slug === 'profile.update') {
            abort_if(! $authUser, 403, 'You do not have permission to access this page.');
            return $authUser;
        }

        abort_if(! $authUser || ! $slug || ! $authUser->hasPermission($slug), 403, 'You do not have permission to access this page.');

        return $authUser;
    }

    protected function accessibleShortcodeQuery(User $user)
    {
        $query = Shortcode::query()->with('user');

        if (! $user->canViewAllShortcodes()) {
            $this->applyIdScope($query, 'id', $user->viewableShortcodeIds());
        }

        return $query;
    }

    protected function manageableShortcodeQuery(User $user)
    {
        $query = Shortcode::query()->with('user');

        if (! $user->canViewAllShortcodes()) {
            $this->applyIdScope($query, 'id', $user->manageableShortcodeIds());
        }

        return $query;
    }

    protected function serviceSelectableShortcodeQuery(User $user)
    {
        $query = Shortcode::query()->with('user');

        if (! $user->canViewAllShortcodes() && ! $user->canViewAllServices()) {
            $this->applyIdScope($query, 'id', $user->serviceSelectableShortcodeIds());
        }

        return $query;
    }

    protected function accessibleServiceQuery(User $user)
    {
        $query = Service::query()->with(['shortcode.user', 'user']);

        if (! $user->canViewAllServices()) {
            $this->applyIdScope($query, 'id', $user->viewableServiceIds());
        }

        return $query;
    }

    protected function manageableServiceQuery(User $user)
    {
        $query = Service::query()->with(['shortcode.user', 'user']);

        if (! $user->canViewAllServices() && ! $user->canViewAllShortcodes()) {
            $this->applyIdScope($query, 'id', $user->manageableServiceIds());
        }

        return $query;
    }

    protected function transactionVisibleServiceQuery(User $user)
    {
        $query = Service::query()->with(['shortcode.user', 'user']);

        if ($user->canViewAllTransactions()) {
            return $query;
        }

        $serviceIds = $user->transactionVisibleServiceIds();

        $this->applyIdScope($query, 'services.id', $serviceIds);

        return $query;
    }

    protected function resolveAccessibleShortcode(User $user, $identifier, $column = 'id')
    {
        $shortcode = $this->accessibleShortcodeQuery($user)->where($column, $identifier)->first();

        abort_if(! $shortcode, 403, 'You do not have permission to access this shortcode.');

        return $shortcode;
    }

    protected function resolveManageableShortcode(User $user, $identifier, $column = 'id')
    {
        $shortcode = $this->manageableShortcodeQuery($user)->where($column, $identifier)->first();

        abort_if(! $shortcode, 403, 'You do not have permission to manage this shortcode.');

        return $shortcode;
    }

    protected function resolveAccessibleService(User $user, $identifier, $column = 'id')
    {
        $service = $this->accessibleServiceQuery($user)->where($column, $identifier)->first();

        abort_if(! $service, 403, 'You do not have permission to access this service.');

        return $service;
    }

    protected function resolveManageableService(User $user, $identifier, $column = 'id')
    {
        $service = $this->manageableServiceQuery($user)->where($column, $identifier)->first();

        abort_if(! $service, 403, 'You do not have permission to manage this service.');

        return $service;
    }

    protected function redirectPathForUser(User $user)
    {
        $paths = [
            'dashboard' => '/dashboard',
            'documentation' => '/documentation',
            'shortcode' => '/shortcode',
            'services' => '/services',
            'transaction' => '/transaction',
            'transaction_reports' => '/transaction-reports',
            'users' => '/users',
            'audit_logs' => '/audit-logs',
        ];

        foreach ($paths as $permission => $path)
            {
                if ($user->canAccessPage($permission))
                    {
                        return $path;
                    }
            }

        if ($user->hasPermission('users.manage_roles')) {
            return '/roles';
        }

        return '/profile';
    }

    protected function applyTransactionVisibility(User $user, $query)
    {
        $this->applyActiveServiceTransactionScope($query);

        if (! $user->canViewAllTransactions()) {
            $servicePairs = $user->transactionVisibleServicePairs();
            $keywordRules = $user->transactionVisibleKeywordRules();
            $applyAmountRanges = User::supportsTransactionAmountLimits()
                || User::supportsServiceAmountLimitHistory()
                || User::supportsAccountKeywordLimitHistory();

            $query->where(function ($visibilityQuery) use ($servicePairs, $keywordRules, $applyAmountRanges) {
                $hasConstraint = false;

                foreach ($servicePairs as $pair) {
                    $method = $hasConstraint ? 'orWhere' : 'where';

                    $visibilityQuery->{$method}(function ($serviceQuery) use ($pair, $applyAmountRanges) {
                        $serviceQuery->where('shortcode_id', (int) $pair['shortcode_id'])
                            ->where('type', (string) $pair['service_name']);

                        if ($applyAmountRanges) {
                            $this->applyServiceAmountLimitWindows($serviceQuery, $pair);
                        }
                    });

                    $hasConstraint = true;
                }

                foreach ($keywordRules as $rule) {
                    $method = $hasConstraint ? 'orWhere' : 'where';

                    $visibilityQuery->{$method}(function ($keywordQuery) use ($rule, $applyAmountRanges) {
                        $this->applyAccountKeywordTransactionRule($keywordQuery, $rule, $applyAmountRanges);
                    });

                    $hasConstraint = true;
                }

                if (! $hasConstraint) {
                    $visibilityQuery->whereRaw('1 = 0');
                }
            });
        }

        return $query;
    }

    protected function applyAccountKeywordTransactionRule($query, array $rule, $applyAmountRanges = true)
    {
        $query->where('shortcode_id', (int) $rule['shortcode_id']);

        $this->applyActiveServiceTransactionScope($query);
        $this->applyAccountKeywordMatch($query, $rule['match_type'] ?? 'contains', $rule['match_pattern'] ?? '');

        if ($applyAmountRanges) {
            if (array_key_exists('assignment_amount_ranges', $rule)) {
                $this->applyAmountRangeUnion($query, $rule['assignment_amount_ranges']);
            } else {
                $this->applyServiceAmountLimitWindows($query, $rule);
            }
        }

        return $query;
    }

    protected function transactionKeywordRuleForUser(User $user, $keywordId)
    {
        if (! User::supportsAccountKeywordAccess()) {
            return null;
        }

        $keywordId = (int) $keywordId;

        if ($keywordId <= 0) {
            return null;
        }

        if ($user->canViewAllTransactions()) {
            $keyword = ServiceAccountKeyword::with(['service.shortcode', 'service.user', 'users'])
                ->where('status', true)
                ->whereKey($keywordId)
                ->first();

            return $keyword ? $this->adminKeywordTransactionRule($keyword) : null;
        }

        foreach ($user->transactionVisibleKeywordRules() as $rule) {
            if ((int) ($rule['keyword_id'] ?? 0) === $keywordId) {
                return $rule;
            }
        }

        return null;
    }

    protected function adminKeywordTransactionRule(ServiceAccountKeyword $keyword)
    {
        $keyword->loadMissing(['service.shortcode', 'service.user', 'users']);

        if (! $keyword->service || ! $keyword->service->shortcode) {
            return null;
        }

        return [
            'keyword_id' => (int) $keyword->id,
            'keyword_name' => (string) $keyword->keyword_name,
            'match_type' => (string) $keyword->match_type,
            'match_pattern' => (string) $keyword->match_pattern,
            'service_id' => (int) $keyword->service->id,
            'shortcode_id' => (int) $keyword->service->shortcode_id,
            'service_name' => (string) $keyword->service->service_name,
            'service_owner' => optional($keyword->service->user)->name ?: 'Default owner',
            'assigned_users' => $keyword->users->pluck('name')->filter()->implode(', '),
            'assignment_amount_ranges' => $this->keywordAssignmentAmountRanges($keyword),
            'bypass_amount_limit_history' => true,
            'amount_limit_windows' => [],
        ];
    }

    protected function keywordAssignmentAmountRanges(ServiceAccountKeyword $keyword)
    {
        $ranges = [];

        foreach ($keyword->users as $user) {
            $ranges[] = [
                'transaction_min_amount' => $user->pivot->transaction_min_amount !== null ? (float) $user->pivot->transaction_min_amount : null,
                'transaction_max_amount' => $user->pivot->transaction_max_amount !== null ? (float) $user->pivot->transaction_max_amount : null,
            ];
        }

        return $ranges;
    }

    protected function applyAccountKeywordMatch($query, $matchType, $matchPattern)
    {
        $matchTypes = collect(explode(',', strtolower((string) $matchType)))
            ->map(function ($type) {
                return trim($type);
            })
            ->filter(function ($type) {
                return in_array($type, ['contains', 'starts_with', 'ends_with', 'exact', 'regex'], true);
            })
            ->unique()
            ->values()
            ->all();
        $matchPattern = trim((string) $matchPattern);

        if ($matchPattern === '') {
            return $query->whereRaw('1 = 0');
        }

        if (empty($matchTypes)) {
            $matchTypes = ['contains'];
        }

        $tokens = $this->accountKeywordTokens($matchPattern);

        if (empty($tokens) && ! in_array('regex', $matchTypes, true)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($accountQuery) use ($matchTypes, $matchPattern, $tokens) {
            $hasRule = false;

            foreach ($matchTypes as $matchType) {
                if ($matchType === 'regex') {
                    $method = $hasRule ? 'orWhereRaw' : 'whereRaw';
                    $accountQuery->{$method}('LOWER(account) REGEXP ?', [strtolower($matchPattern)]);
                    $hasRule = true;
                    continue;
                }

                foreach ($tokens as $token) {
                    $method = $hasRule ? 'orWhereRaw' : 'whereRaw';
                    $token = strtolower($token);

                    if ($matchType === 'starts_with') {
                        $accountQuery->{$method}('LOWER(account) LIKE ?', [$token.'%']);
                    } elseif ($matchType === 'ends_with') {
                        $accountQuery->{$method}('LOWER(account) LIKE ?', ['%'.$token]);
                    } elseif ($matchType === 'exact') {
                        $accountQuery->{$method}('LOWER(account) = ?', [$token]);
                    } else {
                        $accountQuery->{$method}('LOWER(account) LIKE ?', ['%'.$token.'%']);
                    }

                    $hasRule = true;
                }
            }
        });
    }

    protected function accountKeywordTokens($matchPattern)
    {
        return collect(preg_split('/[\r\n,]+/', (string) $matchPattern))
            ->map(function ($token) {
                return trim($token);
            })
            ->filter(function ($token) {
                return $token !== '';
            })
            ->unique()
            ->values()
            ->all();
    }

    protected function applyServiceAmountLimitWindows($query, array $pair)
    {
        $windows = $pair['amount_limit_windows'] ?? [];
        $bypassHistory = ! empty($pair['bypass_amount_limit_history']);

        if (! $bypassHistory && ! empty($windows)) {
            $query->where(function ($windowQuery) use ($windows) {
                $hasWindow = false;

                foreach ($windows as $window) {
                    $method = $hasWindow ? 'orWhere' : 'where';

                    $windowQuery->{$method}(function ($singleWindowQuery) use ($window) {
                        if (! empty($window['effective_from'])) {
                            $singleWindowQuery->where('trans_time', '>=', $window['effective_from']);
                        }

                        if (! empty($window['effective_to'])) {
                            $singleWindowQuery->where('trans_time', '<', $window['effective_to']);
                        }

                        if (($window['transaction_min_amount'] ?? null) !== null) {
                            $singleWindowQuery->where('amount', '>=', (float) $window['transaction_min_amount']);
                        }

                        if (($window['transaction_max_amount'] ?? null) !== null) {
                            $singleWindowQuery->where('amount', '<=', (float) $window['transaction_max_amount']);
                        }
                    });

                    $hasWindow = true;
                }
            });

            return $query;
        }

        if (($pair['transaction_min_amount'] ?? null) !== null) {
            $query->where('amount', '>=', (float) $pair['transaction_min_amount']);
        }

        if (($pair['transaction_max_amount'] ?? null) !== null) {
            $query->where('amount', '<=', (float) $pair['transaction_max_amount']);
        }

        return $query;
    }

    protected function applyAmountRangeUnion($query, array $ranges)
    {
        $ranges = array_values(array_filter($ranges, function ($range) {
            return is_array($range);
        }));

        if (empty($ranges)) {
            return $query;
        }

        $query->where(function ($amountQuery) use ($ranges) {
            $hasRange = false;

            foreach ($ranges as $range) {
                $method = $hasRange ? 'orWhere' : 'where';

                $amountQuery->{$method}(function ($singleRangeQuery) use ($range) {
                    $minAmount = $range['transaction_min_amount'] ?? null;
                    $maxAmount = $range['transaction_max_amount'] ?? null;

                    if ($minAmount !== null) {
                        $singleRangeQuery->where('amount', '>=', (float) $minAmount);
                    }

                    if ($maxAmount !== null) {
                        $singleRangeQuery->where('amount', '<=', (float) $maxAmount);
                    }

                    if ($minAmount === null && $maxAmount === null) {
                        $singleRangeQuery->whereRaw('1 = 1');
                    }
                });

                $hasRange = true;
            }
        });

        return $query;
    }

    protected function applyActiveServiceTransactionScope($query)
    {
        if (! Schema::hasColumn('services', 'deleted_at')) {
            return $query;
        }

        return $query->whereExists(function ($serviceQuery) {
            $serviceQuery->select(DB::raw(1))
                ->from('services as active_transaction_services')
                ->whereColumn('active_transaction_services.shortcode_id', 'transactions.shortcode_id')
                ->whereColumn('active_transaction_services.service_name', 'transactions.type')
                ->whereNull('active_transaction_services.deleted_at');
        });
    }

    protected function ensureServiceAmountLimitHistory($serviceId, array $userIds, $effectiveFrom = '2000-01-01 00:00:00')
    {
        if (! User::supportsServiceAmountLimitHistory()) {
            return;
        }

        $now = now();

        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId <= 0) {
                continue;
            }

            $historyExists = DB::table('service_user_amount_limit_histories')
                ->where('service_id', (int) $serviceId)
                ->where('user_id', $userId)
                ->exists();
            $activeHistoryExists = DB::table('service_user_amount_limit_histories')
                ->where('service_id', (int) $serviceId)
                ->where('user_id', $userId)
                ->whereNull('effective_to')
                ->exists();

            if ($activeHistoryExists) {
                continue;
            }

            DB::table('service_user_amount_limit_histories')->insert([
                'service_id' => (int) $serviceId,
                'user_id' => $userId,
                'transaction_min_amount' => null,
                'transaction_max_amount' => null,
                'effective_from' => $historyExists ? $now : $effectiveFrom,
                'effective_to' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function closeServiceAmountLimitHistory($serviceId, array $userIds)
    {
        if (! User::supportsServiceAmountLimitHistory() || empty($userIds)) {
            return;
        }

        $now = now();

        DB::table('service_user_amount_limit_histories')
            ->where('service_id', (int) $serviceId)
            ->whereIn('user_id', array_unique(array_map('intval', $userIds)))
            ->whereNull('effective_to')
            ->update([
                'effective_to' => $now,
                'updated_at' => $now,
            ]);
    }

    protected function applyTransactionAmountRange(User $user, $query)
    {
        if (! $user->hasTransactionAmountRangeLimit()) {
            return $query;
        }

        list($minAmount, $maxAmount) = $user->transactionAmountRange();

        if ($minAmount !== null) {
            $query->where('amount', '>=', $minAmount);
        }

        if ($maxAmount !== null) {
            $query->where('amount', '<=', $maxAmount);
        }

        return $query;
    }

    protected function canAccessTransactionShortcode(User $user, Shortcode $shortcode)
    {
        return $user->canViewAllTransactions()
            || in_array((int) $shortcode->id, $user->transactionVisibleShortcodeIds(), true)
            || in_array((int) $shortcode->id, $user->transactionVisibleShortcodeIdsFromServices(), true)
            || in_array((int) $shortcode->id, $user->transactionVisibleShortcodeIdsFromKeywords(), true);
    }

    protected function canAccessTransactionService(User $user, $shortcodeId, $serviceName)
    {
        if ($user->canViewAllTransactions()) {
            return true;
        }

        foreach ($user->transactionVisibleServicePairs() as $pair) {
            if ((int) $pair['shortcode_id'] === (int) $shortcodeId && (string) $pair['service_name'] === (string) $serviceName) {
                return true;
            }
        }

        foreach ($user->transactionVisibleKeywordRules() as $rule) {
            if ((int) $rule['shortcode_id'] === (int) $shortcodeId) {
                return true;
            }
        }

        return false;
    }

    protected function applyIdScope($query, $column, array $ids)
    {
        if (empty($ids)) {
            $query->whereRaw('1 = 0');
            return $query;
        }

        return $query->whereIn($column, $ids);
    }

    protected function recordAudit(Request $request, $action, $subject = null, array $oldValues = [], array $newValues = [], array $options = [])
    {
        $authUser = $request->user();

        if (! $authUser)
            {
                return;
            }

        $payload = [
            'user_id' => $authUser->id,
            'user_name' => $authUser->name,
            'action' => $action,
            'auditable_type' => $options['auditable_type'] ?? $this->resolveAuditType($subject),
            'auditable_id' => $options['auditable_id'] ?? $this->resolveAuditId($subject),
            'auditable_label' => $options['auditable_label'] ?? $this->resolveAuditLabel($subject),
            'page_name' => $options['page_name'] ?? ucfirst(str_replace('-', ' ', trim($request->path(), '/'))),
            'old_values' => $this->sanitizeAuditPayload($oldValues),
            'new_values' => $this->sanitizeAuditPayload($newValues),
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
        ];

        if (Schema::hasColumn('audit_logs', 'login_activity_id')) {
            $payload['login_activity_id'] = $options['login_activity_id']
                ?? ($request->hasSession() ? $request->session()->get('auth.login_activity_id') : null);
        }

        if (Schema::hasColumn('audit_logs', 'is_restorable')) {
            $payload['is_restorable'] = (bool) ($options['is_restorable'] ?? false);
        }

        if (Schema::hasColumn('audit_logs', 'restore_payload')) {
            $payload['restore_payload'] = $options['restore_payload'] ?? null;
        }

        if (Schema::hasColumn('audit_logs', 'restored_at')) {
            $payload['restored_at'] = $options['restored_at'] ?? null;
        }

        if (Schema::hasColumn('audit_logs', 'restored_by')) {
            $payload['restored_by'] = $options['restored_by'] ?? null;
        }

        AuditLog::create($payload);
    }

    protected function sanitizeAuditPayload(array $payload = [])
    {
        $sanitized = [];

        foreach ($payload as $key => $value)
            {
                if (is_array($value))
                    {
                        $sanitized[$key] = $this->sanitizeAuditPayload($value);
                        continue;
                    }

                if (in_array(strtolower((string)$key), ['password', 'password_confirmation', 'consumerkey', 'consumersecret', 'passkey', 'remember_token'], true))
                    {
                        $sanitized[$key] = '[hidden]';
                        continue;
                    }

                $sanitized[$key] = $value;
            }

        return $sanitized;
    }

    protected function resolveAuditType($subject)
    {
        if ($subject instanceof Model)
            {
                return class_basename($subject);
            }

        return $subject ? (string)$subject : null;
    }

    protected function resolveAuditId($subject)
    {
        return $subject instanceof Model ? $subject->getKey() : null;
    }

    protected function resolveAuditLabel($subject)
    {
        if ($subject instanceof Model)
            {
                foreach (['name', 'username', 'email', 'shortcode', 'service_name'] as $field)
                    {
                        $value = $subject->getAttributeValue($field);

                        if (is_scalar($value) && trim((string) $value) !== '')
                            {
                                return Str::limit((string) $value, 255, '');
                            }
                    }

                return Str::limit(class_basename($subject).' #'.$subject->getKey(), 255, '');
            }

        if ($subject === null) {
            return null;
        }

        return Str::limit((string) $subject, 255, '');
    }

    protected function maskMsisdn($value)
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 4).str_repeat('*', max(strlen($digits) - 7, 3)).substr($digits, -3);
    }

    protected function timestamp()
    {
        return Carbon::now();
    }
}
