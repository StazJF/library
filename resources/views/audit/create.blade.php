@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 820px;">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h3 class="mb-0">Start Audit Session</h3>
            <div class="text-muted small">Recommended for end-of-school-year inventory checking.</div>
        </div>
        <a href="{{ route('audit.index') }}" class="btn btn-outline-dark">Back</a>
    </div>

    @php
        // Default SY heuristic: if month is Jul-Dec, SY is current-next; else previous-current.
        $now = \Carbon\Carbon::now();
        $startYear = $now->month >= 7 ? $now->year : $now->year - 1;
        $defaultSY = $startYear . '-' . ($startYear + 1);
    @endphp

    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold">Audit Details</div>
        <div class="card-body">
            <form method="POST" action="{{ route('audit.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">School Year</label>
                    <input
                        type="text"
                        name="school_year"
                        class="form-control @error('school_year') is-invalid @enderror"
                        value="{{ old('school_year', $defaultSY) }}"
                        placeholder="2025-2026"
                        autocomplete="off"
                    />
                    @error('school_year')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Format: YYYY-YYYY</div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeBorrowed" name="include_borrowed" value="1" {{ old('include_borrowed') ? 'checked' : '' }}>
                            <label class="form-check-label" for="includeBorrowed">
                                Include borrowed copies
                            </label>
                            <div class="form-text">If unchecked, borrowed copies won’t be flagged as “missing”.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeLostDamaged" name="include_lost_damaged" value="1" {{ old('include_lost_damaged') ? 'checked' : '' }}>
                            <label class="form-check-label" for="includeLostDamaged">
                                Include copies already marked lost/damaged
                            </label>
                            <div class="form-text">If unchecked, existing lost/damaged copies won’t be included in audit scope.</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror" placeholder="e.g. Year-end audit, library main room only">{{ old('notes') }}</textarea>
                    @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-play me-2"></i>Start Session
                    </button>
                    <a class="btn btn-outline-dark" href="{{ route('audit.index') }}">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

