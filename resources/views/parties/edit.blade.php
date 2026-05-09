@extends('layouts.app')

@section('title', 'Edit party')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit party</h1>
    <a href="{{ route('parties.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('parties.update', $party) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $party->name) }}" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="sub_category_id" class="form-label">Party sub category <span class="text-danger">*</span></label>
                <select name="sub_category_id" id="sub_category_id" class="form-select form-select-theme @error('sub_category_id') is-invalid @enderror" required>
                    @foreach($partySubCategories as $sc)
                        <option value="{{ $sc->id }}" @selected(old('sub_category_id', $party->sub_category_id) == $sc->id)>{{ $sc->category?->name ?? '—' }} — {{ $sc->name }}</option>
                    @endforeach
                </select>
                @error('sub_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="phone" class="form-label">Phone</label>
                <input type="text" class="form-control form-control-theme @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $party->phone) }}" maxlength="255">
                @error('phone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control form-control-theme @error('address') is-invalid @enderror" id="address" name="address" rows="2" maxlength="2000">{{ old('address', $party->address) }}</textarea>
                @error('address')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="opening_balance" class="form-label">Opening balance (Rs)</label>
                <input type="number" class="form-control form-control-theme @error('opening_balance') is-invalid @enderror" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', number_format((float) $party->opening_balance, 2, '.', '')) }}" step="0.01">
                @error('opening_balance')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Update</button>
        </form>
    </div>
</div>
@endsection
