@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit Customer</h1>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label">Phone</label>
                    <input type="text" class="form-control form-control-theme" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}">
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control form-control-theme" id="email" name="email" value="{{ old('email', $customer->email) }}">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="cnic" class="form-label">CNIC</label>
                    <input type="text" class="form-control form-control-theme" id="cnic" name="cnic" value="{{ old('cnic', $customer->cnic) }}">
                </div>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control form-control-theme" id="address" name="address" rows="2">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme" id="notes" name="notes" rows="2">{{ old('notes', $customer->notes) }}</textarea>
            </div>
            <button type="submit" class="btn btn-pink">Update Customer</button>
        </form>
    </div>
</div>
@endsection
