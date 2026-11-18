@extends('layouts.app')

@section('title', 'Blocks & plots — '.$project->name)

@section('content')
@include('society.partials.nav')

<div class="mb-4">
    <h1 class="h3 mb-1">Blocks / sectors &amp; plots</h1>
    <p class="text-muted mb-0">Create blocks, generate plot numbers, set sizes and availability.</p>
</div>

<div class="row g-3 mb-4">
    @foreach($statuses as $key => $label)
        <div class="col-6 col-md-3">
            <div class="card card-theme"><div class="card-body py-3">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fs-5 fw-semibold">{{ $statusCounts[$key] ?? 0 }}</div>
            </div></div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card card-theme mb-3">
            <div class="card-body">
                <h5 class="mb-3">Add block</h5>
                <form method="post" action="{{ route('society.blocks.store', $project) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Name</label>
                        <input name="name" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Sector</label>
                        <input name="sector" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            @foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <button class="btn btn-pink btn-sm">Create block</button>
                </form>
            </div>
        </div>

        <div class="card card-theme mb-3">
            <div class="card-body">
                <h5 class="mb-3">Add single plot</h5>
                <form method="post" action="{{ route('society.plots.store', $project) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Block</label>
                        <select name="project_block_id" class="form-select">
                            <option value="">— None —</option>
                            @foreach($blocks as $block)
                                <option value="{{ $block->id }}">{{ $block->name }}@if($block->sector) ({{ $block->sector }})@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Plot number</label>
                        <input name="plot_number" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            @foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-3"><input type="number" min="0" name="area_acre" class="form-control form-control-sm" placeholder="A"></div>
                        <div class="col-3"><input type="number" min="0" name="area_kanal" class="form-control form-control-sm" placeholder="K"></div>
                        <div class="col-3"><input type="number" min="0" name="area_marla" class="form-control form-control-sm" placeholder="M"></div>
                        <div class="col-3"><input type="number" min="0" name="area_sqft" class="form-control form-control-sm" placeholder="S"></div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">List price</label>
                        <input type="number" step="0.01" min="0" name="list_price" class="form-control">
                    </div>
                    <button class="btn btn-pink btn-sm">Add plot</button>
                </form>
            </div>
        </div>

        <div class="card card-theme">
            <div class="card-body">
                <h5 class="mb-3">Bulk generate plots</h5>
                <form method="post" action="{{ route('society.plots.generate', $project) }}">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label">Block</label>
                        <select name="project_block_id" class="form-select" required>
                            @foreach($blocks as $block)
                                <option value="{{ $block->id }}">{{ $block->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-4"><input name="prefix" class="form-control form-control-sm" placeholder="Prefix"></div>
                        <div class="col-4"><input type="number" name="start_number" class="form-control form-control-sm" value="1" min="1" required></div>
                        <div class="col-4"><input type="number" name="count" class="form-control form-control-sm" value="10" min="1" max="500" required></div>
                    </div>
                    <div class="mb-2">
                        <select name="category" class="form-select form-select-sm">
                            @foreach($categories as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-3"><input type="number" min="0" name="area_acre" class="form-control form-control-sm" placeholder="A"></div>
                        <div class="col-3"><input type="number" min="0" name="area_kanal" class="form-control form-control-sm" placeholder="K"></div>
                        <div class="col-3"><input type="number" min="0" name="area_marla" class="form-control form-control-sm" placeholder="M" value="10"></div>
                        <div class="col-3"><input type="number" min="0" name="area_sqft" class="form-control form-control-sm" placeholder="S"></div>
                    </div>
                    <div class="mb-2">
                        <input type="number" step="0.01" min="0" name="list_price" class="form-control form-control-sm" placeholder="List price">
                    </div>
                    <button class="btn btn-outline-theme btn-sm" @disabled($blocks->isEmpty())>Generate</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card card-theme mb-3">
            <div class="card-body">
                <h5 class="mb-3">Blocks</h5>
                <ul class="list-group list-group-flush">
                    @forelse($blocks as $block)
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <div>
                                <strong>{{ $block->name }}</strong>
                                @if($block->sector)<span class="text-muted"> · {{ $block->sector }}</span>@endif
                                <span class="badge text-bg-light">{{ $block->plots->count() }} plots</span>
                            </div>
                            <form method="post" action="{{ route('society.blocks.destroy', [$project, $block]) }}" onsubmit="return confirm('Delete block?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                            </form>
                        </li>
                    @empty
                        <li class="list-group-item px-0 text-muted">No blocks yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="card card-theme">
            <div class="card-body">
                <h5 class="mb-3">Plots ({{ $plots->count() }})</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-theme align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Plot</th>
                                <th>Block</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plots as $plot)
                                <tr>
                                    <td class="fw-semibold">{{ $plot->plot_number }}</td>
                                    <td>{{ $plot->block?->name ?? '—' }}</td>
                                    <td>{{ $categories[$plot->category] ?? $plot->category }}</td>
                                    <td class="small font-monospace">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float)$plot->land_area_marla) }}</td>
                                    <td>{{ $plot->list_price !== null ? 'Rs '.number_format((float)$plot->list_price, 0) : '—' }}</td>
                                    <td><span class="badge text-bg-secondary">{{ $plot->statusLabel() }}</span></td>
                                    <td class="text-end">
                                        @if($plot->isAvailable())
                                            <form method="post" action="{{ route('society.plots.destroy', [$project, $plot]) }}" onsubmit="return confirm('Delete plot?')">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Delete</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-muted text-center">No plots yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
