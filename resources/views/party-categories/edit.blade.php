@extends('layouts.app')

@section('title', 'Edit party category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit party category</h1>
    <a href="{{ route('party-categories.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('party-categories.update', $partyCategory) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $partyCategory->name) }}" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Update</button>
        </form>
    </div>
</div>
@endsection
