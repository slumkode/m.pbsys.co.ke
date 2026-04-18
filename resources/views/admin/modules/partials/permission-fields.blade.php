@php($permissionMode = $permissionMode ?? 'general')
@php($readOnly = !empty($readOnly))
@php($showSelectedOnly = !empty($showSelectedOnly))
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h6 class="mb-1">Page Access & Actions</h6>
            <small class="text-muted">
                Choose what this role or user can open and what actions they can perform on each page.
                @if($permissionMode === 'custom')
                    Only permissions not already provided by the selected role are shown here.
                @elseif($readOnly)
                    This is a read-only view of the permissions currently assigned through the selected role.
                @else
                    Use <strong>Shortcode - Assign Owner</strong> when someone should be allowed to choose shortcode owners.
                @endif
            </small>
        </div>
    </div>

    <div class="row">
        @foreach($permissions as $pageKey => $page)
            @php($visibleActions = collect($page['actions'])->filter(function ($action, $actionKey) use ($pageKey, $selectedPermissions, $showSelectedOnly) {
                $slug = $pageKey.'.'.$actionKey;

                return ! $showSelectedOnly || in_array($slug, $selectedPermissions ?? [], true);
            }))
            @if($visibleActions->isNotEmpty() || ! $showSelectedOnly)
                <div class="col-lg-6 mb-3 permission-page-card" data-page-key="{{ $pageKey }}">
                    <div class="card h-100 shadow-sm border">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h6 class="mb-1">{{ $page['label'] }}</h6>
                                    <small class="text-muted">{{ $page['description'] }}</small>
                                </div>
                                <span class="badge badge-default">{{ $showSelectedOnly ? $visibleActions->count() : count($page['actions']) }} options</span>
                            </div>

                            @foreach($visibleActions as $actionKey => $action)
                                @php($slug = $pageKey.'.'.$actionKey)
                                <label class="d-flex align-items-start border rounded px-3 py-2 mb-2 permission-option" data-permission-slug="{{ $slug }}">
                                    <span class="custom-control custom-checkbox mt-1">
                                        <input
                                            class="custom-control-input permission-checkbox"
                                            type="checkbox"
                                            @if(!$readOnly) name="{{ $fieldName }}" @endif
                                            id="{{ $formPrefix }}-{{ $slug }}"
                                            value="{{ $slug }}"
                                            @if(in_array($slug, $selectedPermissions ?? [], true)) checked @endif
                                            @if($readOnly) disabled @endif
                                        >
                                        <span class="custom-control-label" for="{{ $formPrefix }}-{{ $slug }}"></span>
                                    </span>
                                    <span class="ml-3">
                                        <span class="font-weight-bold d-block">{{ $action['label'] }}</span>
                                        <small class="text-muted d-block">{{ $action['description'] }}</small>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>
