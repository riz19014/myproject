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
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 sale-pct-no-print">
    <div>
        <h1 class="mb-1">Sale</h1>
        <p class="text-muted small mb-0">
            Project: <strong><x-project-name :project="$project" /></strong>
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
<div class="card card-theme mb-3 sale-pct-no-print">
    <div class="card-body py-3">
        <h2 class="h6 mb-2">Sale type</h2>
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
<div class="{{ $activeType === Sale::TYPE_PERCENTAGE ? '' : 'd-none' }}" id="percentage_pools_card">
    <form method="post" action="{{ route('sale.files.area.update', $projectFile) }}" id="file-area-form">
        @csrf
        @method('PUT')

        {{-- Section 1: Area & land rate --}}
        <div class="sale-pct-section card card-theme mb-3">
            <div class="sale-pct-section__head">
                <div class="sale-pct-section__title-wrap">
                    <span class="sale-pct-section__badge">1</span>
                    <div>
                        <h2 class="sale-pct-section__title">Land area &amp; rate</h2>
                        <p class="sale-pct-section__subtitle mb-0">File area and Rs/acre for land value estimate.</p>
                    </div>
                </div>
            </div>
            <div class="card-body sale-pct-section__body pt-0">
                <div class="row g-2 align-items-end">
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="file_area_acre" class="form-label sale-pct-label">Acre</label>
                        <input type="number" class="form-control form-control-sm form-control-theme file-area-input @error('file_area_acre') is-invalid @enderror" id="file_area_acre" name="file_area_acre" value="{{ old('file_area_acre', $projectFile->area_acre ?? 0) }}" min="0" step="1" required>
                        @error('file_area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="file_area_kanal" class="form-label sale-pct-label">Kanal</label>
                        <input type="number" class="form-control form-control-sm form-control-theme file-area-input @error('file_area_kanal') is-invalid @enderror" id="file_area_kanal" name="file_area_kanal" value="{{ old('file_area_kanal', $projectFile->area_kanal ?? 0) }}" min="0" step="1" required>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="file_area_marla" class="form-label sale-pct-label">Marla</label>
                        <input type="number" class="form-control form-control-sm form-control-theme file-area-input @error('file_area_marla') is-invalid @enderror" id="file_area_marla" name="file_area_marla" value="{{ old('file_area_marla', $projectFile->area_marla ?? 0) }}" min="0" step="1" required>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="file_area_sqft" class="form-label sale-pct-label">Sq ft</label>
                        <input type="number" class="form-control form-control-sm form-control-theme file-area-input @error('file_area_sqft') is-invalid @enderror" id="file_area_sqft" name="file_area_sqft" value="{{ old('file_area_sqft', $projectFile->area_sqft ?? 0) }}" min="0" step="1" required>
                    </div>
                    <div class="col-12 col-lg-4">
                        <label for="sale_amount_per_acre" class="form-label sale-pct-label">Rs per acre</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">Rs</span>
                            <input type="number"
                                   class="form-control form-control-theme @error('sale_amount_per_acre') is-invalid @enderror"
                                   id="sale_amount_per_acre"
                                   name="sale_amount_per_acre"
                                   value="{{ old('sale_amount_per_acre', $saleAmountPerAcre ?? '') }}"
                                   min="0"
                                   step="0.01"
                                   placeholder="0"
                                   inputmode="decimal">
                        </div>
                        @error('sale_amount_per_acre')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="plot-rate-card__words-hint {{ ($saleAmountPerAcre ?? '') !== '' && ($saleAmountPerAcre ?? null) !== null ? '' : 'd-none' }}" id="sale_amount_per_acre_words"></div>
                    </div>
                </div>
                <div class="sale-pct-meta mt-2" id="file_area_preview_label">
                    Total: <strong>{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</strong>
                    · Acres: <strong id="file_acres_preview">{{ SaleExemptionFileCalculator::formatFileCount($fileCalculator['acres'] ?? 0) }}</strong>
                </div>
            </div>
        </div>

        {{-- Section 2: Pool % & per-file rates --}}
        <div class="sale-pct-section card card-theme mb-3">
            <div class="sale-pct-section__head">
                <div class="sale-pct-section__title-wrap">
                    <span class="sale-pct-section__badge">2</span>
                    <div>
                        <h2 class="sale-pct-section__title">Pools &amp; file rates</h2>
                        <p class="sale-pct-section__subtitle mb-0">Pool % for this file and Rs per plot file type.</p>
                    </div>
                </div>
            </div>
            <div class="card-body sale-pct-section__body pt-0">
                <div class="row g-2 mb-2">
                    @foreach($config->components() as $component)
                        <div class="col-6 col-md-3">
                            <label for="pool_percent_{{ $component->id }}" class="form-label sale-pct-label">{{ $component->label }} %</label>
                            <input type="number" class="form-control form-control-sm form-control-theme pool-percent-input @error('pool_percent.'.$component->id) is-invalid @enderror" id="pool_percent_{{ $component->id }}" name="pool_percent[{{ $component->id }}]" value="{{ old('pool_percent.'.$component->id, $config->poolPercent($component->slug)) }}" min="0" max="100" step="0.0001" data-component-slug="{{ $component->slug }}" required>
                            @error('pool_percent.'.$component->id)<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    @endforeach
                </div>
                @php
                    $plotRates = old('plot_rate_per_file', $plotRatesPerFile ?? []);
                    $residentialPlotIndex = 0;
                @endphp
                <div class="row g-2 plot-rates-grid">
                    @foreach($config->components() as $component)
                        @foreach($component->plotTypes as $plot)
                            @php
                                if ($component->slug === SaleExemptionRules::COMPONENT_RESIDENTIAL) {
                                    $residentialPlotIndex++;
                                    $saleLabel = 'R'.$residentialPlotIndex;
                                    $badgeClass = 'plot-rate-card__code--residential';
                                } else {
                                    $saleLabel = 'Commercial';
                                    $badgeClass = 'plot-rate-card__code--commercial';
                                }
                                $nominalMarla = SaleExemptionFileCalculator::nominalMarlaForPlot($plot);
                                $savedRate = old('plot_rate_per_file.'.$plot->slug, $plotRates[$plot->slug] ?? '');
                            @endphp
                            <div class="col-6 col-lg-3">
                                <div class="plot-rate-card plot-rate-card--compact">
                                    <div class="plot-rate-card__top">
                                        <span class="plot-rate-card__code {{ $badgeClass }}">{{ $saleLabel }}</span>
                                        <span class="plot-rate-card__nominal">{{ SaleExemptionFileCalculator::formatMarlaWithUnit($nominalMarla) }}</span>
                                    </div>
                                    <div class="input-group input-group-sm plot-rate-card__input-group">
                                        <span class="input-group-text">Rs</span>
                                        <input type="number"
                                               class="form-control form-control-theme plot-rate-input @error('plot_rate_per_file.'.$plot->slug) is-invalid @enderror"
                                               id="plot_rate_{{ $plot->slug }}"
                                               name="plot_rate_per_file[{{ $plot->slug }}]"
                                               value="{{ $savedRate }}"
                                               min="0"
                                               step="0.01"
                                               placeholder="Per file"
                                               inputmode="decimal"
                                               data-plot-slug="{{ $plot->slug }}"
                                               data-sale-label="{{ $saleLabel }}"
                                               aria-label="{{ $saleLabel }} rate per file">
                                    </div>
                                    @error('plot_rate_per_file.'.$plot->slug)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    <div class="plot-rate-card__words-hint {{ ($savedRate !== '' && $savedRate !== null) ? '' : 'd-none' }}" data-plot-rate-words="{{ $plot->slug }}"></div>
                                </div>
                            </div>
                        @endforeach
                    @endforeach
                </div>
                <div class="mt-2">
                    <button type="submit" class="btn btn-pink btn-sm">Save settings</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Section 3: Exemption pools --}}
    <div class="sale-pct-section card card-theme mb-3">
        <div class="sale-pct-section__head">
            <div class="sale-pct-section__title-wrap">
                <span class="sale-pct-section__badge">3</span>
                <div>
                    <h2 class="sale-pct-section__title">Exemption pools</h2>
                    <p class="sale-pct-section__subtitle mb-0">1 acre = {{ rtrim(rtrim(number_format($marlaPerAcreLand, 4, '.', ''), '0'), '.') }} marla · <a href="{{ route('sale.projects.exemption.edit', $project) }}">Edit project rules</a></p>
                </div>
            </div>
        </div>
        <div class="card-body sale-pct-section__body pt-0">
            @if($fileMarla <= 0)
                <div class="alert alert-warning small py-2 mb-2">Save land area above to calculate pools.</div>
            @endif
            <div class="row g-2" id="pool_cards_row">
                @foreach($config->components() as $component)
                    @php
                        $slug = $component->slug;
                        $poolMarla = $poolsByComponent[$slug] ?? 0;
                        $used = $usedByComponent[$slug] ?? 0;
                        $left = max(0, $poolMarla - $used);
                    @endphp
                    <div class="col-6 col-md-3 pool-card" data-component-slug="{{ $slug }}">
                        <div class="sale-pool-chip">
                            <div class="sale-pool-chip__label">{{ $component->label }}</div>
                            <div class="sale-pool-chip__value pool-total">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($poolMarla) }}</div>
                            <div class="sale-pool-chip__meta"><span class="pool-pct-val">{{ rtrim(rtrim(number_format($config->poolPercent($slug), 4, '.', ''), '0'), '.') }}</span>% · Left <span class="pool-left">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($left) }}</span></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Section 4: Estimation (printable) --}}
    <div class="sale-pct-section card card-theme mb-3" id="sale_estimation_section">
        <div class="sale-pct-section__head sale-pct-section__head--actions">
            <div class="sale-pct-section__title-wrap">
                <span class="sale-pct-section__badge">4</span>
                <div>
                    <h2 class="sale-pct-section__title">Sale estimation</h2>
                    <p class="sale-pct-section__subtitle mb-0">Plot files × rate · Land = acres × Rs/acre</p>
                </div>
            </div>
            <div class="sale-pct-section__actions">
                <a href="{{ route('sale.files.estimation.pdf', $projectFile) }}" class="btn btn-outline-theme btn-sm" target="_blank" rel="noopener">
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Download PDF
                </a>
                <span class="small text-muted">Save settings first for latest values.</span>
            </div>
        </div>
        <div class="card-body sale-pct-section__body pt-0" id="sale_estimation_print_area">
            <div class="sale-est-summary mb-2">
                <div class="sale-est-summary__item">
                    <span class="sale-est-summary__label">Area</span>
                    <span class="sale-est-summary__value" id="calc_total_label">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileMarla) }}</span>
                </div>
                <div class="sale-est-summary__item">
                    <span class="sale-est-summary__label">Acres</span>
                    <span class="sale-est-summary__value" id="calc_acres_label">{{ SaleExemptionFileCalculator::formatFileCount($fileCalculator['acres'] ?? 0) }}</span>
                </div>
                <div class="sale-est-summary__item">
                    <span class="sale-est-summary__label">Land value</span>
                    <span class="sale-est-summary__value" id="calc_land_value_label">
                        @if($landValueEstimate !== null)
                            Rs {{ SaleExemptionFileCalculator::formatRs($landValueEstimate) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
                <div class="sale-est-summary__item sale-est-summary__item--accent">
                    <span class="sale-est-summary__label">Plot files total</span>
                    <span class="sale-est-summary__value" id="calc_total_sale_label">
                        @if(($fileCalculator['total_sale_amount'] ?? null) !== null)
                            Rs {{ SaleExemptionFileCalculator::formatRs($fileCalculator['total_sale_amount']) }}
                        @else
                            —
                        @endif
                    </span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-striped table-theme mb-0 sale-est-table" id="file_calculator_table">
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
                            <th class="text-end">Rs/file</th>
                            <th class="text-end">Line total</th>
                        </tr>
                    </thead>
                    <tbody id="file_calculator_body">
                        @foreach($fileCalculator['rows'] ?? [] as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['sale_code'] ?? $row['code'] }}</td>
                                <td class="small">{{ $row['plot_label'] }}</td>
                                <td class="small">{{ SaleExemptionFileCalculator::formatFileCount($row['share_percent']) }}%</td>
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
                                <td class="text-end small">{{ SaleExemptionFileCalculator::formatRs($row['amount_per_file'] ?? null) }}</td>
                                <td class="text-end fw-semibold">{{ SaleExemptionFileCalculator::formatRs($row['line_sale_amount'] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot id="calc_total_sale_foot_wrap" class="{{ ($fileCalculator['total_sale_amount'] ?? null) !== null || $landValueEstimate !== null ? '' : 'd-none' }}">
                        <tr class="table-light fw-semibold">
                            <td colspan="10" class="text-end">Plot files total</td>
                            <td class="text-end" id="calc_total_sale_footer">{{ SaleExemptionFileCalculator::formatRs($fileCalculator['total_sale_amount'] ?? null) }}</td>
                        </tr>
                        <tr class="table-light fw-semibold {{ $landValueEstimate !== null ? '' : 'd-none' }}" id="calc_land_value_foot_row">
                            <td colspan="10" class="text-end">Land value (acres × Rs/acre)</td>
                            <td class="text-end" id="calc_land_value_footer">
                                @if($landValueEstimate !== null)
                                    {{ SaleExemptionFileCalculator::formatRs($landValueEstimate) }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
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
<div class="card card-theme sale-pct-no-print">
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

@push('head')
<style>
    .sale-pct-section__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.65rem 1rem;
        border-bottom: 1px solid #eef0f2;
        background: linear-gradient(180deg, #fafbfc 0%, #fff 100%);
    }
    .sale-pct-section__head--actions {
        flex-wrap: wrap;
    }
    .sale-pct-section__title-wrap {
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
        min-width: 0;
    }
    .sale-pct-section__badge {
        flex-shrink: 0;
        width: 1.5rem;
        height: 1.5rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.72rem;
        font-weight: 700;
        color: #fff;
        background: #f97316;
        border-radius: 0.35rem;
    }
    .sale-pct-section__title {
        font-size: 0.875rem;
        font-weight: 600;
        margin: 0;
        color: #1a1a1a;
    }
    .sale-pct-section__subtitle {
        font-size: 0.72rem;
        color: #6c757d;
        line-height: 1.3;
    }
    .sale-pct-section__body {
        padding-top: 0.75rem !important;
        padding-bottom: 0.75rem !important;
    }
    .sale-pct-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.2rem;
    }
    .sale-pct-meta {
        font-size: 0.72rem;
        color: #6c757d;
    }
    .sale-pool-chip {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.45rem;
        padding: 0.45rem 0.55rem;
        height: 100%;
    }
    .sale-pool-chip__label {
        font-size: 0.68rem;
        font-weight: 600;
        color: #868e96;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .sale-pool-chip__value {
        font-size: 0.8rem;
        font-weight: 600;
        color: #212529;
        line-height: 1.25;
    }
    .sale-pool-chip__meta {
        font-size: 0.68rem;
        color: #868e96;
        margin-top: 0.1rem;
    }
    .sale-est-summary {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.45rem;
    }
    @media (min-width: 768px) {
        .sale-est-summary {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    .sale-est-summary__item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.45rem;
        padding: 0.45rem 0.55rem;
    }
    .sale-est-summary__item--accent {
        background: #fff7ed;
        border-color: #fed7aa;
    }
    .sale-est-summary__label {
        display: block;
        font-size: 0.65rem;
        font-weight: 600;
        color: #868e96;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .sale-est-summary__value {
        display: block;
        font-size: 0.82rem;
        font-weight: 600;
        color: #212529;
        line-height: 1.25;
    }
    .sale-est-table thead th {
        font-size: 0.72rem;
        white-space: nowrap;
    }
    .plot-rate-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 0.45rem;
        padding: 0.45rem 0.5rem;
        height: 100%;
    }
    .plot-rate-card--compact {
        padding: 0.4rem 0.45rem;
    }
    .plot-rate-card:focus-within {
        border-color: #fdba74;
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.1);
    }
    .plot-rate-card__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.35rem;
        margin-bottom: 0.3rem;
    }
    .plot-rate-card__code {
        display: inline-block;
        font-size: 0.65rem;
        font-weight: 700;
        border-radius: 0.25rem;
        padding: 0.1rem 0.35rem;
        line-height: 1.2;
    }
    .plot-rate-card__code--residential {
        color: #1d4ed8;
        background: #eff6ff;
        border: 1px solid #bfdbfe;
    }
    .plot-rate-card__code--commercial {
        color: #7c3aed;
        background: #f5f3ff;
        border: 1px solid #ddd6fe;
    }
    .plot-rate-card__nominal {
        font-size: 0.65rem;
        color: #868e96;
    }
    .plot-rate-card__input-group .input-group-text {
        font-size: 0.72rem;
        padding: 0.2rem 0.45rem;
    }
    .plot-rate-card__input-group .form-control {
        font-size: 0.78rem;
    }
    .plot-rate-card__words-hint {
        font-size: 0.68rem;
        font-weight: 600;
        color: #f97316;
        line-height: 1.2;
        margin-top: 0.1rem;
    }
    .calc-rs-words {
        font-size: 0.65rem;
        font-weight: 500;
        color: #868e96;
        line-height: 1.15;
    }
    .calc-total-words {
        font-weight: 500;
        color: #868e96;
        font-size: 0.75em;
    }
    .sale-pct-section__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
    }
</style>
@endpush

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
        var acres = marlaPerAcreLand > 0 ? total / marlaPerAcreLand : 0;
        if (fileAreaPreview) {
            fileAreaPreview.innerHTML = 'Total: <strong>' + formatAkmsLabel(total) + '</strong> · Acres: <strong>' + formatNum(acres) + '</strong>';
        }
        var acresPreview = document.getElementById('file_acres_preview');
        if (acresPreview) acresPreview.textContent = formatNum(acres);
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
        buildCalculatorRows(total);
    }

    function readPerAcreRate() {
        var el = document.getElementById('sale_amount_per_acre');
        if (!el) return 0;
        var rate = parseFloat(el.value);
        return !isNaN(rate) && rate > 0 ? rate : 0;
    }

    function updatePerAcreWords() {
        var wordsEl = document.getElementById('sale_amount_per_acre_words');
        var rate = readPerAcreRate();
        var words = rate > 0 ? formatRsWords(rate) : '';
        if (wordsEl) {
            wordsEl.textContent = words ? ('≈ ' + words) : '';
            wordsEl.classList.toggle('d-none', !words);
        }
    }

    var refreshInputs = [fileAreaAcre, fileAreaKanal, fileAreaMarla, fileAreaSqft];
    poolPercentInputs.forEach(function (el) { refreshInputs.push(el); });
    var saleAmountPerAcreEl = document.getElementById('sale_amount_per_acre');
    if (saleAmountPerAcreEl) refreshInputs.push(saleAmountPerAcreEl);
    refreshInputs.forEach(function (el) {
        if (el) el.addEventListener('input', refreshFileAreaPreview);
    });
    if (saleAmountPerAcreEl) {
        saleAmountPerAcreEl.addEventListener('input', updatePerAcreWords);
    }
    refreshFileAreaPreview();
    updatePerAcreWords();

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

    function formatRs(amount) {
        if (amount === null || amount === undefined || isNaN(amount)) {
            return '—';
        }
        return Math.round(amount).toLocaleString('en-PK');
    }

    function scaleAmountWords(x) {
        return (Math.round(x * 100) / 100).toFixed(2).replace(/\.?0+$/, '');
    }

    function formatRsWords(amount) {
        if (amount === null || amount === undefined || isNaN(amount) || amount <= 0) {
            return '';
        }
        var intPart = Math.round(amount);
        if (intPart >= 10000000) {
            return scaleAmountWords(intPart / 10000000) + ' crore';
        }
        if (intPart >= 100000) {
            return scaleAmountWords(intPart / 100000) + ' lac';
        }
        if (intPart >= 1000) {
            return scaleAmountWords(intPart / 1000) + ' thousand';
        }
        return '';
    }

    function formatRsCellHtml(amount) {
        if (amount === null || amount === undefined || isNaN(amount)) {
            return '—';
        }
        var words = formatRsWords(amount);
        var html = formatRs(amount);
        if (words) {
            html += '<div class="calc-rs-words">' + words + '</div>';
        }
        return html;
    }

    function readPlotRates() {
        var rates = {};
        document.querySelectorAll('.plot-rate-input').forEach(function (el) {
            var slug = el.getAttribute('data-plot-slug');
            var value = parseFloat(el.value);
            if (slug && !isNaN(value) && value > 0) {
                rates[slug] = value;
            }
        });
        return rates;
    }

    function plotSaleCode(componentSlug, residentialIndex) {
        if (componentSlug === 'commercial') {
            return 'Commercial';
        }
        return 'R' + residentialIndex;
    }

    function updatePlotRatePreviews() {
        document.querySelectorAll('.plot-rate-input').forEach(function (el) {
            var slug = el.getAttribute('data-plot-slug');
            var wordsEl = document.querySelector('[data-plot-rate-words="' + slug + '"]');
            var rate = parseFloat(el.value);
            var words = !isNaN(rate) && rate > 0 ? formatRsWords(rate) : '';

            if (wordsEl) {
                wordsEl.textContent = words ? ('≈ ' + words) : '';
                wordsEl.classList.toggle('d-none', !words);
            }
        });
    }

    function updateEstimationSummary(acres, totalMarla, totalSale, hasSale, landValue, hasLandValue) {
        var totalLabel = document.getElementById('calc_total_label');
        var acresLabel = document.getElementById('calc_acres_label');
        var landValueLabel = document.getElementById('calc_land_value_label');
        var totalSaleLabel = document.getElementById('calc_total_sale_label');
        var totalSaleFooter = document.getElementById('calc_total_sale_footer');
        var landValueFooter = document.getElementById('calc_land_value_footer');
        var totalSaleFootWrap = document.getElementById('calc_total_sale_foot_wrap');
        var landValueFootRow = document.getElementById('calc_land_value_foot_row');

        if (totalLabel) totalLabel.textContent = formatAkmsLabel(totalMarla);
        if (acresLabel) acresLabel.textContent = formatNum(acres);

        if (landValueLabel) {
            if (hasLandValue) {
                var landWords = formatRsWords(landValue);
                landValueLabel.innerHTML = 'Rs ' + formatRs(landValue) + (landWords ? ' <span class="calc-total-words">(' + landWords + ')</span>' : '');
            } else {
                landValueLabel.textContent = '—';
            }
        }
        if (landValueFooter) {
            landValueFooter.innerHTML = hasLandValue ? formatRsCellHtml(landValue) : '—';
        }
        if (landValueFootRow) {
            landValueFootRow.classList.toggle('d-none', !hasLandValue);
        }

        if (totalSaleLabel) {
            if (hasSale) {
                var totalWords = formatRsWords(totalSale);
                totalSaleLabel.innerHTML = 'Rs ' + formatRs(totalSale) + (totalWords ? ' <span class="calc-total-words">(' + totalWords + ')</span>' : '');
            } else {
                totalSaleLabel.textContent = '—';
            }
        }
        if (totalSaleFooter) {
            totalSaleFooter.innerHTML = hasSale ? formatRsCellHtml(totalSale) : '—';
        }
        if (totalSaleFootWrap) {
            totalSaleFootWrap.classList.toggle('d-none', !hasSale && !hasLandValue);
        }
    }

    function buildCalculatorRows(totalMarla) {
        var mpa = marlaPerAcreLand;
        var acres = mpa > 0 ? totalMarla / mpa : 0;
        var tbody = document.getElementById('file_calculator_body');
        var rates = readPlotRates();
        var perAcreRate = readPerAcreRate();
        var landValue = perAcreRate > 0 ? Math.round(acres * perAcreRate * 100) / 100 : null;
        var hasLandValue = landValue !== null && landValue > 0;
        if (!tbody) {
            updateEstimationSummary(acres, totalMarla, 0, false, landValue, hasLandValue);
            return;
        }

        var html = '';
        var globalIndex = 0;
        var residentialIndex = 0;
        var totalSale = 0;
        var hasSale = false;
        exemptionConfig.forEach(function (comp) {
            (comp.plot_types || []).forEach(function (plot) {
                globalIndex++;
                if (comp.slug === 'residential') {
                    residentialIndex++;
                }
                var saleCode = plotSaleCode(comp.slug, residentialIndex);
                var marla = parseFloat(plot.marla) || 0;
                var nominal = parseFloat(plot.nominal_marla) || 0;
                var product = Math.round(marla * acres * 10000) / 10000;
                var files = nominal > 0 ? Math.round(product / nominal * 10000) / 10000 : 0;
                var fullFiles = Math.floor(files + 1e-9);
                var fractionFiles = Math.round((files - fullFiles) * 10000) / 10000;
                var fractionMarla = Math.round(fractionFiles * nominal * 10000) / 10000;
                var rate = rates[plot.slug] || 0;
                var amountPerFile = rate > 0 ? Math.round(rate * 100) / 100 : null;
                var lineSale = amountPerFile !== null ? Math.round(amountPerFile * files * 100) / 100 : null;
                if (lineSale !== null) {
                    totalSale += lineSale;
                    hasSale = true;
                }
                html += '<tr>' +
                    '<td class="fw-semibold">' + saleCode + '</td>' +
                    '<td class="small">' + plot.label + '</td>' +
                    '<td class="small">' + formatNum(plot.share_percent) + '%</td>' +
                    '<td class="small font-monospace">' +
                        formatMarlaM(marla) + ' × ' + formatNum(acres) +
                        ' = ' + formatMarlaM(product) + ':' + formatMarlaM(nominal) +
                    '</td>' +
                    '<td class="text-end fw-semibold">' + formatNum(files) + '</td>' +
                    '<td class="text-end">' + fullFiles + '</td>' +
                    '<td class="text-end small">' + (fractionFiles > 0 ? formatNum(fractionFiles) : '—') + '</td>' +
                    '<td class="text-end small">' + (fractionMarla > 0 ? formatMarlaM(fractionMarla) : '—') + '</td>' +
                    '<td class="text-end small text-muted">' + formatMarlaM(product) + '</td>' +
                    '<td class="text-end small">' + formatRsCellHtml(amountPerFile) + '</td>' +
                    '<td class="text-end fw-semibold">' + formatRsCellHtml(lineSale) + '</td>' +
                '</tr>';
            });
        });
        tbody.innerHTML = html || '<tr><td colspan="11" class="text-muted small">Configure plot types in project exemption setup.</td></tr>';
        updateEstimationSummary(acres, totalMarla, totalSale, hasSale, landValue, hasLandValue);
    }

    function readFileMarla() {
        if (!fileAreaAcre) return 0;
        return marlaFromAkms(fileAreaAcre.value, fileAreaKanal ? fileAreaKanal.value : 0, fileAreaMarla ? fileAreaMarla.value : 0, fileAreaSqft ? fileAreaSqft.value : 0);
    }

    document.querySelectorAll('.plot-rate-input').forEach(function (el) {
        el.addEventListener('input', function () {
            updatePlotRatePreviews();
            buildCalculatorRows(readFileMarla());
        });
    });

    updatePlotRatePreviews();
    buildCalculatorRows(readFileMarla());
})();
</script>
@endpush
