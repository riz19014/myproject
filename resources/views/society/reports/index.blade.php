@extends('layouts.app')

@section('title', 'Reports — '.$project->name)

@section('content')
@include('society.partials.nav')

@php
    $land = $reports['land'];
    $plots = $reports['plots'];
    $sales = $reports['sales'];
    $payments = $reports['payments'];
    $expenses = $reports['expenses'];
    $mgmt = $reports['management'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Society reports</h1>
        <p class="text-muted mb-0"><x-project-name :project="$project" /> — land, inventory, sales, recovery, expense, and management.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('society.dashboard', $project) }}" class="btn btn-sm btn-outline-theme">Dashboard</a>
        <a href="{{ route('society.sales', $project) }}" class="btn btn-sm btn-outline-theme">Sales</a>
        <a href="{{ route('society.budgets', $project) }}" class="btn btn-sm btn-outline-theme">Budgets</a>
    </div>
</div>

{{-- F preview strip --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg"><div class="card card-theme h-100"><div class="card-body py-3">
        <div class="text-muted small">Saleable remaining</div>
        <div class="fw-semibold">{{ $mgmt['remaining_label'] }}</div>
    </div></div></div>
    <div class="col-6 col-lg"><div class="card card-theme h-100"><div class="card-body py-3">
        <div class="text-muted small">Plots sold / booked</div>
        <div class="fw-semibold">{{ $mgmt['plot_sold'] }} / {{ $mgmt['plot_total'] }}</div>
    </div></div></div>
    <div class="col-6 col-lg"><div class="card card-theme h-100"><div class="card-body py-3">
        <div class="text-muted small">Sale value</div>
        <div class="fw-semibold">Rs {{ number_format($mgmt['sale_value'], 0) }}</div>
    </div></div></div>
    <div class="col-6 col-lg"><div class="card card-theme h-100"><div class="card-body py-3">
        <div class="text-muted small">Received / outstanding</div>
        <div class="fw-semibold">Rs {{ number_format($mgmt['received'], 0) }}</div>
        <div class="small text-muted">Due Rs {{ number_format($mgmt['outstanding'], 0) }}</div>
    </div></div></div>
    <div class="col-6 col-lg"><div class="card card-theme h-100"><div class="card-body py-3">
        <div class="text-muted small">Net profit (received − land − development)</div>
        <div class="fw-semibold {{ $mgmt['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($mgmt['net_profit'], 0) }}</div>
    </div></div></div>
</div>

<nav class="society-report-toc card card-theme mb-4">
    <div class="card-body py-3">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-outline-theme" href="#report-land">A. Land &amp; area</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-land-ledger">Land balance ledger</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-plots">B. Plot inventory</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-sales">C. Sales</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-payments">D. Payment &amp; recovery</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-expenses">E. Expense &amp; profit</a>
            <a class="btn btn-sm btn-outline-theme" href="#report-management">F. Management</a>
        </div>
    </div>
</nav>

{{-- A. Land & Area --}}
<section id="report-land" class="mb-5">
    <h2 class="h4 mb-3">A. Land &amp; area reports</h2>
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card card-theme h-100">
                <div class="card-body">
                    <h3 class="h6 text-muted">1. Land purchase</h3>
                    <div class="mb-2"><span class="text-muted small">Total purchased</span><div class="fw-semibold">{{ $land['purchased_label'] }}</div></div>
                    <div class="mb-2"><span class="text-muted small">Purchase rate / marla</span><div class="fw-semibold">Rs {{ number_format($land['purchase_rate_per_marla'], 2) }}</div></div>
                    <div class="mb-2"><span class="text-muted small">Purchase rate / kanal</span><div class="fw-semibold">Rs {{ number_format($land['purchase_rate_per_kanal'], 2) }}</div></div>
                    <div><span class="text-muted small">Total cost</span><div class="fw-semibold">Rs {{ number_format($land['purchase_cost'], 2) }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-theme h-100">
                <div class="card-body">
                    <h3 class="h6 text-muted">3. Area utilization</h3>
                    <div class="mb-2"><span class="text-muted small">Saleable</span><div class="fw-semibold">{{ $land['saleable_label'] }}</div></div>
                    <div class="mb-2"><span class="text-muted small">Non-saleable (roads, parks, etc.)</span><div class="fw-semibold">{{ $land['non_saleable_label'] }}</div></div>
                    <div class="mb-2"><span class="text-muted small">Sold / booked files</span><div class="fw-semibold">{{ $land['sold_label'] }}</div></div>
                    <div><span class="text-muted small">5. Remaining saleable</span><div class="fw-semibold {{ $land['remaining_marla'] < 0 ? 'text-danger' : 'text-success' }}">{{ $land['remaining_marla'] < 0 ? '−' : '' }}{{ $land['remaining_label'] }}</div></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card card-theme h-100">
                <div class="card-body">
                    <h3 class="h6 text-muted">4. Development packages</h3>
                    @forelse($land['packages'] as $package)
                        <div class="d-flex justify-content-between border-bottom py-2 small gap-2">
                            <span>{{ $package->name }} <span class="text-muted">({{ $package->typeLabel() }})</span></span>
                            <span class="text-nowrap">{{ number_format((float) $package->progress_percent, 0) }}% · Rs {{ number_format((float) $package->estimated_amount, 0) }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No development packages yet. <a href="{{ route('society.development', $project) }}">Add on Development</a>.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="card card-theme mb-3">
        <div class="card-body table-responsive">
            <h3 class="h6 text-muted mb-3">2. Land distribution (master plan)</h3>
            <table class="table table-sm table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Kind</th>
                        <th class="text-end">Area</th>
                        <th class="text-end">Marla</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($land['allocations'] as $a)
                        <tr>
                            <td>{{ $a->categoryLabel() }}</td>
                            <td class="small">{{ $a->isSaleable() ? 'Saleable' : 'Non-saleable' }}</td>
                            <td class="text-end font-monospace">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla((float) $a->land_area_marla) }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $a->land_area_marla, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">No master plan allocations. <a href="{{ route('society.master-plan', $project) }}">Open master plan</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card card-theme" id="report-land-ledger">
        <div class="card-body table-responsive">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h3 class="h6 text-muted mb-1">5. Land balance ledger</h3>
                    <p class="small text-muted mb-2">Purchased → deduct non-saleable (roads, parks, etc.) → deduct each sold/booked file → remaining saleable.</p>
                    <x-land-area-unit-switcher scope="#report-land-ledger" label="Show area as" />
                </div>
                <div class="text-end">
                    <div class="small text-muted">Closing balance</div>
                    <div class="fw-semibold {{ $land['remaining_marla'] < 0 ? 'text-danger' : 'text-success' }}">
                        <x-land-area-label :marla="$land['remaining_marla']" class="society-land-akms-value" />
                    </div>
                </div>
            </div>
            <table class="table table-sm table-theme align-middle mb-0 society-land-ledger-table">
                <thead>
                    <tr>
                        <th style="width: 2.5rem;">#</th>
                        <th style="width: 6.5rem;">Date</th>
                        <th>Particulars</th>
                        <th class="text-end society-land-akms-col">In (area)</th>
                        <th class="text-end society-land-akms-col">Out (area)</th>
                        <th class="text-end society-land-akms-col">Balance</th>
                        <th style="width: 4rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($land['ledger'] as $row)
                        <tr class="{{ $row['is_subtotal'] ? 'society-land-ledger-subtotal' : '' }} {{ $row['kind'] === 'opening' ? 'society-land-ledger-opening' : '' }} {{ $row['kind'] === 'closing' ? 'society-land-ledger-closing' : '' }}">
                            <td class="text-muted small">{{ $row['seq'] }}</td>
                            <td class="text-nowrap small">{{ $row['date'] ?? '—' }}</td>
                            <td class="{{ $row['is_subtotal'] ? 'fw-semibold' : 'small' }}">{{ $row['particulars'] }}</td>
                            <td class="text-end society-land-akms {{ $row['in_marla'] > 0 ? 'is-in' : 'is-empty' }}">
                                <x-land-area-label :marla="$row['in_marla'] > 0 ? $row['in_marla'] : null" class="society-land-akms-value" />
                            </td>
                            <td class="text-end society-land-akms {{ $row['out_marla'] > 0 ? 'is-out' : 'is-empty' }}">
                                <x-land-area-label :marla="$row['out_marla'] > 0 ? $row['out_marla'] : null" class="society-land-akms-value" />
                            </td>
                            <td class="text-end society-land-akms {{ $row['balance_negative'] ? 'is-out' : 'is-balance' }}">
                                <x-land-area-label :marla="$row['balance_marla']" class="society-land-akms-value" />
                            </td>
                            <td class="text-end text-nowrap">
                                @if($row['booking_id'])
                                    <a href="{{ route('society.sales.show', [$project, $row['booking_id']]) }}" class="btn btn-sm btn-outline-theme">File</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>

{{-- B. Plot inventory --}}
<section id="report-plots" class="mb-5">
    <h2 class="h4 mb-3">B. Plot inventory reports</h2>
    <div class="row g-3 mb-3">
        @foreach([
            ['Total plots', $plots['stats']['total']],
            ['Available', $plots['stats']['available']],
            ['Booked', $plots['stats']['booked']],
            ['Sold', $plots['stats']['sold']],
            ['Reserved', $plots['stats']['reserved']],
            ['Cancelled', $plots['stats']['cancelled']],
        ] as [$label, $value])
            <div class="col-6 col-md-4 col-xl-2">
                <div class="card card-theme h-100"><div class="card-body py-3">
                    <div class="text-muted small">{{ $label }}</div>
                    <div class="fs-4 fw-semibold">{{ $value }}</div>
                </div></div>
            </div>
        @endforeach
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">7. Plot size-wise</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Size</th><th class="text-end">Plots</th><th class="text-end">Total area</th></tr></thead>
                        <tbody>
                            @forelse($plots['by_size'] as $row)
                                <tr>
                                    <td class="font-monospace">{{ $row['size'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end font-monospace">{{ \App\Support\LandMeasure::formatAkmsLabelFromMarla($row['marla']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">No plots.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">8. Block-wise</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead>
                            <tr>
                                <th>Block</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Avail</th>
                                <th class="text-end">Booked</th>
                                <th class="text-end">Sold</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plots['by_block'] as $row)
                                <tr>
                                    <td>{{ $row['block'] }}</td>
                                    <td class="text-end">{{ $row['total'] }}</td>
                                    <td class="text-end">{{ $row['available'] }}</td>
                                    <td class="text-end">{{ $row['booked'] }}</td>
                                    <td class="text-end">{{ $row['sold'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center">No blocks / plots. <a href="{{ route('society.blocks', $project) }}">Blocks &amp; plots</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- C. Sales --}}
<section id="report-sales" class="mb-5">
    <h2 class="h4 mb-3">C. Sales reports</h2>

    <div class="card card-theme mb-3">
        <div class="card-body table-responsive">
            <h3 class="h6 text-muted mb-3">1–3 / 5. Plot sales register · customer &amp; plot · booking / allotment</h3>
            <table class="table table-sm table-theme align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Plot / file</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th class="text-end">Sale value</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Outstanding</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales['register'] as $b)
                        <tr>
                            <td class="text-nowrap small">{{ $b->booking_date?->format('j M Y') ?? '—' }}</td>
                            <td>
                                <div class="fw-semibold">{{ $b->plot?->plot_number ?? '—' }}</div>
                                @if($b->plot && (float) $b->plot->land_area_marla > 0)
                                    <div class="small text-muted text-nowrap">
                                        <x-land-area-label :marla="(float) $b->plot->land_area_marla" />
                                    </div>
                                @endif
                            </td>
                            <td>{{ $b->customer?->name ?? $b->party?->name ?? '—' }}</td>
                            <td>{{ $b->statusLabel() }}</td>
                            <td class="text-end">{{ number_format((float) $b->total_price, 0) }}</td>
                            <td class="text-end">{{ number_format($b->paidTotal(), 0) }}</td>
                            <td class="text-end">{{ number_format($b->outstanding(), 0) }}</td>
                            <td class="text-end"><a class="btn btn-sm btn-outline-theme" href="{{ route('society.sales.show', [$project, $b]) }}">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted text-center">No active sales files.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">2. Customer-wise sales</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Customer</th><th class="text-end">Files</th><th class="text-end">Sale</th><th class="text-end">Outst.</th></tr></thead>
                        <tbody>
                            @forelse($sales['by_customer'] as $row)
                                <tr>
                                    <td>{{ $row['customer'] }}</td>
                                    <td class="text-end">{{ $row['files'] }}</td>
                                    <td class="text-end">{{ number_format($row['sale_value'], 0) }}</td>
                                    <td class="text-end">{{ number_format($row['outstanding'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">No customers.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">4. Monthly sales</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Month</th><th class="text-end">Files</th><th class="text-end">Sale value</th><th class="text-end">Received</th></tr></thead>
                        <tbody>
                            @forelse($sales['by_month'] as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td class="text-end">{{ $row['count'] }}</td>
                                    <td class="text-end">{{ number_format($row['sale_value'], 0) }}</td>
                                    <td class="text-end">{{ number_format($row['received'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">No monthly sales yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">6. Transfer history</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Date</th><th>Plot</th><th>Customer</th><th>Notes</th></tr></thead>
                        <tbody>
                            @forelse($sales['transferred'] as $b)
                                <tr>
                                    <td class="small">{{ $b->booking_date?->format('j M Y') ?? '—' }}</td>
                                    <td>{{ $b->plot?->plot_number }}</td>
                                    <td>{{ $b->customer?->name ?? '—' }}</td>
                                    <td class="small">{{ $b->notes ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted text-center">No transfers recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">7. Cancellation &amp; refund</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Cancelled</th><th>Plot</th><th>Customer</th><th>Reason</th><th class="text-end">Was sale</th></tr></thead>
                        <tbody>
                            @forelse($sales['cancelled'] as $b)
                                <tr>
                                    <td class="small">{{ $b->cancelled_at?->format('j M Y') ?? '—' }}</td>
                                    <td>{{ $b->plot?->plot_number }}</td>
                                    <td>{{ $b->customer?->name ?? '—' }}</td>
                                    <td class="small">{{ $b->cancel_reason ?: '—' }}</td>
                                    <td class="text-end">{{ number_format((float) $b->total_price, 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted text-center">No cancellations.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- D. Payments --}}
<section id="report-payments" class="mb-5">
    <h2 class="h4 mb-3">D. Payment &amp; recovery reports</h2>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">1. Total sale value</div>
            <div class="fw-semibold">Rs {{ number_format($payments['sale_value'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">2. Total received</div>
            <div class="fw-semibold text-success">Rs {{ number_format($payments['received'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">3. Outstanding receivables</div>
            <div class="fw-semibold {{ $payments['outstanding'] > 0 ? 'text-danger' : '' }}">Rs {{ number_format($payments['outstanding'], 2) }}</div>
        </div></div></div>
        <div class="col-md-3"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">5. Overdue installments</div>
            <div class="fw-semibold text-danger">Rs {{ number_format($payments['overdue_total'], 2) }}</div>
            <div class="small text-muted">{{ $payments['overdue']->count() }} dues</div>
        </div></div></div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">4. Installment due report</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead>
                            <tr>
                                <th>Due date</th>
                                <th>Plot</th>
                                <th>Customer</th>
                                <th>Label</th>
                                <th class="text-end">Due</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payments['dues'] as $due)
                                @php $booking = $due->plan?->booking; @endphp
                                <tr class="{{ $due->due_date && $due->due_date->isPast() ? 'table-warning' : '' }}">
                                    <td class="text-nowrap small">{{ $due->due_date?->format('j M Y') ?? '—' }}</td>
                                    <td>{{ $booking?->plot?->plot_number ?? '—' }}</td>
                                    <td>{{ $booking?->customer?->name ?? '—' }}</td>
                                    <td class="small">{{ $due->label ?: ('#'.$due->sequence) }}</td>
                                    <td class="text-end">{{ number_format((float) $due->amount, 0) }}</td>
                                    <td class="text-end">{{ number_format($due->outstanding(), 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-muted text-center">No open installment dues.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-theme mb-3">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">8. Cash &amp; bank collection</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Method</th><th class="text-end">Lines</th><th class="text-end">Amount</th></tr></thead>
                        <tbody>
                            @forelse($payments['collection_by_method'] as $row)
                                <tr>
                                    <td>{{ $row['method'] }}</td>
                                    <td class="text-end">{{ $row['lines'] }}</td>
                                    <td class="text-end">{{ number_format($row['total'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">No cash-in yet.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Daybook cash-in</th>
                                <th></th>
                                <th class="text-end">{{ number_format($payments['cash_in'], 0) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="card card-theme">
                <div class="card-body">
                    <h3 class="h6 text-muted mb-2">6–7. Customer ledger / plot payment history</h3>
                    <p class="small text-muted mb-2">Open any sales file for full customer ledger and plot-wise payment sheet.</p>
                    <a href="{{ route('society.sales', $project) }}" class="btn btn-sm btn-outline-theme">Go to sales files</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- E. Expense & profit --}}
<section id="report-expenses" class="mb-5">
    <h2 class="h4 mb-3">E. Expense &amp; profit reports</h2>
    <div class="row g-3 mb-3">
        <div class="col-md-4"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">1. Land purchase expense</div>
            <div class="fw-semibold">Rs {{ number_format($expenses['land_purchase_cost'], 2) }}</div>
            <div class="small text-muted">Daybook land cash-out Rs {{ number_format($expenses['land_cash_out'], 0) }}</div>
        </div></div></div>
        <div class="col-md-4"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">2–6. Development / other expense</div>
            <div class="fw-semibold">Rs {{ number_format($expenses['development_cash_out'], 2) }}</div>
            <div class="small text-muted">Non-land Payment Out</div>
        </div></div></div>
        <div class="col-md-4"><div class="card card-theme"><div class="card-body">
            <div class="text-muted small">7. Total project investment</div>
            <div class="fw-semibold">Rs {{ number_format($expenses['investment'], 2) }}</div>
            <div class="small text-muted">Purchase cost + all cash-out</div>
        </div></div></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card card-theme h-100">
                <div class="card-body table-responsive">
                    <h3 class="h6 text-muted mb-3">Budget lines (mapped actuals)</h3>
                    <table class="table table-sm table-theme mb-0">
                        <thead><tr><th>Category</th><th class="text-end">Estimated</th><th class="text-end">Actual</th></tr></thead>
                        <tbody>
                            @forelse($expenses['budgets'] as $row)
                                <tr>
                                    <td>{{ $row['category'] }}</td>
                                    <td class="text-end">{{ number_format($row['estimated'], 0) }}</td>
                                    <td class="text-end">{{ number_format($row['actual'], 0) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">No budget lines. <a href="{{ route('society.budgets', $project) }}">Add budgets</a>.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-theme h-100">
                <div class="card-body">
                    <h3 class="h6 text-muted mb-3">8. Project profitability</h3>
                    <div class="d-flex justify-content-between border-bottom py-2 small">
                        <span>Cash-in − investment</span>
                        <span class="fw-semibold {{ $expenses['profitability'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($expenses['profitability'], 0) }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 small">
                        <span>Received − land cost − development</span>
                        <span class="fw-semibold {{ $expenses['profit_on_received'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($expenses['profit_on_received'], 0) }}</span>
                    </div>
                    <a href="{{ route('society.budgets', $project) }}" class="btn btn-sm btn-outline-theme mt-2">Expense ledger</a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- F. Management --}}
<section id="report-management" class="mb-4">
    <h2 class="h4 mb-3">F. Management / dashboard reports</h2>
    <div class="row g-3">
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">2. Total land vs saleable</div>
            <div class="fw-semibold">{{ $mgmt['purchased_label'] }}</div>
            <div class="small text-muted">Saleable {{ $mgmt['saleable_label'] }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">3. Total plots vs sold/booked</div>
            <div class="fw-semibold">{{ $mgmt['plot_total'] }} plots</div>
            <div class="small text-muted">{{ $mgmt['plot_sold'] }} sold / booked · remaining saleable {{ $mgmt['remaining_label'] }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">4. Investment vs sales</div>
            <div class="fw-semibold">Inv Rs {{ number_format($mgmt['investment'], 0) }}</div>
            <div class="small text-muted">Sales Rs {{ number_format($mgmt['sale_value'], 0) }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">5. Received vs remaining receivables</div>
            <div class="fw-semibold">Rs {{ number_format($mgmt['received'], 0) }}</div>
            <div class="small text-muted">Outstanding Rs {{ number_format($mgmt['outstanding'], 0) }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">6. Development cost / kanal</div>
            <div class="fw-semibold">Rs {{ number_format($mgmt['development_per_kanal'], 2) }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">7. Average sale rate / marla</div>
            <div class="fw-semibold">Rs {{ number_format($mgmt['avg_sale_rate_per_marla'], 2) }}</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">8. Net profit / loss</div>
            <div class="fw-semibold {{ $mgmt['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">Rs {{ number_format($mgmt['net_profit'], 2) }}</div>
            <div class="small text-muted">Received − land cost − development expense</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">9. Unsold inventory valuation</div>
            <div class="fw-semibold">Rs {{ number_format($mgmt['unsold_valuation'], 0) }}</div>
            <div class="small text-muted">Remaining saleable × avg sale rate (or list prices)</div>
        </div></div></div>
        <div class="col-md-6 col-xl-4"><div class="card card-theme h-100"><div class="card-body">
            <div class="text-muted small">1. Project overview</div>
            <div class="small mb-2">Full live snapshot is on the society dashboard.</div>
            <a href="{{ route('society.dashboard', $project) }}" class="btn btn-sm btn-pink">Open dashboard</a>
        </div></div></div>
    </div>
</section>
@endsection

@push('head')
<style>
    .society-report-toc { position: sticky; top: 0.75rem; z-index: 20; }
    section[id^="report-"],
    #report-land-ledger { scroll-margin-top: 5rem; }
    .society-land-ledger-opening td {
        background: #f8fafc;
    }
    .society-land-ledger-subtotal td {
        background: #fff7ed;
        border-top: 1px solid rgba(249, 115, 22, 0.25);
        border-bottom: 1px solid rgba(249, 115, 22, 0.25);
    }
    .society-land-ledger-closing td {
        background: #f0fdf4;
        border-top: 2px solid rgba(22, 163, 74, 0.25);
    }
    .society-land-ledger-table .society-land-akms-col {
        min-width: 11.5rem;
        white-space: nowrap;
    }
    .society-land-ledger-table .society-land-akms {
        white-space: nowrap;
        vertical-align: middle;
    }
    .society-land-ledger-table .society-land-akms-value {
        display: inline-block;
        white-space: nowrap;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: -0.01em;
        line-height: 1.2;
        padding: 0.2rem 0.45rem;
        border-radius: 6px;
    }
    .society-land-ledger-table .society-land-akms.is-in .society-land-akms-value {
        color: #166534;
        background: #dcfce7;
    }
    .society-land-ledger-table .society-land-akms.is-out .society-land-akms-value {
        color: #b91c1c;
        background: #fee2e2;
    }
    .society-land-ledger-table .society-land-akms.is-balance .society-land-akms-value {
        color: #166534;
        background: #dcfce7;
    }
    .society-land-ledger-table .society-land-akms.is-empty .society-land-akms-value {
        color: #94a3b8;
        background: transparent;
        font-weight: 500;
    }
</style>
@endpush
