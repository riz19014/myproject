@extends('layouts.app')

@section('title', 'Project exemption')

@section('content')
@php
    $totalSavedExemptions = (int) $projects->sum(fn ($p) => (int) $p->sale_exemption_snapshots_count);
@endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-sliders2" aria-hidden="true"></i>
            <span>Project exemption</span>
        </h1>
        <p class="text-muted small mb-0">DHA projects only. Each save adds a new exemption entry — previous saves stay listed. Use trial land area to preview formula file counts.</p>
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
        @if($totalSavedExemptions > 0)
            <span class="small text-muted">
                Total <strong id="exemption-total-count">{{ $totalSavedExemptions }}</strong>
                · Selected <strong id="exemption-selected-count">0</strong>
            </span>
            <label class="btn btn-sm btn-outline-theme mb-0 d-inline-flex align-items-center gap-2">
                <input type="checkbox" class="form-check-input m-0" id="exemption-select-all" aria-label="Select all exemptions">
                <span>Select all</span>
            </label>
            <form method="post" action="{{ route('sale.exemption.destroy-selected') }}" id="exemption-delete-selected-form" class="d-inline">
                @csrf
                @method('DELETE')
                <div id="exemption-selected-ids"></div>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger btn-delete-confirm"
                    id="exemption-delete-selected-btn"
                    data-title="Delete selected exemptions?"
                    data-text="Only the checked saved exemptions will be removed. Sale files that already applied them keep their copy."
                    data-confirm="Yes, delete selected"
                    disabled
                >Delete selected</button>
            </form>
        @else
            <span class="small text-muted">Total <strong>0</strong> exemptions</span>
        @endif
        <a href="{{ route('sale.percentage.index') }}" class="btn btn-outline-theme btn-sm">Sale percentage</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

<div class="card card-theme mb-4">
    <div class="card-header py-3">
        <h2 class="h6 mb-0">Trial land area</h2>
        <p class="text-muted small mb-0">Formula results below use this area for every saved exemption.</p>
    </div>
    <div class="card-body">
        <form method="get" action="{{ route('sale.exemption.index') }}" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label for="area_acre" class="form-label small mb-1">Acre</label>
                <input type="number" class="form-control form-control-theme" id="area_acre" name="area_acre" value="{{ $trialAcre }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_kanal" class="form-label small mb-1">Kanal</label>
                <input type="number" class="form-control form-control-theme" id="area_kanal" name="area_kanal" value="{{ $trialKanal }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_marla" class="form-label small mb-1">Marla</label>
                <input type="number" class="form-control form-control-theme" id="area_marla" name="area_marla" value="{{ $trialMarlaPart }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_sqft" class="form-label small mb-1">SQFT</label>
                <input type="number" class="form-control form-control-theme" id="area_sqft" name="area_sqft" value="{{ $trialSqft }}" min="0" step="1">
            </div>
            <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-pink">Calculate formula</button>
                <div class="small text-muted mt-1">
                    Trial: <strong>{{ $trialLandLabel }}</strong>
                    · {{ rtrim(rtrim(number_format($trialTotalMarla, 4, '.', ''), '0'), '.') }} marla
                </div>
            </div>
        </form>
    </div>
</div>

@if($projects->isEmpty())
    <div class="card card-theme">
        <div class="card-body">
            <p class="text-muted mb-0">
                No DHA projects found.
                Mark a project as <strong>DHA project</strong> on the project edit page, then return here.
            </p>
        </div>
    </div>
