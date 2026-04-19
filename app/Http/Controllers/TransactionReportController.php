<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceAccountKeyword;
use App\Models\Shortcode;
use App\Models\Transaction;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransactionReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'transaction_reports', 'view');
        $defaultStart = Carbon::now()->startOfMonth()->startOfDay();
        $defaultEnd = Carbon::now()->endOfDay();

        return view('admin.modules.transaction-reports', $this->baseViewData($request, [
            'defaultDateFrom' => $defaultStart,
            'defaultDateTo' => $defaultEnd,
            'shortcodeOptions' => $this->reportShortcodeOptions($authUser),
            'serviceOptions' => $this->reportServiceOptions($authUser),
            'keywordOptions' => $this->reportKeywordOptions($authUser),
        ]));
    }

    public function datatable(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'transaction_reports', 'view');
        $columns = [
            0 => 'report_day',
            1 => 'transaction_count',
            2 => 'total_amount',
            3 => 'shortcode_count',
            4 => 'service_count',
        ];

        $query = Transaction::query();
        $this->applyTransactionVisibility($authUser, $query);
        $this->applyFilters($authUser, $query, $request);

        $groupedForCount = (clone $query)
            ->selectRaw('DATE(trans_time) as report_day')
            ->groupBy(DB::raw('DATE(trans_time)'));
        $totalFiltered = DB::query()->fromSub($groupedForCount, 'daily_reports')->count();
        $limit = max(10, min((int) $request->input('length', 25), 100));
        $start = max((int) $request->input('start', 0), 0);
        $order = $columns[$request->input('order.0.column', 0)] ?? 'report_day';
        $dir = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $reports = (clone $query)
            ->selectRaw('DATE(trans_time) as report_day')
            ->selectRaw('COUNT(*) as transaction_count')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('COUNT(DISTINCT shortcode_id) as shortcode_count')
            ->selectRaw("COUNT(DISTINCT CONCAT(shortcode_id, ':', type)) as service_count")
            ->groupBy(DB::raw('DATE(trans_time)'))
            ->orderBy($order, $dir)
            ->offset($start)
            ->limit($limit)
            ->get();

        $data = [];
        $keywordId = (int) $request->input('keyword_id');

        foreach ($reports as $report) {
            $day = Carbon::parse($report->report_day);
            $detailQuery = [
                'start' => $day->copy()->startOfDay()->format('Y-m-d H:i:s'),
                'end' => $day->copy()->endOfDay()->format('Y-m-d H:i:s'),
            ];

            if ($keywordId > 0) {
                $detailQuery['keyword'] = $keywordId;
            }

            $details = $authUser->hasPermission('transaction.view')
                ? '<a class="btn btn-default btn-sm" href="'.url('/transaction').'?'.http_build_query($detailQuery).'">View Transactions</a>'
                : '<span class="text-muted">No action</span>';

            $data[] = [
                'day' => $day->format('d M Y'),
                'transactions' => number_format((int) $report->transaction_count),
                'total_amount' => 'Ksh '.number_format((float) $report->total_amount, 2),
                'shortcodes' => number_format((int) $report->shortcode_count),
                'services' => number_format((int) $report->service_count),
                'action' => $details,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalFiltered),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ]);
    }

    protected function applyFilters(User $authUser, $query, Request $request)
    {
        list($dateFrom, $dateTo) = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );
        $shortcodeId = (int) $request->input('shortcode_id');
        $serviceKey = trim((string) $request->input('service_key'));
        $keywordId = (int) $request->input('keyword_id');
        $account = trim((string) $request->input('account'));
        $transactionCode = trim((string) $request->input('transaction_code'));
        $customer = trim((string) $request->input('customer'));

        if ($dateFrom) {
            $query->where('trans_time', '>=', $dateFrom->format('Y-m-d H:i:s'));
        }

        if ($dateTo) {
            $query->where('trans_time', '<=', $dateTo->format('Y-m-d H:i:s'));
        }

        if ($shortcodeId > 0) {
            $query->where('shortcode_id', $shortcodeId);
        }

        if ($serviceKey !== '') {
            $parts = explode('|', $serviceKey, 2);

            if (count($parts) === 2) {
                $query->where('shortcode_id', (int) $parts[0])
                    ->where('type', $parts[1]);
            }
        }

        if ($keywordId > 0) {
            $keywordRule = $this->transactionKeywordRuleForUser($authUser, $keywordId);

            abort_if(! $keywordRule, 403, 'You do not have permission to view reports for this keyword.');

            $this->applyAccountKeywordTransactionRule($query, $keywordRule);
        }

        if ($account !== '' || $transactionCode !== '' || $customer !== '') {
            abort_if(! $authUser->hasPermission('transaction.search'), 403, 'You do not have permission to search transactions.');
        }

        if ($account !== '') {
            $query->where('account', 'LIKE', "%{$account}%");
        }

        if ($transactionCode !== '') {
            $query->where('transaction_code', 'LIKE', "%{$transactionCode}%");
        }

        if ($customer !== '') {
            $query->where(function ($builder) use ($customer) {
                $builder->where('customer_name', 'LIKE', "%{$customer}%")
                    ->orWhere('msisdn', 'LIKE', "%{$customer}%");
            });
        }
    }

    protected function resolveDateRange($dateFrom, $dateTo)
    {
        $start = $this->parseDate($dateFrom, false);
        $end = $this->parseDate($dateTo, true);

        if ($start && $end && $start->gt($end)) {
            return [$end->copy(), $start->copy()];
        }

        return [$start, $end];
    }

    protected function parseDate($value, $endOfDay = false)
    {
        if (! $value) {
            return null;
        }

        try {
            $date = Carbon::parse($value);
        } catch (\Exception $exception) {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        }

        return $date;
    }

    protected function reportShortcodeOptions(User $authUser)
    {
        if ($authUser->canViewAllTransactions()) {
            return Shortcode::orderBy('shortcode')->get(['id', 'shortcode', 'group']);
        }

        $shortcodeIds = array_merge(
            $authUser->transactionVisibleShortcodeIdsFromServices(),
            collect($authUser->transactionVisibleServicePairs())->pluck('shortcode_id')->all(),
            $authUser->transactionVisibleShortcodeIdsFromKeywords()
        );

        return Shortcode::whereIn('id', array_values(array_unique(array_map('intval', $shortcodeIds))))
            ->orderBy('shortcode')
            ->get(['id', 'shortcode', 'group']);
    }

    protected function reportServiceOptions(User $authUser)
    {
        if ($authUser->canViewAllTransactions()) {
            return Service::with('shortcode')
                ->orderBy('service_name')
                ->get();
        }

        $serviceIds = array_values(array_unique(array_merge(
            $authUser->transactionVisibleServiceIds(),
            $authUser->transactionVisibleKeywordServiceIds()
        )));

        return Service::with('shortcode')
            ->whereIn('id', $serviceIds)
            ->orderBy('service_name')
            ->get();
    }

    protected function reportKeywordOptions(User $authUser)
    {
        if (! User::supportsAccountKeywordAccess()) {
            return collect();
        }

        $query = ServiceAccountKeyword::with('service.shortcode')
            ->where('status', 1)
            ->whereHas('service', function ($serviceQuery) {
                if (Schema::hasColumn('services', 'deleted_at')) {
                    $serviceQuery->whereNull('deleted_at');
                }
            });

        if (! $authUser->canViewAllTransactions()) {
            $keywordIds = collect($authUser->transactionVisibleKeywordRules())
                ->pluck('keyword_id')
                ->map(function ($keywordId) {
                    return (int) $keywordId;
                })
                ->unique()
                ->values()
                ->all();

            if (empty($keywordIds)) {
                return collect();
            }

            $query->whereIn('id', $keywordIds);
        }

        return $query->orderBy('keyword_name')->get();
    }
}
