@extends('layouts.app')

@section('title', 'Land')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Land</h1>
    <a href="{{ route('lands.create') }}" class="btn btn-pink">Add Land</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Area (Kanal)</th>
                    <th>Location</th>
                    <th>Plots</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($lands as $land)
                    <tr>
                        <td>{{ $land->id }}</td>
                        <td>{{ $land->name }}</td>
                        <td>{{ $land->total_area_kanal ?? '—' }}</td>
                        <td>{{ $land->location ?? '—' }}</td>
                        <td>{{ $land->plots_count }}</td>
                        <td>
                            <a href="{{ route('lands.show', $land) }}" class="btn btn-sm btn-outline-theme">View</a>
                            <a href="{{ route('lands.edit', $land) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('lands.destroy', $land) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Land?" data-text="This will delete the land and all plots and documents.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No land records yet. <a href="{{ route('lands.create') }}">Add one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $lands->links() }}</div>
    </div>
</div>
@endsection
