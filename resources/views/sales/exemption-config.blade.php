@extends('layouts.app')

@section('title', 'Exemption setup — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Percentage sale exemption setup</h1>
        <p class="text-muted small mb-0">Project: <strong>{{ $project->name }}</strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme">Back to files</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

<div class="card card-theme">
    <div class="card-body">
        <p class="text-muted small mb-4">
            Configure allocation categories and plot file ratios for this project.
            <strong>1 Acre = <span id="marla_per_acre_display">{{ rtrim(rtrim(number_format((float) $project->marla_per_acre, 4, '.', ''), '0'), '.') ?: '160' }}</span> Marla</strong> (editable below).
            Pool size = file land × pool %. Plot marla is deducted from the matching pool when you record a percentage sale.
        </p>

        <form method="post" action="{{ route('sale.projects.exemption.update', $project) }}" id="exemption-config-form">
            @csrf
            @method('PUT')

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label for="marla_per_acre" class="form-label">Marla per acre</label>
                    <input type="number" class="form-control form-control-theme @error('marla_per_acre') is-invalid @enderror" id="marla_per_acre" name="marla_per_acre" value="{{ old('marla_per_acre', $project->marla_per_acre ?? 160) }}" min="1" step="0.0001" required>
                    @error('marla_per_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div id="exemption-components">
                @foreach(old('components', $project->saleExemptionComponents) as $ci => $component)
                    @php
                        $compId = is_object($component) ? $component->id : ($component['id'] ?? null);
                        $compSlug = is_object($component) ? $component->slug : ($component['slug'] ?? '');
                        $compLabel = is_object($component) ? $component->label : ($component['label'] ?? '');
                        $compPool = is_object($component) ? $component->pool_percent : ($component['pool_percent'] ?? 0);
                        $plots = is_object($component) ? $component->plotTypes : ($component['plot_types'] ?? []);
                    @endphp
                    <div class="border rounded p-3 mb-3 exemption-component-block" data-index="{{ $ci }}">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h6 mb-0">Allocation category</h2>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-component-btn">Remove category</button>
                        </div>
                        <input type="hidden" name="components[{{ $ci }}][id]" value="{{ old('components.'.$ci.'.id', $compId) }}">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Code</label>
                                <input type="text" class="form-control form-control-theme" name="components[{{ $ci }}][slug]" value="{{ old('components.'.$ci.'.slug', $compSlug) }}" pattern="[a-z0-9_]+" required placeholder="residential">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Label</label>
                                <input type="text" class="form-control form-control-theme" name="components[{{ $ci }}][label]" value="{{ old('components.'.$ci.'.label', $compLabel) }}" required placeholder="Residential">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Pool % of file land</label>
                                <input type="number" class="form-control form-control-theme component-pool-pct" name="components[{{ $ci }}][pool_percent]" value="{{ old('components.'.$ci.'.pool_percent', $compPool) }}" min="0" max="100" step="0.0001" required>
                            </div>
                        </div>
                        <p class="text-muted small component-dist-hint mb-2"></p>

                        <div class="table-responsive">
                            <table class="table table-sm table-theme mb-2">
                                <thead>
                                    <tr>
                                        <th>Plot code</th>
                                        <th>Label</th>
                                        <th>Marla per plot</th>
                                        <th>Nominal marla (÷)</th>
                                        <th>Share % of dist./acre</th>
                                        <th style="width:48px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="plot-types-body">
                                    @foreach(old('components.'.$ci.'.plot_types', $plots) as $pi => $plot)
                                        @php
                                            $plotId = is_object($plot) ? $plot->id : ($plot['id'] ?? null);
                                            $plotSlug = is_object($plot) ? $plot->slug : ($plot['slug'] ?? '');
                                            $plotLabel = is_object($plot) ? $plot->label : ($plot['label'] ?? '');
                                            $plotMarla = is_object($plot) ? $plot->marla_per_plot : ($plot['marla_per_plot'] ?? 0);
                                            $plotNominal = is_object($plot) ? $plot->nominal_marla : ($plot['nominal_marla'] ?? \App\Support\SaleExemptionFileCalculator::nominalMarlaForSlug($plotSlug, $plotMarla));
                                            $plotShare = is_object($plot) ? $plot->share_percent : ($plot['share_percent'] ?? 0);
                                        @endphp
                                        <tr class="plot-type-row">
                                            <td>
                                                <input type="hidden" name="components[{{ $ci }}][plot_types][{{ $pi }}][id]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.id', $plotId) }}">
                                                <input type="text" class="form-control form-control-theme form-control-sm" name="components[{{ $ci }}][plot_types][{{ $pi }}][slug]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.slug', $plotSlug) }}" required>
                                            </td>
                                            <td><input type="text" class="form-control form-control-theme form-control-sm" name="components[{{ $ci }}][plot_types][{{ $pi }}][label]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.label', $plotLabel) }}" required></td>
                                            <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[{{ $ci }}][plot_types][{{ $pi }}][marla_per_plot]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.marla_per_plot', $plotMarla) }}" min="0" step="0.0001" required></td>
                                            <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[{{ $ci }}][plot_types][{{ $pi }}][nominal_marla]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.nominal_marla', $plotNominal) }}" min="0.0001" step="0.0001" required title="Divisor for file count (2K=40, 1K=20, 10M=10, 8M=8)"></td>
                                            <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[{{ $ci }}][plot_types][{{ $pi }}][share_percent]" value="{{ old('components.'.$ci.'.plot_types.'.$pi.'.share_percent', $plotShare) }}" min="0" max="100" step="0.0001" required></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-plot-btn">&times;</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-theme add-plot-btn">Add plot type</button>
                    </div>
                @endforeach
            </div>

            <button type="button" class="btn btn-sm btn-outline-theme mb-4" id="add-component-btn">Add allocation category</button>

            <button type="submit" class="btn btn-pink">Save exemption configuration</button>
        </form>
    </div>
</div>

<template id="component-block-template">
    <div class="border rounded p-3 mb-3 exemption-component-block" data-index="__CI__">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">Allocation category</h2>
            <button type="button" class="btn btn-sm btn-outline-danger remove-component-btn">Remove category</button>
        </div>
        <input type="hidden" name="components[__CI__][id]" value="">
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <label class="form-label">Code</label>
                <input type="text" class="form-control form-control-theme" name="components[__CI__][slug]" pattern="[a-z0-9_]+" required placeholder="residential">
            </div>
            <div class="col-md-5">
                <label class="form-label">Label</label>
                <input type="text" class="form-control form-control-theme" name="components[__CI__][label]" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Pool % of file land</label>
                <input type="number" class="form-control form-control-theme component-pool-pct" name="components[__CI__][pool_percent]" min="0" max="100" step="0.0001" required>
            </div>
        </div>
        <p class="text-muted small component-dist-hint mb-2"></p>
        <div class="table-responsive">
            <table class="table table-sm table-theme mb-2">
                <thead>
                    <tr>
                        <th>Plot code</th>
                        <th>Label</th>
                        <th>Marla per plot</th>
                        <th>Nominal marla (÷)</th>
                        <th>Share % of dist./acre</th>
                        <th style="width:48px;"></th>
                    </tr>
                </thead>
                <tbody class="plot-types-body"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-outline-theme add-plot-btn">Add plot type</button>
    </div>
</template>

<template id="plot-row-template">
    <tr class="plot-type-row">
        <td>
            <input type="hidden" name="components[__CI__][plot_types][__PI__][id]" value="">
            <input type="text" class="form-control form-control-theme form-control-sm" name="components[__CI__][plot_types][__PI__][slug]" required>
        </td>
        <td><input type="text" class="form-control form-control-theme form-control-sm" name="components[__CI__][plot_types][__PI__][label]" required></td>
        <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[__CI__][plot_types][__PI__][marla_per_plot]" min="0" step="0.0001" required></td>
        <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[__CI__][plot_types][__PI__][nominal_marla]" min="0.0001" step="0.0001" required></td>
        <td><input type="number" class="form-control form-control-theme form-control-sm" name="components[__CI__][plot_types][__PI__][share_percent]" min="0" max="100" step="0.0001" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-plot-btn">&times;</button></td>
    </tr>
</template>
@endsection

@push('scripts')
<script>
(function () {
    var container = document.getElementById('exemption-components');
    var marlaPerAcreEl = document.getElementById('marla_per_acre');
    var marlaDisplay = document.getElementById('marla_per_acre_display');
    var componentTpl = document.getElementById('component-block-template');
    var plotTpl = document.getElementById('plot-row-template');
    if (!container) return;

    function readMarlaPerAcre() {
        return parseFloat(marlaPerAcreEl && marlaPerAcreEl.value) || 160;
    }

    function updateDistHint(block) {
        var pct = parseFloat(block.querySelector('.component-pool-pct')?.value) || 0;
        var mpa = readMarlaPerAcre();
        var dist = (pct / 100 * mpa).toFixed(4).replace(/\.?0+$/, '');
        var hint = block.querySelector('.component-dist-hint');
        if (hint) {
            hint.textContent = 'Distribution base: ' + dist + ' marla per acre (' + pct + '% of ' + mpa + ' marla/acre).';
        }
    }

    function reindexComponents() {
        container.querySelectorAll('.exemption-component-block').forEach(function (block, ci) {
            block.dataset.index = String(ci);
            block.querySelectorAll('[name^="components["]').forEach(function (el) {
                el.name = el.name.replace(/components\[\d+\]/, 'components[' + ci + ']');
            });
            block.querySelectorAll('.plot-type-row').forEach(function (row, pi) {
                row.querySelectorAll('[name*="[plot_types]["]').forEach(function (el) {
                    el.name = el.name.replace(/\[plot_types\]\[\d+\]/, '[plot_types][' + pi + ']');
                });
            });
            updateDistHint(block);
        });
    }

    function addPlotRow(block) {
        var ci = block.dataset.index;
        var tbody = block.querySelector('.plot-types-body');
        var pi = tbody.querySelectorAll('.plot-type-row').length;
        var html = plotTpl.innerHTML.replace(/__CI__/g, ci).replace(/__PI__/g, pi);
        tbody.insertAdjacentHTML('beforeend', html);
    }

    document.getElementById('add-component-btn')?.addEventListener('click', function () {
        var ci = container.querySelectorAll('.exemption-component-block').length;
        var html = componentTpl.innerHTML.replace(/__CI__/g, String(ci));
        container.insertAdjacentHTML('beforeend', html);
        var block = container.lastElementChild;
        addPlotRow(block);
        reindexComponents();
    });

    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('add-plot-btn')) {
            addPlotRow(e.target.closest('.exemption-component-block'));
            reindexComponents();
        }
        if (e.target.classList.contains('remove-plot-btn')) {
            e.target.closest('.plot-type-row')?.remove();
            reindexComponents();
        }
        if (e.target.classList.contains('remove-component-btn')) {
            if (container.querySelectorAll('.exemption-component-block').length < 2) {
                alert('At least one allocation category is required.');
                return;
            }
            e.target.closest('.exemption-component-block')?.remove();
            reindexComponents();
        }
    });

    container.addEventListener('input', function (e) {
        if (e.target.classList.contains('component-pool-pct')) {
            updateDistHint(e.target.closest('.exemption-component-block'));
        }
    });

    marlaPerAcreEl?.addEventListener('input', function () {
        if (marlaDisplay) marlaDisplay.textContent = readMarlaPerAcre();
        container.querySelectorAll('.exemption-component-block').forEach(updateDistHint);
    });

    container.querySelectorAll('.exemption-component-block').forEach(function (block) {
        if (!block.querySelector('.plot-type-row')) addPlotRow(block);
        updateDistHint(block);
    });
})();
</script>
@endpush
