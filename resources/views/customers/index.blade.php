@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Customers</h1>
    <a href="{{ route('customers.create') }}" class="btn btn-pink">Add Customer</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th width="180">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>{{ $customer->id }}</td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone ?? '—' }}</td>
                        <td>{{ $customer->email ?? '—' }}</td>
                        <td>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete customer?">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No customers yet. <a href="{{ route('customers.create') }}">Add one</a> (e.g. for plot/file buyers).</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $customers->links() }}</div>
    </div>
</div>
@endsection
