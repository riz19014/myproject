@php
    /** @var array{columns: list<array<string, mixed>>, row_count: int} $sheetGrid */
    $columns = $sheetGrid['columns'] ?? [];
    $rowCount = (int) ($sheetGrid['row_count'] ?? 0);
    $tableClass = $tableClass ?? 'table table-bordered table-sm table-theme pf-sheet-table mb-0';
    $wrapResponsive = $wrapResponsive ?? true;
@endphp
@if($columns === [])
    <p class="text-muted small mb-0">Nothing selected.</p>
@else
    @if($wrapResponsive)<div class="table-responsive">@endif
        <table class="{{ $tableClass }}">
            <thead>
                <tr>
                    @foreach($columns as $column)
                        <th class="text-center text-nowrap" title="{{ $column['full_label'] }}">{{ $column['label'] }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for($r = 0; $r < $rowCount; $r++)
                    <tr>
                        @foreach($columns as $column)
                            @php $row = $column['rows'][$r] ?? null; @endphp
                            <td class="text-end font-monospace small">{{ $row['display'] ?? '' }}</td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-semibold">
                    @foreach($columns as $column)
                        <td class="text-end font-monospace">{{ $column['total_display'] }}</td>
                    @endforeach
                </tr>
            </tfoot>
        </table>
    @if($wrapResponsive)</div>@endif
@endif
