@extends('layouts.app')

@section('title', 'View User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>User Details</h1>
    <div class="d-flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-theme">Edit</a>
        <a href="{{ route('users.index') }}" class="btn btn-outline-theme">Back to List</a>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-theme">
            <tr>
                <th width="150">ID</th>
                <td>{{ $user->id }}</td>
            </tr>
            <tr>
                <th>Name</th>
                <td>{{ $user->name }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $user->email }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($user->is_active)
                        <span class="badge badge-pink">Active</span>
                    @else
                        <span class="badge badge-outline">Inactive</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>
</div>
@endsection
