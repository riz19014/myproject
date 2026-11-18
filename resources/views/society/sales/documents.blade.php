@extends('layouts.app')

@section('title', 'Documents — '.($booking->plot?->plot_number ?? '#'.$booking->id))

@section('content')
@include('society.partials.nav')

@php
    $fileNo = $booking->plot?->plot_number ?? ('#'.$booking->id);
    $buyer = $booking->party?->name ?: ($booking->customer?->name ?? '—');
    $canManage = auth()->user()?->canManageSociety();
    $docsCount = $booking->documents->count();
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Documents</h1>
        <p class="text-muted small mb-0">
            File <strong>{{ $fileNo }}</strong>
            · {{ $buyer }}
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('society.sales.show', [$project, $booking]) }}" class="btn btn-sm btn-outline-theme">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back to file
        </a>
        <a href="{{ route('society.sales.payment-sheet', [$project, $booking]) }}" class="btn btn-sm btn-outline-theme">
            <i class="bi bi-receipt me-1" aria-hidden="true"></i>Payment sheet
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger mb-4">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($canManage)
    <div class="card card-theme ss-docs-upload-card mb-4">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-3">
                <div class="ss-docs-upload-icon" aria-hidden="true">
                    <i class="bi bi-cloud-arrow-up"></i>
                </div>
                <div>
                    <h2 class="h5 mb-1">Upload documents</h2>
                    <p class="text-muted small mb-0">PDF, images, Word or Excel · max <strong>50 MB</strong> each · multiple files allowed.</p>
                </div>
            </div>

            <form method="post" action="{{ route('society.sales.documents.store', [$project, $booking]) }}" enctype="multipart/form-data" id="ss-docs-form">
                @csrf
                <label class="ss-docs-dropzone" id="ss-docs-dropzone" for="ss-docs-input">
                    <input type="file" name="documents[]" id="ss-docs-input" class="ss-docs-file-input" multiple required
                           accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx">
                    <span class="ss-docs-dropzone-inner">
                        <span class="ss-docs-dropzone-glyph" aria-hidden="true">
                            <i class="bi bi-folder2-open"></i>
                        </span>
                        <span class="fw-semibold d-block mb-1">Drop files here or click to browse</span>
                        <span class="small text-muted" id="ss-docs-hint">No files selected yet</span>
                    </span>
                </label>

                <ul class="ss-docs-selected list-unstyled mb-0 d-none" id="ss-docs-selected" aria-live="polite"></ul>

                <div class="mt-3 d-flex flex-wrap align-items-center gap-2">
                    <button type="submit" class="btn btn-pink" id="ss-docs-submit" disabled>
                        <i class="bi bi-upload me-1" aria-hidden="true"></i>Upload files
                    </button>
                    <button type="button" class="btn btn-outline-theme d-none" id="ss-docs-clear">Clear selection</button>
                </div>
            </form>
        </div>
    </div>
@endif

<div class="card card-theme">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Uploaded documents</h2>
            <span class="badge rounded-pill text-bg-light border">
                {{ $docsCount }} {{ $docsCount === 1 ? 'file' : 'files' }}
            </span>
        </div>

        @if($docsCount === 0)
            <div class="ss-docs-empty text-center py-5">
                <i class="bi bi-inbox display-6 text-muted opacity-50" aria-hidden="true"></i>
                <p class="text-muted mb-0 mt-2">No documents yet.@if($canManage) Drop files above to get started.@endif</p>
            </div>
        @else
            <div class="ss-docs-grid">
                @foreach($booking->documents as $doc)
                    @php
                        $ext = strtolower(pathinfo($doc->name, PATHINFO_EXTENSION));
                        $icon = match (true) {
                            in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'gif'], true) => 'bi-file-earmark-image',
                            $ext === 'pdf' => 'bi-file-earmark-pdf',
                            in_array($ext, ['doc', 'docx'], true) => 'bi-file-earmark-word',
                            in_array($ext, ['xls', 'xlsx'], true) => 'bi-file-earmark-excel',
                            default => 'bi-file-earmark',
                        };
                    @endphp
                    <div class="ss-docs-item">
                        <div class="ss-docs-item__icon" aria-hidden="true">
                            <i class="bi {{ $icon }}"></i>
                        </div>
                        <div class="ss-docs-item__body">
                            <a href="{{ $doc->publicUrl() }}" target="_blank" rel="noopener" class="ss-docs-item__name" title="{{ $doc->name }}">
                                {{ $doc->name }}
                            </a>
                            <div class="ss-docs-item__meta">
                                {{ $doc->created_at?->format('d M Y · H:i') ?? '—' }}
                            </div>
                        </div>
                        <div class="ss-docs-item__actions">
                            <a href="{{ $doc->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme" title="Open">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                            </a>
                            @if($canManage)
                                <form method="post" action="{{ route('society.sales.documents.destroy', [$project, $booking, $doc]) }}"
                                      onsubmit="return confirm('Remove this document?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection

