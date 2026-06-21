@extends('layouts.app')

@section('title', 'Add Company')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h1 class="mb-0">Add Company</h1>
    <a href="{{ route('companies.index') }}" class="btn btn-outline-theme">Back to list</a>
</div>

<form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data" id="company-create-form">
    @csrf

    <div class="card card-theme mb-4 company-logo-card">
        <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="company-logo-card__icon" aria-hidden="true">
                    <i class="bi bi-image"></i>
                </div>
                <div>
                    <h2 class="h5 mb-1">Company logo</h2>
                    <p class="text-muted small mb-0">Optional · JPEG, PNG, GIF, or WebP · maximum <strong>2 MB</strong>.</p>
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
                </div>

                <div class="col-lg-5">
                    <div class="company-logo-preview">
                        <div class="company-logo-preview__header">
                            <span class="company-logo-preview__title">Logo preview</span>
                        </div>
                        <div class="company-logo-preview__frame">
                            <img src=""
                                 alt="Logo preview"
                                 class="company-logo-preview__img d-none"
                                 id="company-logo-preview-img">
                            <div class="company-logo-preview__empty" id="company-logo-preview-empty">
                                <i class="bi bi-building" aria-hidden="true"></i>
                                <span>No logo selected</span>
                            </div>
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

            @include('company.partials.details-fields')

            <div class="mt-4">
                <button type="submit" class="btn btn-pink" id="company-create-save-btn">
                    <span class="company-create-save-btn__text">Add company</span>
                    <span class="spinner-border spinner-border-sm ms-1 d-none" id="company-create-save-spinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</form>
@endsection

@include('company.partials.logo-styles')

@push('scripts')
<script>
(function() {
    var MAX_BYTES = 2 * 1024 * 1024;
    var input = document.getElementById('company_logo');
    var dropzone = document.getElementById('company-logo-dropzone');
    var hint = document.getElementById('company-logo-hint');
    var previewImg = document.getElementById('company-logo-preview-img');
    var previewEmpty = document.getElementById('company-logo-preview-empty');
    var form = document.getElementById('company-create-form');
    var saveBtn = document.getElementById('company-create-save-btn');
    var saveSpinner = document.getElementById('company-create-save-spinner');

    function handleFile(file) {
        if (!file) {
            hint.textContent = 'No file selected';
            previewImg.classList.add('d-none');
            previewEmpty.classList.remove('d-none');
            return;
        }

        if (!file.type.match(/^image\/(jpeg|jpg|png|gif|webp)$/i) || file.size > MAX_BYTES) {
            hint.textContent = 'No file selected';
            previewImg.classList.add('d-none');
            previewEmpty.classList.remove('d-none');
            return;
        }

        hint.textContent = file.name;
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            previewImg.classList.remove('d-none');
            previewEmpty.classList.add('d-none');
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

    if (form && saveBtn) {
        form.addEventListener('submit', function() {
            saveBtn.disabled = true;
            saveSpinner.classList.remove('d-none');
            saveBtn.querySelector('.company-create-save-btn__text').textContent = 'Saving…';
        });
    }
})();
</script>
@endpush
