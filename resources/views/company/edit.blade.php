@extends('layouts.app')

@section('title', 'Edit Company')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="mb-1">Edit Company</h1>
        <p class="text-muted small mb-0">{{ $company->name }}</p>
    </div>
    <a href="{{ route('companies.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<div class="card card-theme mb-4 company-logo-card">
    <div class="card-body p-4">
        <div class="d-flex align-items-start gap-3 mb-4">
            <div class="company-logo-card__icon" aria-hidden="true">
                <i class="bi bi-image"></i>
            </div>
            <div>
                <h2 class="h5 mb-1">Company logo</h2>
                <p class="text-muted small mb-0">JPEG, PNG, GIF, or WebP · maximum <strong>2 MB</strong>.</p>
            </div>
        </div>

        <div class="row g-4 align-items-stretch">
            <div class="col-lg-7">
                <label class="company-logo-dropzone" id="company-logo-dropzone" for="company_logo">
                    <input type="file"
                           class="company-logo-file-input @error('logo') is-invalid @enderror"
                           id="company_logo"
                           name="logo"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                    <span class="company-logo-dropzone__inner">
                        <i class="bi bi-cloud-arrow-up company-logo-dropzone__icon" aria-hidden="true"></i>
                        <span class="fw-semibold d-block">Choose logo or drag here</span>
                        <span class="small text-muted" id="company-logo-hint">No file selected</span>
                    </span>
                </label>
                @error('logo')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror

                <div id="company-logo-upload-error" class="alert alert-theme-danger small py-2 mt-3 mb-0 d-none" role="alert"></div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <button type="button" class="btn btn-pink" id="company-logo-upload-btn" disabled>
                        <span class="company-logo-upload-btn__text">Upload logo</span>
                        <span class="spinner-border spinner-border-sm ms-1 d-none" id="company-logo-upload-spinner" role="status" aria-hidden="true"></span>
                    </button>
                    <span class="small text-muted">Upload saves the logo immediately.</span>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="company-logo-preview">
                    <div class="company-logo-preview__header">
                        <span class="company-logo-preview__title">Current logo</span>
                        <button type="button"
                                class="btn btn-sm btn-outline-theme @if(!$company->logo_path) d-none @endif"
                                id="company-logo-remove-btn">
                            Remove
                        </button>
                    </div>
                    <div class="company-logo-preview__frame" id="company-logo-preview-frame">
                        <div class="company-logo-preview__spinner d-none" id="company-logo-preview-spinner" aria-hidden="true">
                            <span class="spinner-border text-warning" role="status"></span>
                            <span class="small text-muted mt-2">Uploading…</span>
                        </div>
                        @if($company->logo_path)
                            <img src="{{ route('companies.logo.show', $company) }}?v={{ $company->updated_at?->timestamp }}"
                                 alt="Company logo"
                                 class="company-logo-preview__img"
                                 id="company-logo-preview-img">
                            <div class="company-logo-preview__empty d-none" id="company-logo-preview-empty">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>No logo uploaded</span>
                            </div>
                        @else
                            <img src=""
                                 alt="Company logo"
                                 class="company-logo-preview__img d-none"
                                 id="company-logo-preview-img">
                            <div class="company-logo-preview__empty" id="company-logo-preview-empty">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>No logo uploaded</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Company details</h2>
        <p class="text-muted small mb-4">Used across the application (reports, printouts, etc.).</p>

        <form action="{{ route('companies.update', $company) }}" method="POST" id="company-details-form">
            @csrf
            @method('PUT')
            @include('company.partials.details-fields', ['company' => $company])
            <div class="mt-4">
                <button type="submit" class="btn btn-pink" id="company-details-save-btn">
                    <span class="company-details-save-btn__text">Save company</span>
                    <span class="spinner-border spinner-border-sm ms-1 d-none" id="company-details-save-spinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@include('company.partials.logo-styles')

