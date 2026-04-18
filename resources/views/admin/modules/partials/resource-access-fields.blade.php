<div class="mb-3">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <h6 class="mb-1">Specific Resource Access</h6>
            <small class="text-muted">Use this for extra visibility on shared shortcodes or selected services. Keyword access is managed from the Keywords page.</small>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="card h-100 shadow-sm border">
                <div class="card-body">
                    <h6 class="mb-1">Shared Shortcodes</h6>
                    <small class="text-muted d-block mb-3">Grant visibility for a shared shortcode. Transaction access still follows the services selected for the user, unless the user has Transactions - View All.</small>

                    @if(($sharedShortcodes ?? collect())->isEmpty())
                        <label class="input-label mb-0">No shared shortcodes are available to assign right now.</label>
                    @else
                        <div style="max-height: 260px; overflow-y: auto;">
                            @foreach($sharedShortcodes as $shortcode)
                                <label class="d-flex align-items-start border rounded px-3 py-2 mb-2 resource-option resource-option-shortcode" data-owner-user-id="{{ optional($shortcode->user)->id ?: '' }}">
                                    <span class="custom-control custom-checkbox mt-1">
                                        <input
                                            class="custom-control-input resource-checkbox"
                                            type="checkbox"
                                            name="{{ $shortcodeFieldName }}"
                                            id="{{ $formPrefix }}-shortcode-{{ $shortcode->id }}"
                                            value="{{ $shortcode->id }}"
                                            @if(in_array($shortcode->id, $selectedShortcodes ?? [], true)) checked @endif
                                        >
                                        <span class="custom-control-label" for="{{ $formPrefix }}-shortcode-{{ $shortcode->id }}"></span>
                                    </span>
                                    <span class="ml-3">
                                        <span class="font-weight-bold d-block">{{ $shortcode->shortcode }}</span>
                                        <small class="text-muted d-block">Owner: {{ optional($shortcode->user)->name ?: 'Unknown user' }}</small>
                                        <small class="text-muted d-block">Group: {{ $shortcode->group ?: 'Not set' }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-3">
            <div class="card h-100 shadow-sm border">
                <div class="card-body">
                    <h6 class="mb-1">Services</h6>
                    <small class="text-muted d-block mb-3">Grant transaction access to selected services, or set amount limits for services already owned by the user.</small>

                    @if(($sharedServices ?? collect())->isEmpty())
                        <label class="input-label mb-0">No services are available to assign right now.</label>
                    @else
                        <div style="max-height: 260px; overflow-y: auto;">
                            @foreach($sharedServices as $service)
                                <div class="border rounded px-3 py-2 mb-2 resource-option resource-option-service" data-owner-user-id="{{ optional($service->user)->id ?: '' }}" data-service-id="{{ $service->id }}" data-sharing-mode="{{ optional($service->shortcode)->sharing_mode ?: 'dedicated' }}">
                                    <label class="d-flex align-items-start mb-0">
                                        <span class="custom-control custom-checkbox mt-1">
                                            <input
                                                class="custom-control-input resource-checkbox"
                                                type="checkbox"
                                                name="{{ $serviceFieldName }}"
                                                id="{{ $formPrefix }}-service-{{ $service->id }}"
                                                value="{{ $service->id }}"
                                                @if(in_array($service->id, $selectedServices ?? [], true)) checked @endif
                                            >
                                            <span class="custom-control-label" for="{{ $formPrefix }}-service-{{ $service->id }}"></span>
                                        </span>
                                        <span class="ml-3">
                                            <span class="font-weight-bold d-block">{{ $service->service_name }}</span>
                                            <small class="text-muted d-block">Shortcode: {{ optional($service->shortcode)->shortcode ?: 'Not linked' }}</small>
                                            <small class="text-muted d-block">Service owner: {{ optional($service->user)->name ?: 'Default owner' }}</small>
                                        </span>
                                    </label>
                                    @if(\App\User::supportsTransactionAmountLimits())
                                        <div class="form-row mt-2 service-amount-range">
                                            <div class="col-md-6">
                                                <label class="input-label mb-1" for="{{ $formPrefix }}-service-{{ $service->id }}-min">Min Amount</label>
                                                <input
                                                    type="number"
                                                    class="form-control form-control-sm service-amount-min"
                                                    name="service_amount_limits[{{ $service->id }}][min]"
                                                    id="{{ $formPrefix }}-service-{{ $service->id }}-min"
                                                    data-service-id="{{ $service->id }}"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="No minimum"
                                                >
                                            </div>
                                            <div class="col-md-6">
                                                <label class="input-label mb-1" for="{{ $formPrefix }}-service-{{ $service->id }}-max">Max Amount</label>
                                                <input
                                                    type="number"
                                                    class="form-control form-control-sm service-amount-max"
                                                    name="service_amount_limits[{{ $service->id }}][max]"
                                                    id="{{ $formPrefix }}-service-{{ $service->id }}-max"
                                                    data-service-id="{{ $service->id }}"
                                                    min="0"
                                                    step="0.01"
                                                    placeholder="No maximum"
                                                >
                                            </div>
                                            <div class="col-12">
                                                <small class="text-muted">If changed, the new limit applies from save time forward. Older transactions keep the limit that was active then.</small>
                                            </div>
                                            @if(\App\User::supportsTransactionAmountLimitHistoryBypass())
                                                <div class="col-12 mt-2">
                                                    <input type="hidden" name="service_amount_limits[{{ $service->id }}][bypass_history]" value="0">
                                                    <label class="custom-control custom-checkbox mb-0">
                                                        <input
                                                            type="checkbox"
                                                            class="custom-control-input service-bypass-history"
                                                            name="service_amount_limits[{{ $service->id }}][bypass_history]"
                                                            value="1"
                                                            data-service-id="{{ $service->id }}"
                                                        >
                                                        <span class="custom-control-label">Apply current min/max to past transactions too</span>
                                                    </label>
                                                    <small class="text-muted d-block ml-4">Leave unchecked to preserve the old limit history for older transactions.</small>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
