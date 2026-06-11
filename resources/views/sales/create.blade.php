@extends('layouts.app')

@section('title', 'Sale — '.$projectFile->file_number)

@php
    use App\Models\Sale;
    use App\Support\SaleExemptionFileCalculator;
    use App\Support\SaleExemptionRules;
    $fileMarla = (float) $projectFile->land_area_marla;
    $config = $exemptionConfig;
    $poolsByComponent = $poolsByComponent ?? [];
    $usedByComponent = $usedByComponent ?? [];
    $marlaPerAcreLand = $config->marlaPerAcreLand();
    $activeType = old('sale_type', request('type', Sale::TYPE_DIRECT));
    if (! in_array($activeType, [Sale::TYPE_DIRECT, Sale::TYPE_PERCENTAGE], true)) {
        $activeType = Sale::TYPE_DIRECT;
    }
@endphp

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Sale</h1>
        <p class="text-muted small mb-0">
            Project: <strong>{{ $project->name }}</strong>
            · File: <strong>{{ $projectFile->file_number }}</strong>
            · Total: <strong>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</strong>
            · Remaining (direct): <strong>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($remainingDirect) }}</strong>
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-outline-theme btn-sm">Project exemption setup</a>
        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme">Back to files</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Step 1: choose sale type --}}
<div class="card card-theme mb-4">
    <div class="card-body">
        <h2 class="h6 mb-2">Sale type</h2>
        <p class="text-muted small mb-3">Choose how you are selling from this file, then fill in the details below.</p>
        <div class="row g-2" role="tablist" aria-label="Sale type">
            <div class="col-md-6">
                <button type="button"
                    class="btn w-100 text-start sale-type-picker {{ $activeType === Sale::TYPE_DIRECT ? 'btn-pink' : 'btn-outline-theme' }}"
                    data-sale-type="{{ Sale::TYPE_DIRECT }}"
                    id="sale_type_direct_btn"
                    role="tab"
                    aria-selected="{{ $activeType === Sale::TYPE_DIRECT ? 'true' : 'false' }}"
                    aria-controls="sale_panel_direct">
                    <span class="d-block fw-semibold">Type 1 — Direct sale</span>
                    <span class="d-block small opacity-75 mt-1">Sell land by area from the file (acre, kanal, marla, sq ft).</span>
                </button>
            </div>
            <div class="col-md-6">
                <button type="button"
                    class="btn w-100 text-start sale-type-picker {{ $activeType === Sale::TYPE_PERCENTAGE ? 'btn-pink' : 'btn-outline-theme' }}"
                    data-sale-type="{{ Sale::TYPE_PERCENTAGE }}"
                    id="sale_type_percentage_btn"
                    role="tab"
                    aria-selected="{{ $activeType === Sale::TYPE_PERCENTAGE ? 'true' : 'false' }}"
                    aria-controls="sale_panel_percentage">
                    <span class="d-block fw-semibold">Type 2 — Percentage sale</span>
                    <span class="d-block small opacity-75 mt-1">Sell exempt plots from residential or commercial pools.</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Percentage: file area + pools (only for type 2) --}}
