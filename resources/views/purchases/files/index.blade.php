@extends('layouts.app')

@section('title', 'Purchase files')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Purchase files</h1>
        <p class="text-muted small mb-0">Named files per project. Add <strong>sellers</strong> (party, land, amount) against each file.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('purchase.files.create', $projectId ? ['project' => $projectId] : []) }}" class="btn btn-pink">Add purchase file</a>
        @if($projectId)
            <a href="{{ route('projects.show', $projectId) }}" class="btn btn-outline-theme">Back to project</a>
        @endif
        <a href="{{ route('purchase.index') }}" class="btn btn-outline-theme">Back to Purchase</a>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <form method="get" action="{{ route('purchase.files.index') }}" id="purchase-files-filter-form" class="row g-2 align-items-end">
            <div class="col-md-6 col-lg-4">
                <label for="project" class="form-label">Filter by project</label>
                <select name="project" id="project" class="form-select form-select-theme">
                    <option value="">All projects</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" @selected((string) $projectId === (string) $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-5">
                <label for="q" class="form-label">Search</label>
                <input type="search" name="q" id="q" class="form-control form-control-theme" value="{{ $search ?? '' }}" placeholder="File name, project, seller or dealer party…" autocomplete="off">
            </div>
            <div class="col-md-12 col-lg-3 d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-pink flex-grow-1" id="purchase-files-search-btn">
                    <span class="spinner-border spinner-border-sm me-1 d-none" id="purchase-files-search-spinner" role="status" aria-hidden="true"></span>
                    Search
                </button>
                @if(($search ?? '') !== '' || $projectId)
                    <a href="{{ route('purchase.files.index') }}" class="btn btn-outline-theme">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        @if($files->isEmpty())
            @if(($search ?? '') !== '' || $projectId)
                <p class="text-muted mb-0">No purchase files match your filters. <a href="{{ route('purchase.files.index') }}">Clear filters</a> or <a href="{{ route('purchase.files.create') }}">add a file</a>.</p>
            @else
                <p class="text-muted mb-0">No purchase files yet. <a href="{{ route('purchase.files.create') }}">Create one</a>, then add sellers.</p>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>File name</th>
                            <th style="width: 110px;">File date</th>
                            <th>Project</th>
                            <th class="text-center" style="width: 90px;">Sellers</th>
                            <th class="text-center" style="width: 110px;">Documents</th>
                            <th style="width: 200px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                            @php
                                $fileTotalMarla = (float) $file->purchaseItems->sum('land_area_marla');
                                $fileTotalRs = (float) $file->purchaseItems->sum('line_total_rs');
                                $fileSellerNames = $file->purchaseItems
                                    ->map(fn ($item) => $item->party?->name)
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $fileMozas = $file->purchaseItems
                                    ->map(fn ($item) => trim((string) ($item->moza ?? '')))
                                    ->filter()
                                    ->unique()
                                    ->values();
                                $fileKhasras = $file->purchaseItems
                                    ->map(fn ($item) => trim((string) ($item->khasra ?? '')))
                                    ->filter()
                                    ->unique()
                                    ->values();
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    {{ $file->file_name }}
                                    @if($file->isSaleLand())
                                        <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 ms-1" title="Marked as sale land on {{ $file->sale_land_at->format('d M Y') }}">Sale land</span>
                                    @endif
                                </td>
                                <td class="small text-nowrap">{{ $file->file_date?->format('d M Y') ?? '—' }}</td>
                                <td class="small">{{ $file->project?->name ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.sellers', $file) }}" class="purchase-sellers-badge" title="View or add sellers for this file">
                                        <i class="bi bi-people" aria-hidden="true"></i>
                                        <span>{{ $file->purchase_items_count }}</span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('purchase.files.documents', $file) }}" class="btn btn-sm btn-outline-theme py-0 px-2" title="Upload or view documents">
                                        <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                        <span class="ms-1">Upload</span>
                                        @if($file->documents_count > 0)
                                            <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark border ms-1">{{ $file->documents_count }}</span>
                                        @endif
                                    </a>
                                </td>
                                <td class="text-nowrap">
                                    @if($file->isSaleLand())
                                        <a href="{{ route('projects.sale-land', $file->project_id) }}" class="btn btn-sm btn-outline-secondary" title="Already marked on {{ $file->sale_land_at->format('d M Y') }}">View sale land</a>
                                    @else
                                        <button type="button"
                                            class="btn btn-sm btn-outline-theme btn-sale-land-confirm"
                                            data-form-id="sale-land-form-{{ $file->id }}"
                                            data-file-name="{{ $file->file_name }}"
                                            data-file-date="{{ $file->file_date?->format('d M Y') ?? '—' }}"
                                            data-project-name="{{ $file->project?->name ?? '—' }}"
                                            data-sellers-count="{{ $file->purchase_items_count }}"
                                            data-land-area="{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($fileTotalMarla) }}"
                                            data-total-rs="{{ number_format($fileTotalRs, 0) }}"
                                            data-seller-names="{{ $fileSellerNames->isEmpty() ? '—' : $fileSellerNames->implode(', ') }}"
                                            data-mozas="{{ $fileMozas->isEmpty() ? '—' : $fileMozas->implode(', ') }}"
                                            data-khasras="{{ $fileKhasras->isEmpty() ? '—' : $fileKhasras->implode(', ') }}"
                                            data-documents-count="{{ $file->documents_count }}">Sale land</button>
                                        <form id="sale-land-form-{{ $file->id }}" method="post" action="{{ route('purchase.files.sale-land', $file) }}" class="d-none">
                                            @csrf
                                        </form>
                                    @endif
                                    <a href="{{ route('purchase.files.edit', $file) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                    <form action="{{ route('purchase.files.destroy', $file) }}" method="post" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Delete purchase file?" data-text="All sellers and documents on this file will be deleted too.">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection

