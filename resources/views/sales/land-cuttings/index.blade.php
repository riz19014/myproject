@extends('layouts.app')

@section('title', 'Land cutting — Sale #'.$sale->id)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Land cutting</h1>
        <div class="text-muted small">
            Sale #{{ $sale->id }}
            @if($sale->project)
                · Project: <strong><x-project-name :project="$sale->project" /></strong>
            @endif
        </div>
    </div>
    <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Back to Sale</a>
</div>

@php
    $saleMarla = (float) $sale->land_area_marla;
    $cutMarla = $sale->totalCuttingsMarla();
    $netMarla = $sale->netSaleableMarla();
@endphp

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Sale total land</div>
                <div class="fs-5 fw-bold">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($saleMarla) }}</div>
                <div class="text-muted small mt-1">{{ rtrim(rtrim(number_format($saleMarla, 4, '.', ''), '0'), '.') ?: '0' }} marla</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Cuttings total</div>
                <div class="fs-5 fw-bold">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($cutMarla) }}</div>
                <div class="text-muted small mt-1">{{ rtrim(rtrim(number_format($cutMarla, 4, '.', ''), '0'), '.') ?: '0' }} marla</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card card-theme h-100 border {{ $netMarla < 0 ? 'border-danger' : 'border-success' }} border-opacity-25">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Net saleable</div>
                <div class="fs-5 fw-bold {{ $netMarla < 0 ? 'text-danger' : 'text-success' }}">
                    @if($netMarla < 0)
                        −{{ number_format(abs($netMarla), 2) }} marla
                    @else
                        {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($netMarla) }}
                    @endif
                </div>
                <div class="text-muted small mt-1">{{ rtrim(rtrim(number_format($netMarla, 4, '.', ''), '0'), '.') ?: '0' }} marla</div>
                @if($netMarla < 0)
                    <p class="text-danger small mb-0 mt-2">Cuttings exceed sale land; adjust cuttings or the sale record.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Cutting types</h2>
        <p class="text-muted small mb-2">Add one row per cutting. Choose the type, then enter land in <strong>Acre, Kanal, Marla, Sq ft</strong> (whole numbers).</p>
        <ul class="small text-muted mb-0 ps-3">
            @foreach(\App\Models\LandCutting::TYPES as $key => $label)
                <li><strong>{{ $label }}</strong> <span class="text-muted">({{ $key }})</span></li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-theme">
            <div class="card-body">
                <h2 class="h5 mb-3">Add cutting</h2>
                <form action="{{ route('sale.records.land-cuttings.store', $sale) }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="cutting_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="cutting_type" id="cutting_type" class="form-select form-select-theme @error('cutting_type') is-invalid @enderror" required>
                            <option value="" disabled @selected(old('cutting_type') === null || old('cutting_type') === '')>— Select type —</option>
                            @foreach(\App\Models\LandCutting::TYPES as $key => $label)
                                <option value="{{ $key }}" @selected(old('cutting_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('cutting_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="area_acre" class="form-label">Acre</label>
                            <input type="number" class="form-control form-control-theme @error('area_acre') is-invalid @enderror" id="area_acre" name="area_acre" value="{{ old('area_acre', 0) }}" min="0" step="1" required>
                            @error('area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="area_kanal" class="form-label">Kanal</label>
                            <input type="number" class="form-control form-control-theme @error('area_kanal') is-invalid @enderror" id="area_kanal" name="area_kanal" value="{{ old('area_kanal', 0) }}" min="0" step="1" required>
                            @error('area_kanal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="area_marla" class="form-label">Marla</label>
                            <input type="number" class="form-control form-control-theme @error('area_marla') is-invalid @enderror" id="area_marla" name="area_marla" value="{{ old('area_marla', 0) }}" min="0" step="1" required>
                            @error('area_marla')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label for="area_sqft" class="form-label">Sq ft</label>
                            <input type="number" class="form-control form-control-theme @error('area_sqft') is-invalid @enderror" id="area_sqft" name="area_sqft" value="{{ old('area_sqft', 0) }}" min="0" step="1" required>
                            @error('area_sqft')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <button type="submit" class="btn btn-pink">Save cutting</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-theme">
            <div class="card-body">
                <h2 class="h5 mb-3">Cuttings for this sale</h2>
                @if($sale->landCuttings->isEmpty())
                    <p class="text-muted small mb-0">No cuttings yet. Use the form to add each type and area.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-striped table-theme mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Land area</th>
                                    <th class="text-end">Marla</th>
                                    <th style="width: 100px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->landCuttings->sortBy('id') as $lc)
                                    <tr>
                                        <td class="fw-medium">{{ $lc->typeLabel() }}</td>
                                        <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $lc->land_area_marla) }}</td>
                                        <td class="text-end small text-muted">{{ rtrim(rtrim(number_format((float) $lc->land_area_marla, 4, '.', ''), '0'), '.') ?: '0' }}</td>
                                        <td class="text-end">
                                            <form action="{{ route('sale.records.land-cuttings.destroy', [$sale, $lc]) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-theme btn-delete-confirm" data-title="Remove cutting?" data-text="Remove this {{ $lc->typeLabel() }} cutting?">Remove</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
