@extends('layouts.app')

@section('title', 'Edit purchase line #'.$item->id)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Edit purchase line</h1>
        <div class="text-muted small">Line <strong>#{{ $item->id }}</strong> · Project: <strong>{{ $project->name }}</strong></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase.records.create', ['project' => $project->id]) }}" class="btn btn-outline-theme">Add more lines for this project</a>
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-theme">Back to Purchase</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($parties->isEmpty())
            <div class="alert alert-theme-danger py-2 small">No parties yet. Add parties first.</div>
        @endif

        <form method="post" action="{{ route('purchase.records.update', $item) }}" id="purchase-edit-form">
            @csrf
            @method('PUT')

            <div class="border rounded p-3 mb-4 bg-body-secondary bg-opacity-10">
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 col-lg-4">
                        <label for="party_id" class="form-label">Party <span class="text-danger">*</span></label>
                        <select class="form-select form-select-theme @error('party_id') is-invalid @enderror" id="party_id" name="party_id" required @if($parties->isEmpty()) disabled @endif>
                            <option value="" @if((int) old('party_id', $item->party_id) === 0) selected @endif disabled>— Select party —</option>
                            @foreach($parties as $party)
                                <option value="{{ $party->id }}" @selected((int) old('party_id', $item->party_id) === (int) $party->id)>{{ $party->name }}</option>
                            @endforeach
                        </select>
                        @error('party_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="js-party-name-display small text-muted mt-1">{{ $parties->firstWhere('id', (int) old('party_id', $item->party_id))?->name ?? 'Party name appears here when you select a party…' }}</div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="moza" class="form-label">Moza</label>
                        <input type="text" class="form-control form-control-theme @error('moza') is-invalid @enderror" id="moza" name="moza" value="{{ old('moza', $item->moza) }}" maxlength="255" autocomplete="off">
                        @error('moza')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <label for="khasra" class="form-label">Khasra #</label>
                        <input type="text" class="form-control form-control-theme @error('khasra') is-invalid @enderror" id="khasra" name="khasra" value="{{ old('khasra', $item->khasra) }}" maxlength="255" autocomplete="off">
                        @error('khasra')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="mt-3">
                    <div class="fw-semibold small mb-2">Area <span class="text-danger">*</span> <span class="text-muted fw-normal">(whole numbers)</span></div>
                    <div class="row g-3">
                        <div class="col-6 col-md-3">
                            <label for="area_acre" class="form-label">Acre</label>
                            <input type="number" class="form-control form-control-theme @error('area_acre') is-invalid @enderror" id="area_acre" name="area_acre" value="{{ old('area_acre', $item->area_acre) }}" min="0" step="1" required>
                            @error('area_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="area_kanal" class="form-label">Kanal</label>
                            <input type="number" class="form-control form-control-theme @error('area_kanal') is-invalid @enderror" id="area_kanal" name="area_kanal" value="{{ old('area_kanal', $item->area_kanal) }}" min="0" step="1" required>
                            @error('area_kanal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="area_marla" class="form-label">Marla</label>
                            <input type="number" class="form-control form-control-theme @error('area_marla') is-invalid @enderror" id="area_marla" name="area_marla" value="{{ old('area_marla', $item->area_marla) }}" min="0" step="1" required>
                            @error('area_marla')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="area_sqft" class="form-label">Sq ft</label>
                            <input type="number" class="form-control form-control-theme @error('area_sqft') is-invalid @enderror" id="area_sqft" name="area_sqft" value="{{ old('area_sqft', $item->area_sqft) }}" min="0" step="1" required>
                            @error('area_sqft')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="amount_per_acre" class="form-label">Amount per acre (Rs) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-theme @error('amount_per_acre') is-invalid @enderror" id="amount_per_acre" name="amount_per_acre" value="{{ old('amount_per_acre', $item->amount_per_acre) }}" min="0" step="0.01" required placeholder="0">
                            @error('amount_per_acre')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-pink" @if($parties->isEmpty()) disabled @endif>Save changes</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function() {
    var sel = document.getElementById('party_id');
    var disp = document.querySelector('.js-party-name-display');
    if (!sel || !disp) return;
    function sync() {
        var opt = sel.options[sel.selectedIndex];
        var name = opt && opt.value ? opt.text : '';
        disp.textContent = name || 'Party name appears here when you select a party…';
    }
    sel.addEventListener('change', sync);
})();
</script>
@endpush
