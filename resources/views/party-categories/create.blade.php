@extends('layouts.app')

@section('title', 'Create party category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create party category</h1>
    <a href="{{ route('party-categories.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('party-categories.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Property related" required maxlength="255">
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-pink">Create</button>
        </form>
    </div>
</div>
@endsection
