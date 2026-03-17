@extends('layouts.app')

@section('title', 'Factory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Factory</h1>
    <a href="{{ route('factories.create') }}" class="btn btn-pink">Add Factory</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Purchase Cost</th>
                    <th>Location</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factories as $factory)
                    <tr>
                        <td>{{ $factory->id }}</td>
                        <td>{{ $factory->name }}</td>
                        <td>{{ $factory->purchase_cost ? number_format($factory->purchase_cost) : '—' }}</td>
                        <td>{{ $factory->location ?? '—' }}</td>
                        <td>
                            <a href="{{ route('factories.show', $factory) }}" class="btn btn-sm btn-outline-theme">View</a>
                            <a href="{{ route('factories.edit', $factory) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('factories.destroy', $factory) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Factory?" data-text="This will delete the factory and its expense links from DayBook.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No factory records yet. <a href="{{ route('factories.create') }}">Add one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $factories->links() }}</div>
    </div>
</div>
@endsection
