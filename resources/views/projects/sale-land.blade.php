@extends('layouts.app')

@section('title', 'Sale land — '.$project->name)

@section('content')
@php
    $formulaColumns = $saleLandSheet['formula_columns'] ?? [];
    $sheetRows = $saleLandSheet['rows'] ?? [];
    $formulaTotals = $saleLandSheet['formula_totals'] ?? ['total_land' => '—', 'formula_values' => []];
    $scopedPurchaseFiles = $scopedPurchaseFiles ?? collect();
    $scopedPurchaseFileIds = $scopedPurchaseFiles->pluck('id')->all();
    $fileCount = collect($sheetRows)->where('show_file_name', true)->count();
    $rowCount = count($sheetRows);
@endphp
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
        <h1 class="mb-1">Sale land</h1>
        <p class="text-muted small mb-1">
            Project: <strong>{{ $project->name }}</strong>
            @if($scopedPurchaseFiles->isNotEmpty())
                · File: <strong>{{ $scopedPurchaseFiles->pluck('file_name')->implode(', ') }}</strong>
                · <a href="{{ route('projects.sale-land', $project) }}">View all</a>
            @endif
        </p>
        @if($sheetRows !== [])
            <p class="text-muted small mb-0">
                {{ $fileCount }} {{ $fileCount === 1 ? 'file' : 'files' }}
                · {{ $rowCount }} mouza {{ $rowCount === 1 ? 'row' : 'rows' }}
                · Total land: <strong>{{ $formulaTotals['total_land'] }}</strong>
            </p>
        @endif
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end">
        @if($sheetRows !== [])
            <a href="#" class="btn btn-outline-theme btn-sm sale-land-pdf-link" id="sale-land-pdf-btn" data-base-url="{{ route('projects.sale-land.pdf', $project) }}">Download PDF</a>
        @endif
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-outline-theme btn-sm">Purchase files</a>
        <a href="{{ route('sale.files.index', $project) }}" class="btn btn-outline-theme btn-sm">Sale files</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme btn-sm">Back to project</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif

@if($sheetRows === [])
    <div class="card card-theme mb-4">
        <div class="card-body">
            <p class="text-muted small mb-0">
                No sale land records yet. Open <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}">purchase files</a>,
                click <strong>Sale land</strong> on a file, and confirm to generate formula files here.
            </p>
        </div>
    </div>