<div class="card card-theme mb-4 {{ $activeType === Sale::TYPE_PERCENTAGE ? '' : 'd-none' }}" id="percentage_pools_card">
    <div class="card-body">
        <h2 class="h6 mb-2">Define file land area</h2>
        <p class="text-muted small mb-3">Set the total land in this file (e.g. 30 kanal). Residential and commercial exemption pools for making plot files are calculated from this area.</p>
        <form method="post" action="{{ route('sale.files.area.update', $projectFile) }}" id="file-area-form" class="mb-4">
            @csrf
            @method('PUT')
            <h3 class="h6 text-muted mb-2">Exemption pool % <span class="fw-normal">(this file — override project defaults)</span></h3>
            <div class="row g-2 mb-3">
                @foreach($config->components() as $component)
                    <div class="col-md-6">
                        <label for="pool_percent_{{ $component->id }}" class="form-label">{{ $component->label }} pool %</label>
                        <input type="number" class="form-control form-control-theme pool-percent-input @error('pool_percent.'.$component->id) is-invalid @enderror" id="pool_percent_{{ $component->id }}" name="pool_percent[{{ $component->id }}]" value="{{ old('pool_percent.'.$component->id, $config->poolPercent($component->slug)) }}" min="0" max="100" step="0.0001" data-component-slug="{{ $component->slug }}" required>
                        @error('pool_percent.'.$component->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Project default: {{ rtrim(rtrim(number_format((float) $component->pool_percent, 4, '.', ''), '0'), '.') }}%</div>
                    </div>
                @endforeach
            </div>

            <h3 class="h6 text-muted mb-2">File land (acre, kanal, marla, sq ft)</h3>
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <label for="file_area_acre" class="form-label">Acre</label>
                    <input type="number" class="form-control form-control-theme @error('file_area_acre') is-invalid @enderror" id="file_area_acre" name="file_area_acre" value="{{ old('file_area_acre', $projectFile->area_acre ?? 0) }}" min="0" step="1" required>
                    @error('file_area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="file_area_kanal" class="form-label">Kanal</label>
                    <input type="number" class="form-control form-control-theme @error('file_area_kanal') is-invalid @enderror" id="file_area_kanal" name="file_area_kanal" value="{{ old('file_area_kanal', $projectFile->area_kanal ?? 0) }}" min="0" step="1" required>
                    @error('file_area_kanal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="file_area_marla" class="form-label">Marla</label>
                    <input type="number" class="form-control form-control-theme @error('file_area_marla') is-invalid @enderror" id="file_area_marla" name="file_area_marla" value="{{ old('file_area_marla', $projectFile->area_marla ?? 0) }}" min="0" step="1" required>
                    @error('file_area_marla')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="file_area_sqft" class="form-label">Sq ft</label>
                    <input type="number" class="form-control form-control-theme @error('file_area_sqft') is-invalid @enderror" id="file_area_sqft" name="file_area_sqft" value="{{ old('file_area_sqft', $projectFile->area_sqft ?? 0) }}" min="0" step="1" required>
                    @error('file_area_sqft')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="submit" class="btn btn-outline-theme btn-sm">Save area &amp; pool %</button>
                <span class="text-muted small" id="file_area_preview_label">
                    Total: <strong>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</strong>
                </span>
            </div>
        </form>

        <h2 class="h6 mb-3">Exemption pools (from file area)</h2>
        <p class="text-muted small mb-3" id="pool_summary_text">Configured per project (1 acre = {{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }} marla). <a href="{{ route('sale.projects.exemption.edit', $project) }}">Edit ratios &amp; categories</a>.</p>
        @if($fileMarla <= 0)
            <div class="alert alert-warning small mb-3">Enter and save file land area above to calculate pools before recording percentage sales.</div>
        @endif
        <div class="row g-2" id="pool_cards_row">
            @foreach($config->components() as $component)
                @php
                    $slug = $component->slug;
                    $poolMarla = $poolsByComponent[$slug] ?? 0;
                    $used = $usedByComponent[$slug] ?? 0;
                    $left = max(0, $poolMarla - $used);
                @endphp
                <div class="col-md-6 pool-card" data-component-slug="{{ $slug }}">
                    <div class="border rounded p-2 small">
                        <div class="text-muted">{{ $component->label }} pool</div>
                        <div class="fw-semibold pool-total">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($poolMarla) }}</div>
                        <div class="text-muted mt-1 pool-pct-line"><span class="pool-pct-val">{{ rtrim(rtrim(number_format($config->poolPercent($slug), 4, '.', ''), '0'), '.') }}</span>% of file</div>
                        <div class="text-muted mt-1">Used: <span class="pool-used">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($used) }}</span> · Left: <span class="pool-left">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($left) }}</span></div>
                    </div>
                </div>
            @endforeach
        </div>

        <hr class="my-4">

        <h2 class="h6 mb-2">Plot files calculator</h2>
        <p class="text-muted small mb-3">Enter any land area — get how many exempt plot files can be made. Formula: <strong>(marla per plot × acres) ÷ nominal marla</strong>. After the decimal: <strong>fraction × nominal marla</strong> (e.g. 0.4 × 40M = 16M). Acres = total marla ÷ {{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }}.</p>
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <label for="calc_area_acre" class="form-label">Acre</label>
                <input type="number" class="form-control form-control-theme calc-area-input" id="calc_area_acre" value="{{ old('calc_area_acre', $projectFile->area_acre ?? 0) }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-3">
                <label for="calc_area_kanal" class="form-label">Kanal</label>
                <input type="number" class="form-control form-control-theme calc-area-input" id="calc_area_kanal" value="{{ old('calc_area_kanal', $projectFile->area_kanal ?? 0) }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-3">
                <label for="calc_area_marla" class="form-label">Marla</label>
                <input type="number" class="form-control form-control-theme calc-area-input" id="calc_area_marla" value="{{ old('calc_area_marla', $projectFile->area_marla ?? 0) }}" min="0" step="1">
            </div>
            <div class="col-6 col-md-3">
                <label for="calc_area_sqft" class="form-label">Sq ft</label>
                <input type="number" class="form-control form-control-theme calc-area-input" id="calc_area_sqft" value="{{ old('calc_area_sqft', $projectFile->area_sqft ?? 0) }}" min="0" step="1">
            </div>
        </div>
        <p class="small mb-2">
            Total: <strong id="calc_total_label">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</strong>
            · Acres: <strong id="calc_acres_label">{{ SaleExemptionFileCalculator::formatFileCount($fileCalculator['acres'] ?? 0) }}</strong>
        </p>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-theme mb-0" id="file_calculator_table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Plot file</th>
                        <th>Share %</th>
                        <th>Calculation</th>
                        <th class="text-end">Files</th>
                        <th class="text-end">Full</th>
                        <th class="text-end">After decimal</th>
                        <th class="text-end">Decimal in marla</th>
                        <th class="text-end">Pool line marla</th>
                    </tr>
                </thead>
                <tbody id="file_calculator_body">
                    @foreach($fileCalculator['rows'] ?? [] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['code'] }}</td>
                            <td>{{ $row['plot_label'] }}</td>
                            <td>{{ SaleExemptionFileCalculator::formatFileCount($row['share_percent']) }}%</td>
                            <td class="small font-monospace">
                                {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['marla_per_plot']) }}
                                × <span class="calc-acres-inline">{{ SaleExemptionFileCalculator::formatFileCount($fileCalculator['acres']) }}</span>
                                = {{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}
                                :{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['nominal_marla']) }}
                            </td>
                            <td class="text-end fw-semibold">{{ SaleExemptionFileCalculator::formatFileCount($row['file_count']) }}</td>
                            <td class="text-end">{{ $row['full_files'] }}</td>
                            <td class="text-end small">{{ $row['fraction_files'] > 0 ? SaleExemptionFileCalculator::formatFileCount($row['fraction_files']) : '—' }}</td>
                            <td class="text-end small">{{ $row['fraction_marla'] > 0 ? SaleExemptionFileCalculator::formatMarlaWithUnit($row['fraction_marla']) : '—' }}</td>
                            <td class="text-end small text-muted">{{ SaleExemptionFileCalculator::formatMarlaWithUnit($row['product_marla']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Type 1: Direct sale form --}}
<div class="card card-theme mb-4 {{ $activeType === Sale::TYPE_DIRECT ? '' : 'd-none' }}" id="sale_panel_direct" role="tabpanel">
    <div class="card-body">
        <h2 class="h6 mb-1">Direct sale</h2>
        <p class="text-muted small mb-3">Sell a plot by area from this file (e.g. 5 marla from a 30 kanal file).</p>
        <form method="post" action="{{ route('sale.files.sale.store', $projectFile) }}">
            @csrf
            <input type="hidden" name="sale_type" value="{{ Sale::TYPE_DIRECT }}">
            <div class="mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <label for="direct_customer_id" class="form-label mb-0">Customer <span class="text-danger">*</span></label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="direct_customer_reset" aria-label="Clear customer">Reset</button>
                </div>
                <select class="form-select form-control-theme @error('customer_id') is-invalid @enderror" id="direct_customer_id" name="customer_id" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected((int) old('customer_id') === $c->id && old('sale_type', $activeType) === Sale::TYPE_DIRECT)>{{ $c->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Acre</label>
                    <input type="number" class="form-control form-control-theme @error('area_acre') is-invalid @enderror" name="area_acre" value="{{ old('sale_type') === Sale::TYPE_DIRECT ? old('area_acre', 0) : 0 }}" min="0" step="1" required>
                    @error('area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6">
                    <label class="form-label">Kanal</label>
                    <input type="number" class="form-control form-control-theme" name="area_kanal" value="{{ old('sale_type') === Sale::TYPE_DIRECT ? old('area_kanal', 0) : 0 }}" min="0" step="1" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Marla</label>
                    <input type="number" class="form-control form-control-theme" name="area_marla" value="{{ old('sale_type') === Sale::TYPE_DIRECT ? old('area_marla', 5) : 5 }}" min="0" step="1" required>
                </div>
                <div class="col-6">
                    <label class="form-label">Sq ft</label>
                    <input type="number" class="form-control form-control-theme" name="area_sqft" value="{{ old('sale_type') === Sale::TYPE_DIRECT ? old('area_sqft', 0) : 0 }}" min="0" step="1" required>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-theme @error('total_amount') is-invalid @enderror" name="total_amount" value="{{ old('sale_type') === Sale::TYPE_DIRECT ? old('total_amount') : '' }}" min="0" step="0.01" required>
                @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-pink">Save direct sale</button>
        </form>
    </div>
</div>

{{-- Type 2: Percentage sale form --}}
{{--<div class="card card-theme mb-4 {{ $activeType === Sale::TYPE_PERCENTAGE ? '' : 'd-none' }}" id="sale_panel_percentage" role="tabpanel">
    <div class="card-body">
        <h2 class="h6 mb-1">Percentage sale</h2>
        <p class="text-muted small mb-3">Sell exempt plot types from the residential or commercial pool.</p>
        <form method="post" action="{{ route('sale.files.sale.store', $projectFile) }}" id="percentage-sale-form">
            @csrf
            <input type="hidden" name="sale_type" value="{{ Sale::TYPE_PERCENTAGE }}">
            <div class="mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <label class="form-label mb-0" for="percentage_customer_id">Customer <span class="text-danger">*</span></label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="percentage_customer_reset" aria-label="Clear customer">Reset</button>
                </div>
                <select class="form-select form-control-theme @error('customer_id') is-invalid @enderror" id="percentage_customer_id" name="customer_id" required>
                    <option value="">Select customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" @selected((int) old('customer_id') === $c->id && old('sale_type', $activeType) === Sale::TYPE_PERCENTAGE)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Allocation category <span class="text-danger">*</span></label>
                <select class="form-select form-control-theme" name="component" id="sale_component" required>
                    @foreach($config->components() as $component)
                        <option value="{{ $component->slug }}" @selected(old('component') === $component->slug)>
                            {{ $component->label }} ({{ rtrim(rtrim(number_format($config->poolPercent($component->slug), 4, '.', ''), '0'), '.') }}%)
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Plot file type <span class="text-danger">*</span></label>
                <select class="form-select form-control-theme @error('plot_type') is-invalid @enderror" name="plot_type" id="sale_plot_type" required>
                    @foreach($config->components() as $component)
                        @foreach($component->plotTypes as $plot)
                            <option value="{{ $plot->slug }}" data-component="{{ $component->slug }}" data-marla="{{ $plot->marla_per_plot }}" data-share="{{ $plot->share_percent }}" data-dist="{{ $component->distributionMarlaPerAcre() }}" @selected(old('plot_type') === $plot->slug)>
                                {{ $plot->label }} — {{ $plot->marla_per_plot }} marla ({{ rtrim(rtrim(number_format((float) $plot->share_percent, 4, '.', ''), '0'), '.') }}% of {{ rtrim(rtrim(number_format($component->distributionMarlaPerAcre(), 4, '.', ''), '0'), '.') }} marla/acre)
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('plot_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @error('plot_quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-text" id="plot_marla_hint"></div>
            </div>
            <div class="mb-3">
                <label class="form-label">Quantity <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-theme" name="plot_quantity" id="plot_quantity" value="{{ old('plot_quantity', 1) }}" min="1" max="999" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                <input type="number" class="form-control form-control-theme" name="total_amount" value="{{ old('sale_type') === Sale::TYPE_PERCENTAGE ? old('total_amount') : '' }}" min="0" step="0.01" required>
            </div>
            <button type="submit" class="btn btn-pink">Save percentage sale</button>
        </form>
    </div>
</div>--}}

@if($recentSales->isNotEmpty())
<div class="card card-theme">
    <div class="card-body">
        <h2 class="h6 mb-3">Sales on this file</h2>
        <div class="table-responsive">
            <table class="table table-sm table-striped table-theme mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Detail</th>
                        <th class="text-end">Land</th>
                        <th class="text-end">Rs</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentSales as $sale)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="small">{{ $sale->isDirect() ? 'Direct' : 'Percentage' }}</td>
                            <td class="small">{{ $sale->customer?->name ?? '—' }}</td>
                            <td class="small">
                                @if($sale->isPercentage())
                                    {{ $config->findComponent($sale->component)?->label ?? ucfirst($sale->component) }} · {{ $config->plotLabel($sale->component, $sale->plot_type) }}
                                    @if($sale->plot_quantity > 1) × {{ $sale->plot_quantity }} @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $sale->land_area_marla) }}</td>
                            <td class="text-end small">{{ number_format((float) $sale->total_amount, 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var TYPE_DIRECT = @json(Sale::TYPE_DIRECT);
    var TYPE_PERCENTAGE = @json(Sale::TYPE_PERCENTAGE);
    var pickers = document.querySelectorAll('.sale-type-picker');
    var panelDirect = document.getElementById('sale_panel_direct');
    var panelPercentage = document.getElementById('sale_panel_percentage');
    var poolsCard = document.getElementById('percentage_pools_card');
    var activeType = @json($activeType);

    function setSaleType(type) {
        activeType = type;
        pickers.forEach(function (btn) {
            var isActive = btn.getAttribute('data-sale-type') === type;
            btn.classList.toggle('btn-pink', isActive);
            btn.classList.toggle('btn-outline-theme', !isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        if (panelDirect) panelDirect.classList.toggle('d-none', type !== TYPE_DIRECT);
        if (panelPercentage) panelPercentage.classList.toggle('d-none', type !== TYPE_PERCENTAGE);
        if (poolsCard) poolsCard.classList.toggle('d-none', type !== TYPE_PERCENTAGE);
        var url = new URL(window.location.href);
        url.searchParams.set('type', type);
        window.history.replaceState({}, '', url);
    }

    pickers.forEach(function (btn) {
        btn.addEventListener('click', function () {
            setSaleType(btn.getAttribute('data-sale-type'));
        });
    });

    var componentEl = document.getElementById('sale_component');
    var plotEl = document.getElementById('sale_plot_type');
    var qtyEl = document.getElementById('plot_quantity');
    var hintEl = document.getElementById('plot_marla_hint');

    function syncPlotOptions() {
        if (!componentEl || !plotEl) return;
        var comp = componentEl.value;
        var opts = plotEl.querySelectorAll('option');
        var firstVisible = null;
        opts.forEach(function (opt) {
            var show = opt.getAttribute('data-component') === comp;
            opt.hidden = !show;
            opt.disabled = !show;
            if (show && !firstVisible) firstVisible = opt;
        });
        var current = plotEl.options[plotEl.selectedIndex];
        if (!current || current.disabled) {
            if (firstVisible) plotEl.value = firstVisible.value;
        }
        updateHint();
    }

    function updateHint() {
        if (!plotEl || !hintEl) return;
        var opt = plotEl.options[plotEl.selectedIndex];
        if (!opt || opt.disabled) return;
        var marla = parseFloat(opt.getAttribute('data-marla') || '0');
        var qty = parseInt(qtyEl.value, 10) || 1;
        hintEl.textContent = 'Uses ' + (marla * qty).toFixed(3) + ' marla from pool.';
    }

    if (componentEl && plotEl) {
        componentEl.addEventListener('change', syncPlotOptions);
        plotEl.addEventListener('change', updateHint);
        if (qtyEl) qtyEl.addEventListener('input', updateHint);
        syncPlotOptions();
    }

    function bindSelectReset(selectEl, resetBtn) {
        if (!selectEl || !resetBtn) return;
        function sync() {
            var has = String(selectEl.value || '').trim() !== '';
            resetBtn.classList.toggle('d-none', !has);
            resetBtn.setAttribute('aria-hidden', has ? 'false' : 'true');
        }
        selectEl.addEventListener('change', sync);
        resetBtn.addEventListener('click', function () {
            selectEl.value = '';
            sync();
            selectEl.focus();
        });
        sync();
    }

    bindSelectReset(document.getElementById('direct_customer_id'), document.getElementById('direct_customer_reset'));
    bindSelectReset(document.getElementById('percentage_customer_id'), document.getElementById('percentage_customer_reset'));

    var exemptionConfig = @json($exemptionJson);
    var usedByComponent = @json($usedByComponent);
    var marlaPerAcreLand = @json($marlaPerAcreLand);
    var poolPercentInputs = document.querySelectorAll('.pool-percent-input');
    var fileAreaAcre = document.getElementById('file_area_acre');
    var fileAreaKanal = document.getElementById('file_area_kanal');
    var fileAreaMarla = document.getElementById('file_area_marla');
    var fileAreaSqft = document.getElementById('file_area_sqft');
    var fileAreaPreview = document.getElementById('file_area_preview_label');

    function marlaFromAkms(a, k, m, s) {
        return (parseInt(a, 10) || 0) * 160
            + (parseInt(k, 10) || 0) * 20
            + (parseInt(m, 10) || 0)
            + (parseInt(s, 10) || 0) / 272.25;
    }

    function formatAkmsLabel(totalMarla) {
        var eps = 1e-6;
        if (totalMarla <= eps) return 'A 0 — K 0 — M 0 — SQFT 0';
        var wholeMarla = Math.floor(totalMarla + eps);
        var a = Math.floor(wholeMarla / 160);
        var r = wholeMarla - a * 160;
        var k = Math.floor(r / 20);
        var m = r - k * 20;
        var frac = totalMarla - wholeMarla;
        if (frac < 0) frac = 0;
        var sqft = frac > eps ? Math.round(frac * 272.25) : 0;
        return 'A ' + a + ' — K ' + k + ' — M ' + m + ' — SQFT ' + sqft;
    }

    function refreshFileAreaPreview() {
        if (!fileAreaAcre) return;
        var total = marlaFromAkms(fileAreaAcre.value, fileAreaKanal.value, fileAreaMarla.value, fileAreaSqft.value);
        if (fileAreaPreview) {
            fileAreaPreview.innerHTML = 'Total: <strong>' + formatAkmsLabel(total) + '</strong>';
        }
        function poolPctForSlug(slug) {
            var inp = document.querySelector('.pool-percent-input[data-component-slug="' + slug + '"]');
            if (inp) {
                var n = parseFloat(inp.value);
                if (!isNaN(n)) return n;
            }
            var c = exemptionConfig.find(function (x) { return x.slug === slug; });
            return c ? c.pool_percent : 0;
        }

        document.querySelectorAll('.pool-card').forEach(function (card) {
            var slug = card.getAttribute('data-component-slug');
            var pct = poolPctForSlug(slug);
            var pool = total * pct / 100;
            var used = usedByComponent[slug] || 0;
            var pctEl = card.querySelector('.pool-pct-val');
            var totalEl = card.querySelector('.pool-total');
            var leftEl = card.querySelector('.pool-left');
            if (pctEl) pctEl.textContent = String(pct).replace(/\.?0+$/, '');
            if (totalEl) totalEl.textContent = formatAkmsLabel(pool);
            if (leftEl) leftEl.textContent = formatAkmsLabel(Math.max(0, pool - used));
        });
    }

    var refreshInputs = [fileAreaAcre, fileAreaKanal, fileAreaMarla, fileAreaSqft];
    poolPercentInputs.forEach(function (el) { refreshInputs.push(el); });
    refreshInputs.forEach(function (el) {
        if (el) el.addEventListener('input', refreshFileAreaPreview);
    });
    refreshFileAreaPreview();

    function formatMarlaVal(n) {
        var rounded = Math.round(n * 10000) / 10000;
        if (Math.abs(rounded - Math.round(rounded)) < 1e-9) {
            return String(Math.round(rounded));
        }
        return rounded.toFixed(4).replace(/\.?0+$/, '');
    }

    function formatMarlaM(n) {
        return formatMarlaVal(n) + 'M';
    }

    function formatNum(n) {
        return formatMarlaVal(n);
    }

    function buildCalculatorRows(totalMarla) {
        var mpa = marlaPerAcreLand;
        var acres = mpa > 0 ? totalMarla / mpa : 0;
        var tbody = document.getElementById('file_calculator_body');
        var acresLabel = document.getElementById('calc_acres_label');
        var totalLabel = document.getElementById('calc_total_label');
        if (acresLabel) acresLabel.textContent = formatNum(acres);
        if (totalLabel) totalLabel.textContent = formatAkmsLabel(totalMarla);
        if (!tbody) return;

        var html = '';
        var globalIndex = 0;
        exemptionConfig.forEach(function (comp) {
            var prefix = (comp.slug || 'x').charAt(0).toUpperCase();
            (comp.plot_types || []).forEach(function (plot) {
                globalIndex++;
                var marla = parseFloat(plot.marla) || 0;
                var nominal = parseFloat(plot.nominal_marla) || 0;
                var product = Math.round(marla * acres * 10000) / 10000;
                var files = nominal > 0 ? Math.round(product / nominal * 10000) / 10000 : 0;
                var fullFiles = Math.floor(files + 1e-9);
                var fractionFiles = Math.round((files - fullFiles) * 10000) / 10000;
                var fractionMarla = Math.round(fractionFiles * nominal * 10000) / 10000;
                var wholeMarla = Math.round(fullFiles * marla * 10000) / 10000;
                var totalLineMarla = Math.round((wholeMarla + fractionMarla) * 10000) / 10000;
                html += '<tr>' +
                    '<td class="fw-semibold">' + prefix + '.' + globalIndex + '</td>' +
                    '<td>' + plot.label + '</td>' +
                    '<td>' + formatNum(plot.share_percent) + '%</td>' +
                    '<td class="small font-monospace">' +
                        formatMarlaM(marla) + ' × ' + formatNum(acres) +
                        ' = ' + formatMarlaM(product) + ':' + formatMarlaM(nominal) +
                    '</td>' +
                    '<td class="text-end fw-semibold">' + formatNum(files) + '</td>' +
                    '<td class="text-end">' + fullFiles + '</td>' +
                    '<td class="text-end small">' + (fractionFiles > 0 ? formatNum(fractionFiles) : '—') + '</td>' +
                    '<td class="text-end small">' + (fractionMarla > 0 ? formatMarlaM(fractionMarla) : '—') + '</td>' +
                    '<td class="text-end small text-muted">' + formatMarlaM(product) + '</td>' +
                '</tr>';
            });
        });
        tbody.innerHTML = html || '<tr><td colspan="9" class="text-muted">Configure plot types in project exemption setup.</td></tr>';
    }

    function readCalcMarla() {
        var a = document.getElementById('calc_area_acre');
        var k = document.getElementById('calc_area_kanal');
        var m = document.getElementById('calc_area_marla');
        var s = document.getElementById('calc_area_sqft');
        if (!a) return 0;
        return marlaFromAkms(a.value, k ? k.value : 0, m ? m.value : 0, s ? s.value : 0);
    }

    function syncCalcFromFileAreaForm() {
        var pairs = [
            ['file_area_acre', 'calc_area_acre'],
            ['file_area_kanal', 'calc_area_kanal'],
            ['file_area_marla', 'calc_area_marla'],
            ['file_area_sqft', 'calc_area_sqft']
        ];
        pairs.forEach(function (p) {
            var src = document.getElementById(p[0]);
            var dst = document.getElementById(p[1]);
            if (src && dst) dst.value = src.value;
        });
        buildCalculatorRows(readCalcMarla());
    }

    document.querySelectorAll('.calc-area-input').forEach(function (el) {
        el.addEventListener('input', function () {
            buildCalculatorRows(readCalcMarla());
        });
    });

    [fileAreaAcre, fileAreaKanal, fileAreaMarla, fileAreaSqft].forEach(function (el) {
        if (el) el.addEventListener('input', syncCalcFromFileAreaForm);
    });

    buildCalculatorRows(readCalcMarla());
})();
</script>
@endpush
