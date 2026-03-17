@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Project</h1>
    <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">View</a>
    <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('projects.update', $project) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $project->name) }}" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control form-control-theme" id="description" name="description" rows="2">{{ old('description', $project->description) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme" id="notes" name="notes" rows="2">{{ old('notes', $project->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-pink">Update Project</button>
        </form>
    </div>
</div>
@endsection
