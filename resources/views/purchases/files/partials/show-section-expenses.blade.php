@if($expenseEntryCount === 0)
    <p class="text-muted small mb-0">No subcategory expenses yet.</p>
@else
    <div class="pf-section-stack" data-section-stack="expenses">
        @foreach($expenseGroups as $group)
            <div class="pf-section-item is-item-selected"
                 data-section="expenses"
                 data-item-id="{{ $group['sub_category_id'] }}"
                 data-amount="{{ $group['total'] }}">
                <label class="pf-section-item__label">
                    <input type="checkbox"
                           class="form-check-input pf-item-check"
                           data-section="expenses"
                           value="{{ $group['sub_category_id'] }}"
                           checked
                           aria-label="Include {{ $group['sub_category'] }}">
                    <span class="pf-section-item__content">
                        <span class="pf-section-item__title d-block">{{ $group['sub_category'] }}</span>
                        <span class="pf-section-item__meta small text-muted d-block">{{ $group['category'] }}</span>
                        <span class="pf-section-item__meta small text-muted d-block">{{ $group['entries']->count() }} {{ $group['entries']->count() === 1 ? 'entry' : 'entries' }}</span>
                        <span class="pf-section-item__amount d-block">Rs {{ number_format($group['total'], 2) }}</span>
                    </span>
                </label>
            </div>
        @endforeach
        <div class="pf-section-total" data-section-total="expenses">
            <div class="fw-semibold pf-section-total__amount">Total Rs {{ number_format($totalExpenses, 2) }}</div>
        </div>
    </div>
@endif
