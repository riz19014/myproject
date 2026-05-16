@extends('layouts.app')

@section('title', 'Parties')

@section('content')
@include('partials.party-sc-combo-styles')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Parties</h1>
    <a href="{{ route('parties.create') }}" class="btn btn-pink">Add party</a>
</div>

@if(session('success'))
    <div class="alert alert-theme-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-theme-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('parties.index') }}" id="parties-filter-form" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-3">
                <label for="parties_filter_name" class="form-label">Name</label>
                <input type="search" name="name" id="parties_filter_name" class="form-control form-control-theme" value="{{ $name }}" placeholder="Search name…" autocomplete="off">
            </div>
            <div class="col-md-6 col-lg-2">
                <label for="parties_filter_phone" class="form-label">Phone</label>
                <input type="search" name="phone" id="parties_filter_phone" class="form-control form-control-theme" value="{{ $phone }}" maxlength="11" inputmode="numeric" placeholder="11 digits" autocomplete="off">
            </div>
            <div class="col-md-6 col-lg-3">
                <label for="parties_filter_cnic" class="form-label">CNIC</label>
                <input type="search" name="cnic" id="parties_filter_cnic" class="form-control form-control-theme" value="{{ $cnicQuery !== '' ? \App\Support\CnicFormat::display($cnicQuery) : '' }}" maxlength="15" inputmode="numeric" placeholder="23012-2321373-1" autocomplete="off">
            </div>
            <div class="col-md-6 col-lg-3">
                <label class="form-label" for="parties_filter_sub_search">Sub category</label>
                <div class="party-sc-combo" id="parties_filter_sc_wrap">
                    <input type="hidden" name="sub_category_id" id="parties_filter_sub_category_id" value="{{ $subCategoryId ?? '' }}">
                    <input type="text"
                        class="form-control form-control-theme"
                        id="parties_filter_sub_search"
                        placeholder="All sub categories…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="parties_filter_sc_listbox"
                        aria-autocomplete="list">
                    <ul class="party-sc-listbox d-none" id="parties_filter_sc_listbox" role="listbox" hidden></ul>
                </div>
            </div>
            <div class="col-md-12 col-lg-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink">Filter</button>
                @if($hasFilters)
                    <a href="{{ route('parties.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
        <script type="application/json" id="parties_filter_sub_json">@json($partySubCategories->map(function ($sc) {
            return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
        })->values())</script>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <p class="text-muted small mb-3">Parties use one party sub category (and its party category). Used in Daybook and project links.</p>
        @if($parties->isEmpty())
            @if($hasFilters)
                <p class="text-muted mb-0">No parties match your filters. <a href="{{ route('parties.index') }}">Clear filters</a> or <a href="{{ route('parties.create') }}">add a party</a>.</p>
            @else
                <p class="text-muted mb-0">No parties yet. <a href="{{ route('parties.create') }}">Create one</a> or add from Daybook.</p>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Sub category</th>
                            <th>Phone</th>
                            <th>CNIC</th>
                            <th class="text-end">Opening (Rs)</th>
                            <th width="200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($parties as $party)
                            <tr>
                                <td>{{ $parties->firstItem() + $loop->index }}</td>
                                <td>{{ $party->name }}</td>
                                <td>{{ $party->category?->name ?? '—' }}</td>
                                <td>{{ $party->subCategory?->name ?? '—' }}</td>
                                <td>{{ $party->phone ?: '—' }}</td>
                                <td class="small">{{ $party->cnic ? \App\Support\CnicFormat::display($party->cnic) : '—' }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $party->opening_balance, 2) }}</td>
                                <td>
                                    <a href="{{ route('parties.edit', $party) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                    <form action="{{ route('parties.destroy', $party) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete party?" data-text="Are you sure you want to delete &quot;{{ $party->name }}&quot;? This cannot be undone if no daybook lines use this party.">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($parties->hasPages())
                <div class="pagination-wrapper mt-3">
                    {{ $parties->onEachSide(1)->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection

@push('scripts')
@include('partials.party-form-field-scripts')
<script>
(function () {
    var PF = window.PartyFormFields;
    if (!PF) return;

    var phoneEl = document.getElementById('parties_filter_phone');
    var cnicEl = document.getElementById('parties_filter_cnic');
    if (phoneEl) PF.bindPhoneInput(phoneEl, 11);
    if (cnicEl) PF.bindCnicInput(cnicEl);

    var subJsonEl = document.getElementById('parties_filter_sub_json');
    var rows = [{ id: '', label: '— All sub categories —' }];
    if (subJsonEl) {
        try {
            rows = rows.concat(JSON.parse(subJsonEl.textContent) || []);
        } catch (e) {}
    }

    var subHidden = document.getElementById('parties_filter_sub_category_id');
    var subSearch = document.getElementById('parties_filter_sub_search');
    var subList = document.getElementById('parties_filter_sc_listbox');
    var subCombo = PF.initSubCategoryCombo({
        wrap: document.getElementById('parties_filter_sc_wrap'),
        hidden: subHidden,
        search: subSearch,
        list: subList,
        rows: rows,
        initialId: subHidden ? subHidden.value : '',
        emptyText: 'No sub categories match.'
    });

    if (subSearch && subCombo) {
        subSearch.addEventListener('input', function () {
            if (subSearch.value.trim() === '') {
                subHidden.value = '';
            }
        });
    }
})();
</script>
@endpush
