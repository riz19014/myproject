@extends('layouts.app')

@section('title', 'Partners — '.$project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Partners</h1>
        <p class="text-muted small mb-0">
            Project: <strong>{{ $project->name }}</strong>
            @if($project->landType)
                <span class="text-muted">· {{ $project->landType->name }}</span>
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">All projects</a>
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-body">
        <h2 class="h5 mb-3">Add partner</h2>
        <form action="{{ route('projects.partners.store', $project) }}" method="POST" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-4">
                <label for="party_id" class="form-label">Party <span class="text-danger">*</span></label>
                <select name="party_id" id="party_id" class="form-select form-select-theme @error('party_id') is-invalid @enderror" required>
                    <option value="">— Select party —</option>
                    @foreach($parties as $party)
                        @continue($project->parties->contains('id', $party->id))
                        <option value="{{ $party->id }}" @selected(old('party_id') == $party->id)>{{ $party->name }}</option>
                    @endforeach
                </select>
                @error('party_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-6 col-md-2">
                <label for="area_acre" class="form-label">A</label>
                <input type="number" min="0" step="1" name="area_acre" id="area_acre" class="form-control form-control-theme" value="{{ old('area_acre', 0) }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_kanal" class="form-label">K</label>
                <input type="number" min="0" step="1" name="area_kanal" id="area_kanal" class="form-control form-control-theme" value="{{ old('area_kanal', 0) }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_marla" class="form-label">M</label>
                <input type="number" min="0" step="1" name="area_marla" id="area_marla" class="form-control form-control-theme" value="{{ old('area_marla', 0) }}">
            </div>
            <div class="col-6 col-md-2">
                <label for="area_sqft" class="form-label">SQFT</label>
                <input type="number" min="0" step="1" name="area_sqft" id="area_sqft" class="form-control form-control-theme" value="{{ old('area_sqft', 0) }}">
            </div>
            <div class="col-12">
                <p class="small text-muted mb-2">Land area is optional. Leave A/K/M/SQFT at 0 if not needed.</p>
                <button type="submit" class="btn btn-pink">Add partner</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-theme">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h2 class="h5 mb-0">Current partners ({{ $project->parties->count() }})</h2>
        </div>

        @if($project->parties->isEmpty())
            <p class="text-muted mb-0">No partners on this project yet. Add one above.</p>
        @else
            <form action="{{ route('projects.partners.update', $project) }}" method="POST" id="project-partners-update-form">
                @csrf
                @method('PUT')
                <div class="table-responsive">
                    <table class="table table-striped table-theme mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 56px;">#</th>
                                <th>Partner</th>
                                <th class="text-center" style="width: 80px;">A</th>
                                <th class="text-center" style="width: 80px;">K</th>
                                <th class="text-center" style="width: 80px;">M</th>
                                <th class="text-center" style="width: 90px;">SQFT</th>
                                <th style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($project->parties as $partner)
                                @php
                                    $totalMarla = (float) ($partner->pivot->land_area ?? 0);
                                    $eps = 1e-6;
                                    $wholeMarla = (int) floor($totalMarla + $eps);
                                    $a = intdiv($wholeMarla, 160);
                                    $r = $wholeMarla - $a * 160;
                                    $k = intdiv($r, 20);
                                    $m = $r - $k * 20;
                                    $frac = max(0, $totalMarla - $wholeMarla);
                                    $sqft = $frac > $eps ? (int) round($frac * 272.25) : 0;
                                    $old = old('partners.'.$loop->index);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">
                                        {{ $partner->name }}
                                        <input type="hidden" name="partners[{{ $loop->index }}][party_id]" value="{{ $partner->id }}">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" name="partners[{{ $loop->index }}][area_acre]" class="form-control form-control-sm form-control-theme text-center" value="{{ $old['area_acre'] ?? $a }}">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" name="partners[{{ $loop->index }}][area_kanal]" class="form-control form-control-sm form-control-theme text-center" value="{{ $old['area_kanal'] ?? $k }}">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" name="partners[{{ $loop->index }}][area_marla]" class="form-control form-control-sm form-control-theme text-center" value="{{ $old['area_marla'] ?? $m }}">
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="1" name="partners[{{ $loop->index }}][area_sqft]" class="form-control form-control-sm form-control-theme text-center" value="{{ $old['area_sqft'] ?? $sqft }}">
                                    </td>
                                    <td>
                                        {{-- Remove uses its own form; button outside update form via nested form is invalid HTML, so use a dedicated form below table row via JS-free separate forms after --}}
                                        <button type="submit" form="project-partner-remove-{{ $partner->id }}" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Remove partner?" data-text="Remove {{ $partner->name }} from this project?">Remove</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    <button type="submit" class="btn btn-pink">Save partner areas</button>
                </div>
            </form>

            @foreach($project->parties as $partner)
                <form id="project-partner-remove-{{ $partner->id }}" action="{{ route('projects.partners.destroy', [$project, $partner]) }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                </form>
            @endforeach
        @endif
    </div>
</div>
@endsection
