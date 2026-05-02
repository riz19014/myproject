@extends('layouts.app')

@section('title', 'Edit daybook entry')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
@include('daybook.partials.page-styles')
<div class="daybook-page">
    <div class="daybook-page-heading mb-3">
        <div class="daybook-card-heading align-items-center">
            <div class="daybook-card-heading__title mb-0">
                <h1 class="daybook-card-title mb-0">Edit entry #{{ $entry->id }}</h1>
                <p class="text-muted small mb-0 mt-2">{{ $entry->entry_date->format('l, j M Y') }}</p>
            </div>
            <div class="daybook-card-heading__actions">
                <a href="{{ route('daybook.index', ['date' => $entry->entry_date->toDateString()]) }}" class="btn btn-outline-theme btn-sm">Back to daybook</a>
                <a href="{{ route('daybook.show', $entry) }}" class="btn btn-outline-theme btn-sm">View entry</a>
            </div>
        </div>
    </div>

    <div class="card daybook-card mb-4">
        <div class="daybook-card__accent" aria-hidden="true"></div>
        <div class="card-body p-0">
            <div class="daybook-form-inner pb-2">
                @include('daybook.partials.entry-form', [
                    'daybookFormAction' => route('daybook.update', $entry),
                    'daybookFormUsePut' => true,
                    'daybookReturnDate' => null,
                    'daybookProjectIdDefault' => $daybookProjectIdDefault ?? '',
                    'daybookPartyIdDefault' => $daybookPartyIdDefault ?? '',
                    'daybookPartySubCategoryIdDefault' => $daybookPartySubCategoryIdDefault ?? '',
                    'daybookEntryDate' => $daybookEntryDate ?? now()->toDateString(),
                    'daybookTypeDefault' => $daybookTypeDefault ?? 'cash_out',
                    'daybookAmountDefault' => $daybookAmountDefault ?? '',
                    'daybookDescriptionDefault' => $daybookDescriptionDefault ?? '',
                ])
            </div>
            <div class="daybook-form-inner pt-0 pb-4">
                <button type="submit" form="daybook-entry-form" class="daybook-save-record text-nowrap" id="daybook-save-record-btn" aria-label="Save changes">
                    <span class="daybook-save-record__idle">
                        <i class="bi bi-floppy2 app-sidebar-logout__icon" aria-hidden="true"></i>
                        <span>Save changes</span>
                    </span>
                    <span class="daybook-save-record__busy" aria-hidden="true">
                        <span class="daybook-save-spinner" role="status" aria-hidden="true"></span>
                        <span>Saving…</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
@include('daybook.partials.entry-modals')
@endpush

@push('scripts')
@include('daybook.partials.entry-form-scripts')
@endpush
