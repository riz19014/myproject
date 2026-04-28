@extends('layouts.app')

@section('title', 'Create land type')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create land type</h1>
    <a href="{{ route('land-types.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('land-types.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Factory, House, Plot" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="party_sub_category_id" class="form-label">Party sub category <span class="text-muted fw-normal">(optional)</span></label>
                <select name="party_sub_category_id" id="party_sub_category_id" class="form-select form-select-theme @error('party_sub_category_id') is-invalid @enderror">
                    <option value="">— None —</option>
                    @foreach($partySubCategories as $sc)
                        <option value="{{ $sc->id }}" @selected(old('party_sub_category_id') == $sc->id)>{{ $sc->category?->name }} — {{ $sc->name }}</option>
                    @endforeach
                </select>
                @error('party_sub_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Create</button>
        </form>
    </div>
</div>
@endsection
