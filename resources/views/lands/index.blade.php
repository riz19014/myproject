@extends('layouts.app')

@section('title', 'Sold Land Files')

@push('head')
<style>
    .sold-land-stat {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        padding: 0.85rem 1rem;
        background: #f8fafc;
        height: 100%;
    }
    .sold-land-stat.is-sold { background: #fff7ed; border-color: rgba(234, 88, 12, 0.2); }
    .sold-land-stat.is-left { background: #ecfdf5; border-color: rgba(5, 150, 105, 0.2); }
    .sold-land-stat__label {
        display: block;
        font-size: 0.72rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .sold-land-stat__value {
        display: block;
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a;
    }
    .sold-land-left { color: #047857; font-weight: 700; }
    .sold-land-detail {
        display: grid;
        gap: 0.15rem;
    }
    .sold-land-detail__meta { color: #64748b; font-size: 0.78rem; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Sold Land Files</h1>
        <p class="text-muted small mb-0">Clear sale records with file leftover balance after each sale.</p>
    </div>
    <a href="{{ route('daybook.index') }}" class="btn btn-outline-theme">Open Daybook</a>
</div>

{{-- File leftover inventory --}}
<div class="card card-theme mb-4">
    <div class="card-header py-3">
        <h2 class="h6 mb-0">File leftover balance</h2>
        <p class="text-muted small mb-0">Total / sold / remaining land for files that have sale records.</p>
    </div>
    <div class="card-body">
        @if(($inventoryFiles ?? collect())->isEmpty())
            <p class="text-muted small mb-0">No sold files yet. Record a sale from Daybook → Sale.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>File</th>
                            <th>Project</th>
                            <th>Total land</th>
                            <th>Sold</th>
                            <th>Leftover</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($inventoryFiles as $file)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ route('purchase.files.show', $file['purchase_file_id']) }}" class="text-decoration-none">
                                        {{ $file['file_name'] }}
                                    </a>
                                    <div class="text-muted small">{{ $file['sales_count'] }} sale record(s)</div>
                                </td>
                                <td class="small">
                                    @if($file['project_id'])
                                        <a href="{{ route('sale.files.index', $file['project_id']) }}" class="text-decoration-none">
                                            <x-project-name :name="$file['project_name']" :is-dha="$file['project_is_dha'] ?? false" />
                                        </a>
                                    @else
                                        <x-project-name :name="$file['project_name']" :is-dha="$file['project_is_dha'] ?? false" />
                                    @endif
                                </td>
                                <td class="small">{{ $file['total_label'] }}</td>
                                <td class="small">{{ $file['sold_label'] }}</td>
                                <td class="small sold-land-left">{{ $file['remaining_label'] }}</td>
                                <td>
                                    @if($file['status'] === 'Fully Sold')
                                        <span class="badge text-bg-secondary">Fully Sold</span>
                                    @elseif($file['status'] === 'Partially Sold')
                                        <span class="badge text-bg-warning">Partially Sold</span>
                                    @else
                                        <span class="badge text-bg-success">{{ $file['status'] }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($file['file_sale_url'])
                                        <a href="{{ $file['file_sale_url'] }}" class="btn btn-sm btn-outline-theme">File sale</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

{{-- Sale records --}}
<div class="card card-theme mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="h6 mb-0">Sale records</h2>
            <p class="text-muted small mb-0">Each daybook file sale with stamp, purchaser, plot, and file leftover.</p>
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="sold-land-stat">
                    <span class="sold-land-stat__label">Records</span>
                    <span class="sold-land-stat__value">{{ $fileSaleRecordSummary['records_count'] }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sold-land-stat">
                    <span class="sold-land-stat__label">Complete / Pending</span>
                    <span class="sold-land-stat__value">{{ $fileSaleRecordSummary['complete_count'] }} / {{ $fileSaleRecordSummary['pending_count'] }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sold-land-stat is-sold">
                    <span class="sold-land-stat__label">Area sold (records)</span>
                    <span class="sold-land-stat__value">{{ $fileSaleRecordSummary['total_sold_label'] }}</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="sold-land-stat is-left">
                    <span class="sold-land-stat__label">Total amount</span>
                    <span class="sold-land-stat__value">{{ $fileSaleRecordSummary['total_amount_formatted'] }}</span>
                </div>
            </div>
        </div>

        @if($fileSaleRecords->isEmpty())
            <p class="text-muted small mb-0">
                No file sale records yet. Use the <strong>Sale</strong> button on Daybook to record a DHA file sale.
            </p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>eStamp</th>
                            <th>File &amp; parties</th>
                            <th>Purchaser</th>
                            <th>Plot sold</th>
                            <th>Land details</th>
                            <th>File leftover</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Docs</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fileSaleRecords as $record)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="small">{{ $record['created_at'] }}</td>
                                <td class="fw-semibold small">{{ $record['e_stamp_id'] }}</td>
                                <td>
                                    <div class="sold-land-detail">
                                        <a href="{{ route('purchase.files.show', $record['purchase_file_id']) }}" class="text-decoration-none fw-semibold">
                                            {{ $record['file_name'] }}
                                        </a>
                                        <span class="sold-land-detail__meta">
                                            @if($record['project_id'])
                                                <a href="{{ route('sale.files.index', $record['project_id']) }}" class="text-decoration-none">
                                                    <x-project-name :name="$record['project_name']" :is-dha="$record['project_is_dha'] ?? false" />
                                                </a>
                                            @else
                                                <x-project-name :name="$record['project_name']" :is-dha="$record['project_is_dha'] ?? false" />
                                            @endif
                                        </span>
                                        <span class="sold-land-detail__meta">Owner: {{ $record['land_owner'] }}</span>
                                        <span class="sold-land-detail__meta">Provider: {{ $record['land_provider'] }}</span>
                                    </div>
                                </td>
                                <td class="small fw-semibold">{{ $record['purchaser_name'] }}</td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $record['plot_label'] }}</div>
                                    <div class="sold-land-detail__meta">Area: {{ $record['land_area_label'] }}</div>
                                </td>
                                <td class="small">
                                    <div>Mouza: {{ $record['moza'] }}</div>
                                    <div class="sold-land-detail__meta">Khewat: {{ $record['khewat_no'] }}</div>
                                    <div class="sold-land-detail__meta">Khatoni: {{ $record['khatooni_no'] }}</div>
                                    <div class="sold-land-detail__meta">Khasra: {{ $record['khasra'] }}</div>
                                </td>
                                <td class="small">
                                    <div>Total: {{ $record['file_total_label'] }}</div>
                                    <div>Sold: {{ $record['file_sold_label'] }}</div>
                                    <div class="sold-land-left">Left: {{ $record['file_remaining_label'] }}</div>
                                    <div class="mt-1">
                                        @if($record['file_status'] === 'Fully Sold')
                                            <span class="badge text-bg-secondary">{{ $record['file_status'] }}</span>
                                        @elseif($record['file_status'] === 'Partially Sold')
                                            <span class="badge text-bg-warning">{{ $record['file_status'] }}</span>
                                        @else
                                            <span class="badge text-bg-success">{{ $record['file_status'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end fw-semibold">{{ $record['amount_formatted'] }}</td>
                                <td>
                                    @if($record['status'] === 'complete')
                                        <span class="badge text-bg-success">{{ $record['status_label'] }}</span>
                                    @elseif($record['status'] === 'pending')
                                        <span class="badge text-bg-warning">{{ $record['status_label'] }}</span>
                                    @else
                                        <span class="badge text-bg-secondary">{{ $record['status_label'] }}</span>
                                    @endif
                                    @if(($record['notes'] ?? '—') !== '—')
                                        <div class="sold-land-detail__meta mt-1">{{ $record['notes'] }}</div>
                                    @endif
                                </td>
                                <td class="small">
                                    @forelse($record['documents'] as $doc)
                                        @if($doc['url'])
                                            <a href="{{ $doc['url'] }}" target="_blank" rel="noopener" class="d-block text-decoration-none">{{ $doc['name'] }}</a>
                                        @else
                                            <span class="d-block">{{ $doc['name'] }}</span>
                                        @endif
                                    @empty
                                        <span class="text-muted">—</span>
                                    @endforelse
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card card-theme mb-4">
    <div class="card-header py-3">
        <h2 class="h6 mb-0">Daybook sold-area entries (legacy)</h2>
        <p class="text-muted small mb-0">Older daybook entries that recorded sold area directly on the voucher.</p>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="small text-muted">Sold files</div>
                <div class="fw-semibold">{{ $fileSaleSoldSummary['files_count'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Sale entries</div>
                <div class="fw-semibold">{{ $fileSaleSoldSummary['entries_count'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Total area sold</div>
                <div class="fw-semibold">{{ $fileSaleSoldSummary['total_sold_label'] }}</div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Total amount</div>
                <div class="fw-semibold">{{ $fileSaleSoldSummary['total_amount_formatted'] }}</div>
            </div>
        </div>

        @if($soldFiles->isEmpty())
            <p class="text-muted small mb-0">No daybook sold-area entries found.</p>
        @else
            <div class="table-responsive">
                <table class="table table-striped table-theme align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>File</th>
                            <th>Project</th>
                            <th>Total land</th>
                            <th>Sold</th>
                            <th>Remaining</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th>Last sale</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($soldFiles as $file)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-semibold">
                                    <a href="{{ route('purchase.files.show', $file['purchase_file_id']) }}" class="text-decoration-none">
                                        {{ $file['file_name'] }}
                                    </a>
                                    <div class="text-muted small">{{ $file['entries_count'] }} daybook {{ $file['entries_count'] === 1 ? 'sale' : 'sales' }}</div>
                                </td>
                                <td class="small">
                                    @if($file['project_id'])
                                        <a href="{{ route('sale.files.index', $file['project_id']) }}" class="text-decoration-none"><x-project-name :name="$file['project_name']" :is-dha="$file['project_is_dha'] ?? false" /></a>
                                    @else
                                        <x-project-name :name="$file['project_name']" :is-dha="$file['project_is_dha'] ?? false" />
                                    @endif
                                </td>
                                <td class="small">{{ $file['total_label'] }}</td>
                                <td class="small fw-semibold">{{ $file['sold_label'] }}</td>
                                <td class="small sold-land-left">{{ $file['remaining_label'] }}</td>
                                <td>
                                    @if($file['status'] === 'Fully Sold')
                                        <span class="badge text-bg-secondary">Fully Sold</span>
                                    @elseif($file['status'] === 'Partially Sold')
                                        <span class="badge text-bg-warning">Partially Sold</span>
                                    @else
                                        <span class="badge text-bg-success">{{ $file['status'] }}</span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">{{ $file['amount_formatted'] }}</td>
                                <td class="small">{{ $file['last_sale_date'] }}</td>
                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-theme"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#sold-file-entries-{{ $file['purchase_file_id'] }}"
                                            aria-expanded="false">
                                        View sales
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="sold-file-entries-{{ $file['purchase_file_id'] }}">
                                <td colspan="10" class="bg-light-subtle">
                                    <div class="table-responsive p-2">
                                        <table class="table table-sm table-bordered table-theme mb-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Voucher</th>
                                                    <th>Area sold</th>
                                                    <th>Category</th>
                                                    <th>Sub category</th>
                                                    <th>Paid by</th>
                                                    <th class="text-end">Amount</th>
                                                    <th>Description</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($file['entries'] as $entry)
                                                    <tr>
                                                        <td>{{ $entry['date'] }}</td>
                                                        <td>{{ $entry['voucher'] }}</td>
                                                        <td class="fw-semibold">{{ $entry['area'] }}</td>
                                                        <td>{{ $entry['category'] }}</td>
                                                        <td>{{ $entry['party'] }}</td>
                                                        <td>{{ $entry['paid_by'] }}</td>
                                                        <td class="text-end">{{ $entry['amount'] }}</td>
                                                        <td class="small">{{ $entry['description'] }}</td>
                                                        <td>
                                                            <a href="{{ $entry['url'] }}" class="btn btn-sm btn-outline-theme">Open</a>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card card-theme">
    <div class="card-header py-3">
        <h2 class="h6 mb-0">Land records</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-theme align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Area (Kanal)</th>
                        <th>Location</th>
                        <th>Plots</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lands as $land)
                        <tr>
                            <td>{{ $land->id }}</td>
                            <td>{{ $land->name }}</td>
                            <td>{{ $land->total_area_kanal ?? '—' }}</td>
                            <td>{{ $land->location ?? '—' }}</td>
                            <td>{{ $land->plots_count }}</td>
                            <td>
                                <a href="{{ route('lands.show', $land) }}" class="btn btn-sm btn-outline-theme">View</a>
                                <a href="{{ route('lands.edit', $land) }}" class="btn btn-sm btn-outline-theme">Edit</a>
                                <form action="{{ route('lands.destroy', $land) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-danger-theme btn-delete-confirm" data-title="Delete Land?" data-text="This will delete the land and all plots and documents.">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No land records yet. <a href="{{ route('lands.create') }}">Add one</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $lands->links() }}</div>
    </div>
</div>
@endsection