@else
    <div class="card card-theme mb-4">
        <div class="card-body border-bottom py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label for="sale-land-search" class="form-label small mb-1">Search</label>
                    <input type="search"
                           id="sale-land-search"
                           class="form-control form-control-theme form-control-sm"
                           placeholder="File, LP, owner, mouza, khasra…"
                           autocomplete="off">
                </div>
                <div class="col-md-7 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                    <span class="small text-muted" id="sale-land-search-count"></span>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-check-all">Select all</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-check-none">Clear</button>
                    <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-sm btn-outline-secondary">Exemption setup</a>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="sale-land-sheet-split" id="sale-land-sheet-split">
                <div class="sale-land-sheet-split__frozen">
                    <table class="table table-sm table-bordered table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--frozen">
                        <thead>
                            <tr>
                                <th class="sale-land-sheet__file-col">File name</th>
                                <th class="sale-land-sheet__sr-col text-center">SR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}" class="{{ $row['show_file_name'] ? 'sale-land-sheet__file-group-start' : '' }}">
                                    @if($row['show_file_name'])
                                        <td class="sale-land-sheet__file-name-cell" rowspan="{{ $row['file_name_rowspan'] }}">
                                            <div class="sale-land-sheet__file-name-wrap">
                                                <div class="sale-land-sheet__file-name-main">
                                                    <input type="checkbox"
                                                           class="form-check-input sale-land-file-check"
                                                           value="{{ $row['purchase_file_id'] }}"
                                                           id="sale-land-file-{{ $row['purchase_file_id'] }}"
                                                           aria-label="Include {{ $row['file_name'] }} in PDF"
                                                           @checked(in_array($row['purchase_file_id'], $scopedPurchaseFileIds, true))>
                                                    <label class="sale-land-sheet__file-name-text mb-0" for="sale-land-file-{{ $row['purchase_file_id'] }}">{{ $row['file_name'] }}</label>
                                                </div>
                                                <div class="sale-land-sheet__file-actions">
                                                    <form method="post"
                                                          action="{{ route('projects.sale-land.destroy', [$project, $row['purchase_file_id']]) }}"
                                                          class="sale-land-sheet__file-delete-form">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-danger sale-land-sheet__file-delete-btn btn-delete-confirm"
                                                                data-title="Delete sale land?"
                                                                data-text="Remove &quot;{{ $row['file_name'] }}&quot; from sale land? The purchase file and sellers will stay; only this sale land record and its formula overrides will be removed."
                                                                data-confirm="Yes, delete"
                                                                title="Delete sale land"
                                                                aria-label="Delete sale land for {{ $row['file_name'] }}">
                                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="sale-land-sheet__file-sale-link sale-land-open-sale-modal"
                                                            data-purchase-file-id="{{ $row['purchase_file_id'] }}"
                                                            title="Sell to customer"
                                                            aria-label="Sell {{ $row['file_name'] }} to customer">
                                                        <i class="bi bi-files" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                    <td class="sale-land-sheet__sr-col text-center">{{ $row['sr'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="sale-land-sheet__totals-row">
                                <td colspan="2" class="fw-semibold small">Total</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="sale-land-sheet-split__scroll">
                    <table class="table table-sm table-bordered table-theme mb-0 align-middle sale-land-sheet sale-land-sheet--scroll">
                        <thead>
                            <tr>
                                <th>LP</th>
                                <th>Land owner</th>
                                <th>Transfer to</th>
                                <th>Mouza</th>
                                <th>Khasra</th>
                                <th class="sale-land-sheet__land-col">Total land</th>
                                @foreach($formulaColumns as $column)
                                    <th class="text-end sale-land-sheet__formula-col" title="{{ $column['plot_label'] }} — {{ $column['component_label'] }}">
                                        {{ $column['short_label'] }}<br><span class="sale-land-sheet__formula-code">{{ $column['code'] }}</span>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sheetRows as $row)
                                <tr data-row-idx="{{ $loop->index }}"
                                    class="{{ $row['show_file_name'] ? 'sale-land-sheet__file-group-start' : '' }}"
                                    data-purchase-file-id="{{ $row['purchase_file_id'] }}"
                                    data-moza-key="{{ $row['moza_key'] }}"
                                    data-search-text="{{ e(strtolower(implode(' ', array_filter([
                                        $row['file_name'],
                                        $row['land_provider'],
                                        $row['land_owner'],
                                        $row['transfer_to'],
                                        $row['moza'],
                                        $row['khasra'],
                                        $row['total_land'],
                                        collect($row['formula_values'] ?? [])->pluck('display')->implode(' '),
                                        collect($row['formula_values'] ?? [])->pluck('breakdown')->implode(' '),
                                    ])))) }}">
                                    <td class="sale-land-sheet__editable"
                                        data-field="land_provider"
                                        data-value="{{ $row['land_provider'] }}"
                                        title="Double-click to edit LP">
                                        <span class="sale-land-sheet__editable-display">{{ $row['land_provider'] }}</span>
                                    </td>
                                    <td>{{ $row['land_owner'] }}</td>
                                    <td class="sale-land-sheet__editable"
                                        data-field="transfer_to"
                                        data-value="{{ $row['transfer_to'] }}"
                                        title="Double-click to edit transfer to">
                                        <span class="sale-land-sheet__editable-display">{{ $row['transfer_to'] }}</span>
                                    </td>
                                    <td>{{ $row['moza'] }}</td>
                                    <td>{{ $row['khasra'] }}</td>
                                    <td class="sale-land-sheet__land-col fw-semibold">{{ $row['total_land'] }}</td>
                                    @foreach($formulaColumns as $column)
                                        @php
                                            $formula = $row['formula_values'][$column['plot_key']] ?? null;
                                        @endphp
                                        <td class="text-end sale-land-sheet__formula-col">
                                            @if($formula)
                                                {{ $formula['display'] }}
                                                @if(($formula['breakdown'] ?? '—') !== '—')
                                                    <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="sale-land-sheet__totals-row">
                                <td colspan="5" class="fw-semibold small">Total</td>
                                <td class="sale-land-sheet__land-col fw-semibold">{{ $formulaTotals['total_land'] }}</td>
                                @foreach($formulaColumns as $column)
                                    @php
                                        $formula = $formulaTotals['formula_values'][$column['plot_key']] ?? null;
                                    @endphp
                                    <td class="text-end sale-land-sheet__formula-col">
                                        @if($formula)
                                            {{ $formula['display'] }}
                                            @if(($formula['breakdown'] ?? '—') !== '—')
                                                <div class="sale-land-sheet__formula-breakdown">{{ $formula['breakdown'] }}</div>
                                            @endif
                                        @else
                                            —
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <p class="text-muted small text-center py-4 mb-0 d-none" id="sale-land-search-empty">No rows match your search.</p>
        </div>
    </div>
@endif
@endsection

