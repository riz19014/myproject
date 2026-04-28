@extends('layouts.app')

@section('title', 'Party categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Party categories</h1>
    <a href="{{ route('party-categories.create') }}" class="btn btn-pink">Add party category</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partyCategories as $partyCategory)
                    <tr>
                        <td>{{ $partyCategory->id }}</td>
                        <td>{{ $partyCategory->name }}</td>
                        <td>
                            <a href="{{ route('party-categories.edit', $partyCategory) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('party-categories.destroy', $partyCategory) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete party category?" data-text="Are you sure you want to delete &quot;{{ $partyCategory->name }}&quot;? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center">No party categories yet. <a href="{{ route('party-categories.create') }}">Create one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($partyCategories->hasPages())
            <div class="pagination-wrapper">
                {{ $partyCategories->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
