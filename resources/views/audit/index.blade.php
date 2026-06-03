@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Book Audit</h3>
            <div class="text-muted small">Audit physical book copies against database control numbers.</div>
        </div>
        <a href="{{ route('audit.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Start Audit Session
        </a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if($openSession)
        <div class="alert alert-warning d-flex align-items-center justify-content-between">
            <div>
                <div class="fw-semibold">Open audit session found</div>
                <div class="small text-muted">
                    SY {{ $openSession->school_year }} • Started {{ $openSession->started_at?->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <a class="btn btn-outline-dark" href="{{ route('audit.show', $openSession) }}">Continue Inspecting</a>
                <a class="btn btn-dark" href="{{ route('audit.summary', $openSession) }}">View Summary</a>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between">
            <div class="fw-semibold">Audit Sessions</div>
            <div class="text-muted small">Most recent first</div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>School Year</th>
                        <th>Started</th>
                        <th>Status</th>
                        <th>By</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                        <tr>
                            <td class="fw-semibold">{{ $s->school_year }}</td>
                            <td>{{ $s->started_at?->timezone(config('app.display_timezone'))->format('M d, Y h:i A') }}</td>
                            <td>
                                @php
                                    $badge = $s->status === 'OPEN' ? 'warning' : 'success';
                                @endphp
                                <span class="badge bg-{{ $badge }}">{{ $s->status }}</span>
                            </td>
                            <td class="text-muted">
                                {{ $s->creator?->name ?: ($s->creator?->email ?: 'N/A') }}
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-dark" href="{{ route('audit.summary', $s) }}">Summary</a>
                                <a class="btn btn-sm btn-dark" href="{{ route('audit.show', $s) }}">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No audit sessions yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