@push('scripts')
<script>
(function() {
    var MAX_BYTES = 2 * 1024 * 1024;
    var uploadUrl = @json(route('companies.logo.store', $company));
    var removeUrl = @json(route('companies.logo.destroy', $company));
    var logoShowUrl = @json(route('companies.logo.show', $company));
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    var input = document.getElementById('company_logo');
    var dropzone = document.getElementById('company-logo-dropzone');
    var hint = document.getElementById('company-logo-hint');
    var uploadBtn = document.getElementById('company-logo-upload-btn');
    var uploadSpinner = document.getElementById('company-logo-upload-spinner');
    var uploadError = document.getElementById('company-logo-upload-error');
    var previewSpinner = document.getElementById('company-logo-preview-spinner');
    var previewImg = document.getElementById('company-logo-preview-img');
    var previewEmpty = document.getElementById('company-logo-preview-empty');
    var removeBtn = document.getElementById('company-logo-remove-btn');
    var detailsForm = document.getElementById('company-details-form');
    var saveBtn = document.getElementById('company-details-save-btn');
    var saveSpinner = document.getElementById('company-details-save-spinner');

    var selectedFile = null;

    function setUploadError(message) {
        if (!message) {
            uploadError.classList.add('d-none');
            uploadError.textContent = '';
            return;
        }
        uploadError.textContent = message;
        uploadError.classList.remove('d-none');
    }

    function setUploading(active) {
        uploadBtn.disabled = active || !selectedFile;
        uploadSpinner.classList.toggle('d-none', !active);
        previewSpinner.classList.toggle('d-none', !active);
        uploadBtn.querySelector('.company-logo-upload-btn__text').textContent = active ? 'Uploading…' : 'Upload logo';
    }

    function updatePreview(url) {
        if (url) {
            previewImg.src = url;
            previewImg.classList.remove('d-none');
            previewEmpty.classList.add('d-none');
            removeBtn.classList.remove('d-none');
        } else {
            previewImg.src = '';
            previewImg.classList.add('d-none');
            previewEmpty.classList.remove('d-none');
            removeBtn.classList.add('d-none');
        }
    }

    function syncRemoveButton() {
        if (previewImg.src && !previewImg.classList.contains('d-none')) {
            removeBtn.classList.remove('d-none');
        } else {
            removeBtn.classList.add('d-none');
        }
    }

    function handleFile(file) {
        setUploadError('');
        if (!file) {
            selectedFile = null;
            hint.textContent = 'No file selected';
            uploadBtn.disabled = true;
            return;
        }

        if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/i)) {
            setUploadError('Please choose a JPEG, PNG, GIF, or WebP image.');
            selectedFile = null;
            uploadBtn.disabled = true;
            hint.textContent = 'No file selected';
            return;
        }

        if (file.size > MAX_BYTES) {
            setUploadError('Logo must be 2 MB or smaller.');
            selectedFile = null;
            uploadBtn.disabled = true;
            hint.textContent = 'No file selected';
            return;
        }

        selectedFile = file;
        hint.textContent = file.name;
        uploadBtn.disabled = false;

        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            previewEmpty.classList.add('d-none');
            syncRemoveButton();
        };
        reader.readAsDataURL(file);
    }

    if (input) {
        input.addEventListener('change', function() {
            handleFile(input.files && input.files[0] ? input.files[0] : null);
        });
    }

    ['dragenter', 'dragover'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault();
            dropzone.classList.add('company-logo-dropzone--active');
        });
    });
    ['dragleave', 'drop'].forEach(function(ev) {
        dropzone.addEventListener(ev, function(e) {
            e.preventDefault();
            dropzone.classList.remove('company-logo-dropzone--active');
        });
    });
    dropzone.addEventListener('drop', function(e) {
        var file = e.dataTransfer.files && e.dataTransfer.files[0] ? e.dataTransfer.files[0] : null;
        if (file && input) {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        }
        handleFile(file);
    });

    function uploadLogo(formData) {
        setUploading(true);
        setUploadError('');

        return fetch(uploadUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok) {
                    var msg = data.message;
                    if (!msg && data.errors) {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    throw new Error(msg || 'Upload failed.');
                }
                return data;
            });
        });
    }

    uploadBtn.addEventListener('click', function() {
        if (!selectedFile) {
            return;
        }

        var formData = new FormData();
        formData.append('logo', selectedFile);

        uploadLogo(formData)
            .then(function(data) {
                var url = data.logo_url ? data.logo_url + '?v=' + Date.now() : null;
                updatePreview(url);
                selectedFile = null;
                input.value = '';
                hint.textContent = 'No file selected';
                uploadBtn.disabled = true;
            })
            .catch(function(err) {
                setUploadError(err.message || 'Upload failed.');
            })
            .finally(function() {
                setUploading(false);
            });
    });

    removeBtn.addEventListener('click', function() {
        if (!window.confirm('Remove the current company logo?')) {
            return;
        }

        setUploading(true);
        fetch(removeUrl, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function(res) {
            return res.json().then(function(data) {
                if (!res.ok) {
                    throw new Error(data.message || 'Could not remove logo.');
                }
                return data;
            });
        })
            .then(function() {
                updatePreview(null);
                selectedFile = null;
                if (input) {
                    input.value = '';
                }
                hint.textContent = 'No file selected';
                uploadBtn.disabled = true;
            })
            .catch(function(err) {
                setUploadError(err.message || 'Could not remove logo.');
            })
            .finally(function() {
                setUploading(false);
            });
    });

    if (detailsForm && saveBtn) {
        detailsForm.addEventListener('submit', function() {
            saveBtn.disabled = true;
            saveSpinner.classList.remove('d-none');
            saveBtn.querySelector('.company-details-save-btn__text').textContent = 'Saving…';
        });
    }

    syncRemoveButton();
})();
</script>
@endpush
