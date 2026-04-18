@extends('admin.includes.body')
@section('title', 'MPesa API Documentation')
@section('subtitle','MPesa API Documentation')
@section('content')
    <style>
        .mpesa-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #0f4c81 0%, #167f6b 55%, #f4f7fb 55%, #f4f7fb 100%);
            border: 0;
            color: #fff;
        }

        .mpesa-hero::after {
            content: "";
            position: absolute;
            right: -70px;
            top: -70px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
        }

        .mpesa-hero .card-body {
            position: relative;
            z-index: 1;
            padding: 2rem;
        }

        .mpesa-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.14);
            color: #fff;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .mpesa-panel {
            border: 1px solid #e5ebf2;
            border-radius: 0.8rem;
            background: #fff;
            margin-bottom: 1.5rem;
        }

        .mpesa-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #edf2f7;
        }

        .mpesa-panel-body {
            padding: 1.25rem;
        }

        .mpesa-kicker {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6c7b8a;
            font-weight: 700;
            margin-bottom: 0.35rem;
        }

        .mpesa-step {
            display: flex;
            margin-bottom: 1rem;
        }

        .mpesa-step:last-child {
            margin-bottom: 0;
        }

        .mpesa-step-number {
            width: 2rem;
            height: 2rem;
            flex: 0 0 2rem;
            margin-right: 0.9rem;
            border-radius: 50%;
            background: #0f4c81;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }

        .mpesa-card-lite {
            padding: 1rem;
            border-radius: 0.75rem;
            border: 1px solid #e5ebf2;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            margin-bottom: 0.9rem;
        }

        .mpesa-card-lite:last-child {
            margin-bottom: 0;
        }

        .mpesa-note {
            border-left: 4px solid #1b8f74;
            background: #f4fbf8;
            border-radius: 0.5rem;
            padding: 0.9rem 1rem;
            color: #36515a;
        }

        .mpesa-inline-code {
            padding: 0.15rem 0.4rem;
            border-radius: 0.35rem;
            background: #eef4fb;
            color: #0f4c81;
            font-size: 0.82rem;
        }

        .mpesa-code {
            background: #0d1b2a;
            color: #d8e7ff;
            border-radius: 0.85rem;
            padding: 1rem;
            min-height: 130px;
            font-size: 0.82rem;
            line-height: 1.55;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 0;
        }

        .mpesa-warning-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
        }

        .mpesa-warning-list li {
            border: 1px solid #f5d9a7;
            background: #fff8e8;
            color: #835f1d;
            border-radius: 0.65rem;
            padding: 0.75rem 0.9rem;
            margin-bottom: 0.65rem;
        }

        .mpesa-warning-list li:last-child {
            margin-bottom: 0;
        }

        @media (max-width: 767.98px) {
            .mpesa-hero {
                background: linear-gradient(160deg, #0f4c81 0%, #167f6b 100%);
            }

            .mpesa-panel-header {
                display: block;
            }

            .mpesa-panel-header .btn {
                margin-top: 0.75rem;
            }
        }
    </style>

    @php($serviceCount = $shortcodes->sum(function ($item) { return $item->service->count(); }))

    <div class="card mpesa-hero shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <span class="mpesa-badge">{{ $environmentLabel }} Mode</span>
                        <span class="mpesa-badge">Internal Endpoint: /api/checkout</span>
                        <span class="mpesa-badge">Sandbox Preview Included</span>
                    </div>
                    <h2 class="mb-2 text-white">Professional MPesa documentation for the API already inside this project</h2>
                    <p class="mb-0" style="max-width: 720px; color: rgba(255,255,255,0.88);">
                        This guide explains the current checkout flow, field meanings, callback paths, shortcode and service mapping, and lets each logged-in user generate safe dummy examples based on the shortcodes and services assigned to them.
                    </p>
                </div>
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="mpesa-card-lite text-dark h-100">
                                <div class="mpesa-kicker">Shortcodes</div>
                                <h2 class="mb-0">{{ $shortcodes->count() }}</h2>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="mpesa-card-lite text-dark h-100">
                                <div class="mpesa-kicker">Services</div>
                                <h2 class="mb-0">{{ $serviceCount }}</h2>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mpesa-card-lite text-dark">
                                <div class="mpesa-kicker">Checkout URL</div>
                                <div class="small" style="word-break: break-all;">{{ $internalEndpoint }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8">
            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Intro</div>
                        <h4 class="mb-0">How the existing integration works</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="mpesa-note mb-4">
                        The public endpoint in this app is <span class="mpesa-inline-code">POST {{ $internalEndpoint }}</span>. It uses the shortcode <span class="mpesa-inline-code">group</span> value stored in the database to load credentials, normalize the phone number, and submit an STK push to Safaricom.
                    </div>
                    @foreach($quickStartSteps as $index => $step)
                        <div class="mpesa-step">
                            <div class="mpesa-step-number">{{ $index + 1 }}</div>
                            <div>
                                <h5 class="mb-1">{{ $step['title'] }}</h5>
                                <p class="text-muted mb-0">{{ $step['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">API Request</div>
                        <h4 class="mb-0">Checkout fields explained</h4>
                    </div>
                    <button class="btn btn-default btn-sm copy-inline" data-copy-text="{{ $internalEndpoint }}" type="button">Copy URL</button>
                </div>
                <div class="mpesa-panel-body">
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Field</th>
                                <th>Type</th>
                                <th>Required</th>
                                <th>Meaning</th>
                                <th>Example</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($apiRequestFields as $field)
                                <tr>
                                    <td><strong>{{ $field['field'] }}</strong></td>
                                    <td>{{ $field['type'] }}</td>
                                    <td>{{ $field['required'] }}</td>
                                    <td>{{ $field['description'] }}</td>
                                    <td><span class="mpesa-inline-code">{{ $field['example'] }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="text-muted small">Double-click any code block on this page to copy it.</p>
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="mpesa-kicker">JSON Example</div>
                            <pre class="mpesa-code" id="request-json-example">{
  "group": "school-paybill",
  "amount": 1500,
  "msisdn": "0712345678",
  "account": "ADM10042",
  "description": "April school fees"
}</pre>
                        </div>
                        <div class="col-lg-6">
                            <div class="mpesa-kicker">cURL Example</div>
                            <pre class="mpesa-code" id="request-curl-example">curl --request POST '{{ $internalEndpoint }}' \
  --header 'Content-Type: application/json' \
  --data '{"group":"school-paybill","amount":1500,"msisdn":"0712345678","account":"ADM10042","description":"April school fees"}'</pre>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Setup Fields</div>
                        <h4 class="mb-0">Shortcodes and services</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <h5 class="mb-3">Shortcode fields</h5>
                            @foreach($shortcodeFields as $field)
                                <div class="mpesa-card-lite">
                                    <h6 class="mb-1">{{ $field['field'] }}</h6>
                                    <p class="text-muted mb-0">{{ $field['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                        <div class="col-lg-6">
                            <h5 class="mb-3">Service fields</h5>
                            @foreach($serviceFields as $field)
                                <div class="mpesa-card-lite">
                                    <h6 class="mb-1">{{ $field['field'] }}</h6>
                                    <p class="text-muted mb-0">{{ $field['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Callback Flow</div>
                        <h4 class="mb-0">Platform callbacks and routing</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="table-responsive mb-4">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                            <tr>
                                <th>Callback</th>
                                <th>URL</th>
                                <th>Purpose</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>STK Request Callback</td>
                                <td style="word-break: break-all;">{{ $callbackEndpoints['stk_request_callback'] }}</td>
                                <td>Receives the asynchronous STK result from Safaricom.</td>
                            </tr>
                            <tr>
                                <td>STK Query Callback</td>
                                <td style="word-break: break-all;">{{ $callbackEndpoints['stk_query_callback'] }}</td>
                                <td>Receives the query result when checkout status is checked.</td>
                            </tr>
                            <tr>
                                <td>C2B Validation</td>
                                <td style="word-break: break-all;">{{ $callbackEndpoints['c2b_validation'] }}</td>
                                <td>Optionally calls the service verification URL before accepting payment.</td>
                            </tr>
                            <tr>
                                <td>C2B Confirmation</td>
                                <td style="word-break: break-all;">{{ $callbackEndpoints['c2b_confirmation'] }}</td>
                                <td>Stores the payment and forwards normalized JSON to the service callback URL.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mpesa-note">
                        Prefix routing matters. If a service prefix is <span class="mpesa-inline-code">ADM</span>, a bill reference such as <span class="mpesa-inline-code">ADM10042</span> is routed to that service. If no prefix matches, the app falls back to a service without a prefix.
                    </div>
                </div>
            </div>

            <div class="mpesa-panel shadow-sm" id="mpesa-sandbox-panel">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Sandbox</div>
                        <h4 class="mb-0">Generate dummy requests from your saved setup</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="mpesa-note mb-4">
                        This sandbox does not send live traffic. It uses your assigned shortcode and service records to prepare realistic examples, request bodies, callback payloads, and cURL commands while keeping the secret credentials on the server.
                    </div>
                    @if($shortcodes->isEmpty())
                        <div class="alert alert-warning mb-0">
                            No shortcode is assigned to your account yet. Add or assign a shortcode first, then return here to use the sandbox.
                        </div>
                    @else
                        <form id="mpesa-sandbox-form">
                            @csrf
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="sandbox-shortcode">Shortcode</label>
                                    <select class="custom-select" id="sandbox-shortcode" name="shortcode_id">
                                        @foreach($shortcodes as $shortcode)
                                            <option value="{{ $shortcode->id }}">{{ $shortcode->shortcode }} @if($shortcode->group) - {{ $shortcode->group }} @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="sandbox-service">Service</label>
                                    <select class="custom-select" id="sandbox-service" name="service_id"></select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="sandbox-amount">Amount</label>
                                    <input type="number" min="1" step="0.01" class="form-control" id="sandbox-amount" name="amount" value="1500">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-group col-md-4">
                                    <label for="sandbox-msisdn">Customer MSISDN</label>
                                    <input type="text" class="form-control" id="sandbox-msisdn" name="msisdn" value="0712345678">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="sandbox-account">Account Reference</label>
                                    <input type="text" class="form-control" id="sandbox-account" name="account" value="ADM10042">
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="sandbox-description">Description</label>
                                    <input type="text" class="form-control" id="sandbox-description" name="description" value="April school fees">
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                                <small class="text-muted">Tip: if a service uses a prefix, begin the account reference with that prefix to preview the exact routing.</small>
                                <div class="mt-3 mt-md-0">
                                    <button type="button" class="btn btn-default mr-2" id="use-sample-data">Use Sample Data</button>
                                    <button type="submit" class="btn btn-primary">Generate Preview</button>
                                </div>
                            </div>
                        </form>

                        <div id="mpesa-preview-results" style="display: none;">
                            <div class="row mb-4">
                                <div class="col-lg-4 mb-3">
                                    <div class="mpesa-card-lite h-100">
                                        <div class="mpesa-kicker">Credential Source</div>
                                        <h5 id="preview-shortcode" class="mb-2">-</h5>
                                        <p class="text-muted mb-1"><strong>Group:</strong> <span id="preview-group">-</span></p>
                                        <p class="text-muted mb-1"><strong>Type:</strong> <span id="preview-type">-</span></p>
                                        <p class="text-muted mb-0"><strong>Status:</strong> <span id="preview-status">-</span></p>
                                    </div>
                                </div>
                                <div class="col-lg-8 mb-3">
                                    <div class="mpesa-card-lite h-100">
                                        <div class="mpesa-kicker">Routing Notes</div>
                                        <p class="text-muted mb-2"><strong>Normalized MSISDN:</strong> <span id="preview-msisdn">-</span></p>
                                        <p class="text-muted mb-2"><strong>Prefixed Bill Reference:</strong> <span id="preview-bill-ref">-</span></p>
                                        <p class="text-muted mb-0" id="preview-credentials-note">-</p>
                                    </div>
                                </div>
                            </div>
                            <div id="preview-warnings-wrap" class="mb-4" style="display: none;">
                                <div class="mpesa-kicker">Warnings</div>
                                <ul class="mpesa-warning-list" id="preview-warnings"></ul>
                            </div>
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">Internal Request JSON</div>
                                    <pre class="mpesa-code" id="preview-internal-json"></pre>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">cURL Command</div>
                                    <pre class="mpesa-code" id="preview-internal-curl"></pre>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">Outbound Daraja Payload</div>
                                    <pre class="mpesa-code" id="preview-daraja-json"></pre>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">Service Callback Example</div>
                                    <pre class="mpesa-code" id="preview-service-callback"></pre>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">STK Success Callback</div>
                                    <pre class="mpesa-code" id="preview-stk-callback"></pre>
                                </div>
                                <div class="col-lg-6 mb-4">
                                    <div class="mpesa-kicker">Validation Request</div>
                                    <pre class="mpesa-code" id="preview-validation-json"></pre>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Environment</div>
                        <h4 class="mb-0">Current MPesa links</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="mpesa-card-lite">
                        <h6 class="mb-1">OAuth token</h6>
                        <p class="text-muted mb-0" style="word-break: break-all;">{{ $mpesaLinks['token_link'] }}</p>
                    </div>
                    <div class="mpesa-card-lite">
                        <h6 class="mb-1">STK push request</h6>
                        <p class="text-muted mb-0" style="word-break: break-all;">{{ $mpesaLinks['checkout_processlink'] }}</p>
                    </div>
                    <div class="mpesa-card-lite">
                        <h6 class="mb-1">STK query</h6>
                        <p class="text-muted mb-0" style="word-break: break-all;">{{ $mpesaLinks['checkout_querylink'] }}</p>
                    </div>
                    <div class="mpesa-card-lite">
                        <h6 class="mb-1">C2B register</h6>
                        <p class="text-muted mb-0" style="word-break: break-all;">{{ $mpesaLinks['c2b_regiterUrl'] }}</p>
                    </div>
                    @if(!empty($mpesaLinks['c2b_transactionUrl']))
                        <div class="mpesa-card-lite">
                            <h6 class="mb-1">C2B simulate</h6>
                            <p class="text-muted mb-0" style="word-break: break-all;">{{ $mpesaLinks['c2b_transactionUrl'] }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Assigned Setup</div>
                        <h4 class="mb-0">Your shortcodes and services</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    @if($shortcodes->isEmpty())
                        <p class="text-muted mb-0">No shortcode is assigned to your account yet.</p>
                    @else
                        @foreach($shortcodes as $shortcode)
                            <div class="mpesa-card-lite">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1">{{ $shortcode->shortcode }}</h5>
                                        <p class="text-muted mb-2">Group: <span class="mpesa-inline-code">{{ $shortcode->group ?: 'Not set' }}</span></p>
                                    </div>
                                    <span class="badge {{ $shortcode->status ? 'badge-success' : 'badge-default' }}">{{ $shortcode->status ? 'Active' : 'Inactive' }}</span>
                                </div>
                                <p class="text-muted mb-2"><strong>Type:</strong> {{ $shortcode->shortcode_type }}</p>
                                @if($shortcode->service->isEmpty())
                                    <p class="text-muted mb-0">No services linked yet.</p>
                                @else
                                    <div class="small text-muted">Services</div>
                                    <ul class="mb-0 pl-3">
                                        @foreach($shortcode->service as $service)
                                            <li>
                                                {{ $service->service_name }}
                                                @if($service->prefix)
                                                    <span class="mpesa-inline-code">{{ $service->prefix }}</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <div class="mpesa-panel shadow-sm">
                <div class="mpesa-panel-header">
                    <div>
                        <div class="mpesa-kicker">Response Examples</div>
                        <h4 class="mb-0">Typical outcomes</h4>
                    </div>
                </div>
                <div class="mpesa-panel-body">
                    <div class="mpesa-kicker">Accepted request</div>
                    <pre class="mpesa-code mb-4" id="response-success-example">{!! json_encode($responseExamples['success'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) !!}</pre>
                    <div class="mpesa-kicker">Validation failure example</div>
                    <pre class="mpesa-code" id="response-failure-example">{!! json_encode($responseExamples['failure'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) !!}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function () {
            var sandboxCatalog = @json($sandboxCatalog);
            var sandboxForm = $('#mpesa-sandbox-form');
            var shortcodeSelect = $('#sandbox-shortcode');
            var serviceSelect = $('#sandbox-service');

            function findShortcode(shortcodeId) {
                var parsedId = parseInt(shortcodeId, 10);

                return sandboxCatalog.find(function(item) {
                    return item.id === parsedId;
                }) || null;
            }

            function safeStringify(value) {
                return JSON.stringify(value || {}, null, 2);
            }

            function applyPrefixHint(shortcode) {
                if (!shortcode || !shortcode.services.length) {
                    return;
                }

                var chosenService = shortcode.services.find(function(service) {
                    return String(service.id) === String(serviceSelect.val());
                }) || shortcode.services[0];

                if (chosenService && chosenService.prefix && $('#sandbox-account').val().trim() === '') {
                    $('#sandbox-account').val(chosenService.prefix + '10042');
                }
            }

            function renderServices(shortcodeId) {
                var shortcode = findShortcode(shortcodeId);
                var options = ['<option value="">Auto detect service</option>'];

                if (shortcode && shortcode.services.length) {
                    shortcode.services.forEach(function(service) {
                        var suffix = service.prefix ? ' - ' + service.prefix : '';
                        options.push('<option value="' + service.id + '">' + service.name + suffix + '</option>');
                    });
                }

                serviceSelect.html(options.join(''));

                if (shortcode && shortcode.services.length) {
                    serviceSelect.val(shortcode.services[0].id);
                }

                applyPrefixHint(shortcode);
            }

            function loadSampleData() {
                var shortcode = findShortcode(shortcodeSelect.val());
                var service = shortcode && shortcode.services.length ? shortcode.services[0] : null;
                var prefix = service && service.prefix ? service.prefix : 'ADM';

                $('#sandbox-amount').val('1500');
                $('#sandbox-msisdn').val('0712345678');
                $('#sandbox-account').val(prefix + '10042');
                $('#sandbox-description').val('April school fees');

                if (service) {
                    serviceSelect.val(service.id);
                }
            }

            function renderWarnings(warnings) {
                var wrap = $('#preview-warnings-wrap');
                var list = $('#preview-warnings');

                list.empty();

                if (!warnings || !warnings.length) {
                    wrap.hide();
                    return;
                }

                warnings.forEach(function(warning) {
                    list.append('<li>' + $('<div>').text(warning).html() + '</li>');
                });

                wrap.show();
            }

            function renderPreview(preview) {
                $('#preview-shortcode').text(preview.credential_source.shortcode || '-');
                $('#preview-group').text(preview.credential_source.group || 'Not set');
                $('#preview-type').text(preview.credential_source.shortcode_type || '-');
                $('#preview-status').text(preview.credential_source.notification_status || '-');
                $('#preview-msisdn').text(preview.request_summary.normalized_msisdn || '-');
                $('#preview-bill-ref').text(preview.request_summary.prefixed_bill_ref || '-');
                $('#preview-credentials-note').text(preview.credential_source.credentials_note || '-');

                $('#preview-internal-json').text(safeStringify(preview.internal_payload));
                $('#preview-internal-curl').text(preview.internal_curl || '');
                $('#preview-daraja-json').text(safeStringify(preview.daraja_payload));
                $('#preview-service-callback').text(safeStringify(preview.callback_examples.forwarded_service_callback));
                $('#preview-stk-callback').text(safeStringify(preview.callback_examples.stk_success_callback));
                $('#preview-validation-json').text(safeStringify(preview.callback_examples.c2b_validation_request));

                renderWarnings(preview.warnings || []);
                $('#mpesa-preview-results').show();
            }

            function copyText(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    return navigator.clipboard.writeText(text);
                }

                return new Promise(function(resolve, reject) {
                    var textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.setAttribute('readonly', '');
                    textarea.style.position = 'absolute';
                    textarea.style.left = '-9999px';
                    document.body.appendChild(textarea);
                    textarea.select();

                    try {
                        document.execCommand('copy');
                        document.body.removeChild(textarea);
                        resolve();
                    } catch (error) {
                        document.body.removeChild(textarea);
                        reject(error);
                    }
                });
            }

            shortcodeSelect.on('change', function() {
                renderServices($(this).val());
            });

            serviceSelect.on('change', function() {
                applyPrefixHint(findShortcode(shortcodeSelect.val()));
            });

            $('#use-sample-data').on('click', function() {
                loadSampleData();
                toastr.success('Sample sandbox data loaded.', 'MPesa Sandbox', {
                    timeOut: 1200,
                    closeButton: true,
                    progressBar: true,
                    newestOnTop: true
                });
            });

            $(document).on('click', '.copy-inline', function() {
                copyText($(this).data('copy-text')).then(function() {
                    toastr.success('Copied to clipboard.', 'MPesa Documentation', {
                        timeOut: 1200,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                }).catch(function() {
                    toastr.error('Unable to copy automatically.', 'MPesa Documentation', {
                        timeOut: 1500,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                });
            });

            $(document).on('dblclick', '.mpesa-code', function() {
                copyText($(this).text()).then(function() {
                    toastr.success('Code block copied.', 'MPesa Documentation', {
                        timeOut: 1200,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                }).catch(function() {
                    toastr.error('Unable to copy automatically.', 'MPesa Documentation', {
                        timeOut: 1500,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                });
            });

            if (sandboxForm.length) {
                renderServices(shortcodeSelect.val());
                loadSampleData();

                sandboxForm.on('submit', function(event) {
                    event.preventDefault();

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('documentation.preview') }}",
                        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                        data: sandboxForm.serialize(),
                        success: function(response) {
                            renderPreview(response.preview);
                            toastr.success(response.msg, response.header, {
                                timeOut: 1400,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                        },
                        error: function(xhr) {
                            if (xhr.responseJSON && xhr.responseJSON.errors) {
                                $.each(xhr.responseJSON.errors, function(key, messages) {
                                    toastr.error(messages[0], 'Validation', {
                                        timeOut: 1800,
                                        closeButton: true,
                                        progressBar: true,
                                        newestOnTop: true
                                    });
                                });
                                return;
                            }

                            toastr.error('Unable to generate the sandbox preview right now.', 'MPesa Sandbox', {
                                timeOut: 1800,
                                closeButton: true,
                                progressBar: true,
                                newestOnTop: true
                            });
                        }
                    });
                });
            }
        })();
    </script>
@endsection
