@extends('layouts.app')

@section('title', 'Add Project')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add Project</h1>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. DHA Land" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="land_area" class="form-label">Land area</label>
                    <input type="number" step="0.0001" min="0" class="form-control form-control-theme @error('land_area') is-invalid @enderror" id="land_area" name="land_area" value="{{ old('land_area') }}" placeholder="0">
                    @error('land_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="land_area_unit" class="form-label">Unit</label>
                    <select name="land_area_unit" id="land_area_unit" class="form-select form-select-theme @error('land_area_unit') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach (['acre' => 'Acre', 'kanal' => 'Kanal', 'marla' => 'Marla', 'sqft' => 'Sq ft'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('land_area_unit') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('land_area_unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="field_type" class="form-label">Field type</label>
                    <select name="field_type" id="field_type" class="form-select form-select-theme @error('field_type') is-invalid @enderror">
                        <option value="">—</option>
                        <option value="sale" @selected(old('field_type') === 'sale')>Sale</option>
                        <option value="purchase" @selected(old('field_type') === 'purchase')>Purchase</option>
                    </select>
                    @error('field_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label for="land_type_id" class="form-label">Land type</label>
                <select name="land_type_id" id="land_type_id" class="form-select form-select-theme @error('land_type_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($landTypes as $lt)
                        <option value="{{ $lt->id }}" @selected(old('land_type_id') == $lt->id)>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @error('land_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control form-control-theme @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-pink">Create Project</button>
        </form>
    </div>
</div>
@endsection
