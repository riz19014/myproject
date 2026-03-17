@extends('layouts.app')

@section('title', 'Jobs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Jobs</h1>
    <a href="{{ route('jobs.create') }}" class="btn btn-pink">Add Job</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Positions</th>
                    <th>Salary Range</th>
                    <th>Location</th>
                    <th>Job Type</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($jobs as $job)
                    <tr>
                        <td>{{ $job->id }}</td>
                        <td>{{ $job->title }}</td>
                        <td>{{ $job->no_of_positions }}</td>
                        <td>{{ $job->salary_range ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $job->location === 'remote' ? 'badge-pink' : 'badge-outline' }}">
                                {{ ucfirst($job->location) }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-pink">{{ str_replace('_', ' ', ucfirst($job->job_type)) }}</span>
                        </td>
                        <td>
                            <a href="{{ route('jobs.show', $job) }}" class="btn btn-sm btn-outline-theme">View</a>
                            <a href="{{ route('jobs.edit', $job) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('jobs.destroy', $job) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Job?" data-text="Are you sure you want to delete &quot;{{ $job->title }}&quot;? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No jobs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $jobs->links() }}</div>
    </div>
</div>
@endsection
