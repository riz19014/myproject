@if($paymentEntryCount === 0)
    <p class="text-muted small mb-0">No payments recorded yet.</p>
@else
    <div class="pf-section-stack" data-section-stack="payments">
        <div class="pf-section-item pf-section-item--muted is-item-selected"
             data-section="payments"
             data-item-id="opening"
             data-amount="{{ $landTotalRs }}"
             data-is-opening="1">
            <label class="pf-section-item__label">
                <input type="checkbox"
                       class="form-check-input pf-item-check"
                       data-section="payments"
                       value="opening"
                       checked
                       aria-label="Include land total">
                <span class="pf-section-item__content">
                    <span class="pf-section-item__title d-block">Land total (file)</span>
                    <span class="pf-section-item__amount d-block">Rs {{ number_format($landTotalRs, 2) }}</span>
                </span>
            </label>
        </div>
        @foreach($paymentRows as $row)
            @php $e = $row['entry']; @endphp
            <div class="pf-section-item is-item-selected"
                 data-section="payments"
                 data-item-id="{{ $e->id }}"
                 data-amount="{{ (float) $e->amount }}"
                 data-entry-type="{{ $e->type }}"
                 data-balance="{{ $row['balance'] }}">
                <label class="pf-section-item__label">
                    <input type="checkbox"
                           class="form-check-input pf-item-check"
                           data-section="payments"
                           value="{{ $e->id }}"
                           checked
                           aria-label="Include payment {{ $row['date'] }}">
                    <span class="pf-section-item__content">
                        <span class="pf-section-item__title d-block">{{ $row['date'] }}</span>
                        <span class="pf-section-item__meta small d-block">{{ $row['party'] }}</span>
                        @if($row['description'] !== '—')
                            <span class="pf-section-item__meta small text-muted d-block">{{ $row['description'] }}</span>
                        @endif
                        <span class="pf-section-item__meta small text-muted d-block">{{ $row['payment'] }}</span>
                        <span class="pf-section-item__amount d-block">Rs {{ number_format((float) $e->amount, 2) }}</span>
                        <span class="pf-section-item__meta small d-block">
                            Balance:
                            @if($row['balance'] >= 0)
                                Rs {{ number_format($row['balance'], 2) }}
                            @else
                                Overpaid Rs {{ number_format(abs($row['balance']), 2) }}
                            @endif
                        </span>
                    </span>
                </label>
            </div>
        @endforeach
        <div class="pf-section-total" data-section-total="payments">
            <div class="small text-muted pf-section-total__paid">Paid Rs {{ number_format($totalPaid, 2) }}</div>
            <div class="fw-semibold pf-section-total__balance">
                Balance payable
                @if($balancePayable >= 0)
                    Rs {{ number_format($balancePayable, 2) }}
                @else
                    <span class="text-muted">Overpaid Rs {{ number_format(abs($balancePayable), 2) }}</span>
                @endif
            </div>
        </div>
    </div>
@endif
