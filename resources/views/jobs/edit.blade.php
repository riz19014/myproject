@extends('layouts.app')

@section('title', 'Edit Job')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Job</h1>
    <a href="{{ route('jobs.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('jobs.update', $job) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control form-control-theme @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $job->title) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="no_of_positions" class="form-label">Number of Positions</label>
                    <input type="number" class="form-control form-control-theme @error('no_of_positions') is-invalid @enderror" id="no_of_positions" name="no_of_positions" value="{{ old('no_of_positions', $job->no_of_positions) }}" min="1" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="salary_range" class="form-label">Salary Range</label>
                    <input type="text" class="form-control form-control-theme @error('salary_range') is-invalid @enderror" id="salary_range" name="salary_range" value="{{ old('salary_range', $job->salary_range) }}" placeholder="e.g. $50,000 - $70,000">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="location" class="form-label">Location</label>
                    <select class="form-select form-select-theme @error('location') is-invalid @enderror" id="location" name="location" required>
                        <option value="onsite" {{ old('location', $job->location) === 'onsite' ? 'selected' : '' }}>Onsite</option>
                        <option value="remote" {{ old('location', $job->location) === 'remote' ? 'selected' : '' }}>Remote</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select form-select-theme @error('job_type') is-invalid @enderror" id="job_type" name="job_type" required>
                        <option value="permanent" {{ old('job_type', $job->job_type) === 'permanent' ? 'selected' : '' }}>Permanent</option>
                        <option value="contract" {{ old('job_type', $job->job_type) === 'contract' ? 'selected' : '' }}>Contract</option>
                        <option value="part_time" {{ old('job_type', $job->job_type) === 'part_time' ? 'selected' : '' }}>Part Time</option>
                        <option value="full_time" {{ old('job_type', $job->job_type) === 'full_time' ? 'selected' : '' }}>Full Time</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control form-control-theme @error('description') is-invalid @enderror" id="description" name="description" rows="4">{{ old('description', $job->description) }}</textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-pink">Update Job</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