@if($sheetRows !== [])
@push('modals')
    <div class="modal fade" id="sale-land-sale-modal" tabindex="-1" aria-labelledby="sale-land-sale-modal-title" aria-hidden="true" data-bs-focus="false">
        <div class="modal-dialog modal-fullscreen-lg-down sale-land-sale-modal-dialog" style="max-width: 96vw;">
            <div class="modal-content card-theme sale-land-sale-modal-content">
                <div class="modal-header flex-shrink-0">
                    <h2 class="modal-title h5 mb-0" id="sale-land-sale-modal-title">Sell to customer</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="#" id="sale-land-sale-form" class="sale-land-sale-modal-form">
                    @csrf
                    <div class="modal-body sale-land-sale-modal__scroll">
                        @if($errors->any())
                            <div class="alert alert-danger small">
                                <ul class="mb-0 ps-3">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <p class="text-muted small mb-3" id="sale-land-sale-modal-meta">
                            Project: <strong>{{ $project->name }}</strong>
                            · Total land: <strong id="sale-land-sale-total-land">—</strong>
                        </p>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <h3 class="h6 mb-0">Select mouza</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-sale-mouza-all">Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="sale-land-sale-mouza-none">Clear</button>
                            </div>
                        </div>
                        <p class="text-muted small mb-2">Choose one or more mouza rows to sell from. Plot file availability below updates from your selection.</p>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered table-striped table-theme mb-0 align-middle sale-land-sale-sheet-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 52px;">Pick</th>
                                        <th>Mouza</th>
                                        <th>Khasra</th>
                                        <th>LP</th>
                                        <th>Owner</th>
                                        <th>Transfer to</th>
                                        <th class="text-end">Land</th>
                                    </tr>
                                </thead>
                                <tbody id="sale-land-sale-mouza-body"></tbody>
                            </table>
                        </div>
                        <p class="small text-muted mb-3" id="sale-land-sale-mouza-count"></p>

                        <h3 class="h6 mb-2">Choose plot file type to sell</h3>
                        <p class="text-muted small mb-3">Select which exempt plot file you are selling to the customer (e.g. 2K Residential, 1K Residential).</p>
                        <div class="table-responsive mb-0">
                            <table class="table table-sm table-bordered table-striped table-theme mb-0 align-middle sale-land-sale-sheet-table">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 52px;">Pick</th>
                                        <th style="width: 72px;">Code</th>
                                        <th>Plot file</th>
                                        <th class="text-end" style="width: 110px;">Total</th>
                                        <th class="text-end" style="width: 110px;">Left</th>
                                    </tr>
                                </thead>
                                <tbody id="sale-land-sale-plot-options"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="sale-land-sale-modal__fields flex-shrink-0">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label for="sale_land_customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select form-select-theme @error('customer_id') is-invalid @enderror"
                                        id="sale_land_customer_id"
                                        name="customer_id"
                                        required>
                                    <option value="">Select customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" @selected((int) old('customer_id') === (int) $customer->id)>{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if($customers->isEmpty())
                                    <div class="form-text">No customers yet. <a href="{{ route('customers.create') }}">Add a customer</a> first.</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label for="sale_land_plot_quantity" class="form-label">Plot quantity <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control form-control-theme @error('plot_quantity') is-invalid @enderror"
                                       id="sale_land_plot_quantity"
                                       name="plot_quantity"
                                       value="{{ old('plot_quantity', 1) }}"
                                       min="1"
                                       max="999"
                                       required>
                                @error('plot_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <div class="form-text" id="sale-land-sale-qty-hint"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="sale_land_total_amount" class="form-label">Amount (Rs) <span class="text-danger">*</span></label>
                                <input type="number"
                                       class="form-control form-control-theme @error('total_amount') is-invalid @enderror"
                                       id="sale_land_total_amount"
                                       name="total_amount"
                                       value="{{ old('total_amount') }}"
                                       min="0"
                                       step="0.01"
                                       required>
                                @error('total_amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer flex-wrap gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-pink" id="sale-land-sale-submit">Save sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endpush
@endif

@push('head')
<style>
    .sale-land-sheet-split {
        display: flex;
        align-items: flex-start;
        width: 100%;
    }
    .sale-land-sheet-split__frozen {
        flex: 0 0 auto;
        z-index: 2;
        background: #fff;
        border-right: 2px solid #dee2e6;
    }
    .sale-land-sheet-split__scroll {
        flex: 1 1 auto;
        min-width: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sale-land-sheet {
        --sale-land-sr-width: 48px;
        --sale-land-file-width: 190px;
        margin-bottom: 0;
    }
    .sale-land-sheet thead th {
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
        vertical-align: bottom;
        background: #f5f5f5 !important;
    }
    .sale-land-sheet--scroll thead th {
        position: sticky;
        top: 0;
        z-index: 1;
    }
    .sale-land-sheet tbody td {
        font-size: 0.85rem;
        vertical-align: middle;
    }
    .sale-land-sheet__row--hidden {
        display: none;
    }
    .sale-land-sheet tbody tr.is-hovered td {
        background: #f8f9fa !important;
    }
    .sale-land-sheet__file-group-start td {
        border-top: 2px solid #adb5bd !important;
    }
    .sale-land-sheet__file-group-start:first-child td,
    .sale-land-sheet tbody tr:first-child td {
        border-top: 0 !important;
    }
    .sale-land-sheet__sr-col {
        width: var(--sale-land-sr-width);
        min-width: var(--sale-land-sr-width);
        max-width: var(--sale-land-sr-width);
    }
    .sale-land-sheet__file-col {
        width: var(--sale-land-file-width);
        min-width: var(--sale-land-file-width);
        max-width: var(--sale-land-file-width);
    }
    .sale-land-sheet__file-name-wrap {
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
    }
    .sale-land-sheet__file-name-main {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        align-items: flex-start;
        gap: 0.4rem;
    }
    .sale-land-sheet__file-name-text {
        flex: 1 1 auto;
        min-width: 0;
        word-break: break-word;
        cursor: pointer;
        font-weight: 600;
    }
    .sale-land-file-check {
        flex: 0 0 auto;
        margin-top: 0.2rem;
    }
    .sale-land-sheet__file-actions {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.25rem;
    }
    .sale-land-sheet__file-delete-form {
        flex: 0 0 auto;
    }
    .sale-land-sheet__file-delete-btn {
        padding: 0.15rem 0.35rem;
        line-height: 1;
        font-size: 0.75rem;
    }
    .sale-land-sheet__file-sale-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.65rem;
        height: 1.65rem;
        font-size: 0.75rem;
        color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.08);
        border: 1px solid rgba(249, 115, 22, 0.22);
        border-radius: 0.25rem;
        text-decoration: none;
        line-height: 1;
        padding: 0;
        transition: background 0.15s ease, border-color 0.15s ease;
    }
    .sale-land-sheet__file-sale-link:hover {
        background: rgba(249, 115, 22, 0.14);
        border-color: rgba(249, 115, 22, 0.35);
        color: var(--accent-orange, #f97316);
    }
    .sale-land-sale-sheet-table th,
    .sale-land-sale-sheet-table td {
        min-width: 72px;
        font-size: 0.88rem;
        vertical-align: middle;
    }
    #sale-land-sale-modal .sale-land-sale-modal-dialog {
        margin: 0.75rem auto;
        height: calc(100vh - 1.5rem);
        max-height: calc(100vh - 1.5rem);
    }
    #sale-land-sale-modal .sale-land-sale-modal-content {
        height: 100%;
        max-height: calc(100vh - 1.5rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    #sale-land-sale-modal .sale-land-sale-modal-form {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }
    #sale-land-sale-modal .sale-land-sale-modal__scroll {
        flex: 1 1 auto;
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
    }
    #sale-land-sale-modal .sale-land-sale-modal__fields {
        flex: 0 0 auto;
        padding: 1rem 1.25rem;
        background: #fff;
        border-top: 1px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 -4px 16px rgba(15, 23, 42, 0.06);
    }
    #sale-land-sale-modal .modal-footer {
        background: #fff;
    }
    @media (max-width: 991.98px) {
        #sale-land-sale-modal .sale-land-sale-modal-dialog {
            margin: 0;
            height: 100vh;
            max-height: 100vh;
        }
        #sale-land-sale-modal .sale-land-sale-modal-content {
            max-height: 100vh;
            border-radius: 0;
        }
    }
    .sale-land-sale-mouza-row.is-selected td {
        background: rgba(249, 115, 22, 0.08) !important;
    }
    .sale-land-sale-mouza-row {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .sale-land-sale-plot-row {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .sale-land-sale-plot-row:hover:not(.is-disabled) {
        background: rgba(249, 115, 22, 0.05) !important;
    }
    .sale-land-sale-plot-row.is-selected td {
        background: rgba(249, 115, 22, 0.1) !important;
    }
    .sale-land-sale-plot-row.is-disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .sale-land-pdf-link.is-loading {
        pointer-events: none;
        opacity: 0.65;
    }
    .sale-land-sheet__formula-code {
        font-size: 0.7rem;
        font-weight: 600;
        color: #6c757d;
    }
    .sale-land-sheet__formula-breakdown {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 0.15rem;
        line-height: 1.3;
    }
    .sale-land-sheet__formula-col {
        min-width: 90px;
    }
    .sale-land-sheet__totals-row td {
        background: #f5f5f5 !important;
        border-top: 2px solid #6c757d !important;
        font-weight: 600;
    }
    .sale-land-sheet__land-col {
        white-space: nowrap;
        min-width: 130px;
    }
    .sale-land-sheet__editable {
        cursor: text;
        min-width: 100px;
    }
    .sale-land-sheet__editable.is-editing {
        padding: 0.25rem;
    }
    .sale-land-sheet__editable-input {
        width: 100%;
        min-width: 100px;
        font-size: 0.85rem;
        padding: 0.25rem 0.4rem;
    }
    .sale-land-sheet__editable.is-saving {
        opacity: 0.6;
    }
</style>
@endpush

@if($sheetRows !== [])
@push('scripts')
<script>
(function() {
    function syncSaleLandRowHeights() {
        var frozenRows = document.querySelectorAll('.sale-land-sheet--frozen tbody tr');
        var scrollRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr');
        var frozenHeadRows = document.querySelectorAll('.sale-land-sheet--frozen thead tr');
        var scrollHeadRows = document.querySelectorAll('.sale-land-sheet--scroll thead tr');

        frozenHeadRows.forEach(function(fRow, i) {
            var sRow = scrollHeadRows[i];
            if (!sRow) {
                return;
            }
            fRow.style.height = '';
            sRow.style.height = '';
            var headH = Math.max(fRow.offsetHeight, sRow.offsetHeight);
            fRow.style.height = headH + 'px';
            sRow.style.height = headH + 'px';
        });

        frozenRows.forEach(function(fRow, i) {
            var sRow = scrollRows[i];
            if (!sRow) {
                return;
            }
            fRow.style.height = '';
            sRow.style.height = '';
            var rowH = Math.max(fRow.offsetHeight, sRow.offsetHeight);
            fRow.style.height = rowH + 'px';
            sRow.style.height = rowH + 'px';
        });

        var frozenFoot = document.querySelector('.sale-land-sheet--frozen tfoot tr');
        var scrollFoot = document.querySelector('.sale-land-sheet--scroll tfoot tr');
        if (frozenFoot && scrollFoot) {
            frozenFoot.style.height = '';
            scrollFoot.style.height = '';
            var footH = Math.max(frozenFoot.offsetHeight, scrollFoot.offsetHeight);
            frozenFoot.style.height = footH + 'px';
            scrollFoot.style.height = footH + 'px';
        }
    }

    syncSaleLandRowHeights();
    window.addEventListener('resize', syncSaleLandRowHeights);
    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(syncSaleLandRowHeights);
    }

    var updateUrl = @json(route('projects.sale-land.moza-row.update', $project));
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var token = csrf ? csrf.getAttribute('content') : '';
    var activeCell = null;

    function displayValue(value) {
        var v = String(value || '').trim();
        return v === '' ? '—' : v;
    }

    function closeEditor(cell, restore) {
        if (!cell || !cell.classList.contains('is-editing')) {
            return;
        }
        var input = cell.querySelector('.sale-land-sheet__editable-input');
        var previous = cell.dataset.value || '';
        var value = restore ? previous : (input ? input.value.trim() : previous);
        cell.classList.remove('is-editing');
        cell.innerHTML = '<span class="sale-land-sheet__editable-display">' + escapeHtml(displayValue(value)) + '</span>';
        cell.dataset.value = value;
        if (activeCell === cell) {
            activeCell = null;
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function saveCell(cell, input) {
        var row = cell.closest('tr');
        var field = cell.dataset.field;
        var value = input.value.trim();
        var purchaseFileId = row ? row.dataset.purchaseFileId : '';
        var mozaKey = row ? row.dataset.mozaKey : '';

        cell.classList.add('is-saving');

        fetch(updateUrl, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                purchase_file_id: parseInt(purchaseFileId, 10),
                moza_key: mozaKey,
                field: field,
                value: value
            })
        })
        .then(function(res) {
            if (!res.ok) {
                throw new Error('Save failed');
            }
            return res.json();
        })
        .then(function(data) {
            cell.classList.remove('is-saving', 'is-editing');
            cell.dataset.value = value;
            cell.innerHTML = '<span class="sale-land-sheet__editable-display">' + escapeHtml(displayValue(data.value)) + '</span>';
            activeCell = null;
            syncSaleLandRowHeights();
        })
        .catch(function() {
            cell.classList.remove('is-saving');
            closeEditor(cell, true);
            alert('Could not save. Please try again.');
        });
    }

    function openEditor(cell) {
        if (activeCell && activeCell !== cell) {
            closeEditor(activeCell, true);
        }
        if (cell.classList.contains('is-editing')) {
            return;
        }

        var current = cell.dataset.value || '';
        cell.classList.add('is-editing');
        cell.innerHTML = '<input type="text" class="sale-land-sheet__editable-input form-control form-control-theme" value="' + escapeHtml(current === '—' ? '' : current) + '">';
        var input = cell.querySelector('.sale-land-sheet__editable-input');
        activeCell = cell;
        input.focus();
        input.select();

        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveCell(cell, input);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeEditor(cell, true);
            }
        });

        input.addEventListener('blur', function() {
            setTimeout(function() {
                if (cell.classList.contains('is-editing') && !cell.classList.contains('is-saving')) {
                    closeEditor(cell, true);
                }
            }, 120);
        });
    }

    document.querySelectorAll('.sale-land-sheet--scroll tbody tr').forEach(function(sRow) {
        var idx = sRow.dataset.rowIdx;
        var fRow = document.querySelector('.sale-land-sheet--frozen tbody tr[data-row-idx="' + idx + '"]');
        function setHover(on) {
            sRow.classList.toggle('is-hovered', on);
            if (fRow) {
                fRow.classList.toggle('is-hovered', on);
            }
        }
        sRow.addEventListener('mouseenter', function() { setHover(true); });
        sRow.addEventListener('mouseleave', function() { setHover(false); });
        if (fRow) {
            fRow.addEventListener('mouseenter', function() { setHover(true); });
            fRow.addEventListener('mouseleave', function() { setHover(false); });
        }
    });

    document.querySelectorAll('.sale-land-sheet__editable').forEach(function(cell) {
        cell.addEventListener('dblclick', function() {
            openEditor(cell);
        });
    });

    var searchInput = document.getElementById('sale-land-search');
    var searchCount = document.getElementById('sale-land-search-count');
    var searchEmpty = document.getElementById('sale-land-search-empty');
    var sheetSplit = document.getElementById('sale-land-sheet-split');
    var totalRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr').length;

    function applySaleLandSearch() {
        var q = searchInput ? searchInput.value.trim().toLowerCase() : '';
        var scrollRows = document.querySelectorAll('.sale-land-sheet--scroll tbody tr');
        var fileIdsToShow = new Set();
        var visibleCount = 0;

        scrollRows.forEach(function(row) {
            var haystack = row.dataset.searchText || '';
            var fileId = row.dataset.purchaseFileId;
            if (q === '' || haystack.indexOf(q) !== -1) {
                fileIdsToShow.add(fileId);
            }
        });

        if (q !== '') {
            document.querySelectorAll('.sale-land-sheet__file-name-text').forEach(function(label) {
                if (label.textContent.toLowerCase().indexOf(q) !== -1) {
                    var frozenRow = label.closest('tr');
                    if (frozenRow) {
                        var scrollRow = document.querySelector('.sale-land-sheet--scroll tbody tr[data-row-idx="' + frozenRow.dataset.rowIdx + '"]');
                        if (scrollRow) {
                            fileIdsToShow.add(scrollRow.dataset.purchaseFileId);
                        }
                    }
                }
            });
        }

        scrollRows.forEach(function(row) {
            var show = q === '' || fileIdsToShow.has(row.dataset.purchaseFileId);
            row.classList.toggle('sale-land-sheet__row--hidden', !show);
            if (show) {
                visibleCount++;
            }
        });

        document.querySelectorAll('.sale-land-sheet--frozen tbody tr').forEach(function(row) {
            var scrollRow = document.querySelector('.sale-land-sheet--scroll tbody tr[data-row-idx="' + row.dataset.rowIdx + '"]');
            var show = scrollRow && !scrollRow.classList.contains('sale-land-sheet__row--hidden');
            row.classList.toggle('sale-land-sheet__row--hidden', !show);
        });

        if (searchCount) {
            if (q === '') {
                searchCount.textContent = totalRows + ' mouza ' + (totalRows === 1 ? 'row' : 'rows');
            } else {
                searchCount.textContent = 'Showing ' + visibleCount + ' of ' + totalRows + ' rows';
            }
        }

        if (searchEmpty) {
            searchEmpty.classList.toggle('d-none', q === '' || visibleCount > 0);
        }

        if (sheetSplit) {
            sheetSplit.classList.toggle('d-none', q !== '' && visibleCount === 0);
        }

        syncSaleLandRowHeights();
    }

    if (searchInput) {
        searchInput.addEventListener('input', applySaleLandSearch);
        applySaleLandSearch();
    }

    var checkAllBtn = document.getElementById('sale-land-check-all');
    var checkNoneBtn = document.getElementById('sale-land-check-none');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.sale-land-file-check').forEach(function(cb) {
                cb.checked = true;
            });
        });
    }
    if (checkNoneBtn) {
        checkNoneBtn.addEventListener('click', function() {
            document.querySelectorAll('.sale-land-file-check').forEach(function(cb) {
                cb.checked = false;
            });
        });
    }

    var saleLandModalData = @json($saleLandModalData ?? []);
    var saleLandSaleUrlTemplate = @json(route('projects.sale-land.sale.store', [$project, '__FILE__']));
    var saleLandModalEl = document.getElementById('sale-land-sale-modal');
    var saleLandSaleForm = document.getElementById('sale-land-sale-form');
    var saleLandModalInstance = saleLandModalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(saleLandModalEl) : null;
    var saleLandPlotOptionsEl = document.getElementById('sale-land-sale-plot-options');
    var saleLandMouzaBodyEl = document.getElementById('sale-land-sale-mouza-body');
    var saleLandQtyInput = document.getElementById('sale_land_plot_quantity');
    var saleLandQtyHint = document.getElementById('sale-land-sale-qty-hint');
    var saleLandSubmitBtn = document.getElementById('sale-land-sale-submit');
    var activeSaleLandFile = null;
    var activeSaleLandPlot = null;

    function escapeSaleLandHtml(value) {
        return String(value || '—')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatPlotFileCount(value) {
        var n = parseFloat(value);
        if (isNaN(n) || n <= 0) {
            return '0';
        }
        if (Math.abs(n - Math.round(n)) < 0.0001) {
            return String(Math.round(n));
        }
        return String(parseFloat(n.toFixed(4)));
    }

    function getSelectedMouzaKeys() {
        if (!saleLandMouzaBodyEl) {
            return [];
        }
        return Array.from(saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-check:checked'))
            .map(function(cb) { return cb.value; });
    }

    function buildPlotOptionsFromSelection(file) {
        var selectedKeys = getSelectedMouzaKeys();
        var selectedRows = (file.mouza_rows || []).filter(function(row) {
            return selectedKeys.indexOf(String(row.moza_key)) !== -1;
        });

        return (file.plot_options || []).map(function(option) {
            var available = selectedRows.reduce(function(sum, row) {
                var formula = (row.formula_values || {})[option.plot_key];
                return sum + parseFloat(formula ? formula.file_count : 0);
            }, 0);
            var usedQty = parseFloat(option.used_quantity || 0);
            var remaining = Math.max(0, available - usedQty);

            return Object.assign({}, option, {
                available_files: available,
                available_display: formatPlotFileCount(available),
                remaining_files: remaining,
                remaining_display: formatPlotFileCount(remaining),
            });
        });
    }

    function updateMouzaRowStates() {
        if (!saleLandMouzaBodyEl) {
            return;
        }
        saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-row').forEach(function(row) {
            var cb = row.querySelector('.sale-land-sale-mouza-check');
            row.classList.toggle('is-selected', !!(cb && cb.checked));
        });
        var countEl = document.getElementById('sale-land-sale-mouza-count');
        var selected = getSelectedMouzaKeys().length;
        var total = (activeSaleLandFile && activeSaleLandFile.mouza_rows) ? activeSaleLandFile.mouza_rows.length : 0;
        if (countEl) {
            if (selected === 0) {
                countEl.textContent = 'No mouza selected — pick at least one row above.';
            } else {
                countEl.textContent = selected + ' of ' + total + ' mouza ' + (total === 1 ? 'row' : 'rows') + ' selected';
            }
        }
    }

    function onMouzaSelectionChange() {
        updateMouzaRowStates();
        if (activeSaleLandFile) {
            renderSaleLandPlotOptions(activeSaleLandFile);
        }
    }

    function renderSaleLandMouzaRows(file) {
        if (!saleLandMouzaBodyEl) {
            return;
        }
        saleLandMouzaBodyEl.innerHTML = (file.mouza_rows || []).map(function(row, index) {
            var inputId = 'sale_land_moza_' + file.purchase_file_id + '_' + index;
            return '<tr class="sale-land-sale-mouza-row is-selected" data-moza-key="' + escapeSaleLandHtml(row.moza_key) + '">' +
                '<td class="text-center">' +
                    '<input type="checkbox" class="form-check-input sale-land-sale-mouza-check" id="' + inputId + '" value="' + escapeSaleLandHtml(row.moza_key) + '" checked aria-label="Select mouza ' + escapeSaleLandHtml(row.moza) + '">' +
                '</td>' +
                '<td><label class="mb-0" for="' + inputId + '">' + escapeSaleLandHtml(row.moza) + '</label></td>' +
                '<td>' + escapeSaleLandHtml(row.khasra) + '</td>' +
                '<td>' + escapeSaleLandHtml(row.land_provider) + '</td>' +
                '<td>' + escapeSaleLandHtml(row.land_owner) + '</td>' +
                '<td>' + escapeSaleLandHtml(row.transfer_to) + '</td>' +
                '<td class="text-end text-nowrap">' + escapeSaleLandHtml(row.total_land) + '</td>' +
            '</tr>';
        }).join('');

        saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-row').forEach(function(row) {
            row.addEventListener('click', function(e) {
                if (e.target && (e.target.type === 'checkbox' || e.target.tagName === 'LABEL')) {
                    return;
                }
                var cb = row.querySelector('.sale-land-sale-mouza-check');
                if (cb) {
                    cb.checked = !cb.checked;
                    onMouzaSelectionChange();
                }
            });
        });
        saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-check').forEach(function(cb) {
            cb.addEventListener('change', onMouzaSelectionChange);
        });
        onMouzaSelectionChange();
    }

    var saleLandMouzaAllBtn = document.getElementById('sale-land-sale-mouza-all');
    var saleLandMouzaNoneBtn = document.getElementById('sale-land-sale-mouza-none');
    if (saleLandMouzaAllBtn) {
        saleLandMouzaAllBtn.addEventListener('click', function() {
            saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-check').forEach(function(cb) {
                cb.checked = true;
            });
            onMouzaSelectionChange();
        });
    }
    if (saleLandMouzaNoneBtn) {
        saleLandMouzaNoneBtn.addEventListener('click', function() {
            saleLandMouzaBodyEl.querySelectorAll('.sale-land-sale-mouza-check').forEach(function(cb) {
                cb.checked = false;
            });
            onMouzaSelectionChange();
        });
    }

    function findSaleLandFile(fileId) {
        return saleLandModalData.find(function(file) {
            return String(file.purchase_file_id) === String(fileId);
        }) || null;
    }

    function updateSaleLandPlotSelection(rowEl) {
        if (!saleLandPlotOptionsEl) {
            return;
        }
        saleLandPlotOptionsEl.querySelectorAll('.sale-land-sale-plot-row').forEach(function(el) {
            el.classList.remove('is-selected');
            var radio = el.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = false;
            }
        });
        if (!rowEl) {
            activeSaleLandPlot = null;
            if (saleLandQtyHint) {
                saleLandQtyHint.textContent = '';
            }
            if (saleLandSubmitBtn) {
                saleLandSubmitBtn.disabled = true;
            }
            return;
        }
        rowEl.classList.add('is-selected');
        var radio = rowEl.querySelector('input[type="radio"]');
        if (radio) {
            radio.checked = true;
            activeSaleLandPlot = {
                component: radio.dataset.component,
                plotType: radio.dataset.plotType,
                remaining: parseFloat(radio.dataset.remaining || '0')
            };
        }
        if (saleLandQtyInput && activeSaleLandPlot) {
            var maxQty = Math.max(1, Math.floor(activeSaleLandPlot.remaining));
            saleLandQtyInput.max = String(maxQty);
            if (parseInt(saleLandQtyInput.value || '1', 10) > maxQty) {
                saleLandQtyInput.value = String(maxQty);
            }
        }
        if (saleLandQtyHint && activeSaleLandPlot && radio) {
            saleLandQtyHint.textContent = 'Available: ' + radio.dataset.remainingDisplay + ' plot file(s)';
        }
        if (saleLandSubmitBtn && activeSaleLandPlot) {
            saleLandSubmitBtn.disabled = getSelectedMouzaKeys().length === 0;
        }
    }

    function renderSaleLandPlotOptions(file) {
        if (!saleLandPlotOptionsEl) {
            return;
        }
        saleLandPlotOptionsEl.innerHTML = '';
        activeSaleLandPlot = null;

        if (getSelectedMouzaKeys().length === 0) {
            saleLandPlotOptionsEl.innerHTML =
                '<tr><td colspan="5" class="text-muted small text-center py-3">Select at least one mouza to see plot file options.</td></tr>';
            if (saleLandSubmitBtn) {
                saleLandSubmitBtn.disabled = true;
            }
            return;
        }

        var plotOptions = buildPlotOptionsFromSelection(file);
        var hasSelectable = false;
        plotOptions.forEach(function(option, index) {
            var remaining = parseFloat(option.remaining_files || 0);
            var disabled = remaining < 0.9999;
            if (!disabled) {
                hasSelectable = true;
            }
            var optionId = 'sale_land_plot_' + file.purchase_file_id + '_' + index;
            var row = document.createElement('tr');
            row.className = 'sale-land-sale-plot-row' + (disabled ? ' is-disabled' : '');
            row.innerHTML =
                '<td class="text-center">' +
                    '<input type="radio" class="form-check-input" id="' + optionId + '" name="plot_choice"' +
                    ' data-component="' + escapeSaleLandHtml(option.component_slug) + '"' +
                    ' data-plot-type="' + escapeSaleLandHtml(option.plot_slug) + '"' +
                    ' data-remaining="' + remaining + '"' +
                    ' data-remaining-display="' + escapeSaleLandHtml(option.remaining_display) + '"' +
                    (disabled ? ' disabled' : '') + ' aria-label="' + escapeSaleLandHtml(option.label) + '">' +
                '</td>' +
                '<td class="fw-semibold">' + escapeSaleLandHtml(option.code) + '</td>' +
                '<td>' + escapeSaleLandHtml(option.label) + '</td>' +
                '<td class="text-end">' + escapeSaleLandHtml(option.available_display) + '</td>' +
                '<td class="text-end fw-semibold">' + escapeSaleLandHtml(option.remaining_display) + '</td>';

            if (!disabled) {
                row.addEventListener('click', function(e) {
                    if (e.target && e.target.type === 'radio') {
                        updateSaleLandPlotSelection(row);
                        return;
                    }
                    var radio = row.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        updateSaleLandPlotSelection(row);
                    }
                });
                var radio = row.querySelector('input[type="radio"]');
                if (radio) {
                    radio.addEventListener('change', function() {
                        updateSaleLandPlotSelection(row);
                    });
                }
            }
            saleLandPlotOptionsEl.appendChild(row);
        });

        if (!hasSelectable) {
            saleLandPlotOptionsEl.innerHTML =
                '<tr><td colspan="5" class="text-muted small text-center py-3">No plot files available for the selected mouza(s).</td></tr>';
            if (saleLandSubmitBtn) {
                saleLandSubmitBtn.disabled = true;
            }
            return;
        }

        var firstEnabled = saleLandPlotOptionsEl.querySelector('.sale-land-sale-plot-row:not(.is-disabled)');
        if (firstEnabled) {
            updateSaleLandPlotSelection(firstEnabled);
        } else if (saleLandSubmitBtn) {
            saleLandSubmitBtn.disabled = true;
        }
    }

    function openSaleLandSaleModal(fileId) {
        var file = findSaleLandFile(fileId);
        if (!file || !saleLandModalInstance || !saleLandSaleForm) {
            return;
        }
        activeSaleLandFile = file;

        var titleEl = document.getElementById('sale-land-sale-modal-title');
        if (titleEl) {
            titleEl.textContent = file.file_name + ' — Sell to customer';
        }
        var totalLandEl = document.getElementById('sale-land-sale-total-land');
        if (totalLandEl) {
            totalLandEl.textContent = file.total_land;
        }

        renderSaleLandMouzaRows(file);
        saleLandSaleForm.action = saleLandSaleUrlTemplate.replace('__FILE__', String(file.purchase_file_id));
        saleLandModalInstance.show();
    }

    document.querySelectorAll('.sale-land-open-sale-modal').forEach(function(btn) {
        btn.addEventListener('click', function() {
            openSaleLandSaleModal(this.dataset.purchaseFileId);
        });
    });

    if (saleLandSaleForm) {
        saleLandSaleForm.addEventListener('submit', function(e) {
            var selectedMouzas = getSelectedMouzaKeys();
            if (selectedMouzas.length === 0) {
                e.preventDefault();
                alert('Select at least one mouza to sell from.');
                return;
            }
            if (!activeSaleLandPlot) {
                e.preventDefault();
                alert('Choose a plot file type to sell from.');
                return;
            }
            saleLandSaleForm.querySelectorAll('input[name="moza_keys[]"]').forEach(function(el) {
                el.remove();
            });
            selectedMouzas.forEach(function(key) {
                var mozaInput = document.createElement('input');
                mozaInput.type = 'hidden';
                mozaInput.name = 'moza_keys[]';
                mozaInput.value = key;
                saleLandSaleForm.appendChild(mozaInput);
            });
            var existingComponent = saleLandSaleForm.querySelector('input[name="component"]');
            var existingPlotType = saleLandSaleForm.querySelector('input[name="plot_type"]');
            if (existingComponent) {
                existingComponent.remove();
            }
            if (existingPlotType) {
                existingPlotType.remove();
            }
            var componentInput = document.createElement('input');
            componentInput.type = 'hidden';
            componentInput.name = 'component';
            componentInput.value = activeSaleLandPlot.component;
            var plotTypeInput = document.createElement('input');
            plotTypeInput.type = 'hidden';
            plotTypeInput.name = 'plot_type';
            plotTypeInput.value = activeSaleLandPlot.plotType;
            saleLandSaleForm.appendChild(componentInput);
            saleLandSaleForm.appendChild(plotTypeInput);
        });
    }
})();
</script>
@endpush
@endif

