@extends('layouts.app')

@section('title', 'Edit party sub category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit party sub category</h1>
    <a href="{{ route('party-sub-categories.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('party-sub-categories.update', $partySubCategory) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="category_id" class="form-label">Party category</label>
                <select name="category_id" id="category_id" class="form-select form-select-theme @error('category_id') is-invalid @enderror" required>
                    @foreach($partyCategories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $partySubCategory->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $partySubCategory->name) }}" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Update</button>
        </form>
    </div>
</div>
@endsection
