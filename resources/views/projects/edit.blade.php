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
            <div class="mb-4">
                <label for="land_type_id" class="form-label">Land type</label>
                <select name="land_type_id" id="land_type_id" class="form-select form-select-theme @error('land_type_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($landTypes as $lt)
                        <option value="{{ $lt->id }}" @selected(old('land_type_id', $project->land_type_id) == $lt->id)>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @error('land_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <div class="form-check">
                    <input class="form-check-input @error('is_dha') is-invalid @enderror" type="checkbox" id="is_dha" name="is_dha" value="1" {{ old('is_dha', $project->is_dha) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_dha">DHA project</label>
                </div>
                @error('is_dha')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-pink">Update Project</button>
        </form>
    </div>
</div>
@endsection
