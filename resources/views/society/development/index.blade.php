@extends('layouts.app')

@section('title', 'Development — '.$project->name)

@section('content')
@include('society.partials.nav')

<div class="mb-4">
    <h1 class="h3 mb-1">Development management</h1>
    <p class="text-muted mb-0">Roads, sewerage, water, electricity — packages, BOQ, contractors, and progress.</p>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card card-theme">
            <div class="card-body">
                <h5 class="mb-3">New package</h5>
                <form method="post" action="{{ route('society.development.store', $project) }}">
                    @csrf
                    <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
                    <div class="mb-2">
                        <label class="form-label">Type</label>
                        <select name="package_type" class="form-select">
                            @foreach($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Contractor</label>
                        <select name="contractor_party_id" class="form-select">
                            <option value="">— Optional —</option>
                            @foreach($parties as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="mb-2"><label class="form-label">Estimated</label><input type="number" step="0.01" min="0" name="estimated_amount" class="form-control" value="0"></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Target</label><input type="date" name="target_date" class="form-control"></div>
                    </div>
                    <button class="btn btn-pink btn-sm">Create</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        @forelse($packages as $package)
            @php
                $paid = $package->contractor_party_id
                    ? (float) ($contractorPayments[$package->contractor_party_id] ?? 0)
                    : 0;
            @endphp
            <div class="card card-theme mb-3">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
                        <div>
                            <h5 class="mb-0">{{ $package->name }}</h5>
                            <div class="small text-muted">
                                {{ $package->typeLabel() }} · {{ $package->statusLabel() }} ·
                                {{ number_format((float)$package->progress_percent, 1) }}% ·
                                Est Rs {{ number_format((float)$package->estimated_amount, 2) }} ·
                                BOQ est {{ number_format($package->boqEstimatedTotal(), 2) }} /
                                actual {{ number_format($package->boqActualTotal(), 2) }}
                                @if($package->contractor)
                                    · Contractor {{ $package->contractor->name }}
                                    (daybook out Rs {{ number_format($paid, 2) }})
                                @endif
                            </div>
                        </div>
                        <form method="post" action="{{ route('society.development.destroy', [$project, $package]) }}" onsubmit="return confirm('Delete package?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </div>

                    <div class="progress mb-3" style="height: 8px;">
                        <div class="progress-bar" style="width: {{ min(100, (float)$package->progress_percent) }}%"></div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>Add BOQ item</h6>
                            <form method="post" action="{{ route('society.development.boq', [$project, $package]) }}" class="row g-2">
                                @csrf
                                <div class="col-12"><input name="item_name" class="form-control form-control-sm" placeholder="Item" required></div>
                                <div class="col-4"><input name="unit" class="form-control form-control-sm" placeholder="Unit"></div>
                                <div class="col-4"><input type="number" step="0.0001" name="quantity" class="form-control form-control-sm" placeholder="Qty" required></div>
                                <div class="col-4"><input type="number" step="0.01" name="unit_rate" class="form-control form-control-sm" placeholder="Rate" required></div>
                                <div class="col-12"><button class="btn btn-sm btn-outline-theme">Add BOQ</button></div>
                            </form>
                            @if($package->boqItems->isNotEmpty())
                                <ul class="list-unstyled small mt-2 mb-0">
                                    @foreach($package->boqItems as $item)
                                        <li>{{ $item->item_name }} — {{ $item->quantity }} {{ $item->unit }} × {{ number_format((float)$item->unit_rate, 2) }} = {{ number_format((float)$item->estimated_amount, 2) }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>Log progress</h6>
                            <p class="small text-muted mb-2">Enter the new completion % (0–100), then save. Current: <strong>{{ number_format((float) $package->progress_percent, 1) }}%</strong></p>
                            <form method="post" action="{{ route('society.development.progress', [$project, $package]) }}">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-4">
                                        <label class="form-label small mb-0">Progress %</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            name="progress_percent"
                                            class="form-control form-control-sm"
                                            value="{{ old('progress_percent', (float) $package->progress_percent > 0 ? $package->progress_percent : '') }}"
                                            placeholder="e.g. 25"
                                            required
                                        >
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small mb-0">Date</label>
                                        <input type="date" name="logged_on" class="form-control form-control-sm" value="{{ old('logged_on', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small mb-0">Status</label>
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="" @selected(old('status', '') === '')>Auto (from %)</option>
                                            @foreach($statuses as $k => $v)
                                                <option value="{{ $k }}" @selected(old('status') === $k)>{{ $v }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-0">Notes</label>
                                        <input name="notes" class="form-control form-control-sm" placeholder="Optional note" value="{{ old('notes') }}">
                                    </div>
                                    <div class="col-12"><button type="submit" class="btn btn-sm btn-pink">Save progress</button></div>
                                </div>
                            </form>
                            @if($package->progressLogs->isNotEmpty())
                                <div class="mt-3">
                                    <div class="small text-muted mb-1">Recent logs</div>
                                    <ul class="list-unstyled small mb-0">
                                        @foreach($package->progressLogs as $log)
                                            <li class="border-bottom py-1 d-flex justify-content-between gap-2">
                                                <span>
                                                    <span class="fw-semibold">{{ number_format((float) $log->progress_percent, 1) }}%</span>
                                                    <span class="text-muted">· {{ $log->logged_on?->format('j M Y') }}</span>
                                                    @if($log->notes)
                                                        <span class="text-muted">— {{ $log->notes }}</span>
                                                    @endif
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card card-theme"><div class="card-body text-muted">No development packages yet.</div></div>
        @endforelse
    </div>
</div>
@endsection
