@extends('layouts.app')

@section('title', 'Companies')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Companies</h1>
    <a href="{{ route('companies.create') }}" class="btn btn-pink">Add Company</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-theme align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 72px;">Logo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Owner</th>
                        <th>Address</th>
                        <th style="width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $company)
                        <tr>
                            <td>
                                @if($company->logo_path)
                                    <img src="{{ route('companies.logo.show', $company) }}"
                                         alt="{{ $company->name }} logo"
                                         class="rounded border bg-white"
                                         style="width: 48px; height: 48px; object-fit: contain; padding: 2px;">
                                @else
                                    <span class="d-inline-flex align-items-center justify-content-center rounded border bg-light text-muted"
                                          style="width: 48px; height: 48px;">
                                        <i class="bi bi-building" aria-hidden="true"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="fw-medium">{{ $company->name }}</td>
                            <td>{{ $company->phone ?? '—' }}</td>
                            <td>{{ $company->owner_name ?? '—' }}</td>
                            <td class="text-truncate" style="max-width: 220px;">{{ $company->address ?? '—' }}</td>
                            <td>
                                <a href="{{ route('companies.edit', $company) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                <form action="{{ route('companies.destroy', $company) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete company?">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No companies yet.
                                <a href="{{ route('companies.create') }}">Add one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($companies->hasPages())
            <div class="mt-3">{{ $companies->links() }}</div>
        @endif
    </div>
</div>
@endsection
