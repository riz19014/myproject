@extends('layouts.app')

@section('title', 'Add sale')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">Add sale</h1>
    <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Back to Sale</a>
</div>

<div class="card card-theme" style="max-width: 36rem;">
    <div class="card-body">
        <p class="text-muted small mb-4">Choose a <strong>Sale</strong> project. On the next step you will enter land area, parties or buyers, and total amount.</p>

        @if($projects->isEmpty())
            <p class="text-muted mb-0">No sale projects yet. Set a project to Sale (Projects → Edit or Daybook flow), then return here.</p>
        @else
            <form method="get" action="{{ route('sale.records.create') }}">
                <div class="mb-4">
                    <label for="project" class="form-label">Project <span class="text-danger">*</span></label>
                    <select class="form-select form-select-theme" id="project" name="project" required>
                        <option value="" disabled selected>— Select project —</option>
                        @foreach($projects as $p)
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
