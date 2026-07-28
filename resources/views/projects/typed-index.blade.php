@extends('layouts.app')

@php
    $label = $type === 'sale' ? 'Sale' : 'Purchase';
    $icon = $type === 'sale' ? 'bi-graph-up-arrow' : 'bi-bag-check';
@endphp

@section('title', $label)

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1 d-flex align-items-center gap-2">
            <i class="bi {{ $icon }}" aria-hidden="true"></i>
            <span>{{ $label }}</span>
        </h1>
        @if($type === 'sale')
            <div class="text-muted small">Sale lines, land cuttings, and net saleable area by project.</div>
        @elseif($type === 'purchase')
            <div class="text-muted small">Purchase lines by party (Moza, Khasra, land area, amount per acre) and projects.</div>
        @else
            <div class="text-muted small">Projects where type is <strong class="text-capitalize">{{ $type }}</strong>.</div>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if($type === 'sale')
            <a href="{{ route('sale.index') }}" class="btn btn-pink">Sale</a>
            <a href="{{ route('projects.create', ['context' => 'sale']) }}" class="btn btn-outline-theme">Add sale project</a>
        @else
            <a href="{{ route('purchase.records.create') }}" class="btn btn-pink">Add purchase</a>
            <a href="{{ route('purchase.files.create') }}" class="btn btn-outline-theme">Add purchase file</a>
            <a href="{{ route('projects.create', ['context' => $type]) }}" class="btn btn-outline-theme">Add project</a>
        @endif
        <a href="{{ route('projects.index') }}" class="btn btn-outline-theme">All projects</a>
    </div>
</div>

@if($type === 'sale')
    <div class="card card-theme mb-4">
        <div class="card-body">
            <h2 class="h5 mb-3">Sales</h2>
            @if($sales->isEmpty())
                <p class="text-muted small mb-0">No sale lines yet. Use <strong>Add sale</strong> to record land area, parties or buyers, and amount against a sale project.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-theme mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 72px;">ID</th>
                                <th>Project</th>
                                <th style="min-width: 200px;">Sale land</th>
                                <th class="text-end" style="width: 120px;">Cuttings</th>
                                <th class="text-end" style="width: 120px;">Net saleable</th>
                                <th>Parties / buyers</th>
                                <th class="text-end" style="width: 120px;">Total (Rs)</th>
                                <th style="width: 100px;">Date</th>
                                <th class="text-center" style="width: 56px;" title="Land cutting"><span class="visually-hidden">Cuttings</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sales as $sale)
                                @php
                                    $names = $sale->participants->map(function ($sp) {
                                        return $sp->party?->name ?? $sp->customer?->name ?? '—';
                                    })->filter()->values();
                                    $cutMarla = (float) $sale->landCuttings->sum('land_area_marla');
                                    $netMarla = (float) $sale->land_area_marla - $cutMarla;
                                @endphp
                                <tr>
                                    <td>{{ $sale->id }}</td>
                                    <td class="fw-semibold">@if($sale->project)<x-project-name :project="$sale->project" />@else—@endif</td>
                                    <td class="small">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $sale->land_area_marla) }}</td>
                                    <td class="text-end small">{{ $cutMarla > 0 ? \App\Support\LandMeasure::formatAkmsLabelFromMarla($cutMarla) : '—' }}</td>
                                    <td class="text-end small fw-semibold {{ $netMarla < 0 ? 'text-danger' : '' }}">
                                        @if($netMarla < 0)
                                            −{{ number_format(abs($netMarla), 2) }} marla
                                        @else
                                            {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($netMarla) }}
                                        @endif
                                    </td>
                                    <td class="small">{{ $names->isEmpty() ? '—' : $names->implode(', ') }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $sale->total_amount, 0) }}</td>
                                    <td class="text-muted small">{{ $sale->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('sale.records.land-cuttings.index', $sale) }}" class="btn btn-sm btn-outline-theme p-2" title="Land cutting" aria-label="Land cutting for sale {{ $sale->id }}">
                                            <i class="bi bi-scissors" aria-hidden="true"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

