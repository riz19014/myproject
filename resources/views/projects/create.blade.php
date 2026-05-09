@extends('layouts.app')

@php
    $isTypedContext = ($context ?? null) === 'sale' || ($context ?? null) === 'purchase';
    $contextLabel = ($context ?? null) === 'purchase' ? 'Purchase' : (($context ?? null) === 'sale' ? 'Sale' : null);
@endphp

@section('title', 'Add Project')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Add Project</h1>
    @if($isTypedContext)
        <a href="{{ route($context.'.index') }}" class="btn btn-outline-theme">Back to {{ $contextLabel }}</a>
    @else
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">Back to List</a>
    @endif
</div>

<div class="card card-theme">
    <div class="card-body">
        <form action="{{ route('projects.store') }}" method="POST">
            @csrf
            @if($isTypedContext)
                <input type="hidden" name="context" value="{{ $context }}">
            @endif

            @if($isTypedContext)
                @if($assignableProjects->isEmpty())
                    <div class="alert alert-secondary border mb-4" role="status">
                        <div class="small">No projects yet. Create one using the form below, or add a project from <strong>Daybook</strong> first.</div>
                    </div>
                @else
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-25">
                        <h2 class="h5 mb-2">Existing project</h2>
                        <p class="text-muted small mb-3">All projects are listed below. Choose one and add it to <strong>{{ $contextLabel }}</strong> (updates type to {{ $contextLabel }} unless it is already {{ $contextLabel }}).</p>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="assign_project_id" class="form-label">Select project</label>
                                <select name="assign_project_id" id="assign_project_id" class="form-select form-select-theme @error('assign_project_id') is-invalid @enderror">
                                    <option value="">— Choose a project —</option>
                                    @foreach($assignableProjects as $p)
                                        <option value="{{ $p->id }}" @selected(old('assign_project_id') == $p->id)>
                                            {{ $p->name }}
                                            @if($p->landType)
                                                — {{ $p->landType->name }}
                                            @endif
                                            @if($p->field_type)
                                                <span class="text-muted">({{ ucfirst($p->field_type) }})</span>
                                            @else
                                                <span class="text-muted">(Unassigned)</span>
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('assign_project_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="submit_action" value="assign" class="btn btn-outline-theme w-100" formnovalidate>Add to {{ $contextLabel }}</button>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

            <h2 class="h5 mb-3">New project</h2>

            <div class="mb-3">
                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. DHA Land" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="land_area" class="form-label">Land area</label>
                    <input type="number" step="0.0001" min="0" class="form-control form-control-theme @error('land_area') is-invalid @enderror" id="land_area" name="land_area" value="{{ old('land_area') }}" placeholder="0">
                    @error('land_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="land_area_unit" class="form-label">Unit</label>
                    <select name="land_area_unit" id="land_area_unit" class="form-select form-select-theme @error('land_area_unit') is-invalid @enderror">
                        <option value="">—</option>
                        @foreach (['acre' => 'Acre', 'kanal' => 'Kanal', 'marla' => 'Marla', 'sqft' => 'Sq ft'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('land_area_unit') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('land_area_unit')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="field_type" class="form-label">Field type</label>
                    <select name="field_type" id="field_type" class="form-select form-select-theme @error('field_type') is-invalid @enderror">
                        <option value="">—</option>
                        <option value="sale" @selected(old('field_type', $context ?? '') === 'sale')>Sale</option>
                        <option value="purchase" @selected(old('field_type', $context ?? '') === 'purchase')>Purchase</option>
                    </select>
                    @error('field_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="mb-3">
                <label for="land_type_id" class="form-label">Land type</label>
                <select name="land_type_id" id="land_type_id" class="form-select form-select-theme @error('land_type_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($landTypes as $lt)
                        <option value="{{ $lt->id }}" @selected(old('land_type_id') == $lt->id)>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @error('land_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control form-control-theme @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-control form-control-theme @error('notes') is-invalid @enderror" id="notes" name="notes" rows="2">{{ old('notes') }}</textarea>
                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" name="submit_action" value="create" class="btn btn-pink">Create Project</button>
            </div>
        </form>
    </div>
</div>
@endsection
