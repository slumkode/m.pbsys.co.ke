<?php

namespace App\Http\Controllers;

use App\Models\Shortcode;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Datatables extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function alltrans(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'transaction', 'view');
        $query = Transaction::query();
        $keywordId = (int) $request->input('keyword_id');

        if ($keywordId > 0) {
            $keywordRule = $this->transactionKeywordRuleForUser($authUser, $keywordId);

            abort_if(! $keywordRule, 403, 'You do not have permission to view transactions for this keyword.');

            $this->applyAccountKeywordTransactionRule($query, $keywordRule);
        }

        $this->applyTransactionVisibility($authUser, $query);

        return $this->transactionResponse($request, $query);
    }

    public function get_trans_by_shortcode(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'transaction', 'view');
        $shortcode = Shortcode::where('shortcode', $request->input('shortcode'))->firstOrFail();

        abort_if(
            ! $this->canAccessTransactionShortcode($authUser, $shortcode),
            403,
            'You do not have permission to view transactions for this shortcode.'
        );

        $query = Transaction::where('shortcode_id', $shortcode->id);
        $this->applyTransactionVisibility($authUser, $query);

        return $this->transactionResponse(
            $request,
            $query
        );
    }

    public function get_trans_by_service(Request $request)
    {
        $authUser = $this->requireActionPermission($request, 'transaction', 'view');
        $shortcode = Shortcode::where('shortcode', $request->input('shortcode'))->firstOrFail();
        $serviceName = (string) $request->input('service');

        abort_if(
            ! $this->canAccessTransactionService($authUser, $shortcode->id, $serviceName),
            403,
            'You do not have permission to view transactions for this service.'
        );

        $query = Transaction::where('shortcode_id', $shortcode->id)
            ->where('type', $serviceName);
        $this->applyTransactionVisibility($authUser, $query);

        return $this->transactionResponse($request, $query);
    }

    protected function transactionResponse(Request $request, $query)
    {
        $authUser = $request->user();
        $startDate = $this->parseTransactionFilterDate($request->input('date_start'), false);
        $endDate = $this->parseTransactionFilterDate($request->input('date_end'), true);

        if ($startDate && $endDate && $startDate->greaterThan($endDate)) {
            $swap = $startDate;
            $startDate = $endDate;
            $endDate = $swap;
        }

        if ($startDate) {
            $query->where('trans_time', '>=', $startDate->format('Y-m-d H:i:s'));
        }

        if ($endDate) {
            $query->where('trans_time', '<=', $endDate->format('Y-m-d H:i:s'));
        }

        $columns = [
            0 => 'shortcode_id',
            1 => 'customer_name',
            2 => 'msisdn',
            3 => 'transaction_code',
            4 => 'account',
            5 => 'type',
            6 => 'channel',
            7 => 'trans_time',
            8 => 'amount',
        ];

        $search = trim((string)$request->input('search.value'));
        $limit = (int)$request->input('length', 10);
        $start = (int)$request->input('start', 0);
        $order = $columns[$request->input('order.0.column', 7)] ?? 'trans_time';
        $dir = strtolower((string)$request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $totalData = (clone $query)->count();
        $filteredQuery = clone $query;

        if ($search !== '')
            {
                abort_if(! $authUser->hasPermission('transaction.search'), 403, 'You do not have permission to search transactions.');

                $filteredQuery->where(function ($builder) use ($search) {
                    $builder->where('transaction_code', 'LIKE', "%{$search}%")
                        ->orWhere('account', 'LIKE', "%{$search}%")
                        ->orWhere('customer_name', 'LIKE', "%{$search}%")
                        ->orWhere('msisdn', 'LIKE', "%{$search}%")
                        ->orWhere('amount', 'LIKE', "%{$search}%");
                });
            }

        $totalFiltered = (clone $filteredQuery)->count();
        $sum = (clone $filteredQuery)->sum('amount');
        $posts = $filteredQuery->offset($start)
            ->limit($limit)
            ->orderBy($order, $dir)
            ->get();

        $data = [];

        foreach ($posts as $post)
            {
                $shortcode = Shortcode::where('id', $post->shortcode_id)->first();
                $msisdn = $authUser->hasPermission('transaction.view_msisdn')
                    ? $post->msisdn
                    : $this->maskMsisdn($post->msisdn);

                $data[] = [
                    'shortcode' => $shortcode ? $shortcode->shortcode : '',
                    'transaction_code' => $post->transaction_code,
                    'account' => $post->account,
                    'amount' => $post->amount,
                    'msisdn' => $msisdn,
                    'customer_name' => $post->customer_name,
                    'origin' => $post->type,
                    'channel' => $post->channel,
                    'transaction_time' => date('j M Y h:i a', strtotime($post->trans_time)),
                ];
            }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
            'total' => $sum,
        ]);
    }

    protected function parseTransactionFilterDate($value, $endOfDay = false)
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
}
