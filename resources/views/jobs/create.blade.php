@extends('layouts.app')

@section('title', 'Create Job')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Create Job</h1>
    <a href="{{ route('jobs.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('jobs.store') }}" method="POST">
            @csrf

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control form-control-theme @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="no_of_positions" class="form-label">Number of Positions</label>
                    <input type="number" class="form-control form-control-theme @error('no_of_positions') is-invalid @enderror" id="no_of_positions" name="no_of_positions" value="{{ old('no_of_positions', 1) }}" min="1" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="salary_range" class="form-label">Salary Range</label>
                    <input type="text" class="form-control form-control-theme @error('salary_range') is-invalid @enderror" id="salary_range" name="salary_range" value="{{ old('salary_range') }}" placeholder="e.g. $50,000 - $70,000">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-select form-select-theme @error('location') is-invalid @enderror" id="location" name="location" required>
                        <option value="">Select location</option>
                        <option value="onsite" {{ old('location') === 'onsite' ? 'selected' : '' }}>Onsite</option>
                        <option value="remote" {{ old('location') === 'remote' ? 'selected' : '' }}>Remote</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select form-select-theme @error('job_type') is-invalid @enderror" id="job_type" name="job_type" required>
                        <option value="">Select job type</option>
                        <option value="permanent" {{ old('job_type') === 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('job_type') === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="part_time" {{ old('job_type') === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="full_time" {{ old('job_type') === 'full_time' ? 'selected' : '' }}>Full Time</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control form-control-theme @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description') }}</textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-pink">Create Job</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
