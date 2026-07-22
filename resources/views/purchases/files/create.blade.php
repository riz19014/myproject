@extends('layouts.app')

@section('title', 'Add purchase file')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <h1 class="mb-0">Add purchase file</h1>
    <a href="{{ route('purchase.files.index', $projectId ? ['project' => $projectId] : []) }}" class="btn btn-outline-theme">Back</a>
</div>

<div class="card card-theme">
    <div class="card-body">
        <form method="post" action="{{ route('purchase.files.store') }}">
            @csrf
            <div class="mb-3">
                <label for="project_id" class="form-label">Project <span class="text-danger">*</span></label>
                <select name="project_id" id="project_id" class="form-select form-select-theme @error('project_id') is-invalid @enderror" required>
                    <option value="" disabled @if(!old('project_id', $projectId)) selected @endif>— Select project —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((int) old('project_id', $projectId) === (int) $p->id)>{{ $p->labeledName() }}</option>
                    @endforeach
                </select>
                @error('project_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-8">
                    <label for="file_name" class="form-label">File name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme @error('file_name') is-invalid @enderror" id="file_name" name="file_name" value="{{ old('file_name') }}" maxlength="255" required placeholder="e.g. 23 kanal 5 marla, DHA block A">
                    @error('file_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="file_date" class="form-label">File date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-theme @error('file_date') is-invalid @enderror" id="file_date" name="file_date" value="{{ old('file_date', now()->toDateString()) }}" required>
                    @error('file_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <label class="form-label mb-0" for="purchase_file_dealers_search">Dealers (parties)</label>
                        <p class="text-muted small mb-0">Search and add dealer / agent parties for this file.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-theme text-nowrap" id="purchase_file_add_dealer_btn" title="Add new dealer">
                        <i class="bi bi-person-plus" aria-hidden="true"></i> Add dealer
                    </button>
                </div>
                @include('purchases.files.partials.dealers-multi-select', [
                    'parties' => $parties,
                    'showDealerCommissions' => true,
                ])
                @error('dealer_party_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-pink">Create file</button>
        </form>
    </div>
</div>
@endsection

@push('modals')
@include('purchases.files.partials.dealer-party-modal', ['partySubCategories' => $partySubCategories])
@endpush

@push('scripts')
@include('purchases.files.partials.dealers-multi-select-scripts')
@include('purchases.files.partials.dealer-party-modal-scripts')
@endpush
