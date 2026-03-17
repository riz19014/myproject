@extends('layouts.app')

@section('title', $factory->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>{{ $factory->name }}</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('factories.edit', $factory) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('factories.index') }}" class="btn btn-outline-theme">Back to List</a>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <p class="mb-1"><strong>Purchase cost:</strong> {{ $factory->purchase_cost ? number_format($factory->purchase_cost) : '—' }} &nbsp;|&nbsp; <strong>Date:</strong> {{ $factory->purchase_date?->format('d M Y') ?? '—' }} &nbsp;|&nbsp; <strong>Location:</strong> {{ $factory->location ?? '—' }}</p>
        @if($factory->notes)<p class="mb-0 text-muted small">{{ $factory->notes }}</p>@endif
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <h5 class="mb-3">Factory expenses (from DayBook)</h5>
        <p class="text-muted small">All expenses entered in DayBook with link “Factory → {{ $factory->name }}” (purchase, maintenance, operational, etc.) appear here. Enter once in DayBook; it auto-links here.</p>
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                @forelse($expenses as $e)
                    <tr>
                        <td>{{ $e->entry_date->format('d M Y') }}</td>
                        <td>{{ $e->type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</td>
                        <td>{{ number_format($e->amount) }}</td>
                        <td>{{ $e->description ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">No expenses linked yet. <a href="{{ route('daybook.create') }}">Add entry in DayBook</a> and link to this factory.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
