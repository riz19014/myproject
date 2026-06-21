<div class="mb-3">
    <label for="company_name" class="form-label">Company name <span class="text-danger">*</span></label>
    <input type="text"
           class="form-control form-control-theme @error('name') is-invalid @enderror"
           id="company_name"
           name="name"
           value="{{ old('name', $company?->name ?? '') }}"
           required>
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="mb-3">
    <label for="company_address" class="form-label">Address</label>
    <textarea class="form-control form-control-theme @error('address') is-invalid @enderror"
              id="company_address"
              name="address"
              rows="3">{{ old('address', $company?->address ?? '') }}</textarea>
    @error('address')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
<div class="row g-3">
    <div class="col-md-6">
        <label for="company_phone" class="form-label">Phone</label>
        <input type="text"
               class="form-control form-control-theme @error('phone') is-invalid @enderror"
               id="company_phone"
               name="phone"
               value="{{ old('phone', $company?->phone ?? '') }}">
        @error('phone')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label for="company_email" class="form-label">Email</label>
        <input type="email"
               class="form-control form-control-theme @error('email') is-invalid @enderror"
               id="company_email"
               name="email"
               value="{{ old('email', $company?->email ?? '') }}">
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
<div class="row g-3 mt-0">
    <div class="col-md-12">
        <label for="company_owner_name" class="form-label">Owner name</label>
        <input type="text"
               class="form-control form-control-theme @error('owner_name') is-invalid @enderror"
               id="company_owner_name"
               name="owner_name"
               value="{{ old('owner_name', $company?->owner_name ?? '') }}">
        @error('owner_name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>
