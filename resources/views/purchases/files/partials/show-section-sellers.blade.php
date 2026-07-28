@if($sellers->isEmpty())
    <p class="text-muted small mb-0">No sellers on this file yet.</p>
@else
    <div class="pf-section-stack" data-section-stack="sellers">
        @foreach($sellers as $seller)
            <div class="pf-section-item is-item-selected"
                 data-section="sellers"
                 data-item-id="{{ $seller->id }}"
                 data-amount="{{ (float) $seller->line_total_rs }}"
                 data-area-marla="{{ (float) $seller->land_area_marla }}">
                <label class="pf-section-item__label">
                    <input type="checkbox"
                           class="form-check-input pf-item-check"
                           data-section="sellers"
                           value="{{ $seller->id }}"
                           checked
                           aria-label="Include {{ $seller->party?->name ?? 'seller' }}">
                    <span class="pf-section-item__content">
                        <span class="pf-section-item__title">{{ $loop->iteration }}. {{ $seller->party?->name ?? '—' }}</span>
                        <span class="pf-section-item__meta small text-muted d-block">
                            {{ $seller->moza ?: '—' }} · {{ $seller->khasra ?: '—' }}
                        </span>
                        <span class="pf-section-item__meta small d-block">{{ $seller->landAreaLabel() }}</span>
                        <span class="pf-section-item__meta small text-muted d-block">Rs {{ number_format((float) $seller->amount_per_acre, 0) }} / acre</span>
                        <span class="pf-section-item__amount d-block">Rs {{ number_format((float) $seller->line_total_rs, 2) }}</span>
                    </span>
                </label>
            </div>
        @endforeach
        <div class="pf-section-total" data-section-total="sellers">
            <div class="small text-muted pf-section-total__area">{{ $landAreaLabel }}</div>
            <div class="fw-semibold pf-section-total__amount">Total Rs {{ number_format($landTotalRs, 2) }}</div>
        </div>
    </div>
@endif
