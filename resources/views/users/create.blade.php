@extends('layouts.app')

@section('title', 'Create User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create User</h1>
    <a href="{{ route('users.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control form-control-theme @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control form-control-theme @error('password') is-invalid @enderror" id="password" name="password" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="btn btn-pink mt-4">Create User</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
