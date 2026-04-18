<div class="audit-log-details-wrapper p-3 border-top">
    @php($displayOldValues = $oldValues ?? $log->old_values)
    @php($displayNewValues = $newValues ?? $log->new_values)
    @php($prettyOldValues = $formattedOldValues ?? (!empty($displayOldValues) ? json_encode($displayOldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null))
    @php($prettyNewValues = $formattedNewValues ?? (!empty($displayNewValues) ? json_encode($displayNewValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null))
    @php($resolvedUrl = $displayUrl ?? ($log->url ?: 'N/A'))
    @php($diffSummary = $diffSummary ?? null)
    @php($changesDetected = isset($changesDetected) ? (bool) $changesDetected : ($displayOldValues !== $displayNewValues))
    @php($loginActivity = $log->loginActivity ?? null)

    <div class="row audit-log-meta-row">
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">Logged At</label>
            <div>{{ optional($log->created_at)->format('d M Y, H:i:s') ?: 'N/A' }}</div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">Actioned By</label>
            <div>{{ $log->user_name ?: 'System' }}</div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">Object</label>
            <div>{{ $log->auditable_type ?: 'General' }}</div>
            <small class="text-muted">{{ $log->auditable_label ?: 'No label' }}</small>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">Page</label>
            <div>{{ $log->page_name ?: 'N/A' }}</div>
        </div>
    </div>

    <div class="row audit-log-meta-row">
        <div class="col-md-6 mb-3">
            <label class="input-label d-block">URL</label>
            <div class="text-break">{{ $resolvedUrl }}</div>
            @if(!empty($maskAuditUrl))
                {{-- <small class="text-muted">Masked because your audit log access is limited to your own actions.</small> --}}
            @endif
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">IP Address</label>
            <div>{{ $log->ip_address ?: 'N/A' }}</div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <label class="input-label d-block">Restore Status</label>
            @if($log->restored_at)
                <div>Restored on {{ $log->restored_at->format('d M Y, H:i:s') }}</div>
                <small class="text-muted">By {{ optional($log->restoredBy)->name ?: 'User #'.$log->restored_by }}</small>
            @elseif($log->canBeRestored())
                <div>Available for restore</div>
            @else
                <div>Not restorable</div>
            @endif
        </div>
    </div>

    @if($loginActivity)
        <div class="border rounded p-3 mb-3 bg-white">
            <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                <div>
                    <strong>Login Session Details</strong>
                    <div class="text-muted small">Linked to this audit entry from the user's active session.</div>
                </div>
                <span class="badge badge-light">Session #{{ $loginActivity->id }}</span>
            </div>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="input-label d-block">User</label>
                    <div>{{ optional($loginActivity->user)->name ?: $log->user_name ?: 'N/A' }}</div>
                    <small class="text-muted">{{ optional($loginActivity->user)->email ?: optional($loginActivity->user)->username }}</small>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="input-label d-block">Login / Last Seen</label>
                    <div>{{ optional($loginActivity->login_at)->format('d M Y, H:i:s') ?: 'N/A' }}</div>
                    <small class="text-muted">Last seen: {{ optional($loginActivity->last_seen_at)->format('d M Y, H:i:s') ?: 'N/A' }}</small>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="input-label d-block">Network</label>
                    <div>{{ $loginActivity->ip_address ?: 'N/A' }}</div>
                    @if($loginActivity->previous_ip_address)
                        <small class="text-muted">Previous: {{ $loginActivity->previous_ip_address }}</small>
                    @endif
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <label class="input-label d-block">Device</label>
                    <div>{{ $loginActivity->device_type ?: 'Unknown' }} / {{ $loginActivity->browser ?: 'Unknown' }}</div>
                    <small class="text-muted">{{ $loginActivity->platform ?: 'Unknown platform' }}</small>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="input-label d-block">Last Page</label>
                    <div class="text-break">{{ $loginActivity->last_url ?: 'N/A' }}</div>
                    @if($loginActivity->previous_url)
                        <small class="text-muted text-break d-block">Previous: {{ $loginActivity->previous_url }}</small>
                    @endif
                </div>
                <div class="col-md-6">
                    <label class="input-label d-block">Browser Location</label>
                    @if($loginActivity->latitude !== null && $loginActivity->longitude !== null)
                        <div>
                            {{ $loginActivity->latitude }}, {{ $loginActivity->longitude }}
                            @if($loginActivity->location_accuracy)
                                <span class="text-muted">({{ $loginActivity->location_accuracy }}m accuracy)</span>
                            @endif
                        </div>
                        <small class="text-muted">
                            Captured: {{ optional($loginActivity->location_captured_at)->format('d M Y, H:i:s') ?: 'N/A' }}
                            <a target="_blank" rel="noopener" href="https://www.openstreetmap.org/?mlat={{ $loginActivity->latitude }}&mlon={{ $loginActivity->longitude }}#map=16/{{ $loginActivity->latitude }}/{{ $loginActivity->longitude }}">Open map</a>
                        </small>
                    @else
                        <div>N/A</div>
                        <small class="text-muted">Browser location is only stored if the user grants permission.</small>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
        <div class="mb-2 mb-md-0">
            <span class="audit-log-change-state {{ $changesDetected ? 'audit-log-change-state--changed' : 'audit-log-change-state--unchanged' }}">
                {{ $changesDetected ? 'Changes detected' : 'No changes were made' }}
            </span>
        </div>
        <div class="audit-log-change-hint">
            Red lines show removed values. Green lines show newly saved values.
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="audit-log-json-card">
                <div class="audit-log-json-header">Previous Data</div>
                @if(!empty($diffSummary['previous_lines']))
                    <div class="audit-log-diff">
                        @foreach($diffSummary['previous_lines'] as $line)
                            <div class="audit-log-diff-line audit-log-diff-line--{{ $line['type'] }}">
                                <span class="audit-log-diff-marker">{{ $line['marker'] }}</span>
                                <pre class="audit-log-diff-code"><code>{{ $line['text'] }}</code></pre>
                            </div>
                        @endforeach
                    </div>
                @elseif(!empty($prettyOldValues))
                    <pre class="audit-log-json"><code>{{ $prettyOldValues }}</code></pre>
                @else
                    <div class="audit-log-empty-state">No previous data recorded.</div>
                @endif
            </div>
        </div>
        <div class="col-md-6">
            <div class="audit-log-json-card">
                <div class="audit-log-json-header">New Data</div>
                @if(!empty($diffSummary['new_lines']))
                    <div class="audit-log-diff">
                        @foreach($diffSummary['new_lines'] as $line)
                            <div class="audit-log-diff-line audit-log-diff-line--{{ $line['type'] }}">
                                <span class="audit-log-diff-marker">{{ $line['marker'] }}</span>
                                <pre class="audit-log-diff-code"><code>{{ $line['text'] }}</code></pre>
                            </div>
                        @endforeach
                    </div>
                @elseif(!empty($prettyNewValues))
                    <pre class="audit-log-json"><code>{{ $prettyNewValues }}</code></pre>
                @else
                    <div class="audit-log-empty-state">No new data recorded.</div>
                @endif
            </div>
        </div>
    </div>
</div>
