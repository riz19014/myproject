@extends('layouts.app')

@section('title', 'DayBook Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>New DayBook Entry</h1>
    <a href="{{ route('daybook.index') }}" class="btn btn-outline-theme">Back to DayBook</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <p class="text-muted small mb-3">Enter payment once here. If you link it to a Project, Land, Factory, or Customer, it will automatically appear on that record.</p>
        <form action="{{ route('daybook.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="entry_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-theme @error('entry_date') is-invalid @enderror" id="entry_date" name="entry_date" value="{{ old('entry_date', date('Y-m-d')) }}" required>
                    @error('entry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4 mb-3">
                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                    <select id="type" name="type" class="form-select form-select-theme" required>
                        <option value="cash_in" {{ old('type') === 'cash_in' ? 'selected' : '' }}>Cash In</option>
                        <option value="cash_out" {{ old('type') === 'cash_out' ? 'selected' : '' }}>Cash Out</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control form-control-theme @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control form-control-theme" id="description" name="description" rows="2" placeholder="e.g. Office expenses, Customer payment, Land purchase...">{{ old('description') }}</textarea>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="link_type" class="form-label">Link to (optional)</label>
                    <select id="link_type" name="link_type" class="form-select form-select-theme">
                        <option value="office" {{ old('link_type', 'office') === 'office' ? 'selected' : '' }}>Office (general)</option>
                        <option value="project" {{ old('link_type') === 'project' ? 'selected' : '' }}>Project</option>
                        <option value="land" {{ old('link_type') === 'land' ? 'selected' : '' }}>Land</option>
                        <option value="plot" {{ old('link_type') === 'plot' ? 'selected' : '' }}>Plot</option>
                        <option value="factory" {{ old('link_type') === 'factory' ? 'selected' : '' }}>Factory</option>
                        <option value="customer" {{ old('link_type') === 'customer' ? 'selected' : '' }}>Customer</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="link_id_wrap">
                    <label for="link_id" class="form-label">Select record</label>
                    <select id="link_id" name="link_id" class="form-select form-select-theme">
                        <option value="">—</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" data-type="project" {{ old('link_type') === 'project' && old('link_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                        @foreach($lands as $l)
                            <option value="{{ $l->id }}" data-type="land" {{ old('link_type') === 'land' && old('link_id') == $l->id ? 'selected' : '' }}>{{ $l->name }}</option>
                        @endforeach
                        @foreach($plots as $pl)
                            <option value="{{ $pl->id }}" data-type="plot" {{ old('link_type') === 'plot' && old('link_id') == $pl->id ? 'selected' : '' }}>Plot: {{ $pl->plot_number }} ({{ $pl->land->name }})</option>
                        @endforeach
                        @foreach($factories as $f)
                            <option value="{{ $f->id }}" data-type="factory" {{ old('link_type') === 'factory' && old('link_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                        @endforeach
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-type="customer" {{ old('link_type') === 'customer' && old('link_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('link_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-pink">Save Entry</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('link_type').addEventListener('change', function() {
    var type = this.value;
    var sel = document.getElementById('link_id');
    for (var i = 0; i < sel.options.length; i++) {
        var opt = sel.options[i];
        if (opt.value === '') { opt.style.display = 'block'; continue; }
        opt.style.display = opt.getAttribute('data-type') === type ? 'block' : 'none';
        if (opt.style.display === 'none' && opt.selected) opt.selected = false;
    }
    sel.value = '';
});
document.getElementById('link_type').dispatchEvent(new Event('change'));
</script>
@endpush
@endsection
