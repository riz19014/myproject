@extends('layouts.app')

@section('title', 'Add sale — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Add sale</h1>
        <div class="text-muted small">Project: <strong><x-project-name :project="$project" /></strong></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.records.create') }}" class="btn btn-outline-theme">Change project</a>
        <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Back to Sale</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form method="post" action="{{ route('sale.records.store') }}">
            @csrf
            <input type="hidden" name="project_id" value="{{ $project->id }}">

            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Land area</h2>
            <p class="text-muted small mb-3">Whole numbers only (same rules as Daybook).</p>
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <label for="area_acre" class="form-label">Acre</label>
                    <input type="number" class="form-control form-control-theme @error('area_acre') is-invalid @enderror" id="area_acre" name="area_acre" value="{{ old('area_acre', 0) }}" min="0" step="1" required>
                    @error('area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="area_kanal" class="form-label">Kanal</label>
                    <input type="number" class="form-control form-control-theme @error('area_kanal') is-invalid @enderror" id="area_kanal" name="area_kanal" value="{{ old('area_kanal', 0) }}" min="0" step="1" required>
                    @error('area_kanal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="area_marla" class="form-label">Marla</label>
                    <input type="number" class="form-control form-control-theme @error('area_marla') is-invalid @enderror" id="area_marla" name="area_marla" value="{{ old('area_marla', 0) }}" min="0" step="1" required>
                    @error('area_marla')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-6 col-md-3">
                    <label for="area_sqft" class="form-label">Sq ft</label>
                    <input type="number" class="form-control form-control-theme @error('area_sqft') is-invalid @enderror" id="area_sqft" name="area_sqft" value="{{ old('area_sqft', 0) }}" min="0" step="1" required>
                    @error('area_sqft')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Parties &amp; buyers</h2>
            <p class="text-muted small mb-3">Select any dealers (<strong>Parties</strong>) and/or buyers (<strong>Customers</strong>). At least one is required.</p>

            @error('party_ids')
                <div class="alert alert-theme-danger py-2 small mb-3">{{ $message }}</div>
            @enderror

            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="fw-semibold small mb-2">Parties (dealers)</div>
                    <div class="border rounded-3 p-3 bg-body-secondary bg-opacity-25" style="max-height: 220px; overflow-y: auto;">
                        @forelse($parties as $party)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="party_ids[]" value="{{ $party->id }}" id="party_{{ $party->id }}"
                                    @checked(in_array($party->id, array_map('intval', (array) old('party_ids', [])), true))>
                                <label class="form-check-label small" for="party_{{ $party->id }}">{{ $party->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No parties yet. Add them under Parties.</p>
                        @endforelse
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="fw-semibold small mb-2">Customers (buyers)</div>
                    <div class="border rounded-3 p-3 bg-body-secondary bg-opacity-25" style="max-height: 220px; overflow-y: auto;">
                        @forelse($customers as $customer)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="customer_ids[]" value="{{ $customer->id }}" id="customer_{{ $customer->id }}"
                                    @checked(in_array($customer->id, array_map('intval', (array) old('customer_ids', [])), true))>
                                <label class="form-check-label small" for="customer_{{ $customer->id }}">{{ $customer->name }}</label>
                            </div>
                        @empty
                            <p class="text-muted small mb-0">No customers yet. Add them under Customers.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.08em;">Total amount</h2>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-theme @error('total_amount') is-invalid @enderror" id="total_amount" name="total_amount" value="{{ old('total_amount') }}" min="0" step="0.01" required placeholder="0">
                    @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <button type="submit" class="btn btn-pink">Save sale</button>
        </form>
    </div>
</div>
@endsection
