@extends('layouts.app')

@section('title', 'Party sub categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Party sub categories</h1>
    <a href="{{ route('party-sub-categories.create') }}" class="btn btn-pink">Add party sub category</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Name</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partySubCategories as $partySubCategory)
                    <tr>
                        <td>{{ $partySubCategory->id }}</td>
                        <td>{{ $partySubCategory->category->name }}</td>
                        <td>{{ $partySubCategory->name }}</td>
                        <td>
                            <a href="{{ route('party-sub-categories.edit', $partySubCategory) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('party-sub-categories.destroy', $partySubCategory) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete party sub category?" data-text="Are you sure you want to delete &quot;{{ $partySubCategory->name }}&quot;? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No party sub categories yet. <a href="{{ route('party-sub-categories.create') }}">Create one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($partySubCategories->hasPages())
            <div class="pagination-wrapper">
                {{ $partySubCategories->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
