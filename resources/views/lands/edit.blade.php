@extends('layouts.app')

@section('title', 'Edit Land')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Land</h1>
    <a href="{{ route('lands.show', $land) }}" class="btn btn-outline-theme">View</a>
    <a href="{{ route('lands.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('lands.update', $land) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Land Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme" id="name" name="name" value="{{ old('name', $land->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="total_area_kanal" class="form-label">Total Area (Kanal)</label>
                    <input type="number" step="0.01" class="form-control form-control-theme" id="total_area_kanal" name="total_area_kanal" value="{{ old('total_area_kanal', $land->total_area_kanal) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control form-control-theme" id="location" name="location" value="{{ old('location', $land->location) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="purchase_date" class="form-label">Purchase Date</label>
                    <input type="date" class="form-control form-control-theme" id="purchase_date" name="purchase_date" value="{{ old('purchase_date', $land->purchase_date?->format('Y-m-d')) }}">
                </div>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme" id="notes" name="notes" rows="2">{{ old('notes', $land->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-pink">Update Land</button>
        </form>
    </div>
</div>
@endsection
