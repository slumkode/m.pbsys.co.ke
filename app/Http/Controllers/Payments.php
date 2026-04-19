<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\ServiceAccountKeyword;
use App\Models\Shortcode;
use App\Models\Setting;
use App\Models\Transaction;
use App\User;
use App\Utils\Mpesa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;


class Payments extends Controller
	{

		public $mpesa;
		public $data;
	    public function __construct()
			{
                $this->middleware('auth');
				$this->mpesa            =   new MPesa();

			}

        public function index(Request $request)
			{
                $authUser = $request->user();

                if (! $authUser->canAccessPage('dashboard'))
                    {
                        return redirect($this->redirectPathForUser($authUser));
                    }

                if ($authUser->hasPermission('dashboard.search')) {
                    $request->validate([
                        'start' => 'nullable|date',
                        'end' => 'nullable|date',
                    ]);
                } else {
                    $request->merge(['start' => null, 'end' => null]);
                }

                list($startDate, $endDate) = $this->resolveDashboardDateRange($request);

                return view('admin.modules.dashboard', $this->buildDashboardData($request, $startDate, $endDate));
			}
        public function index2(Request $request)
            {
                $authUser = $request->user();

                if (! $authUser || ! $authUser->canAccessPage('dashboard'))
                    {
                        return redirect($authUser ? $this->redirectPathForUser($authUser) : '/');
                    }

                if (! $authUser->hasPermission('dashboard.search')) {
                    return redirect()->route('dashboard');
                }

                $request->validate([
                    'start' => 'required|date',
                    'end' => 'required|date',
                ]);

                list($startDate, $endDate) = $this->resolveDashboardDateRange($request);

                return redirect()->route('dashboard', [
                    'start' => $startDate->format('Y-m-d H:i:s'),
                    'end' => $endDate->format('Y-m-d H:i:s'),
                ]);
            }
        public function shortcode(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'shortcode', 'view');

                return view('admin.modules.shortcode', $this->baseViewData($request, [
                    'shortcode' => $this->accessibleShortcodeQuery($authUser)->paginate(10),
                    'shortcodeOwners' => $authUser->hasPermission('shortcode.assign_owner')
                        ? User::orderBy('name')->get(['id', 'name', 'email'])
                        : collect(),
                ]));


            }
        public function checktrans(Request $request, $shortcode,$service = null)
            {
                $authUser = $this->requireActionPermission($request, 'transaction', 'view');
                $allowedShortcode = Shortcode::where('shortcode', $shortcode)->firstOrFail();

                if ($service === null) {
                    abort_if(
                        ! $this->canAccessTransactionShortcode($authUser, $allowedShortcode),
                        403,
                        'You do not have permission to view transactions for this shortcode.'
                    );
                } else {
                    abort_if(
                        ! $this->canAccessTransactionService($authUser, $allowedShortcode->id, $service),
                        403,
                        'You do not have permission to view transactions for this service.'
                    );
                }

                return view('admin.modules.gtrans', $this->baseViewData($request, [
                    'service' => $service,
                    'shortcode' => $allowedShortcode->shortcode,
                ]));
            }
        public function saveshortcode(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'shortcode', 'create');
                $validatedData = $request->validate([
                    'shortcode'         =>  'required|unique:shortcodes|integer',
                    'group'             =>  'required|string|max:255',
                    'type'              =>  'required',
                    'sharing_mode'      =>  'required|in:dedicated,shared',
                    'owner_user_id'     =>  'nullable|integer|exists:users,id',
                    'consumerkey'       =>  'required',
                    'consumersecret'    =>  'required',
                    'passkey'           =>  'nullable',
                    'transaction_status_initiator' => 'nullable|string|max:255',
                    'transaction_status_credential' => 'nullable|string',
                    'transaction_status_credential_encrypted' => 'nullable|boolean',
                    'transaction_status_identifier' => 'nullable|in:shortcode,tillnumber,msisdn',
                ]);

                if($validatedData)
                    {
                        $shortcode                  =   new Shortcode();
                        $shortcode->shortcode       =   $request->shortcode;
                        $shortcode->shortcode_type  =   $request->type;
                        $shortcode->group           =   trim(strtolower($request->group));
                        $shortcode->sharing_mode    =   $request->sharing_mode;
                        $shortcode->consumerkey     =   trim($request->consumerkey);
                        $shortcode->consumersecret  =   trim($request->consumersecret);
                        $shortcode->passkey         =   trim($request->passkey);
                        $shortcode->user_id         =   $this->resolveShortcodeOwnerId($authUser, $request->input('owner_user_id'));
                        $this->fillShortcodeTransactionStatusSettings($shortcode, $request, true);
                        $req                        =   $shortcode->save();
                        if($req)
                            {
                                $this->recordAudit($request, 'created', $shortcode, [], $this->shortcodeAuditData($shortcode), [
                                    'page_name' => 'Shortcode',
                                ]);
                                return array('status'=>TRUE,'msg'=>'Shortcode insert successful','header'=>'shortcode');
                            }
                        else
                            {
                                return array('status'=>False,'msg'=>'Shortcode insert not successful','header'=>'shortcode');
                            }
                    }
                else
                    {
                       return array('status'=>FALSE,'msg'=>$validatedData->errors());
                    }

            }
        public function editshortcode(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'shortcode', 'update');
                $validatedData = $request->validate([
                    'id'                =>  'required|integer|exists:shortcodes,id',
                    'shortcode'         =>  'required|integer',
                    'group'             =>  'required|string|max:255',
                    'type'              =>  'required',
                    'sharing_mode'      =>  'required|in:dedicated,shared',
                    'owner_user_id'     =>  'nullable|integer|exists:users,id',
                    'consumerkey'       =>  'required',
                    'consumersecret'    =>  'required',
                    'passkey'           =>  'nullable',
                    'transaction_status_initiator' => 'nullable|string|max:255',
                    'transaction_status_credential' => 'nullable|string',
                    'transaction_status_credential_encrypted' => 'nullable|boolean',
                    'transaction_status_identifier' => 'nullable|in:shortcode,tillnumber,msisdn',
                    'clear_transaction_status_credentials' => 'nullable|boolean',
                ]);
                if($validatedData)
                    {
                        $shortcode                  =   $this->resolveManageableShortcode($authUser, (int)$request->id);
                        $oldValues                  =   $this->shortcodeAuditData($shortcode);
                        $shortcode->shortcode       =   $request->shortcode;
                        $shortcode->shortcode_type  =   $request->type;
                        $shortcode->group           =   trim(strtolower($request->group));
                        $shortcode->sharing_mode    =   $request->sharing_mode;
                        $shortcode->consumerkey     =   trim($request->consumerkey);
                        $shortcode->consumersecret  =   trim($request->consumersecret);
                        $shortcode->passkey         =   trim($request->passkey);
                        $shortcode->user_id         =   $this->resolveShortcodeOwnerId($authUser, $request->input('owner_user_id'), $shortcode->user_id);
                        $this->fillShortcodeTransactionStatusSettings($shortcode, $request, false);
                        $req                        =   $shortcode->save();

                        if($req)
                            {
                                $this->enforceShortcodeOwnershipRules($shortcode);
                                $this->recordAudit($request, 'updated', $shortcode, $oldValues, $this->shortcodeAuditData($shortcode), [
                                    'page_name' => 'Shortcode',
                                ]);
                                return array('status'=>TRUE,'msg'=>'Shortcode edit successful','header'=>'shortcode');
                            }
                        else
                            {
                                return array('status'=>False,'msg'=>'Shortcode edit not successful','header'=>'shortcode');
                            }
                    }
                else
                    {
                        return array('status'=>FALSE,'msg'=>$validatedData->errors());
                    }
            }
        public function startnotification(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'shortcode', 'update');
                $shortcode = $this->resolveManageableShortcode($authUser, (int)$request->id);
                $oldValues = $this->shortcodeAuditData($shortcode);

                $start = $this->mpesa->C2B_REGISTER([
                    'consumerkey' => $request->consumerkey,
                    'consumersecret' => $request->consumersecret,
                    'shortcode' => $request->shortcode,
                ]);
                $data = json_decode($start, true);

                if (! is_array($data))
                    {
                        return [
                            'status' => false,
                            'msg' => 'Safaricom returned an invalid response while registering notification URLs.',
                            'header' => 'Notification',
                        ];
                    }

                $responseDescription = strtolower(trim((string)($data['ResponseDescription'] ?? '')));
                $responseCode = trim((string)($data['ResponseCode'] ?? ''));
                $errorCode = trim((string)($data['errorCode'] ?? ''));
                $errorMessage = strtolower(trim((string)($data['errorMessage'] ?? '')));
                $alreadyRegistered = $errorCode === '500.003.1001' || strpos($errorMessage, 'urls are already registered') !== false;
                $registered = $responseCode === '0' || $responseDescription === 'success' || $alreadyRegistered;

                if ($registered)
                    {
                        $shortcode->status = 1;
                        $saved = $shortcode->save();

                        if (! $saved)
                            {
                                return [
                                    'status' => false,
                                    'msg' => 'Safaricom accepted the notification request, but the shortcode status could not be saved locally.',
                                    'header' => 'Notification',
                                ];
                            }

                        if ($saved && (bool)($oldValues['status'] ?? false) !== (bool)$shortcode->status)
                            {
                                $this->recordAudit($request, 'status_changed', $shortcode, $oldValues, $this->shortcodeAuditData($shortcode), [
                                    'page_name' => 'Shortcode',
                                ]);
                            }

                        return [
                            'status' => true,
                            'msg' => $alreadyRegistered
                                ? 'Notification URLs are already registered with Safaricom. Notification has been marked as active.'
                                : 'Notification started successfully.',
                            'header' => 'Notification',
                        ];
                    }

                return [
                    'status' => false,
                    'msg' => $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Notification failed to start.',
                    'header' => 'Notification',
                ];
            }


        public function services(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'services', 'view');

                return view('admin.modules.service', $this->baseViewData($request, [
                    'services' => $this->accessibleServiceQuery($authUser)->paginate(10),
                    'shortcodes' => $this->serviceSelectableShortcodeQuery($authUser)->get(),
                    'serviceAssignees' => User::orderBy('name')->get(['id', 'name', 'email']),
                    'defaultServiceShortcodeIds' => $this->accessibleServiceQuery($authUser)
                        ->where(function ($query) {
                            $query->whereNull('prefix')
                                ->orWhere('prefix', '');
                        })
                        ->pluck('shortcode_id')
                        ->map(function ($id) {
                            return (int) $id;
                        })
                        ->unique()
                        ->values()
                        ->all(),
                ]));


            }

        public function transaction(Request $request)
            {
                $this->requireActionPermission($request, 'transaction', 'view');

                return view('admin.modules.transaction', $this->baseViewData($request));
            }
        public function addservice(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'services', 'create');
                $validatedData = $request->validate([
                    'prefix'                =>  'nullable|max:4|alpha',
                    'shortcode'             =>  'required|integer',
                    'assigned_user_id'      =>  'nullable|integer|exists:users,id',
                    'service_name'          =>  'required',
                    'description'           =>  'nullable|string',
                    'verification_callback' =>  'nullable|url',
                    'response_callback'     =>  'nullable|url'
                ]);
                if($validatedData)
                    {
                        $shortcode                    = $this->serviceSelectableShortcodeQuery($authUser)->where('id', (int) $request->shortcode)->first();
                        abort_if(! $shortcode, 403, 'You do not have permission to use this shortcode.');
                        $normalizedPrefix             = $this->normalizeServicePrefix($request->input('prefix'));
                        $serviceName                  = trim((string) $request->input('service_name'));
                        $routingRuleMessage           = $this->validateServiceRoutingRules($shortcode, $serviceName, $normalizedPrefix);

                        if ($routingRuleMessage) {
                            return array('status'=>false,'msg'=>$routingRuleMessage,'header'=>'Service');
                        }

                        $service                      = new Service();
                        $service->prefix              = $normalizedPrefix;
                        $service->shortcode_id        = $shortcode->id;
                        $service->user_id             = $this->resolveServiceOwnerId($shortcode, $request->input('assigned_user_id'));
                        $service->service_name        = $serviceName;
                        $service->service_description = $request->filled('description') ? $request->description : null;
                        $service->verification_url    = $request->filled('verification_callback') ? $request->verification_callback : null;
                        $service->callback_url        = $request->filled('response_callback') ? trim($request->response_callback) : '';
                        $req                          = $service->save();
                        if($req)
                            {
                                $this->syncServiceOwnershipAccess($service);
                                $this->recordAudit($request, 'created', $service, [], $this->serviceAuditData($service), [
                                    'page_name' => 'Services',
                                ]);
                                return array('status'=>TRUE,'msg'=>'Service created successful','header'=>'Service');
                            }
                        else
                            {
                                return array('status'=>False,'msg'=>'Service creation failed','header'=>'Service');
                            }
                    }
                else
                    {
                        return array('status'=>FALSE,'msg'=>$validatedData->errors());
                    }
            }
        public function editservice(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'services', 'update');
                $validatedData = $request->validate([
                    'id'                    =>  'required|integer|exists:services,id',
                    'prefix'                =>  'nullable|max:4|alpha',
                    'shortcode'             =>  'required|integer',
                    'assigned_user_id'      =>  'nullable|integer|exists:users,id',
                    'service_name'          =>  'required',
                    'description'           =>  'nullable|string',
                    'verification_callback' =>  'nullable|url',
                    'response_callback'     =>  'nullable|url'
                ]);
                if($validatedData)
                    {
                        $shortcode                      =   $this->serviceSelectableShortcodeQuery($authUser)->where('id', (int) $request->shortcode)->first();
                        abort_if(! $shortcode, 403, 'You do not have permission to use this shortcode.');
                        $service                        =   $this->resolveManageableService($authUser, (int) $request->id);
                        $normalizedPrefix               =   $this->normalizeServicePrefix($request->input('prefix'));
                        $serviceName                    =   trim((string) $request->input('service_name'));
                        $routingRuleMessage             =   $this->validateServiceRoutingRules($shortcode, $serviceName, $normalizedPrefix, $service->id);

                        if ($routingRuleMessage) {
                            return array('status'=>false,'msg'=>$routingRuleMessage,'header'=>'Service');
                        }

                        $oldValues                      =   $this->serviceAuditData($service);
                        $service->shortcode_id          =   $request->shortcode;
                        $service->user_id               =   $this->resolveServiceOwnerId($shortcode, $request->input('assigned_user_id'));
                        $service->prefix                =   $normalizedPrefix;
                        $service->service_name          =   $serviceName;
                        $service->service_description   =   $request->filled('description') ? $request->description : null;
                        $service->verification_url      =   $request->filled('verification_callback') ? $request->verification_callback : null;
                        $service->callback_url          =   $request->filled('response_callback') ? trim($request->response_callback) : '';
                        $service->shortcode_id          =   $shortcode->id;
                        $req                            =   $service->save();
                        if($req)
                            {
                                $this->syncServiceOwnershipAccess($service, $oldValues['user_id'] ?? null);
                                $this->recordAudit($request, 'updated', $service, $oldValues, $this->serviceAuditData($service), [
                                    'page_name' => 'Services',
                                ]);
                                return array('status'=>TRUE,'msg'=>'Service created successful','header'=>'Service');
                            }
                        else
                            {
                                return array('status'=>False,'msg'=>'Service creation failed','header'=>'Service');
                            }
                    }
                else
                    {
                        return array('status'=>FALSE,'msg'=>$validatedData->errors());
                    }
            }
        public function destroyservice(Request $request)
            {
                $authUser = $this->requireActionPermission($request, 'services', 'delete');
                abort_if(
                    ! Schema::hasColumn('services', 'deleted_at'),
                    422,
                    'Run the latest migrations before deleting services so restore can work safely.'
                );

                $validatedData = $request->validate([
                    'id' => 'required|integer|exists:services,id',
                ]);

                if ($validatedData)
                    {
                        $service = $this->resolveManageableService($authUser, (int) $request->input('id'));
                        $service->loadMissing(['shortcode.user', 'user']);
                        $oldValues = $this->serviceAuditData($service);
                        $restorePayload = [
                            'type' => 'service',
                            'service_id' => $service->id,
                        ];

                        DB::transaction(function () use ($request, $service, $oldValues, $restorePayload) {
                            $service->delete();

                            $this->recordAudit($request, 'deleted', $service, $oldValues, [], [
                                'page_name' => 'Services',
                                'is_restorable' => true,
                                'restore_payload' => $restorePayload,
                            ]);
                        });

                        return [
                            'status' => true,
                            'msg' => 'Service deleted successfully. You can restore it from Audit Logs.',
                            'header' => 'Services',
                        ];
                    }

                return [
                    'status' => false,
                    'msg' => 'Service deletion failed.',
                    'header' => 'Services',
                ];
            }
		public function register(Request $request, $shortcode)
			{
                $authUser = $this->requireActionPermission($request, 'shortcode', 'update');
				//$result		=	$this->mpesa->C2B_REGISTER($shortcode);
				$code = $this->resolveManageableShortcode($authUser, $shortcode, 'shortcode');



				$setting = new Setting();

				//$setting->save([]);
				$setting->shortcode_id = $code->id;
				$setting->meta_name = "dandia doh";
				$setting->meta_value = "Active";
				$setting->userid = 1;
				$setting->save();



				//print_r($result);
			}
		public function checkout()
			{
				$mrq	=	$this->input->raw_input_stream;
				@file_put_contents(APPPATH."logs/mpesarq.txt", "\n".$mrq,FILE_APPEND);
				$mrq 	=	json_decode($mrq);
				if(isset($mrq))
					{
						$phone  = 	$this->assist->localizePhoneNumber($mrq->phoneno);
						$mpesa 	=	json_decode($this->mpesa->checkout($phone,$mrq->amount,substr($mrq->orderid,0,10),"payment for the epaper"));
						@file_put_contents(APPPATH."logs/mpesarequestres.txt", "\n".$mpesa,FILE_APPEND);
						if(isset($mpesa->ResponseCode))
							{
								if($mpesa->ResponseCode === "0")
									{
										$val 	=	array(
															"CheckoutRequestID" =>	$mpesa->CheckoutRequestID,
															"merchant_id"	 	=> 	$mpesa->MerchantRequestID,
															"amount"  			=>	$mrq->amount,
															"order_id"			=>	$mrq->orderid,
															// "email"				=>	$mrq->email,
															"date_created"		=>	date("Y-m-d H:i:s"),
															"paymentmode"		=>	"mpesa",
															// "currencycode"		=>  $mrq->currencycode,
															"phonenumber"		=>	(int)$phone
										             	);
										$x		=	$this->hmode->processPayment($val);
										$x      =   (object)array("status"=>$x);
										$this->output->set_content_type('application/json')
						        					 ->set_output(json_encode($x));
					        		}
					        	else
						        	{
						        		$x		=	"Failed";
										$x      =   (object)array("status"=>$x,"response"=>"request failed");
										$this->output->set_content_type('application/json')
						        					 ->set_output(json_encode($x));
						        	}
					        }
					    else
				        	{
				        		$x		=	"Failed";
								$x      =   (object)array("status"=>$x,"response"=>"No handshake made to safaricom");
								$this->output->set_content_type('application/json')
				        					 ->set_output(json_encode($x));
				        	}
			        }
			    else
		        	{
		        		$x		=	"Failed";
						$x      =   (object)array("status"=>$x,"response"=>"invalid Json");
						$this->output->set_content_type('application/json')
		        					 ->set_output(json_encode($x));
		        	}
			}
		public function updaterecord(Request $request)
            {
                $permissionMap = [
                    'users' => 'users.update',
                    'shortcodes' => 'shortcode.update',
                    'services' => 'services.update',
                ];

                $slug = $permissionMap[$request->table] ?? null;
                abort_if(! $slug || ! $request->user() || ! $request->user()->hasPermission($slug), 403, 'You do not have permission to update this record.');

                $oldRecord = (array) DB::table($request->table)
                    ->where('id', (int)$request->id)
                    ->first();

                $res = DB::table($request->table)
                        ->where('id', (int)$request->id)
                        ->update([$request->column => $request->value]);
                if($res)
                    {
                        $newRecord = (array) DB::table($request->table)
                            ->where('id', (int)$request->id)
                            ->first();
                        $this->recordAudit($request, 'updated', ucfirst((string) $request->table), $oldRecord, $newRecord, [
                            'page_name' => ucfirst((string) $request->table),
                            'auditable_id' => (int) $request->id,
                            'auditable_label' => ucfirst((string) $request->table).' #'.(int) $request->id,
                        ]);

                        return array('status'=>TRUE,'msg'=>'Record update successful','header'=>ucfirst($request->table));
                    }
                else
                    {

                        return array('status'=>False,'msg'=>'Record update failed','header'=>ucfirst($request->table));
                    }
            }

        protected function buildDashboardData(Request $request, Carbon $startDate, Carbon $endDate)
            {
                $authUser = $request->user();
                $services = $this->transactionVisibleServiceQuery($authUser)->orderBy('service_name')->get();
                $keywordRules = collect($this->dashboardKeywordRules($authUser));
                $cardServices = $this->dashboardReportCardServices($authUser, $services);
                $cardKeywordRules = $this->dashboardReportCardKeywordRules($authUser, $keywordRules);
                $serviceKeys = $services->mapWithKeys(function ($service) {
                    return [(int) $service->shortcode_id.'|'.(string) $service->service_name => true];
                })->all();

                if (! $authUser->canViewAllTransactions()) {
                    $keywordRules = $keywordRules->reject(function ($rule) use ($serviceKeys) {
                        return isset($serviceKeys[(int) $rule['shortcode_id'].'|'.(string) $rule['service_name']]);
                    })->values();
                }
                $scopeServiceKeys = $services->map(function ($service) {
                    return (int) $service->shortcode_id.'|'.(string) $service->service_name;
                })->all();
                $scopeShortcodeIds = $services->pluck('shortcode_id')->map(function ($id) {
                    return (int) $id;
                })->all();

                foreach ($keywordRules as $rule) {
                    $scopeServiceKeys[] = (int) $rule['shortcode_id'].'|'.(string) $rule['service_name'];
                    $scopeShortcodeIds[] = (int) $rule['shortcode_id'];
                }

                $report = [];
                $keywordReport = [];
                $summaryStats = $this->dashboardSummaryStats($authUser, $startDate, $endDate);
                $serviceReportStats = $this->dashboardServiceReportStats($authUser, $cardServices, $startDate, $endDate);

                $dashboardSummary = [
                    'total_amount' => $summaryStats['amount'],
                    'transaction_count' => $summaryStats['count'],
                    'service_count' => count(array_unique($scopeServiceKeys)),
                    'shortcode_count' => count(array_unique($scopeShortcodeIds)),
                ];

                foreach ($cardServices as $value)
                    {
                        $reportKey = $value->id;
                        $stats = $serviceReportStats[$this->dashboardServiceReportKey($value->shortcode_id, $value->service_name)] ?? [
                            'amount' => 0,
                            'count' => 0,
                            'latest' => null,
                        ];

                        $report[$reportKey] = [
                            'amount' => $stats['amount'],
                            'count' => $stats['count'],
                            'latest' => $stats['latest'],
                        ];
                    }

                foreach ($cardKeywordRules as $rule)
                    {
                        $stats = $this->dashboardKeywordReportStats($rule, $startDate, $endDate);

                        $keywordReport[] = [
                            'keyword_id' => $rule['keyword_id'],
                            'keyword_name' => $rule['keyword_name'],
                            'service_name' => $rule['service_name'],
                            'assigned_users' => $rule['assigned_users'] ?? $authUser->name,
                            'service_owner' => $rule['service_owner'] ?? '',
                            'shortcode_id' => $rule['shortcode_id'],
                            'amount' => $stats['amount'],
                            'count' => $stats['count'],
                            'latest' => $stats['latest'],
                        ];
                    }

                return $this->baseViewData($request, [
                    'services' => $cardServices,
                    'report' => $report,
                    'keywordReport' => $keywordReport,
                    'dashboardSummary' => $dashboardSummary,
                    'canSearchDashboard' => $authUser->hasPermission('dashboard.search'),
                    'canViewDashboardReports' => $authUser->hasPermission('dashboard.reports'),
                    'reportStart' => $startDate,
                    'reportEnd' => $endDate,
                ]);
            }

        protected function dashboardSummaryStats(User $authUser, Carbon $startDate, Carbon $endDate)
            {
                $query = Transaction::query()
                    ->where("trans_time", '>=', $startDate->format('Y-m-d H:i:s'))
                    ->where("trans_time", '<=', $endDate->format('Y-m-d H:i:s'));

                $this->applyTransactionVisibility($authUser, $query);

                $stats = $query
                    ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
                    ->selectRaw('COUNT(*) as transaction_count')
                    ->first();

                return [
                    'amount' => $stats ? (float) $stats->total_amount : 0,
                    'count' => $stats ? (int) $stats->transaction_count : 0,
                ];
            }

        protected function dashboardServiceReportStats(User $authUser, $cardServices, Carbon $startDate, Carbon $endDate)
            {
                if ($cardServices->isEmpty()) {
                    return [];
                }

                $query = Transaction::query()
                    ->where("trans_time", '>=', $startDate->format('Y-m-d H:i:s'))
                    ->where("trans_time", '<=', $endDate->format('Y-m-d H:i:s'));

                $this->applyTransactionVisibility($authUser, $query);

                return $query
                    ->select('shortcode_id', 'type')
                    ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
                    ->selectRaw('COUNT(*) as transaction_count')
                    ->selectRaw('MAX(trans_time) as latest_trans_time')
                    ->groupBy('shortcode_id', 'type')
                    ->get()
                    ->mapWithKeys(function ($stats) {
                        return [
                            $this->dashboardServiceReportKey($stats->shortcode_id, $stats->type) => [
                                'amount' => (float) $stats->total_amount,
                                'count' => (int) $stats->transaction_count,
                                'latest' => $stats->latest_trans_time,
                            ],
                        ];
                    })
                    ->all();
            }

        protected function dashboardKeywordReportStats(array $rule, Carbon $startDate, Carbon $endDate)
            {
                $query = Transaction::query()
                    ->where("trans_time", '>=', $startDate->format('Y-m-d H:i:s'))
                    ->where("trans_time", '<=', $endDate->format('Y-m-d H:i:s'));

                $this->applyAccountKeywordTransactionRule($query, $rule);

                $stats = $query
                    ->selectRaw('COALESCE(SUM(amount), 0) as total_amount')
                    ->selectRaw('COUNT(*) as transaction_count')
                    ->selectRaw('MAX(trans_time) as latest_trans_time')
                    ->first();

                return [
                    'amount' => $stats ? (float) $stats->total_amount : 0,
                    'count' => $stats ? (int) $stats->transaction_count : 0,
                    'latest' => $stats ? $stats->latest_trans_time : null,
                ];
            }

        protected function dashboardServiceReportKey($shortcodeId, $serviceName)
            {
                return (int) $shortcodeId.'|'.(string) $serviceName;
            }

        protected function dashboardReportCardServices(User $authUser, $services)
            {
                return $services->values();
            }

        protected function dashboardReportCardKeywordRules(User $authUser, $keywordRules)
            {
                return $keywordRules->values();
            }

        protected function dashboardKeywordRules(User $authUser)
            {
                if (! User::supportsAccountKeywordAccess()) {
                    return [];
                }

                if (! $authUser->canViewAllTransactions()) {
                    return $authUser->transactionVisibleKeywordRules();
                }

                return ServiceAccountKeyword::with(['service.shortcode', 'service.user', 'users'])
                    ->where('status', true)
                    ->orderBy('keyword_name')
                    ->get()
                    ->map(function ($keyword) {
                        return $this->adminKeywordTransactionRule($keyword);
                    })
                    ->filter(function ($rule) {
                        return is_array($rule) && $rule['service_id'] > 0 && $rule['shortcode_id'] > 0 && $rule['service_name'] !== '';
                    })
                    ->values()
                    ->all();
            }

        protected function resolveDashboardDateRange(Request $request)
            {
                $start = $this->parseDashboardDateBoundary($request->input('start'), Carbon::today(), false);
                $end = $this->parseDashboardDateBoundary($request->input('end'), Carbon::today()->endOfDay(), true);

                if ($start->greaterThan($end)) {
                    $swap = $start;
                    $start = $end;
                    $end = $swap;
                }

                return [$start, $end];
            }

        protected function parseDashboardDateBoundary($value, Carbon $fallback, $endOfDay = false)
            {
                if (! $value) {
                    return $fallback->copy();
                }

                $date = Carbon::parse($value);

                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value)) {
                    return $endOfDay ? $date->endOfDay() : $date->startOfDay();
                }

                return $date;
            }

        protected function shortcodeAuditData(Shortcode $shortcode)
            {
                return [
                    'id' => $shortcode->id,
                    'shortcode' => $shortcode->shortcode,
                    'group' => $shortcode->group,
                    'shortcode_type' => $shortcode->shortcode_type,
                    'sharing_mode' => $shortcode->sharing_mode ?? 'dedicated',
                    'consumerkey' => $shortcode->consumerkey,
                    'consumersecret' => $shortcode->consumersecret,
                    'passkey' => $shortcode->passkey,
                    'status' => (bool)$shortcode->status,
                    'user_id' => $shortcode->user_id,
                    'owner_name' => optional($shortcode->user)->name,
                    'transaction_status_initiator' => Schema::hasColumn('shortcodes', 'transaction_status_initiator') ? $shortcode->transaction_status_initiator : null,
                    'transaction_status_identifier' => Schema::hasColumn('shortcodes', 'transaction_status_identifier') ? $shortcode->transaction_status_identifier : null,
                    'transaction_status_credential_configured' => Schema::hasColumn('shortcodes', 'transaction_status_credential') && trim((string) $shortcode->transaction_status_credential) !== '',
                ];
            }

        protected function resolveShortcodeOwnerId(User $authUser, $requestedOwnerId = null, $fallbackOwnerId = null)
            {
                if (! $authUser->hasPermission('shortcode.assign_owner'))
                    {
                        return (int) ($fallbackOwnerId ?: $authUser->id);
                    }

                if ($requestedOwnerId && User::whereKey((int) $requestedOwnerId)->exists())
                    {
                        return (int) $requestedOwnerId;
                    }

                if ($fallbackOwnerId && User::whereKey((int) $fallbackOwnerId)->exists())
                    {
                        return (int) $fallbackOwnerId;
                    }

                return (int) $authUser->id;
            }

        protected function fillShortcodeTransactionStatusSettings(Shortcode $shortcode, Request $request, $creating = false)
            {
                if (! Schema::hasColumn('shortcodes', 'transaction_status_initiator')) {
                    return;
                }

                $shortcode->transaction_status_initiator = $request->filled('transaction_status_initiator')
                    ? trim((string) $request->input('transaction_status_initiator'))
                    : null;
                $shortcode->transaction_status_identifier = $request->filled('transaction_status_identifier')
                    ? trim((string) $request->input('transaction_status_identifier'))
                    : 'shortcode';
                $shortcode->transaction_status_credential_encrypted = $request->boolean('transaction_status_credential_encrypted');

                if ($request->filled('transaction_status_credential')) {
                    $shortcode->transaction_status_credential = trim((string) $request->input('transaction_status_credential'));
                    return;
                }

                if ($creating || $request->boolean('clear_transaction_status_credentials')) {
                    $shortcode->transaction_status_credential = null;
                }
            }

        protected function serviceAuditData(Service $service)
            {
                return [
                    'id' => $service->id,
                    'shortcode_id' => $service->shortcode_id,
                    'shortcode' => optional($service->shortcode)->shortcode,
                    'user_id' => $service->user_id,
                    'owner_name' => optional($service->user)->name,
                    'service_name' => $service->service_name,
                    'service_description' => $service->service_description,
                    'prefix' => $service->prefix,
                    'verification_url' => $service->verification_url,
                    'callback_url' => $service->callback_url,
                ];
            }

        protected function resolveServiceOwnerId(Shortcode $shortcode, $requestedUserId = null)
            {
                if ($this->shortcodeIsDedicated($shortcode))
                    {
                        return (int) $shortcode->user_id;
                    }

                if ($requestedUserId && User::whereKey((int) $requestedUserId)->exists())
                    {
                        return (int) $requestedUserId;
                    }

                return (int) $shortcode->user_id;
            }

        protected function syncServiceOwnershipAccess(Service $service, $previousOwnerId = null)
            {
                if (! User::supportsServiceVisibilityAssignments())
                    {
                        return;
                    }

                $service->loadMissing('shortcode');
                $viewerIds = [$service->user_id];

                if ($service->shortcode && $this->shortcodeIsDedicated($service->shortcode))
                    {
                        $existingViewerIds = $service->viewers()->pluck('users.id')->all();
                        $service->viewers()->sync($viewerIds);
                        $this->closeServiceAmountLimitHistory($service->id, array_diff($existingViewerIds, $viewerIds));
                        $this->ensureServiceAmountLimitHistory($service->id, $viewerIds);
                        return;
                    }

                $originalViewerIds = $service->viewers()->pluck('users.id')->all();
                $existingViewerIds = $originalViewerIds;

                if ($previousOwnerId && (int) $previousOwnerId !== (int) $service->user_id)
                    {
                        $existingViewerIds = array_values(array_diff($existingViewerIds, [(int) $previousOwnerId]));
                    }

                $syncedViewerIds = array_values(array_unique(array_merge($existingViewerIds, $viewerIds)));
                $service->viewers()->sync($syncedViewerIds);
                $this->closeServiceAmountLimitHistory($service->id, array_diff($originalViewerIds, $syncedViewerIds));
                $this->ensureServiceAmountLimitHistory($service->id, $syncedViewerIds);
            }

        protected function enforceShortcodeOwnershipRules(Shortcode $shortcode)
            {
                $shortcode->loadMissing('service');

                if (! $this->shortcodeIsDedicated($shortcode))
                    {
                        return;
                    }

                foreach ($shortcode->service as $service)
                    {
                        $service->user_id = $shortcode->user_id;
                        $service->save();
                        $this->syncServiceOwnershipAccess($service);
                    }

                if (User::supportsShortcodeVisibilityAssignments())
                    {
                        $shortcode->viewers()->sync([]);
                    }
            }

        protected function shortcodeIsDedicated(Shortcode $shortcode)
            {
                return ($shortcode->sharing_mode ?? 'dedicated') !== 'shared';
            }

        protected function normalizeServicePrefix($prefix)
            {
                $prefix = trim((string) $prefix);

                return $prefix === '' ? null : strtolower($prefix);
            }

        protected function defaultServiceNameForShortcode(Shortcode $shortcode)
            {
                return 'default-'.$shortcode->shortcode;
            }

        protected function validateServiceRoutingRules(Shortcode $shortcode, $serviceName, $prefix, $ignoreServiceId = null)
            {
                $defaultServiceQuery = Service::query()
                    ->where('shortcode_id', $shortcode->id)
                    ->where(function ($query) {
                        $query->whereNull('prefix')
                            ->orWhere('prefix', '');
                    });

                if ($ignoreServiceId) {
                    $defaultServiceQuery->where('id', '!=', (int) $ignoreServiceId);
                }

                $defaultExists = $defaultServiceQuery->exists();
                $expectedDefaultName = $this->defaultServiceNameForShortcode($shortcode);
                $isDefaultNamedService = Str::lower(trim((string) $serviceName)) === Str::lower($expectedDefaultName);

                if ($defaultExists && $prefix === null) {
                    return 'Code Prefix is required because shortcode '.$shortcode->shortcode.' already has a default service. Only the default service should keep an empty prefix.';
                }

                if (! $defaultExists && $prefix === null && ! $isDefaultNamedService) {
                    return 'If Code Prefix is blank, this becomes the default service for shortcode '.$shortcode->shortcode.'. Use the service name '.$expectedDefaultName.' or add a prefix for a non-default service.';
                }

                return null;
            }
    }
