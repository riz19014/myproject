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

            <div class="mb-3">
                <label for="name" class="form-label">Project Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control form-control-theme @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. DHA Land" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label for="land_type_id" class="form-label">Land type</label>
                <select name="land_type_id" id="land_type_id" class="form-select form-select-theme @error('land_type_id') is-invalid @enderror">
                    <option value="">—</option>
                    @foreach($landTypes as $lt)
                        <option value="{{ $lt->id }}" @selected(old('land_type_id') == $lt->id)>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @error('land_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-pink">Create Project</button>
        </form>
    </div>
</div>
@endsection
