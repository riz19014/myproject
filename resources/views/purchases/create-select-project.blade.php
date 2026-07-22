@extends('layouts.app')

@section('title', 'Add purchase')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Add purchase</h1>
    <a href="{{ route('purchase.index') }}" class="btn btn-outline-theme">Back to Purchase</a>
</div>

<div class="card card-theme" style="max-width: 36rem;">
    <div class="card-body">
        <p class="text-muted small mb-4">Choose a <strong>Purchase</strong> project. On the next step you can add one or more lines: party, Moza, Khasra, land area (Acre, Kanal, Marla, Sq ft), and amount per acre.</p>

        @if($purchaseProjects->isEmpty())
            <p class="text-muted mb-0">No purchase projects yet. Create a project and set its type to Purchase, then return here.</p>
        @else
            <form method="get" action="{{ route('purchase.records.create') }}">
                <div class="mb-4">
                    <label for="project" class="form-label">Project <span class="text-danger">*</span></label>
                    <select class="form-select form-select-theme" id="project" name="project" required>
                        <option value="" disabled selected>— Select project —</option>
                        @foreach($purchaseProjects as $p)
                            <option value="{{ $p->id }}">{{ $p->labeledName() }}@if($p->landType) — {{ $p->landType->name }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn btn-pink">Continue</button>
            </form>
        @endif
    </div>
</div>
@endsection
