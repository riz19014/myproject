@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Users</h1>
    <a href="{{ route('users.create') }}" class="btn btn-pink">Add User</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>{{ $user->id }}</td>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-pink">Active</span>
                            @else
                                <span class="badge badge-outline">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-theme">View</a>
                            <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete User?" data-text="Are you sure you want to delete {{ $user->name }}? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-3">{{ $users->links() }}</div>
    </div>
</div>
@endsection
