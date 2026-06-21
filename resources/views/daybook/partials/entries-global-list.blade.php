@php
    /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $sidebarEntryRows */
    /** @var string|null $highlightDate Y-m-d row highlight, optional */
    $highlightDate = $highlightDate ?? null;
@endphp
<div class="daybook-entries-global-list">
    <p class="small text-muted mb-2 mb-lg-3">Search across date, voucher number, description, amount, party or project name, land area (when a project is linked), party category, sub category, payment method, bank, reference, and linked record.</p>
    <div class="mb-2">
        <label for="daybook-sidebar-search" class="visually-hidden">Search daybook entries</label>
        <input type="search" id="daybook-sidebar-search" class="form-control form-control-sm" placeholder="Search by any field…" autocomplete="off" spellcheck="false">
    </div>
    <p class="small text-muted mb-2" id="daybook-sidebar-filter-hint" aria-live="polite">Showing {{ $sidebarEntryRows->count() }} of {{ $sidebarEntryRows->count() }}</p>
    <div class="daybook-sidebar-scroll daybook-entries-global-scroll">
        @if($sidebarEntryRows->isEmpty())
            <p class="small text-muted mb-0 p-2">No daybook lines yet.</p>
        @else
            <table class="table table-sm table-theme mb-0 daybook-sidebar-table">
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col" class="text-end text-nowrap">Rs</th>
                        <th scope="col">Detail</th>
                        <th scope="col" class="text-end text-nowrap"> </th>
                    </tr>
                </thead>
                <tbody id="daybook-sidebar-tbody">
                    @foreach($sidebarEntryRows as $row)
                        <tr class="daybook-sidebar-row @if($highlightDate && $row['entry_date'] === $highlightDate) daybook-sidebar-row--selected-day @endif">
                            <td class="text-nowrap small">
                                <span class="visually-hidden daybook-sidebar-search-blob">{{ $row['search_blob'] }}</span>
                                <a href="{{ route('daybook.index', ['date' => $row['entry_date']]) }}" class="text-decoration-none text-body" title="Open this day in daybook">{{ $row['date_display'] }}</a>
                            </td>
                            <td class="text-end font-monospace small text-nowrap">{{ $row['amount_display'] }}</td>
                            <td class="small">
                                <div class="fw-medium">{{ \Illuminate\Support\Str::limit($row['description'] ?: '—', 64) }}</div>
                                <div class="text-muted">{{ $row['link_label'] }}</div>
                                @if($row['linked_project_name'] !== '' && $row['linked_project_name'] !== $row['project_name'])
                                    <div class="text-muted">{{ $row['linked_project_name'] }}@if($row['linked_project_area'] !== '')<span class="d-block">{{ $row['linked_project_area'] }}</span>@endif</div>
                                @endif
                                @if($row['project_name'] !== '')
                                    <div class="text-muted">{{ $row['project_name'] }}@if($row['project_area'] !== '')<span class="d-block">{{ $row['project_area'] }}</span>@endif</div>
                                @endif
                                @if($row['category'] !== '' || $row['sub_category'] !== '—')
                                    <div class="text-muted">{{ $row['category'] !== '' ? $row['category'].' · ' : '' }}{{ $row['sub_category'] }}</div>
                                @endif
                                @if($row['settlement'] !== '—')
                                    <div class="text-muted">{{ $row['settlement'] }}</div>
                                @endif
                            </td>
                            <td class="text-end text-nowrap p-1">
                                <a href="{{ $row['url'] }}" class="btn btn-sm btn-link p-0 small">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
