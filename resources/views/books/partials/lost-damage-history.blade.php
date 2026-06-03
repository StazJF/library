@php($displayTimezone = $displayTimezone ?? config('app.display_timezone', 'Asia/Manila'))

<div class="table-responsive">
    <table class="table">
        <thead class="table-light">
            <tr>
                <th>Type</th>
                <th>Action</th>
                <th>Ctrl Number</th>
                <th>Book</th>
                <th>Borrower</th>
                <th>Advisory Class</th>
                <th>Borrowed Date</th>
                <th>Note</th>
                <th>Repaired/Handled By</th>
                <th>Date</th>
            </tr>
        </thead>

        <tbody>
        @forelse($history as $log)
            <tr>
                <td>
                    <span class="badge bg-danger">
                        {{ ucfirst($log->type) }}
                    </span>
                </td>

                <td>
                    @if($log->action === 'Found' || $log->action === 'Returned' || $log->action === 'Repaired')
                        <span class="badge bg-success">{{ $log->action }}</span>
                    @else
                        <span class="badge bg-danger">{{ $log->action }}</span>
                    @endif
                </td>

                <td>{{ $log->ctrl_number }}</td>
                <td>{{ $log->book_title }}</td>
                <td>{{ $log->borrower }}</td>
                <td>{{ $log->advisory_class ?? '—' }}</td>
                <td>{{ $log->borrowed_date ? \Carbon\Carbon::parse($log->borrowed_date)->format('M d, Y') : '—' }}</td>
                <td>
                    @php($note = trim((string) ($log->remarks ?? '')))
                    @if($note !== '' && $note !== '—')
                        <div class="small text-muted" style="white-space: pre-wrap;">{{ $note }}</div>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>

                <td>
                    <small>{{ $log->performed_by ?? '—' }}</small>
                </td>

                <td>
                    {{ \Carbon\Carbon::parse($log->created_at)->timezone($displayTimezone)->format('M d, Y h:i A') }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center text-muted">
                    No history logs found
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@if(method_exists($history, 'links'))
    <div class="d-flex justify-content-center mt-3">
        {{ $history->withQueryString()->links() }}
    </div>
@endif
