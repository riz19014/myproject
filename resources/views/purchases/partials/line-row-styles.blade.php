@once
<style>
    /* —— Add seller panel (sellers page card) —— */
    .seller-add-card .seller-add-card__body {
        padding: 1.15rem 1.25rem 1.25rem;
    }
    .seller-add-card__header {
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 1.1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .seller-add-card__icon {
        flex-shrink: 0;
        width: 2.5rem;
        height: 2.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(249, 115, 22, 0.18) 0%, rgba(249, 115, 22, 0.06) 100%);
        color: #ea580c;
        font-size: 1.15rem;
    }
    .seller-add-card__title {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--text-dark, #0f172a);
        margin: 0 0 0.2rem;
        letter-spacing: -0.02em;
    }
    .seller-add-card__hint {
        font-size: 0.8125rem;
        color: var(--text-muted, #64748b);
        margin: 0;
        line-height: 1.45;
    }
    .seller-lines-stack {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        counter-reset: purchase-seller-line;
    }
    .seller-form-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        margin-top: 1rem;
        padding: 0.85rem 1rem;
        border-radius: 10px;
        background: rgba(15, 23, 42, 0.03);
        border: 1px solid rgba(15, 23, 42, 0.08);
    }
    .seller-form-actions .btn-pink {
        min-width: 8.5rem;
    }

    /* —— Single seller line card —— */
    .purchase-line-block--compact {
        position: relative;
        margin-bottom: 0 !important;
        padding: 0;
        border: 1px solid rgba(15, 23, 42, 0.1) !important;
        border-radius: 12px !important;
        background: var(--card-bg, #fff) !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
        counter-increment: purchase-seller-line;
        overflow: hidden;
    }
    .purchase-line-block--compact::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(180deg, #f97316 0%, #fb923c 100%);
        border-radius: 12px 0 0 12px;
    }
    .purchase-line-block__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.55rem 0.85rem 0.55rem 1rem;
        background: rgba(15, 23, 42, 0.025);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }
    .purchase-line-block__badge {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #ea580c;
    }
    .purchase-line-block__badge::after {
        content: ' ' counter(purchase-seller-line);
        color: var(--text-dark, #0f172a);
    }
    .purchase-line-block__remove {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.2rem 0.55rem;
        border-radius: 6px;
        border: 1px solid rgba(220, 38, 38, 0.25);
        background: rgba(254, 242, 242, 0.8);
        color: #b91c1c;
        text-decoration: none;
        line-height: 1.3;
        cursor: pointer;
        user-select: none;
    }
    .purchase-line-block__remove:hover:not(:disabled) {
        background: #fee2e2;
        color: #991b1b;
    }
    .purchase-line-block__remove:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .purchase-line-block__body {
        padding: 0.85rem 1rem 1rem;
    }
    .purchase-line-block__section {
        margin-bottom: 0.75rem;
    }
    .purchase-line-block__section:last-child {
        margin-bottom: 0;
    }
    .purchase-line-block__section-title {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
        margin-bottom: 0.5rem;
    }
    .purchase-line-block--compact .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: #475569;
    }
    .purchase-line-block--compact .form-control {
        padding: 0.4rem 0.6rem;
        font-size: 0.875rem;
        min-height: calc(1.5em + 0.65rem);
        border-radius: 8px;
    }
    .purchase-line-akms-panel {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 0.6rem 0.75rem;
        padding: 0.65rem 0.75rem;
        border-radius: 10px;
        background: linear-gradient(180deg, rgba(249, 115, 22, 0.05) 0%, rgba(15, 23, 42, 0.02) 100%);
        border: 1px solid rgba(249, 115, 22, 0.12);
    }
    .purchase-line-akms-hint {
        flex: 1 1 100%;
        font-size: 0.7rem;
        color: #64748b;
        margin: -0.15rem 0 0.1rem;
    }
    .purchase-line-akms-hint .text-danger {
        font-weight: 700;
    }
    .purchase-line-akms-field {
        flex: 1 1 5.5rem;
        min-width: 5.5rem;
        max-width: 6.75rem;
    }
    .purchase-line-akms-field .form-control {
        padding: 0.5rem 0.65rem;
        font-size: 0.9375rem;
        font-weight: 600;
        min-height: calc(1.5em + 0.75rem);
        text-align: center;
        background: var(--card-bg, #fff);
        border-color: rgba(15, 23, 42, 0.12);
    }
    .purchase-line-akms-field .form-control:focus {
        border-color: rgba(249, 115, 22, 0.45);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
    }
    .purchase-line-rate-field {
        flex: 1 1 8rem;
        min-width: 8rem;
        max-width: 11rem;
        padding-left: 0.5rem;
        margin-left: auto;
        border-left: 1px dashed rgba(15, 23, 42, 0.12);
    }
    .purchase-line-rate-field .form-control {
        font-weight: 600;
        background: var(--card-bg, #fff);
    }
    .purchase-line-rate-field .js-amount-per-acre-hint {
        font-size: 0.7rem;
        color: #ea580c !important;
        font-weight: 600;
    }
    @media (min-width: 992px) {
        .purchase-line-akms-field {
            flex: 0 0 6.25rem;
            min-width: 6.25rem;
            max-width: 6.25rem;
        }
        .purchase-line-akms-hint {
            flex: 0 0 auto;
            width: auto;
            margin-right: 0.25rem;
            padding-bottom: 0.4rem;
        }
        .purchase-line-rate-field {
            flex: 0 0 9.5rem;
            max-width: 9.5rem;
        }
    }
    @media (max-width: 575.98px) {
        .purchase-line-rate-field {
            flex: 1 1 100%;
            max-width: none;
            margin-left: 0;
            padding-left: 0;
            border-left: 0;
            padding-top: 0.35rem;
            border-top: 1px dashed rgba(15, 23, 42, 0.1);
        }
    }
</style>
@endonce