@else
    @foreach($projects as $project)
        @php
            $snapshots = $project->saleExemptionSnapshots;
        @endphp
        <div class="card card-theme mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h2 class="h6 mb-0"><x-project-name :project="$project" /></h2>
                    <p class="text-muted small mb-0">
                        {{ $project->landType?->name ?? '—' }}
                        · {{ (int) $project->project_files_count }} {{ (int) $project->project_files_count === 1 ? 'file' : 'files' }}
                        · {{ (int) $project->sale_exemption_snapshots_count }} saved {{ (int) $project->sale_exemption_snapshots_count === 1 ? 'exemption' : 'exemptions' }}
                    </p>
                </div>
                <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-sm btn-pink">
                    {{ $snapshots->isEmpty() ? 'Create exemption' : 'Add exemption' }}
                </a>
            </div>
            <div class="card-body">
                @if($snapshots->isEmpty())
                    <p class="text-muted small mb-0">No saved exemptions yet. Open <strong>Create exemption</strong>, set Residential / Commercial pool %, and save — each save appears here as a separate row.</p>
                @else
                    @foreach($snapshots as $snapshotIndex => $snapshot)
                        @php
                            $components = $snapshot->components();
                            $fileCalculator = $snapshotTrials[$snapshot->id] ?? ['rows' => [], 'acres' => 0, 'total_marla' => 0];
                            $calcRows = $fileCalculator['rows'] ?? [];
                            $acres = (float) ($fileCalculator['acres'] ?? 0);
                            $saveNumber = $snapshots->count() - $snapshotIndex;
                        @endphp
                        <div class="{{ ! $loop->last ? 'border-bottom pb-4 mb-4' : '' }}">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <div class="d-flex align-items-start gap-2">
                                    <input
                                        type="checkbox"
                                        class="form-check-input mt-1 exemption-snapshot-check"
                                        value="{{ $snapshot->id }}"
                                        aria-label="Select saved exemption #{{ $saveNumber }}"
                                    >
                                    <div>
                                        <h3 class="h6 mb-1">Saved exemption #{{ $saveNumber }}</h3>
                                        <p class="text-muted small mb-0">
                                            {{ $snapshot->created_at->format('d M Y, h:i A') }}
                                            · {{ $snapshot->summaryLabel() }}
                                            · 1 acre = {{ rtrim(rtrim(number_format($snapshot->marlaPerAcre(), 4, '.', ''), '0'), '.') }} marla
                                        </p>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a href="{{ route('sale.projects.exemption.snapshot.edit', [$project, $snapshot]) }}" class="btn btn-sm btn-outline-theme">
                                        Edit
                                    </a>
                                    <form method="post" action="{{ route('sale.projects.exemption.snapshot.destroy', [$project, $snapshot]) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                            data-title="Delete this exemption?"
                                            data-text="Remove saved exemption #{{ $saveNumber }} for {{ $project->name }}? Sale files that already applied it keep their copy."
                                            data-confirm="Yes, delete"
                                        >Delete</button>
                                    </form>
                                </div>
                            </div>
                            <div class="row g-3">
                                <div class="col-lg-5">
                                    <h4 class="h6 mb-2">Exemption list</h4>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-theme mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th class="text-end">Pool %</th>
                                                    <th>Plot types</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($components as $component)
                                                    <tr>
                                                        <td>
                                                            <div class="fw-semibold">{{ $component['label'] }}</div>
                                                            <div class="small text-muted"><code>{{ $component['slug'] }}</code></div>
                                                        </td>
                                                        <td class="text-end">{{ rtrim(rtrim(number_format((float) ($component['pool_percent'] ?? 0), 4, '.', ''), '0'), '.') }}%</td>
                                                        <td class="small">
                                                            @forelse($component['plot_types'] ?? [] as $plot)
                                                                <div>
                                                                    <strong>{{ $plot['label'] }}</strong>
                                                                    <span class="text-muted">
                                                                        · {{ rtrim(rtrim(number_format((float) ($plot['marla_per_plot'] ?? 0), 4, '.', ''), '0'), '.') }}M/plot
                                                                        · ÷{{ rtrim(rtrim(number_format((float) ($plot['nominal_marla'] ?? 0), 4, '.', ''), '0'), '.') }}
                                                                        · {{ rtrim(rtrim(number_format((float) ($plot['share_percent'] ?? 0), 4, '.', ''), '0'), '.') }}%
                                                                    </span>
                                                                </div>
                                                            @empty
                                                                <span class="text-muted">—</span>
                                                            @endforelse
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <h4 class="h6 mb-2">
                                        Formula for trial area
                                        <span class="text-muted fw-normal">· {{ $trialLandLabel }}</span>
                                    </h4>
                                    <p class="small text-muted mb-2">
                                        Acres: <strong>{{ \App\Support\SaleExemptionFileCalculator::formatFileCount($acres) }}</strong>
                                        · Total: <strong>{{ $trialLandLabel }}</strong>
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped table-theme mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Code</th>
                                                    <th>Plot file</th>
                                                    <th class="text-end">Files</th>
                                                    <th>Breakdown</th>
                                                    <th class="text-end">Calculation</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($calcRows as $row)
                                                    <tr>
                                                        <td class="fw-semibold">{{ str_replace('.', '', $row['code']) }}</td>
                                                        <td>
                                                            <div class="fw-semibold">{{ $row['plot_label'] }}</div>
                                                            <div class="small text-muted">{{ $row['component_label'] }}</div>
                                                        </td>
                                                        <td class="text-end fw-semibold">
                                                            {{ \App\Support\SaleExemptionFileCalculator::formatFileCount((float) $row['file_count']) }}
                                                        </td>
                                                        <td class="small text-muted">
                                                            {{ \App\Support\SaleExemptionFileCalculator::formatFileBreakdown(
                                                                (int) $row['full_files'],
                                                                (float) $row['fraction_files'],
                                                                (float) $row['fraction_marla'],
                                                            ) }}
                                                        </td>
                                                        <td class="text-end small font-monospace">
                                                            {{ \App\Support\SaleExemptionFileCalculator::formatMarlaWithUnit((float) $row['marla_per_plot']) }}
                                                            × {{ \App\Support\SaleExemptionFileCalculator::formatFileCount($acres) }}
                                                            ÷ {{ \App\Support\SaleExemptionFileCalculator::formatMarlaWithUnit((float) $row['nominal_marla']) }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-muted small">No plot types in this saved exemption.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    @endforeach
@endif
@endsection

@push('scripts')
<script>
(function () {
    var checks = Array.prototype.slice.call(document.querySelectorAll('.exemption-snapshot-check'));
    var btn = document.getElementById('exemption-delete-selected-btn');
    var idsWrap = document.getElementById('exemption-selected-ids');
    var selectAll = document.getElementById('exemption-select-all');
    var selectedCountEl = document.getElementById('exemption-selected-count');
    if (!checks.length || !btn || !idsWrap) return;

    function syncSelected() {
        var selected = checks.filter(function (el) { return el.checked; });
        idsWrap.innerHTML = '';
        selected.forEach(function (el) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'snapshot_ids[]';
            input.value = el.value;
            idsWrap.appendChild(input);
        });
        if (selectedCountEl) {
            selectedCountEl.textContent = String(selected.length);
        }
        if (selectAll) {
            selectAll.checked = selected.length > 0 && selected.length === checks.length;
            selectAll.indeterminate = selected.length > 0 && selected.length < checks.length;
        }
        btn.disabled = selected.length === 0;
        btn.textContent = selected.length > 0
            ? ('Delete selected (' + selected.length + ')')
            : 'Delete selected';
        btn.dataset.text = selected.length === 1
            ? 'Delete 1 selected exemption? Sale files that already applied it keep their copy.'
            : 'Delete ' + selected.length + ' selected exemptions? Sale files that already applied them keep their copy.';
    }

    checks.forEach(function (el) {
        el.addEventListener('change', syncSelected);
    });

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks.forEach(function (el) {
                el.checked = selectAll.checked;
            });
            syncSelected();
        });
    }

    syncSelected();
})();
</script>
@endpush
