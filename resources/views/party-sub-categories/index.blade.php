@extends('layouts.app')

@section('title', 'Party sub categories')

@section('content')
@include('partials.party-sc-combo-styles')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Party sub categories</h1>
    <a href="{{ route('party-sub-categories.create') }}" class="btn btn-pink">Add party sub category</a>
</div>

<div class="card card-theme mb-4">
    <div class="card-body" style="overflow: visible;">
        <form method="get" action="{{ route('party-sub-categories.index') }}" class="row g-3 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label for="party_sub_categories_filter_name" class="form-label">Sub category name</label>
                <input type="search" name="name" id="party_sub_categories_filter_name" class="form-control form-control-theme" value="{{ $name }}" placeholder="Search sub category name…" autocomplete="off">
            </div>
            <div class="col-md-6 col-lg-4">
                <label class="form-label" for="party_sub_categories_filter_category_search">Category</label>
                <div class="party-sc-combo" id="party_sub_categories_filter_category_wrap">
                    <input type="hidden" name="category_id" id="party_sub_categories_filter_category_id" value="{{ $categoryId ?? '' }}">
                    <input type="text"
                        class="form-control form-control-theme"
                        id="party_sub_categories_filter_category_search"
                        placeholder="All categories…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="party_sub_categories_filter_category_listbox"
                        aria-autocomplete="list">
                    <ul class="party-sc-listbox d-none" id="party_sub_categories_filter_category_listbox" role="listbox" hidden></ul>
                </div>
            </div>
            <div class="col-md-12 col-lg-auto d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink">Filter</button>
                @if($hasFilters)
                    <a href="{{ route('party-sub-categories.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
        <script type="application/json" id="party_sub_categories_filter_category_json">@json($partyCategories->map(fn ($cat) => ['id' => $cat->id, 'label' => $cat->name])->values())</script>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <table class="table table-striped table-theme">
            <thead>
                <tr>
                    <th style="width: 56px;">#</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Unit</th>
                    <th width="200">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partySubCategories as $partySubCategory)
                    <tr>
                        <td>{{ $partySubCategories->firstItem() + $loop->index }}</td>
                        <td>{{ $partySubCategory->name }}</td>
                        <td>{{ $partySubCategory->category->name }}</td>
                        <td>{{ $partySubCategory->unit ?? '—' }}</td>
                        <td>
                            <a href="{{ route('party-sub-categories.edit', $partySubCategory) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                            <form action="{{ route('party-sub-categories.destroy', $partySubCategory) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete party sub category?" data-text="Are you sure you want to delete &quot;{{ $partySubCategory->name }}&quot;? This action cannot be undone.">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center">
                            @if($hasFilters)
                                No party sub categories match your filters. <a href="{{ route('party-sub-categories.index') }}">Clear filters</a>.
                            @else
                                No party sub categories yet. <a href="{{ route('party-sub-categories.create') }}">Create one</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($partySubCategories->hasPages())
            <div class="pagination-wrapper">
                {{ $partySubCategories->onEachSide(1)->links() }}
            </div>
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

    var jsonEl = document.getElementById('party_sub_categories_filter_category_json');
    var rows = [{ id: '', label: '— All categories —' }];
    if (jsonEl) {
        try {
            rows = rows.concat(JSON.parse(jsonEl.textContent) || []);
        } catch (e) {}
    }

    var hidden = document.getElementById('party_sub_categories_filter_category_id');
    var search = document.getElementById('party_sub_categories_filter_category_search');
    var list = document.getElementById('party_sub_categories_filter_category_listbox');
    var combo = PF.initSubCategoryCombo({
        wrap: document.getElementById('party_sub_categories_filter_category_wrap'),
        hidden: hidden,
        search: search,
        list: list,
        rows: rows,
        initialId: hidden ? hidden.value : '',
        emptyText: 'No categories match.'
    });

    if (search && combo) {
        search.addEventListener('input', function () {
            if (search.value.trim() === '') {
                hidden.value = '';
            }
        });
    }
})();
</script>
@endpush
