@php
    /** @var array<string, mixed> $column */
@endphp
<div class="pf-sheet-select-col card card-theme h-100" data-column="{{ $column['key'] }}">
    <div class="card-header pf-sheet-select-col__head">
        <span class="pf-sheet-select-col__title mb-0" title="{{ $column['full_label'] }}">{{ $column['label'] }}</span>
    </div>
    <div class="card-body pf-sheet-select-col__body">
        @if(empty($column['rows']))
            @if($column['key'] === 'grand_total_exp')
                <p class="text-muted small mb-0">Grand total shown in footer row.</p>
            @else
                <p class="text-muted small mb-0">—</p>
            @endif
        @else
            <div class="pf-section-stack">
                @foreach($column['rows'] as $row)
                    <div class="pf-section-item"
                         data-column="{{ $column['key'] }}"
                         data-item-id="{{ $row['id'] }}"
                         data-amount="{{ $row['amount'] }}">
                        @if(!empty($row['label']))
                            <span class="pf-section-item__meta small text-muted d-block">{{ $row['label'] }}</span>
                        @endif
                        <span class="pf-section-item__amount d-block">{{ $row['display'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
        <div class="pf-section-total mt-2">
            <div class="small text-muted">Total</div>
            <div class="fw-semibold pf-column-total-display">{{ $column['total_display'] }}</div>
        </div>
    </div>
</div>
