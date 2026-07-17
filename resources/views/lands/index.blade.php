@extends('layouts.app')

@section('title', 'Sale Land')

@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">Sale Land</h1>
        <p class="text-muted small mb-0">Sold land from Daybook file sales, plus land records.</p>
    </div>
    {{--<a href="{{ route('lands.create') }}" class="btn btn-pink">Add Land</a>--}}
</div>

<div class="card card-theme mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h6 mb-0">Sold files from Daybook (File Sale)</h2>
        <a href="{{ route('daybook.index') }}" class="btn btn-sm btn-outline-theme">Open Daybook</a>
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
            <p class="text-muted small mb-0">
                No file-sale sold area found yet. Create a Daybook entry linked to a File Sale file and enter sold area.
            </p>
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
                                        <a href="{{ route('sale.files.index', $file['project_id']) }}" class="text-decoration-none">{{ $file['project_name'] }}</a>
                                    @else
                                        {{ $file['project_name'] }}
                                    @endif
                                </td>
                                <td class="small">{{ $file['total_label'] }}</td>
                                <td class="small fw-semibold">{{ $file['sold_label'] }}</td>
                                <td class="small">{{ $file['remaining_label'] }}</td>
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
