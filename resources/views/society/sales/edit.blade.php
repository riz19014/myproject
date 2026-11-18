@extends('layouts.app')

@section('title', 'Edit sales file — '.($booking->plot?->plot_number ?? '#'.$booking->id))

@section('content')
@include('society.partials.nav')

@php
    $plot = $booking->plot;
    $fileNo = $plot?->plot_number ?? ('#'.$booking->id);
    $buyer = $booking->party?->name ?: ($booking->customer?->name ?? '—');
@endphp

<div class="mb-4">
    <h1 class="h3 mb-1">Edit sales file {{ $fileNo }}</h1>
    <p class="text-muted mb-0">Buyer: {{ $buyer }} (change party from Daybook if needed)</p>
</div>

<div class="card card-theme" style="max-width:40rem">
    <div class="card-body">
        <form method="post" action="{{ route('society.sales.update', [$project, $booking]) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Sale date</label>
                <input type="date" name="booking_date" class="form-control @error('booking_date') is-invalid @enderror"
                       value="{{ old('booking_date', $booking->booking_date?->format('Y-m-d')) }}" required>
                @error('booking_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label class="form-label">Sale price (Rs)</label>
                <input type="number" step="0.01" min="0.01" name="total_price"
                       class="form-control @error('total_price') is-invalid @enderror"
                       value="{{ old('total_price', $booking->total_price) }}" required>
                @error('total_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row g-2 mb-3">
                <div class="col-3">
                    <label class="form-label">Acre</label>
                    <input type="number" min="0" name="area_acre" class="form-control"
                           value="{{ old('area_acre', $plot?->area_acre ?? 0) }}">
                </div>
                <div class="col-3">
                    <label class="form-label">Kanal</label>
                    <input type="number" min="0" name="area_kanal" class="form-control"
                           value="{{ old('area_kanal', $plot?->area_kanal ?? 0) }}">
                </div>
                <div class="col-3">
                    <label class="form-label">Marla</label>
                    <input type="number" min="0" name="area_marla" class="form-control"
                           value="{{ old('area_marla', $plot?->area_marla ?? 0) }}">
                </div>
                <div class="col-3">
                    <label class="form-label">Sqft</label>
                    <input type="number" min="0" name="area_sqft" class="form-control"
                           value="{{ old('area_sqft', $plot?->area_sqft ?? 0) }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $booking->notes) }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pink">Save</button>
                <a href="{{ route('society.sales.show', [$project, $booking]) }}" class="btn btn-outline-theme">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
