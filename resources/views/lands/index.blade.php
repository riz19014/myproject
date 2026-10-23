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
    .sold-land-record-row { cursor: pointer; }
    .sold-land-record-row:hover { background: rgba(249, 115, 22, 0.06) !important; }
    #soldFileModal {
        z-index: 1060;
    }
    #soldFileModal .modal-dialog {
        pointer-events: auto;
    }
    .sold-file-modal-content {
        background: #ffffff !important;
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        overflow: hidden;
        opacity: 1 !important;
        pointer-events: auto;
    }
    .sold-file-modal-content .modal-header {
        background: #f8fafc !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .sold-file-modal-content .modal-body,
    .sold-file-modal-content .modal-footer {
        background: #ffffff !important;
    }
    .sold-file-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1.75rem;
    }
    .sold-file-modal-item {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        min-width: 0;
    }
    .sold-file-modal-item--wide { grid-column: 1 / -1; }
    .sold-file-modal-key {
        flex: 0 0 42%;
        color: #64748b;
        font-weight: 600;
        font-size: 0.78rem;
    }
    .sold-file-modal-val {
        flex: 1 1 auto;
        font-size: 0.88rem;
        color: #0f172a;
        word-break: break-word;
        text-align: right;
    }
    .sold-file-modal-docs a { display: block; }
    @media (max-width: 575.98px) {
        .sold-file-modal-grid { grid-template-columns: 1fr; }
    }
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
                            @php
                                $soldFileModalData = [
                                    'id' => $record['id'],
                                    'created_at' => $record['created_at'],
                                    'e_stamp_id' => ($record['e_stamp_id'] !== '' && $record['e_stamp_id'] !== null) ? $record['e_stamp_id'] : '—',
                                    'file_name' => $record['file_name'],
                                    'project_name' => $record['project_name'],
                                    'land_owner' => $record['land_owner'],
                                    'land_provider' => $record['land_provider'],
                                    'purchaser_name' => ($record['purchaser_name'] !== '' && $record['purchaser_name'] !== null) ? $record['purchaser_name'] : '—',
                                    'plot_label' => $record['plot_label'],
                                    'land_area_label' => $record['land_area_label'],
                                    'moza' => $record['moza'],
                                    'khewat_no' => $record['khewat_no'],
                                    'khatooni_no' => $record['khatooni_no'],
                                    'khasra' => $record['khasra'],
                                    'file_total_label' => $record['file_total_label'],
                                    'file_sold_label' => $record['file_sold_label'],
                                    'file_remaining_label' => $record['file_remaining_label'],
                                    'file_status' => $record['file_status'],
                                    'amount_formatted' => $record['amount_formatted'],
                                    'status_label' => $record['status_label'],
                                    'notes' => $record['notes'] ?? '—',
                                    'documents' => $record['documents'],
                                    'file_url' => route('purchase.files.show', $record['purchase_file_id']),
                                    'delete_url' => route('sale-land.records.destroy', $record['id']),
                                ];
                            @endphp
                            <tr class="sold-land-record-row"
                                tabindex="0"
                                role="button"
                                data-sold-file="{{ json_encode($soldFileModalData, JSON_HEX_APOS | JSON_HEX_QUOT) }}">
                                <td>{{ $loop->iteration }}</td>
                                <td class="small">{{ $record['created_at'] }}</td>
                                <td class="fw-semibold small">{{ $record['e_stamp_id'] !== '' && $record['e_stamp_id'] !== null ? $record['e_stamp_id'] : '—' }}</td>
                                <td>
                                    <div class="sold-land-detail">
                                        <a href="{{ route('purchase.files.show', $record['purchase_file_id']) }}" class="text-decoration-none fw-semibold" data-stop-sold-modal>
                                            {{ $record['file_name'] }}
                                        </a>
                                        <span class="sold-land-detail__meta">
                                            @if($record['project_id'])
                                                <a href="{{ route('sale.files.index', $record['project_id']) }}" class="text-decoration-none" data-stop-sold-modal>
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
                                <td class="small fw-semibold">{{ $record['purchaser_name'] !== '' && $record['purchaser_name'] !== null ? $record['purchaser_name'] : '—' }}</td>
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
                                            <a href="{{ $doc['url'] }}" target="_blank" rel="noopener" class="d-block text-decoration-none" data-stop-sold-modal>{{ $doc['name'] }}</a>
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