@push('head')
<style>
    .ss-docs-upload-card {
        border: 1px solid var(--border-dark, rgba(0,0,0,.08));
        background: linear-gradient(145deg, var(--card-bg, #fff) 0%, rgba(249, 115, 22, 0.05) 100%);
    }
    .ss-docs-upload-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(249, 115, 22, 0.12);
        color: var(--accent-orange, #f97316);
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .ss-docs-dropzone {
        display: block;
        border: 2px dashed rgba(249, 115, 22, 0.35);
        border-radius: 14px;
        padding: 2.25rem 1.5rem;
        text-align: center;
        cursor: pointer;
        background: var(--input-bg, rgba(0, 0, 0, 0.015));
        transition: border-color .2s ease, background .2s ease, box-shadow .2s ease;
        margin-bottom: 0;
    }
    .ss-docs-dropzone:hover,
    .ss-docs-dropzone.is-active {
        border-color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.06);
        box-shadow: inset 0 0 0 1px rgba(249, 115, 22, 0.12);
    }
    .ss-docs-dropzone-glyph {
        display: inline-flex;
        width: 3.25rem;
        height: 3.25rem;
        border-radius: 999px;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.85rem;
        background: rgba(249, 115, 22, 0.1);
        color: var(--accent-orange, #f97316);
        font-size: 1.4rem;
    }
    .ss-docs-file-input {
        position: absolute;
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        z-index: -1;
    }
    .ss-docs-selected {
        margin-top: 1rem;
        display: grid;
        gap: 0.5rem;
    }
    .ss-docs-selected-chip {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.55rem 0.75rem;
        border-radius: 10px;
        border: 1px solid var(--border-dark, rgba(0,0,0,.08));
        background: var(--card-bg, #fff);
        font-size: 0.9rem;
    }
    .ss-docs-selected-chip i {
        color: var(--accent-orange, #f97316);
        font-size: 1.1rem;
    }
    .ss-docs-selected-chip__name {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        font-weight: 500;
    }
    .ss-docs-selected-chip__size {
        color: var(--bs-secondary-color, #6c757d);
        font-size: 0.8rem;
        white-space: nowrap;
    }
    .ss-docs-empty {
        border: 1px dashed var(--border-dark, rgba(0,0,0,.12));
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.02);
    }
    .ss-docs-grid {
        display: grid;
        gap: 0.75rem;
    }
    .ss-docs-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.85rem 1rem;
        border: 1px solid var(--border-dark, rgba(0,0,0,.08));
        border-radius: 12px;
        background: var(--card-bg, #fff);
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .ss-docs-item:hover {
        border-color: rgba(249, 115, 22, 0.35);
        box-shadow: 0 0.35rem 1rem rgba(0, 0, 0, 0.04);
    }
    .ss-docs-item__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(249, 115, 22, 0.1);
        color: var(--accent-orange, #f97316);
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    .ss-docs-item__body {
        flex: 1;
        min-width: 0;
    }
    .ss-docs-item__name {
        display: block;
        font-weight: 600;
        color: inherit;
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ss-docs-item__name:hover {
        color: var(--accent-orange, #f97316);
    }
    .ss-docs-item__meta {
        font-size: 0.8rem;
        color: var(--bs-secondary-color, #6c757d);
        margin-top: 0.15rem;
    }
    .ss-docs-item__actions {
        display: flex;
        gap: 0.35rem;
        flex-shrink: 0;
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var input = document.getElementById('ss-docs-input');
    var dropzone = document.getElementById('ss-docs-dropzone');
    var hint = document.getElementById('ss-docs-hint');
    var selected = document.getElementById('ss-docs-selected');
    var submitBtn = document.getElementById('ss-docs-submit');
    var clearBtn = document.getElementById('ss-docs-clear');
    if (!input || !dropzone) return;

    function formatBytes(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function fileIcon(name) {
        var n = (name || '').toLowerCase();
        if (/\.(png|jpe?g|webp|gif)$/.test(n)) return 'bi-file-earmark-image';
        if (/\.pdf$/.test(n)) return 'bi-file-earmark-pdf';
        if (/\.docx?$/.test(n)) return 'bi-file-earmark-word';
        if (/\.xlsx?$/.test(n)) return 'bi-file-earmark-excel';
        return 'bi-file-earmark';
    }

    function refresh() {
        var files = Array.from(input.files || []);
        selected.innerHTML = '';
        if (!files.length) {
            selected.classList.add('d-none');
            clearBtn.classList.add('d-none');
            submitBtn.disabled = true;
            hint.textContent = 'No files selected yet';
            return;
        }

        selected.classList.remove('d-none');
        clearBtn.classList.remove('d-none');
        submitBtn.disabled = false;
        hint.textContent = files.length + ' file' + (files.length === 1 ? '' : 's') + ' ready to upload';

        files.forEach(function (file) {
            var li = document.createElement('li');
            li.className = 'ss-docs-selected-chip';
            li.innerHTML =
                '<i class="bi ' + fileIcon(file.name) + '" aria-hidden="true"></i>' +
                '<span class="ss-docs-selected-chip__name"></span>' +
                '<span class="ss-docs-selected-chip__size"></span>';
            li.querySelector('.ss-docs-selected-chip__name').textContent = file.name;
            li.querySelector('.ss-docs-selected-chip__size').textContent = formatBytes(file.size);
            selected.appendChild(li);
        });
    }

    input.addEventListener('change', refresh);

    ;['dragenter', 'dragover'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.add('is-active');
        });
    });
    ;['dragleave', 'drop'].forEach(function (evt) {
        dropzone.addEventListener(evt, function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropzone.classList.remove('is-active');
        });
    });
    dropzone.addEventListener('drop', function (e) {
        var dt = e.dataTransfer;
        if (!dt || !dt.files || !dt.files.length) return;
        var transfer = new DataTransfer();
        Array.from(dt.files).forEach(function (f) { transfer.items.add(f); });
        input.files = transfer.files;
        refresh();
    });

    clearBtn.addEventListener('click', function () {
        input.value = '';
        refresh();
    });
})();
</script>
@endpush
