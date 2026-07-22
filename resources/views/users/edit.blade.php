@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Edit User</h1>
    <a href="{{ route('users.index') }}" class="btn btn-outline-theme">Back to List</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control form-control-theme @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="password" class="form-label">Password <small class="text-muted">(leave blank to keep current)</small></label>
                    <input type="password" class="form-control form-control-theme @error('password') is-invalid @enderror" id="password" name="password">
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="phone" class="form-label">Phone <small class="text-muted">(optional)</small></label>
                    <input type="text" class="form-control form-control-theme @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="11" inputmode="numeric" placeholder="11 digits e.g. 03001234567">
                    @error('phone')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="cnic" class="form-label">CNIC <small class="text-muted">(optional)</small></label>
                    <input type="text" class="form-control form-control-theme @error('cnic') is-invalid @enderror" id="cnic" name="cnic" value="{{ \App\Support\CnicFormat::display(old('cnic', $user->cnic)) }}" maxlength="15" inputmode="numeric" placeholder="23012-2321373-1">
                    @error('cnic')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select form-select-theme @error('type') is-invalid @enderror" id="type" name="type" required>
                        @foreach($userTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $user->type ?? \App\Models\User::TYPE_ACCOUNTANT) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <button type="submit" class="btn btn-pink mt-4">Update User</button>
                </div>
            </div>
        </form>
    </div>
</div>
@include('partials.party-form-field-scripts')
<script>
(function () {
    var PF = window.PartyFormFields;
    if (!PF) return;
    PF.bindCnicInput(document.getElementById('cnic'));
    PF.bindPhoneInput(document.getElementById('phone'), 11);
})();
</script>
@endsection
