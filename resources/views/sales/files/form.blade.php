@extends('layouts.app')

@section('title', 'Add file — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Add file</h1>
        <p class="text-muted small mb-0">Project: <strong>{{ $project->name }}</strong></p>
    </div>
    <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme">Back to files</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form method="post" action="{{ route('sale.files.store', $project) }}">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="file_number" class="form-label">File name / number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme @error('file_number') is-invalid @enderror" id="file_number" name="file_number" value="{{ old('file_number') }}" required placeholder="e.g. Block A File 1">
                    @error('file_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="dealer_party_id" class="form-label">Dealer (optional)</label>
                    <select class="form-select form-control-theme @error('dealer_party_id') is-invalid @enderror" id="dealer_party_id" name="dealer_party_id">
                        <option value="">—</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}" @selected((int) old('dealer_party_id') === $party->id)>{{ $party->name }}</option>
                        @endforeach
                    </select>
                    @error('dealer_party_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.08em;">File total land</h2>
            <p class="text-muted small mb-3">e.g. 30 kanal for the whole file — used for direct sales and percentage pools.</p>
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

            <div class="mb-4">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="btn btn-pink">Save file</button>
        </form>
    </div>
</div>
@endsection
