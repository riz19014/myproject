@extends('layouts.app')

@section('title', 'View Job')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Job Details</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('jobs.edit', $job) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('jobs.index') }}" class="btn btn-outline-theme">Back to List</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-theme">
            <tr>
                <th width="180">ID</th>
                <td>{{ $job->id }}</td>
            </tr>
            <tr>
                <th>Title</th>
                <td>{{ $job->title }}</td>
            </tr>
            <tr>
                <th>Description</th>
                <td>{{ $job->description ? nl2br(e($job->description)) : '-' }}</td>
            </tr>
            <tr>
                <th>Number of Positions</th>
                <td>{{ $job->no_of_positions }}</td>
            </tr>
            <tr>
                <th>Salary Range</th>
                <td>{{ $job->salary_range ?? '-' }}</td>
            </tr>
            <tr>
                <th>Location</th>
                <td>
                    <span class="badge {{ $job->location === 'remote' ? 'badge-pink' : 'badge-outline' }}">
                        {{ ucfirst($job->location) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>Job Type</th>
                <td>
                    <span class="badge badge-pink">{{ str_replace('_', ' ', ucfirst($job->job_type)) }}</span>
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
