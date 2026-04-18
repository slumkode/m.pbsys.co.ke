<div class="audit-log-details-wrapper p-3 border-top">
    @php($displayOldValues = $oldValues ?? $log->old_values)
    @php($displayNewValues = $newValues ?? $log->new_values)
    @php($prettyOldValues = $formattedOldValues ?? (!empty($displayOldValues) ? json_encode($displayOldValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null))
    @php($prettyNewValues = $formattedNewValues ?? (!empty($displayNewValues) ? json_encode($displayNewValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) : null))
    @php($resolvedUrl = $displayUrl ?? ($log->url ?: 'N/A'))
    @php($diffSummary = $diffSummary ?? null)
    @php($changesDetected = isset($changesDetected) ? (bool) $changesDetected : ($displayOldValues !== $displayNewValues))

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
