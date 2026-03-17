@extends('layouts.app')

@section('title', 'Add Factory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add Factory</h1>
    <a href="{{ route('factories.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('factories.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Factory Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="purchase_cost" class="form-label">Purchase Cost</label>
                    <input type="number" step="0.01" class="form-control form-control-theme" id="purchase_cost" name="purchase_cost" value="{{ old('purchase_cost') }}" placeholder="0">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control form-control-theme" id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control form-control-theme" id="location" name="location" value="{{ old('location') }}">
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-pink">Add Factory</button>
        </form>
    </div>
</div>
@endsection