@push('modals')
{{-- Must sit outside .app-main so Bootstrap backdrop/stacking works --}}
<div class="modal fade" id="soldFileModal" tabindex="-1" aria-labelledby="soldFileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content sold-file-modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="soldFileModalLabel">Sold file</h5>
                    <p class="small text-muted mb-0" id="sold-file-modal-subtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="sold-file-modal-grid">
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Date</span><span id="sold-file-modal-date" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">eStamp</span><span id="sold-file-modal-estamp" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">File</span><span id="sold-file-modal-file" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Project</span><span id="sold-file-modal-project" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Purchaser</span><span id="sold-file-modal-purchaser" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Plot sold</span><span id="sold-file-modal-plot" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Land area</span><span id="sold-file-modal-area" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Amount</span><span id="sold-file-modal-amount" class="sold-file-modal-val fw-semibold"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Land owner</span><span id="sold-file-modal-owner" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Land provider</span><span id="sold-file-modal-provider" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Mouza</span><span id="sold-file-modal-moza" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Khasra</span><span id="sold-file-modal-khasra" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Khewat</span><span id="sold-file-modal-khewat" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Khatoni</span><span id="sold-file-modal-khatoni" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">File leftover</span><span id="sold-file-modal-leftover" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item"><span class="sold-file-modal-key">Status</span><span id="sold-file-modal-status" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item sold-file-modal-item--wide"><span class="sold-file-modal-key">Notes</span><span id="sold-file-modal-notes" class="sold-file-modal-val"></span></div>
                    <div class="sold-file-modal-item sold-file-modal-item--wide">
                        <span class="sold-file-modal-key">Documents</span>
                        <span id="sold-file-modal-docs" class="sold-file-modal-val sold-file-modal-docs"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between flex-wrap gap-2">
                <form id="sold-file-delete-form" method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button type="button"
                            class="btn btn-sm btn-danger-theme btn-delete-confirm"
                            data-title="Delete sold file?"
                            data-text="This will remove the sale record and restore leftover land on the purchase file."
                            data-confirm="Yes, delete it">
                        Delete
                    </button>
                </form>
                <div class="d-flex gap-2">
                    <a href="#" id="sold-file-modal-open-file" class="btn btn-sm btn-outline-theme" target="_blank" rel="noopener">Open file</a>
                    <button type="button" class="btn btn-sm btn-pink" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    var modalEl = document.getElementById('soldFileModal');
    if (!modalEl || !window.bootstrap) return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var deleteForm = document.getElementById('sold-file-delete-form');

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value || '—';
    }

    function escapeHtml(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function fillModal(data) {
        setText('sold-file-modal-subtitle', data.file_name || '');
        setText('sold-file-modal-date', data.created_at);
        setText('sold-file-modal-estamp', data.e_stamp_id);
        setText('sold-file-modal-file', data.file_name);
        setText('sold-file-modal-project', data.project_name);
        setText('sold-file-modal-purchaser', data.purchaser_name);
        setText('sold-file-modal-plot', data.plot_label);
        setText('sold-file-modal-area', data.land_area_label);
        setText('sold-file-modal-amount', data.amount_formatted);
        setText('sold-file-modal-owner', data.land_owner);
        setText('sold-file-modal-provider', data.land_provider);
        setText('sold-file-modal-moza', data.moza);
        setText('sold-file-modal-khasra', data.khasra);
        setText('sold-file-modal-khewat', data.khewat_no);
        setText('sold-file-modal-khatoni', data.khatooni_no);
        setText('sold-file-modal-leftover',
            'Total ' + (data.file_total_label || '—') +
            ' · Sold ' + (data.file_sold_label || '—') +
            ' · Left ' + (data.file_remaining_label || '—') +
            ' (' + (data.file_status || '—') + ')'
        );
        setText('sold-file-modal-status', data.status_label);
        setText('sold-file-modal-notes', data.notes);

        var docsEl = document.getElementById('sold-file-modal-docs');
        if (docsEl) {
            var docs = Array.isArray(data.documents) ? data.documents : [];
            if (!docs.length) {
                docsEl.textContent = '—';
            } else {
                docsEl.innerHTML = docs.map(function (doc) {
                    if (doc.url) {
                        return '<a href="' + escapeHtml(doc.url) + '" target="_blank" rel="noopener">' + escapeHtml(doc.name || 'Document') + '</a>';
                    }
                    return '<span>' + escapeHtml(doc.name || 'Document') + '</span>';
                }).join('');
            }
        }

        if (deleteForm && data.delete_url) {
            deleteForm.setAttribute('action', data.delete_url);
        }

        var openFile = document.getElementById('sold-file-modal-open-file');
        if (openFile) {
            openFile.href = data.file_url || '#';
            openFile.classList.toggle('d-none', !data.file_url);
        }
    }

    function openFromRow(row) {
        var raw = row.getAttribute('data-sold-file');
        if (!raw) return;
        try {
            fillModal(JSON.parse(raw));
            modal.show();
        } catch (e) {}
    }

    document.querySelectorAll('.sold-land-record-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('[data-stop-sold-modal], a, button, form')) return;
            openFromRow(row);
        });
        row.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openFromRow(row);
            }
        });
    });
})();
</script>
@endpush
