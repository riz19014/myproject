@extends('layouts.app')

@section('title', 'Documents — '.$purchase_file->file_name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Documents</h1>
        <p class="text-muted small mb-0">
            File: <strong>{{ $purchase_file->file_name }}</strong>
            @if($purchase_file->file_date)
                · Date: <strong>{{ $purchase_file->file_date->format('d M Y') }}</strong>
            @endif
            · Project: <strong>{{ $purchase_file->project->name }}</strong>
        </p>
    </div>
    <a href="{{ route('purchase.files.index') }}" class="btn btn-outline-theme">Back to files</a>
</div>

{{-- Upload section --}}
<div class="card card-theme pf-docs-upload-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-3">
            <div class="pf-docs-upload-icon" aria-hidden="true">
                <i class="bi bi-cloud-arrow-up"></i>
            </div>
            <div>
                <h2 class="h5 mb-1">Upload documents</h2>
                <p class="text-muted small mb-0">Any file type · maximum <strong>1 MB</strong> per file · you can select multiple files at once.</p>
            </div>
        </div>

        <form id="pf-docs-upload-form" class="pf-docs-upload-form" enctype="multipart/form-data" novalidate>
            @csrf
            <label class="pf-docs-dropzone" id="pf-docs-dropzone" for="pf-docs-file-input">
                <input type="file" name="documents[]" id="pf-docs-file-input" class="pf-docs-file-input" multiple>
                <span class="pf-docs-dropzone-inner">
                    <i class="bi bi-folder2-open d-block mb-2" style="font-size: 1.75rem; opacity: 0.7;"></i>
                    <span class="fw-semibold d-block">Choose files or drag here</span>
                    <span class="small text-muted" id="pf-docs-file-hint">No files selected</span>
                </span>
            </label>

            <div id="pf-docs-progress-wrap" class="pf-docs-progress-wrap d-none" aria-live="polite">
                <div class="d-flex justify-content-between align-items-center small mb-1">
                    <span id="pf-docs-progress-label" class="fw-semibold">Uploading…</span>
                    <span id="pf-docs-progress-pct" class="text-muted">0%</span>
                </div>
                <div class="progress pf-docs-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" id="pf-docs-progress-bar" style="width: 0%"></div>
                </div>
            </div>

            <div id="pf-docs-upload-error" class="alert alert-theme-danger small py-2 mt-3 mb-0 d-none" role="alert"></div>

            <div class="mt-3">
                <button type="submit" class="btn btn-pink" id="pf-docs-upload-btn">
                    <span class="pf-docs-btn-text">Upload files</span>
                    <span class="spinner-border spinner-border-sm ms-1 d-none" id="pf-docs-upload-spinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Documents list --}}
<div class="card card-theme">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0">Uploaded documents</h2>
            <span class="badge rounded-pill text-bg-light border" id="pf-docs-count-badge">{{ $purchase_file->documents->count() }} {{ $purchase_file->documents->count() === 1 ? 'file' : 'files' }}</span>
        </div>

        <div id="pf-docs-empty" class="pf-docs-empty text-center py-5 @if($purchase_file->documents->isNotEmpty()) d-none @endif">
            <i class="bi bi-inbox display-6 text-muted opacity-50" aria-hidden="true"></i>
            <p class="text-muted mb-0 mt-2">No documents yet. Upload files using the form above.</p>
        </div>

        <div class="table-responsive @if($purchase_file->documents->isEmpty()) d-none @endif" id="pf-docs-table-wrap">
            <table class="table table-striped table-theme mb-0 align-middle pf-docs-table">
                <thead>
                    <tr>
                        <th>File name</th>
                        <th style="width: 100px;">Size</th>
                        <th style="width: 160px;">Uploaded</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="pf-docs-tbody">
                    @foreach($purchase_file->documents as $doc)
                        @include('purchases.files.partials.document-row', ['purchase_file' => $purchase_file, 'doc' => $doc])
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('head')
<style>
    .pf-docs-upload-card {
        border: 1px solid var(--border-dark);
        background: linear-gradient(145deg, var(--card-bg) 0%, rgba(249, 115, 22, 0.04) 100%);
    }
    .pf-docs-upload-icon {
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
    .pf-docs-dropzone {
        display: block;
        border: 2px dashed rgba(249, 115, 22, 0.35);
        border-radius: 12px;
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        background: var(--input-bg, rgba(0, 0, 0, 0.02));
        transition: border-color 0.2s ease, background 0.2s ease;
        margin-bottom: 0;
    }
    .pf-docs-dropzone:hover,
    .pf-docs-dropzone.pf-docs-dropzone--active {
        border-color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.06);
    }
    .pf-docs-file-input {
        position: absolute;
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        z-index: -1;
    }
    .pf-docs-progress {
        height: 0.65rem;
        border-radius: 999px;
        background: rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }
    .pf-docs-progress .progress-bar {
        background: linear-gradient(90deg, #fb923c, #f97316);
        transition: width 0.15s ease;
    }
    .pf-docs-progress-wrap {
        margin-top: 1.25rem;
    }
    .pf-docs-table .pf-doc-icon {
        color: var(--accent-orange, #f97316);
        margin-right: 0.35rem;
    }
    .pf-docs-empty {
        border: 1px dashed var(--border-dark);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.02);
    }
</style>
@endpush

@push('scripts')
<script>
(function() {
    var MAX_BYTES = 1024 * 1024;
    var uploadUrl = @json(route('purchase.files.documents.store', $purchase_file));
    var destroyUrlTemplate = @json(route('purchase.files.documents.destroy', [$purchase_file, '__DOC__']));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    var form = document.getElementById('pf-docs-upload-form');
    var input = document.getElementById('pf-docs-file-input');
    var dropzone = document.getElementById('pf-docs-dropzone');
    var hint = document.getElementById('pf-docs-file-hint');
    var progressWrap = document.getElementById('pf-docs-progress-wrap');
    var progressBar = document.getElementById('pf-docs-progress-bar');
    var progressPct = document.getElementById('pf-docs-progress-pct');
    var progressLabel = document.getElementById('pf-docs-progress-label');
    var errorEl = document.getElementById('pf-docs-upload-error');
    var uploadBtn = document.getElementById('pf-docs-upload-btn');
    var spinner = document.getElementById('pf-docs-upload-spinner');
    var tbody = document.getElementById('pf-docs-tbody');
    var emptyEl = document.getElementById('pf-docs-empty');
    var tableWrap = document.getElementById('pf-docs-table-wrap');
    var countBadge = document.getElementById('pf-docs-count-badge');

    function setError(msg) {
        if (!errorEl) return;
        if (!msg) {
            errorEl.classList.add('d-none');
            errorEl.textContent = '';
            return;
        }
        errorEl.textContent = msg;
        errorEl.classList.remove('d-none');
    }

    function updateHint() {
        var n = input.files ? input.files.length : 0;
        if (n === 0) {
            hint.textContent = 'No files selected';
            return;
        }
        var names = [];
        for (var i = 0; i < input.files.length; i++) {
            names.push(input.files[i].name);
        }
        hint.textContent = n === 1 ? names[0] : n + ' files: ' + names.join(', ');
    }

    function validateFiles(files) {
        for (var i = 0; i < files.length; i++) {
            if (files[i].size > MAX_BYTES) {
                return '"' + files[i].name + '" exceeds 1 MB. Please choose a smaller file.';
            }
        }
        return null;
    }

    function setProgress(pct, label) {
        progressWrap.classList.remove('d-none');
        progressBar.style.width = pct + '%';
        progressBar.setAttribute('aria-valuenow', String(pct));
        progressPct.textContent = pct + '%';
        if (label) progressLabel.textContent = label;
        if (pct >= 100) {
            progressBar.classList.remove('progress-bar-animated');
        } else {
            progressBar.classList.add('progress-bar-animated');
        }
    }

    function resetProgress() {
        progressWrap.classList.add('d-none');
        progressBar.style.width = '0%';
        progressBar.setAttribute('aria-valuenow', '0');
        progressPct.textContent = '0%';
        progressLabel.textContent = 'Uploading…';
        progressBar.classList.add('progress-bar-animated');
    }

    function updateCount(delta) {
        var n = tbody.querySelectorAll('tr').length;
        countBadge.textContent = n + (n === 1 ? ' file' : ' files');
        if (n === 0) {
            emptyEl.classList.remove('d-none');
            tableWrap.classList.add('d-none');
        } else {
            emptyEl.classList.add('d-none');
            tableWrap.classList.remove('d-none');
        }
    }

    function appendRow(doc) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-doc-id', String(doc.id));
        var destroyUrl = destroyUrlTemplate.replace('__DOC__', String(doc.id));
        tr.innerHTML =
            '<td><i class="bi bi-file-earmark pf-doc-icon" aria-hidden="true"></i>' + escapeHtml(doc.name) + '</td>' +
            '<td class="small text-muted">' + escapeHtml(doc.size_label || '—') + '</td>' +
            '<td class="small text-muted">' + escapeHtml(doc.created_at) + '</td>' +
            '<td class="text-end text-nowrap">' +
            '<a href="' + escapeHtml(doc.url) + '" target="_blank" rel="noopener" class="btn btn-sm btn-outline-theme me-1" title="Open"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i></a>' +
            '<form action="' + escapeHtml(destroyUrl) + '" method="post" class="d-inline pf-doc-delete-form">' +
            '<input type="hidden" name="_token" value="' + escapeHtml(csrf) + '">' +
            '<input type="hidden" name="_method" value="DELETE">' +
            '<button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm" data-title="Remove document?" title="Remove"><i class="bi bi-trash" aria-hidden="true"></i></button>' +
            '</form></td>';
        tbody.insertBefore(tr, tbody.firstChild);
        updateCount(1);
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    input.addEventListener('change', function() {
        setError(null);
        updateHint();
    });

    ['dragenter', 'dragover'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault();
            dropzone.classList.add('pf-docs-dropzone--active');
        });
    });
    ['dragleave', 'drop'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault();
            dropzone.classList.remove('pf-docs-dropzone--active');
        });
    });
    dropzone.addEventListener('drop', function(e) {
        if (e.dataTransfer?.files?.length) {
            input.files = e.dataTransfer.files;
            updateHint();
        }
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        setError(null);
        var files = input.files;
        if (!files || !files.length) {
            setError('Please choose at least one file.');
            return;
        }
        var err = validateFiles(files);
        if (err) {
            setError(err);
            return;
        }

        var formData = new FormData();
        for (var i = 0; i < files.length; i++) {
            formData.append('documents[]', files[i]);
        }

        uploadBtn.disabled = true;
        spinner.classList.remove('d-none');
        setProgress(0, 'Uploading…');

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', function(ev) {
            if (ev.lengthComputable) {
                var pct = Math.min(99, Math.round((ev.loaded / ev.total) * 100));
                setProgress(pct, 'Uploading…');
            }
        });

        xhr.onload = function() {
            uploadBtn.disabled = false;
            spinner.classList.add('d-none');
            var data;
            try {
                data = JSON.parse(xhr.responseText);
            } catch (ex) {
                data = null;
            }
            if (xhr.status >= 200 && xhr.status < 300 && data && data.documents) {
                setProgress(100, 'Complete');
                data.documents.forEach(appendRow);
                input.value = '';
                updateHint();
                setTimeout(resetProgress, 800);
                if (typeof window.showAppToast === 'function') {
                    window.showAppToast(data.message || 'Uploaded.');
                }
                return;
            }
            resetProgress();
            var msg = 'Upload failed. Please try again.';
            if (data && data.message) msg = data.message;
            if (data && data.errors) {
                var parts = [];
                Object.keys(data.errors).forEach(function(k) {
                    parts = parts.concat(data.errors[k]);
                });
                if (parts.length) msg = parts.join(' ');
            }
            setError(msg);
        };

        xhr.onerror = function() {
            uploadBtn.disabled = false;
            spinner.classList.add('d-none');
            resetProgress();
            setError('Network error. Please try again.');
        };

        xhr.send(formData);
    });

    tbody.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-delete-confirm');
        if (!btn || !tbody.contains(btn)) return;
        e.preventDefault();
        var delForm = btn.closest('form');
        if (!delForm) return;
        Swal.fire({
            title: btn.dataset.title || 'Are you sure?',
            text: btn.dataset.text || 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f97316',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, remove',
            cancelButtonText: 'Cancel',
            background: '#fff',
            color: '#0f172a',
            customClass: { popup: 'swal-light', title: 'swal-title', htmlContainer: 'swal-text', confirmButton: 'swal-confirm', cancelButton: 'swal-cancel' }
        }).then(function(result) {
            if (result.isConfirmed) delForm.submit();
        });
    });
})();
</script>
@endpush
