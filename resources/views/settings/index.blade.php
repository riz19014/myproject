@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Settings</h1>
</div>

@if(session('success'))
    <div class="alert alert-theme-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-theme mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Daybook calendar default</h2>
        <p class="text-muted small mb-3">
            When no date is in the URL, Daybook opens on the <strong>first day of this month</strong>. The header date picker and the “New entry” date field use that same day.
            Leave both empty to use <strong>today</strong> instead.
        </p>
        <p class="small mb-2"><span class="text-muted">Stored as code</span> <code>{{ \App\Models\Setting::CODE_DAYBOOK_DEFAULT_MONTH_YEAR }}</code></p>

        <form action="{{ route('settings.update') }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-auto">
                <label class="form-label" for="daybook_default_month">Month</label>
                <select name="daybook_default_month" id="daybook_default_month" class="form-select form-select-theme">
                    <option value="">—</option>
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected(old('daybook_default_month', $daybookDefaultMonth) == $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
                @error('daybook_default_month')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-auto">
                <label class="form-label" for="daybook_default_year">Year</label>
                <input type="number" name="daybook_default_year" id="daybook_default_year" class="form-control form-control-theme @error('daybook_default_year') is-invalid @enderror" min="1970" max="2100" step="1" placeholder="—" value="{{ old('daybook_default_year', $daybookDefaultYear) }}">
                @error('daybook_default_year')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-sm-auto">
                <button type="submit" class="btn btn-pink">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <h2 class="h5 mb-3">All settings</h2>
        <p class="text-muted small mb-3">Key–value rows (extend over time). Values are plain text; some codes use structured formats such as <code>YYYY-MM</code>.</p>
        <div class="table-responsive">
            <table class="table table-striped table-theme mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Value</th>
                        <th>Updated</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($settings as $row)
                        <tr>
                            <td><code>{{ $row->code }}</code></td>
                            <td>{{ $row->value !== null && $row->value !== '' ? $row->value : '—' }}</td>
                            <td class="text-muted small">{{ $row->updated_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-muted">No rows yet. Saving the Daybook default above creates the first row.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
