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
