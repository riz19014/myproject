@extends('layouts.app')

@section('title', 'Land types')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Land types</h1>
    <a href="{{ route('land-types.create') }}" class="btn btn-pink">Add land type</a>
</div>

@if(session('error'))
    <div class="alert alert-theme-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-theme">
    <div class="card-body">
        <p class="text-muted small mb-3">Used when creating projects (e.g. Factory, House, Plot). Optionally link a row to a party sub category for grouping.</p>
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Party sub category</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($landTypes as $landType)
                    <tr>
                        <td>{{ $landType->id }}</td>
                        <td>{{ $landType->name }}</td>
                        <td>
                            @if($landType->partySubCategory)
                                {{ $landType->partySubCategory->category?->name }} — {{ $landType->partySubCategory->name }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('land-types.edit', $landType) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('land-types.destroy', $landType) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete land type?" data-text="Are you sure you want to delete &quot;{{ $landType->name }}&quot;? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No land types yet. <a href="{{ route('land-types.create') }}">Create one</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($landTypes->hasPages())
            <div class="pagination-wrapper">
                {{ $landTypes->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