@push('scripts')
<script>
(function() {
    function setPdfLinkLoading(link, loading) {
        if (loading) {
            if (!link.dataset.pdfOriginalHtml) {
                link.dataset.pdfOriginalHtml = link.innerHTML;
            }
            link.classList.add('is-loading', 'disabled');
            link.setAttribute('aria-disabled', 'true');
            link.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        } else {
            link.classList.remove('is-loading', 'disabled');
            link.removeAttribute('aria-disabled');
            if (link.dataset.pdfOriginalHtml) {
                link.innerHTML = link.dataset.pdfOriginalHtml;
            }
        }
    }

    function buildPdfUrl(baseUrl) {
        var checked = document.querySelectorAll('.sale-land-file-check:checked');
        if (!checked.length) {
            return baseUrl;
        }
        var params = Array.from(checked).map(function(cb) {
            return 'purchase_file[]=' + encodeURIComponent(cb.value);
        });
        return baseUrl + (baseUrl.indexOf('?') === -1 ? '?' : '&') + params.join('&');
    }

    var pdfBtn = document.getElementById('sale-land-pdf-btn');
    if (pdfBtn) {
        pdfBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (pdfBtn.classList.contains('is-loading')) {
                return;
            }

            var baseUrl = pdfBtn.getAttribute('data-base-url');
            if (!baseUrl) {
                return;
            }

            var href = buildPdfUrl(baseUrl);
            setPdfLinkLoading(pdfBtn, true);

            fetch(href, { credentials: 'same-origin', headers: { Accept: 'application/pdf' } })
                .then(function(res) {
                    if (!res.ok) {
                        throw new Error('pdf');
                    }
                    var cd = res.headers.get('Content-Disposition');
                    var fname = 'sale-land.pdf';
                    if (cd) {
                        var mStar = /filename\*\s*=\s*UTF-8''([^;\s]+)/i.exec(cd);
                        var mQuot = /filename\s*=\s*"([^"]+)"/i.exec(cd);
                        var mPlain = /filename\s*=\s*([^;\s]+)/i.exec(cd);
                        if (mStar) fname = decodeURIComponent(mStar[1].replace(/"/g, ''));
                        else if (mQuot) fname = mQuot[1];
                        else if (mPlain) fname = mPlain[1].replace(/"/g, '');
                    }
                    return res.blob().then(function(blob) {
                        return { blob: blob, fname: fname };
                    });
                })
                .then(function(o) {
                    var url = URL.createObjectURL(o.blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = o.fname;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(function() {
                        URL.revokeObjectURL(url);
                    }, 2000);
                })
                .catch(function() {
                    alert('Could not download PDF. Please try again.');
                })
                .finally(function() {
                    setPdfLinkLoading(pdfBtn, false);
                });
        });
    }
})();
</script>
@endpush