@if($type === 'purchase')
    <div class="card card-theme mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h2 class="h5 mb-0">Purchase lines</h2>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('purchase.lines.pdf') }}" class="btn btn-sm btn-outline-theme d-inline-flex align-items-center gap-1">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                        <span>Print PDF</span>
                    </a>
                    <a href="{{ route('purchase.lines.ledger-pdf') }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Per project: book total opening, then daybook payments (party, cash/cheque/pay order, bank, ref) and balance remaining">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        <span>Ledger PDF</span>
                    </a>
                </div>
            </div>
            @if($projects->isNotEmpty())
                <div class="mb-3 pb-3 border-bottom">
                    <div class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing:.06em;">Add lines for project</div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($projects->sortBy('name') as $purchaseProject)
                            <a href="{{ route('purchase.records.create', ['project' => $purchaseProject->id]) }}" class="btn btn-sm btn-outline-theme d-inline-flex align-items-center gap-1" title="Add purchase lines for {{ $purchaseProject->name }}">
                                <span class="text-truncate" style="max-width: 14rem;"><x-project-name :project="$purchaseProject" /></span>
                                <i class="bi bi-plus-lg flex-shrink-0" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.06em;">Purchase files</div>
                        <a href="{{ route('purchase.files.index') }}" class="btn btn-sm btn-outline-theme">All purchase files</a>
                    </div>
                    <p class="text-muted small mb-2">Each file has a name, project, and one or more dealers.</p>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($projects->sortBy('name') as $purchaseProject)
                            <a href="{{ route('purchase.files.index', ['project' => $purchaseProject->id]) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2" title="Purchase files for {{ $purchaseProject->name }}">
                                <i class="bi bi-folder2-open flex-shrink-0" aria-hidden="true"></i>
                                <span class="text-truncate" style="max-width: 11rem;"><x-project-name :project="$purchaseProject" /></span>
                                <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark border">{{ $purchaseProject->purchase_files_count }} {{ $purchaseProject->purchase_files_count === 1 ? 'file' : 'files' }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($purchaseItems->isEmpty())
                <p class="text-muted small mb-0">No purchase lines yet. Pick a project under <strong>Add lines for project</strong> above (or use <strong>Add purchase</strong> in the header), then enter one or more rows. Use <strong>Edit</strong> in the table to change a line.</p>
            @else
                @php
                    $purchaseTotalMarla = \App\Models\PurchaseItem::sumEffectiveMarla($purchaseItems);
                    $purchaseTotalRs = (float) $purchaseItems->sum(fn ($i) => (float) $i->line_total_rs);
                    $purchaseLineCount = $purchaseItems->count();
                @endphp
                <div class="table-responsive">
                    <table class="table table-striped table-theme mb-0 align-middle">
                        <thead>
                            <tr>
                                <th style="width: 56px;">#</th>
                                <th>Project</th>
                                <th style="min-width: 7rem;">File</th>
                                <th>Party</th>
                                <th>Moza</th>
                                <th>Khasra</th>
                                <th style="min-width: 200px;">Area</th>
                                <th class="text-end" style="width: 120px;">Rs / acre</th>
                                <th class="text-end" style="width: 120px;">Line total (Rs)</th>
                                <th style="width: 100px;">Date</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseItems as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">@if($item->project)<x-project-name :project="$item->project" />@else—@endif</td>
                                    <td class="small">{{ $item->purchaseFile?->file_name ?: '—' }}</td>
                                    <td class="small">{{ $item->party?->name ?? '—' }}</td>
                                    <td class="small">{{ $item->moza ?: '—' }}</td>
                                    <td class="small">{{ $item->khasra ?: '—' }}</td>
                                    <td class="small">{{ $item->landAreaLabel() }}</td>
                                    <td class="text-end small">{{ number_format((float) $item->amount_per_acre, 0) }}</td>
                                    <td class="text-end fw-semibold">{{ number_format((float) $item->line_total_rs, 0) }}</td>
                                    <td class="text-muted small">{{ $item->created_at?->format('Y-m-d') }}</td>
                                    <td class="text-center text-nowrap">
                                        <a href="{{ route('purchase.records.edit', $item) }}" class="btn btn-sm btn-outline-theme p-2" title="Edit line" aria-label="Edit purchase line {{ $item->id }}">
                                            <i class="bi bi-pencil" aria-hidden="true"></i>
                                        </a>
                                        <form action="{{ route('purchase.records.destroy', $item) }}" method="post" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger p-2 btn-delete-confirm" title="Remove line" aria-label="Remove purchase line {{ $item->id }}" data-title="Remove this purchase line?" data-text="This only deletes this row from the purchase list." data-confirm="Yes, remove">
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-top border-2 border-secondary border-opacity-25">
                                <td colspan="6" class="p-3 text-end align-middle bg-body-secondary bg-opacity-25">
                                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.06em;">Totals</div>
                                    <div class="text-body-secondary small">{{ $purchaseLineCount }} {{ $purchaseLineCount === 1 ? 'line' : 'lines' }}</div>
                                </td>
                                <td class="p-3 align-middle bg-body-secondary bg-opacity-25">
                                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.06em;">Total area</div>
                                    <div class="fw-bold fs-6 lh-sm">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($purchaseTotalMarla) }}</div>
                                    <div class="text-muted small mt-1">{{ \App\Support\LandMeasure::formatMarlaTotal($purchaseTotalMarla) }}</div>
                                </td>
                                <td class="p-3 text-end text-muted small align-middle bg-body-secondary bg-opacity-25">—</td>
                                <td class="p-3 text-end align-middle bg-body-secondary bg-opacity-25">
                                    <div class="text-muted small fw-semibold text-uppercase mb-1" style="letter-spacing:.06em;">Line total (Rs)</div>
                                    <div class="fw-bold fs-5 text-nowrap">Rs {{ number_format($purchaseTotalRs, 0) }}</div>
                                </td>
                                <td colspan="2" class="p-3 align-middle bg-body-secondary bg-opacity-25"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Projects</div>
                <div class="fs-4 fw-bold">{{ $projects->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Total amount</div>
                <div class="fs-4 fw-bold">Rs {{ number_format($totalAmount, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">With land type</div>
                <div class="fs-4 fw-bold">{{ $projects->whereNotNull('land_type_id')->count() }}</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card card-theme h-100">
            <div class="card-body">
                <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">No land type</div>
                <div class="fs-4 fw-bold">{{ $projects->whereNull('land_type_id')->count() }}</div>
            </div>
        </div>
    </div>
</div>

@forelse($byLandType as $landTypeId => $rows)
    @php
        $landTypeName = $landTypeId && $rows->first() && $rows->first()->landType
            ? $rows->first()->landType->name
            : ($landTypeId ? ('Land type #' . $landTypeId) : 'Uncategorized');
        $sectionTotal = $type === 'sale'
            ? (float) $sales->whereIn('project_id', $rows->pluck('id'))->sum('total_amount')
            : (float) $purchaseItems->whereIn('project_id', $rows->pluck('id'))->sum('line_total_rs');
    @endphp

    <div class="card card-theme mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-0">{{ $landTypeName }}</h5>
                    <div class="text-muted small">{{ $rows->count() }} project(s) · Rs {{ number_format($sectionTotal, 0) }}</div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-theme mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 56px;">#</th>
                            <th>Name</th>
                            <th style="width: 200px;">Party land</th>
                            <th class="text-center" style="width: 90px;">Files</th>
                            <th style="width: 220px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $project)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold"><x-project-name :project="$project" /></td>
                                <td class="small">
                                    @php $partyMarla = \App\Support\LandMeasure::partiesTotalMarla($project->parties); @endphp
                                    @if($partyMarla > 0)
                                        {{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($partyMarla) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $type === 'sale' ? $project->project_files_count : $project->purchase_files_count }}</td>
                                <td>
                                    @if($type === 'sale')
                                        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-sm btn-pink">Files</a>
                                    @else
                                        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-sm btn-outline-secondary">Files</a>
                                    @endif
                                    <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-theme">View</a>
                                    <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-theme btn-sm">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@empty
    <div class="card card-theme">
        <div class="card-body text-center py-5">
            <div class="text-muted">No {{ strtolower($label) }} projects yet.</div>
            <div class="mt-3">
                @if($type === 'sale')
                    <a href="{{ route('projects.create', ['context' => 'sale']) }}" class="btn btn-pink">Add sale project</a>
                @elseif($type === 'purchase')
                    <a href="{{ route('purchase.records.create') }}" class="btn btn-pink">Add purchase</a>
                    <a href="{{ route('projects.create', ['context' => 'purchase']) }}" class="btn btn-outline-theme ms-2">Add purchase project</a>
                @else
                    <a href="{{ route('projects.create', ['context' => $type]) }}" class="btn btn-pink">Create a project</a>
                @endif
            </div>
        </div>
    </div>
@endforelse
@endsection

