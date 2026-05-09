@extends('layouts.app')

@section('title', 'Parties')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Parties</h1>
    <a href="{{ route('parties.create') }}" class="btn btn-pink">Add party</a>
</div>

@if(session('success'))
    <div class="alert alert-theme-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-theme-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-theme">
    <div class="card-body">
        <p class="text-muted small mb-3">Parties use one party sub category (and its party category). Used in Daybook and project links.</p>
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Sub category</th>
                    <th>Phone</th>
                    <th class="text-end">Opening (Rs)</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parties as $party)
                    <tr>
                        <td>{{ $party->id }}</td>
                        <td>{{ $party->name }}</td>
                        <td>{{ $party->category?->name ?? '—' }}</td>
                        <td>{{ $party->subCategory?->name ?? '—' }}</td>
                        <td>{{ $party->phone ?: '—' }}</td>
                        <td class="text-end font-monospace">{{ number_format((float) $party->opening_balance, 2) }}</td>
                        <td>
                            <a href="{{ route('parties.edit', $party) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('parties.destroy', $party) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete party?" data-text="Are you sure you want to delete &quot;{{ $party->name }}&quot;? This cannot be undone if no daybook lines use this party.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center">No parties yet. <a href="{{ route('parties.create') }}">Create one</a> or add from Daybook.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($parties->hasPages())
            <div class="pagination-wrapper">
                {{ $parties->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
