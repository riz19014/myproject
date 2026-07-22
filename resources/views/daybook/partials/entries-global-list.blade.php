@php
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $sidebarEntryRows */
    /** @var string|null $highlightDate Y-m-d row highlight, optional */
    $highlightDate = $highlightDate ?? null;
@endphp
<div class="daybook-entries-global-list">
    <div class="row g-2 align-items-center mb-2">
        <div class="col-12 col-md-3">
            <label for="daybook-sidebar-search" class="visually-hidden">Search daybook entries</label>
            <input type="search" id="daybook-sidebar-search" class="form-control form-control-sm" placeholder="Search by any field…" autocomplete="off" spellcheck="false">
        </div>
        <div class="col-12 col-md-auto">
            <p class="small text-muted mb-0" id="daybook-sidebar-filter-hint" aria-live="polite">Showing {{ $sidebarEntryRows->count() }} of {{ $sidebarEntryRows->count() }}</p>
        </div>
    </div>
    <div class="table-responsive daybook-sidebar-scroll daybook-entries-global-scroll">
        @if($sidebarEntryRows->isEmpty())
            <p class="small text-muted mb-0 p-2">No daybook lines yet.</p>
        @else
            <table class="table table-striped table-theme mb-0 align-middle daybook-sidebar-table daybook-entries-table">
                <thead>
                    <tr>
                        <th scope="col" style="width: 56px;">#</th>
                        <th scope="col" class="text-nowrap">Date</th>
                        <th scope="col" class="text-nowrap">Voucher</th>
                        <th scope="col" class="text-end text-nowrap">Amount (Rs)</th>
                        <th scope="col">Detail</th>
                        <th scope="col" class="text-end text-nowrap" style="width: 90px;">Actions</th>
                    </tr>
                </thead>
                <tbody id="daybook-sidebar-tbody">
                    @foreach($sidebarEntryRows as $row)
                        <tr class="daybook-sidebar-row @if($highlightDate && $row['entry_date'] === $highlightDate) daybook-sidebar-row--selected-day @endif">
                            <td class="text-muted small">{{ $loop->iteration }}</td>
                            <td class="text-nowrap small daybook-entries-date">
                                <span class="visually-hidden daybook-sidebar-search-blob">{{ $row['search_blob'] }}</span>
                                <a href="{{ route('daybook.index', ['date' => $row['entry_date']]) }}" class="text-decoration-none text-body fw-medium" title="Open this day in daybook">{{ $row['date_display'] }}</a>
                            </td>
                            <td class="text-nowrap small">
                                @if($row['voucher_display'] !== '')
                                    <span class="daybook-voucher-chip font-monospace">{{ $row['voucher_display'] }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace small text-nowrap fw-semibold daybook-amount {{ $row['is_cash_in'] ? 'daybook-amount--in' : 'daybook-amount--out' }}">
                                {{ $row['amount_display'] }}
                                <span class="d-block daybook-amount-tag">{{ $row['type_label'] }}</span>
                            </td>
                            <td class="small">
                                <div class="fw-medium">{{ \Illuminate\Support\Str::limit($row['description'] ?: '—', 64) }}</div>
                                <div class="text-muted">{{ $row['link_label'] }}</div>
                                @if($row['linked_project_name'] !== '' && $row['linked_project_name'] !== $row['project_name'])
                                    <div class="text-muted"><x-project-name :name="$row['linked_project_name']" :is-dha="$row['linked_project_is_dha'] ?? false" />@if($row['linked_project_area'] !== '')<span class="d-block">{{ $row['linked_project_area'] }}</span>@endif</div>
                                @endif
                                @if($row['project_name'] !== '')
                                    <div class="text-muted"><x-project-name :name="$row['project_name']" :is-dha="$row['project_is_dha'] ?? false" />@if($row['project_area'] !== '')<span class="d-block">{{ $row['project_area'] }}</span>@endif</div>
                                @endif
                                @if($row['category'] !== '' || $row['sub_category'] !== '—')
                                    <div class="text-muted">{{ $row['category'] !== '' ? $row['category'].' · ' : '' }}{{ $row['sub_category'] }}</div>
                                @endif
                                @if($row['settlement'] !== '—')
                                    <div class="text-muted">{{ $row['settlement'] }}</div>
                                @endif
                                {{-- Paid by temporarily hidden from daybook UI
                                @if(($row['paid_by'] ?? '—') !== '—')
                                    <div class="text-muted">Paid by: {{ $row['paid_by'] }}</div>
                                @endif
                                --}}
                                @if(($row['sold_area'] ?? '—') !== '—')
                                    <div class="text-muted">Sold area: {{ $row['sold_area'] }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap">
                                @php($daybookModalData = [
                                    'id' => $row['id'],
                                    'url' => $row['url'],
                                    'voucher' => $row['voucher_display'],
                                    'date' => $row['date_display'],
                                    'type_label' => $row['type_label'],
                                    'is_cash_in' => $row['is_cash_in'],
                                    'amount' => $row['amount_display'],
                                    'settlement' => $row['settlement'],
                                    'paid_by' => $row['paid_by'],
                                    'description' => $row['description'] ?: '—',
                                    'link_label' => $row['link_label'],
                                    'project_name' => $row['project_name'],
                                    'project_is_dha' => $row['project_is_dha'] ?? false,
                                    'land_type' => $row['land_type'],
                                    'purchase_file' => $row['purchase_file_name'],
                                    'sold_area' => $row['sold_area'] ?? '—',
                                    'category' => $row['category'],
                                    'sub_category' => $row['sub_category'],
                                    'is_factory' => $row['is_factory'],
                                    'factory_sub_category' => $row['factory_sub_category'],
                                    'unit' => $row['unit'],
                                    'quantity' => $row['quantity'],
                                    'unit_price' => $row['unit_price'],
                                ])
                                <button type="button" class="btn btn-sm btn-pink daybook-entries-view-btn" data-daybook-entry="{{ json_encode($daybookModalData, JSON_HEX_APOS | JSON_HEX_QUOT) }}">View</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<div class="modal fade" id="daybookEntryModal" tabindex="-1" aria-labelledby="daybookEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content daybook-entry-modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="daybookEntryModalLabel">Daybook entry</h5>
                    <p class="small text-muted mb-0" id="daybook-modal-subtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daybook-modal-grid">
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Voucher no.</span><span id="daybook-modal-voucher" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Date</span><span id="daybook-modal-date" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Payment</span><span id="daybook-modal-type" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Amount</span><span id="daybook-modal-amount" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Settlement</span><span id="daybook-modal-settlement" class="daybook-modal-val"></span></div>
                    {{-- Paid by temporarily hidden from daybook UI
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Paid by</span><span id="daybook-modal-paid-by" class="daybook-modal-val"></span></div>
                    --}}
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Linked to</span><span id="daybook-modal-link" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Project</span><span id="daybook-modal-project" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Sale file</span><span id="daybook-modal-file" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Sold area</span><span id="daybook-modal-sold-area" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Party sub category</span><span id="daybook-modal-subcat" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Description</span><span id="daybook-modal-description" class="daybook-modal-val"></span></div>
                </div>

                <div id="daybook-modal-factory" class="mt-3 d-none">
                    <h6 class="text-uppercase text-muted small mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        <span>Factory expense</span>
                    </h6>
                    <div class="daybook-modal-grid">
                        <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Sub category</span><span id="daybook-modal-fsubcat" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Unit</span><span id="daybook-modal-unit" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Quantity</span><span id="daybook-modal-qty" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Unit price</span><span id="daybook-modal-price" class="daybook-modal-val"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="daybook-modal-open-full" class="btn btn-outline-theme btn-sm">Open full page</a>
                <button type="button" class="btn btn-pink btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
