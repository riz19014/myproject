<style>
    .daybook-voucher-chip {
        display: inline-block;
        padding: 0.12rem 0.5rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.1);
        color: #1d4ed8 !important;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        line-height: 1.35;
    }
    .daybook-voucher-chip:hover,
    .daybook-voucher-chip:focus {
        background: rgba(37, 99, 235, 0.18);
        color: #1e40af !important;
    }
    #daybookEntryModal {
        z-index: 1080;
    }
    .daybook-entry-modal-content {
        background: #ffffff !important;
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.1);
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }
    .daybook-entry-modal-content .modal-header {
        background: #f8fafc !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .daybook-entry-modal-content .modal-body,
    .daybook-entry-modal-content .modal-footer {
        background: #ffffff !important;
    }
    .daybook-modal-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 1.75rem;
        row-gap: 0;
    }
    .daybook-modal-item {
        display: flex;
        align-items: baseline;
        gap: 0.5rem;
        padding: 0.4rem 0;
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
        min-width: 0;
    }
    .daybook-modal-item--wide {
        grid-column: 1 / -1;
    }
    .daybook-modal-item .daybook-modal-key {
        flex: 0 0 42%;
        color: #64748b !important;
        font-weight: 600;
        font-size: 0.78rem;
    }
    .daybook-modal-item .daybook-modal-val {
        flex: 1 1 auto;
        font-size: 0.88rem;
        color: #0f172a;
        word-break: break-word;
        text-align: right;
    }
    .daybook-modal-item .daybook-modal-val.daybook-amount--in {
        color: #16a34a !important;
    }
    .daybook-modal-item .daybook-modal-val.daybook-amount--out {
        color: #dc2626 !important;
    }
    @media (max-width: 575.98px) {
        .daybook-modal-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
