@extends('layouts.app')

@section('title', 'Projects')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Projects</h1>
    <a href="{{ route('projects.create') }}" class="btn btn-pink">Add Project</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Files</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($projects as $project)
                    <tr>
                        <td>{{ $project->id }}</td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->project_files_count }}</td>
                        <td>
                            <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">View</a>
                            <a href="{{ route('projects.edit', $project) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('projects.destroy', $project) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Project?" data-text="This will delete the project and all its files and documents.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No projects yet. <a href="{{ route('projects.create') }}">Create one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $projects->links() }}</div>
    </div>
</div>
@endsection
