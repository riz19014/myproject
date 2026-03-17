@extends('layouts.app')

@section('title', 'DayBook')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>DayBook</h1>
    <a href="{{ route('daybook.create') }}" class="btn btn-pink">New Entry</a>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">From</label>
                <input type="date" name="from" class="form-control form-control-theme" value="{{ request('from') }}">
            </div>
            <div class="col-auto">
                <label class="form-label">To</label>
                <input type="date" name="to" class="form-control form-control-theme" value="{{ request('to') }}">
            </div>
            <div class="col-auto">
                <label class="form-label">Type</label>
                <select name="type" class="form-select form-select-theme">
                    <option value="">All</option>
                    <option value="cash_in" {{ request('type') === 'cash_in' ? 'selected' : '' }}>Cash In</option>
                    <option value="cash_out" {{ request('type') === 'cash_out' ? 'selected' : '' }}>Cash Out</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label">Link</label>
                <select name="link_type" class="form-select form-select-theme">
                    <option value="">All</option>
                    <option value="office" {{ request('link_type') === 'office' ? 'selected' : '' }}>Office</option>
                    <option value="project" {{ request('link_type') === 'project' ? 'selected' : '' }}>Project</option>
                    <option value="land" {{ request('link_type') === 'land' ? 'selected' : '' }}>Land</option>
                    <option value="plot" {{ request('link_type') === 'plot' ? 'selected' : '' }}>Plot</option>
                    <option value="factory" {{ request('link_type') === 'factory' ? 'selected' : '' }}>Factory</option>
                    <option value="customer" {{ request('link_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-theme">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="card card-theme">
            <div class="card-body py-3">
                <strong>Total Cash In:</strong> {{ number_format($totalIn) }}
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-theme">
            <div class="card-body py-3">
                <strong>Total Cash Out:</strong> {{ number_format($totalOut) }}
            </div>
        </div>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Linked To</th>
                    <th width="120">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $e)
                    <tr>
                        <td>{{ $e->entry_date->format('d M Y') }}</td>
                        <td>{{ $e->type === 'cash_in' ? 'Cash In' : 'Cash Out' }}</td>
                        <td>{{ number_format($e->amount) }}</td>
                        <td>{{ $e->description ? \Str::limit($e->description, 40) : '—' }}</td>
                        <td>{{ $e->getLinkLabel() }}</td>
                        <td>
                            <a href="{{ route('daybook.edit', $e) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('daybook.destroy', $e) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete entry?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">No entries. <a href="{{ route('daybook.create') }}">Add first entry</a>.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $entries->links() }}</div>
    </div>
</div>

<p class="text-muted small mt-3">All payments are entered only here. When you link an entry to a Project, Land, Plot, Factory, or Customer, it automatically appears on that record—no need to enter the same transaction twice.</p>
@endsection