@push('head')
<style>
    .purchase-sellers-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        padding: 0.12rem 0.4rem;
        font-size: 0.7rem;
        font-weight: 600;
        line-height: 1;
        border-radius: 999px;
        text-decoration: none;
        color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.1);
        border: 1px solid rgba(249, 115, 22, 0.22);
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .purchase-sellers-badge:hover {
        background: rgba(249, 115, 22, 0.16);
        border-color: rgba(249, 115, 22, 0.35);
        color: var(--accent-orange, #f97316);
    }
    .purchase-sellers-badge .bi {
        font-size: 0.68rem;
        line-height: 1;
        opacity: 0.85;
    }
    .swal-sale-land-popup {
        width: 52rem !important;
        max-width: calc(100vw - 2rem) !important;
    }
    .swal-sale-land-popup .swal2-icon {
        margin: 0.75rem 0 0.35rem;
        transform: scale(0.85);
    }
    .swal-sale-land-popup .swal2-actions {
        margin-top: 0.75rem;
    }
    .sale-land-swal-details {
        text-align: left;
        margin-top: 0.15rem;
    }
    .sale-land-swal-details__prompt {
        color: #64748b;
        font-size: 0.92rem;
        margin-bottom: 0.75rem;
        text-align: center;
    }
    .sale-land-swal-details__card {
        background: #f8fafc;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        padding: 0.85rem 1rem;
    }
    .sale-land-swal-details__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding-bottom: 0.7rem;
        margin-bottom: 0.7rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .sale-land-swal-details__file-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.3;
        min-width: 0;
    }
    .sale-land-swal-details__header-meta {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem 1.25rem;
        flex-shrink: 0;
    }
    .sale-land-swal-details__row {
        display: flex;
        align-items: stretch;
        flex-wrap: wrap;
        gap: 0.65rem;
    }
    .sale-land-swal-details__row + .sale-land-swal-details__row {
        margin-top: 0.65rem;
    }
    .sale-land-swal-details__item {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        min-width: 0;
        flex: 1 1 auto;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 8px;
        padding: 0.5rem 0.7rem;
    }
    .sale-land-swal-details__item--wide {
        flex: 1 1 100%;
    }
    .sale-land-swal-details__item--third {
        flex: 1 1 calc(33.333% - 0.45rem);
        min-width: 10rem;
    }
    .sale-land-swal-details__label {
        flex: 0 0 auto;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #94a3b8;
        white-space: nowrap;
    }
    .sale-land-swal-details__label::after {
        content: ':';
        margin-left: 0.1rem;
    }
    .sale-land-swal-details__value {
        flex: 1 1 auto;
        font-size: 0.88rem;
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
        min-width: 0;
    }
    .sale-land-swal-details__value--highlight {
        color: #f97316;
    }
    @media (max-width: 768px) {
        .sale-land-swal-details__header {
            flex-direction: column;
            align-items: flex-start;
        }
        .sale-land-swal-details__item--third {
            flex: 1 1 100%;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var form = document.getElementById('purchase-files-filter-form');
    var btn = document.getElementById('purchase-files-search-btn');
    var spinner = document.getElementById('purchase-files-search-spinner');
    if (form && btn && spinner) {
        form.addEventListener('submit', function() {
            btn.disabled = true;
            spinner.classList.remove('d-none');
        });
    }

    function escapeHtml(value) {
        return String(value || '—')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function saleLandDetailItem(label, value, highlight) {
        var valueClass = 'sale-land-swal-details__value' + (highlight ? ' sale-land-swal-details__value--highlight' : '');
        return '' +
            '<div class="sale-land-swal-details__item">' +
                '<span class="sale-land-swal-details__label">' + escapeHtml(label) + '</span>' +
                '<span class="' + valueClass + '">' + escapeHtml(value) + '</span>' +
            '</div>';
    }

    function buildSaleLandSwalHtml(link) {
        var d = link.dataset;
        return '' +
            '<div class="sale-land-swal-details">' +
                '<div class="sale-land-swal-details__prompt">Are you sure you want to make it sale land?</div>' +
                '<div class="sale-land-swal-details__card">' +
                    '<div class="sale-land-swal-details__header">' +
                        '<div class="sale-land-swal-details__file-name">' + escapeHtml(d.fileName) + '</div>' +
                        '<div class="sale-land-swal-details__header-meta">' +
                            saleLandDetailItem('Project', d.projectName) +
                            saleLandDetailItem('File date', d.fileDate) +
                        '</div>' +
                    '</div>' +
                    '<div class="sale-land-swal-details__row">' +
                        saleLandDetailItem('Land area', d.landArea, true) +
                        saleLandDetailItem('Land total (Rs)', d.totalRs, true) +
                        saleLandDetailItem('Sellers', d.sellersCount) +
                        saleLandDetailItem('Documents', d.documentsCount) +
                    '</div>' +
                    '<div class="sale-land-swal-details__row">' +
                        '<div class="sale-land-swal-details__item sale-land-swal-details__item--third">' +
                            '<span class="sale-land-swal-details__label">Owner / seller</span>' +
                            '<span class="sale-land-swal-details__value">' + escapeHtml(d.sellerNames) + '</span>' +
                        '</div>' +
                        '<div class="sale-land-swal-details__item sale-land-swal-details__item--third">' +
                            '<span class="sale-land-swal-details__label">Moza</span>' +
                            '<span class="sale-land-swal-details__value">' + escapeHtml(d.mozas) + '</span>' +
                        '</div>' +
                        '<div class="sale-land-swal-details__item sale-land-swal-details__item--third">' +
                            '<span class="sale-land-swal-details__label">Khasra</span>' +
                            '<span class="sale-land-swal-details__value">' + escapeHtml(d.khasras) + '</span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    document.querySelectorAll('.btn-sale-land-confirm').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var formId = this.dataset.formId;
            Swal.fire({
                title: 'Sale land?',
                html: buildSaleLandSwalHtml(this),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f97316',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, continue',
                cancelButtonText: 'Cancel',
                background: '#fff',
                color: '#0f172a',
                customClass: {
                    popup: 'swal-light swal-sale-land-popup',
                    title: 'swal-title',
                    htmlContainer: 'swal-text',
                    confirmButton: 'swal-confirm',
                    cancelButton: 'swal-cancel'
                }
            }).then(function(result) {
                if (result.isConfirmed && formId) {
                    var form = document.getElementById(formId);
                    if (form) {
                        form.submit();
                    }
                }
            });
        });
    });
})();
</script>
@endpush
