<style>
    #daybookCreateProjectModal .modal-content {
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
        overflow: visible;
    }
    #daybookCreatePurchaseFileModal .modal-content {
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
        overflow: visible;
    }
    #daybookCreatePartyModal .modal-content {
        border-radius: 16px;
        border: 1px solid rgba(15, 23, 42, 0.12);
        box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
        overflow: visible;
    }
    #daybookCreateProjectModal .modal-header,
    #daybookCreatePurchaseFileModal .modal-header,
    #daybookCreatePartyModal .modal-header {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        padding: 1.1rem 1.25rem;
    }
    #daybookCreateProjectModal .modal-title,
    #daybookCreatePurchaseFileModal .modal-title,
    #daybookCreatePartyModal .modal-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 1.15rem;
    }
    #daybookCreateProjectModal .modal-body,
    #daybookCreatePurchaseFileModal .modal-body,
    #daybookCreatePartyModal .modal-body {
        padding: 1.35rem 1.25rem 1.25rem;
    }
    #daybookCreateProjectModal .modal-body {
        overflow: visible;
        position: relative;
        z-index: 2;
    }
    #daybookCreatePartyModal .modal-body {
        overflow: visible;
        position: relative;
        z-index: 2;
    }
    #daybookCreatePartyModal .modal-footer {
        position: relative;
        z-index: 1;
    }
    #daybookCreatePartyModal .daybook-party-sc-combo {
        position: relative;
        z-index: 4;
    }
    #daybookCreatePartyModal .daybook-party-sc-listbox {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 10;
        max-height: min(220px, 40vh);
        overflow-y: auto;
        margin: 0;
        padding: 0.35rem 0;
        list-style: none;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    }
    #daybookCreatePartyModal .daybook-party-sc-listbox button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.45rem 0.85rem;
        border: 0;
        background: transparent;
        font-size: 0.9rem;
        color: #0f172a;
        line-height: 1.35;
    }
    #daybookCreatePartyModal .daybook-party-sc-listbox button:hover,
    #daybookCreatePartyModal .daybook-party-sc-listbox button:focus {
        background: rgba(249, 115, 22, 0.12);
        outline: none;
    }
    #daybookCreatePartyModal .daybook-party-sc-listbox .daybook-party-sc-empty {
        padding: 0.6rem 0.85rem;
        color: #64748b;
        font-size: 0.875rem;
    }
    #daybookCreateProjectModal .modal-footer {
        position: relative;
        z-index: 1;
    }
    #daybookCreateProjectModal .daybook-project-lt-combo {
        position: relative;
        z-index: 6;
    }
    #daybookCreateProjectModal .daybook-project-lt-listbox {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 4px);
        z-index: 12;
        max-height: min(240px, 42vh);
        overflow-y: auto;
        margin: 0;
        padding: 0.35rem 0;
        list-style: none;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.14);
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    }
    #daybookCreateProjectModal .daybook-project-lt-listbox button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.55rem 1rem;
        border: 0;
        background: transparent;
        font-size: 1rem;
        color: #0f172a;
        line-height: 1.4;
    }
    #daybookCreateProjectModal .daybook-project-lt-listbox button:hover,
    #daybookCreateProjectModal .daybook-project-lt-listbox button:focus {
        background: rgba(249, 115, 22, 0.12);
        outline: none;
    }
    #daybookCreateProjectModal .daybook-project-lt-listbox .daybook-project-lt-empty {
        padding: 0.65rem 1rem;
        color: #64748b;
        font-size: 0.9375rem;
    }
    #daybookCreateProjectModal .daybook-project-modal-panel,
    #daybookCreatePurchaseFileModal .daybook-project-modal-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem 1.35rem 1.35rem;
    }
    #daybookCreateProjectModal .daybook-modal-label,
    #daybookCreatePurchaseFileModal .daybook-modal-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        display: block;
    }
    #daybookCreateProjectModal .modal-footer,
    #daybookCreatePurchaseFileModal .modal-footer,
    #daybookCreatePartyModal .modal-footer {
        padding: 1rem 1.25rem 1.25rem;
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        background: #fafafa;
    }
    #daybookCreateProjectModal .modal-header .btn-close,
    #daybookCreatePartyModal .modal-header .btn-close {
        opacity: 0.55;
    }
    #daybookCreateProjectModal .modal-header .btn-close:hover,
    #daybookCreatePartyModal .modal-header .btn-close:hover {
        opacity: 1;
    }
    /* Programmatic focus often hides the caret until selection is set; color makes it obvious */
    #daybook_modal_project_name,
    #daybook_modal_project_land_type_search,
    #daybook_modal_party_name {
        caret-color: #f97316;
    }

    /* Daybook — atmosphere, metrics, depth */
    .daybook-page {
        --db-border: #e2e8f0;
        --db-border-strong: #cbd5e1;
        --db-surface: #ffffff;
        --db-surface-muted: #f8fafc;
        --db-shadow: 0 4px 32px rgba(15, 23, 42, 0.08), 0 0 0 1px rgba(15, 23, 42, 0.04);
        --db-shadow-hover: 0 12px 48px rgba(15, 23, 42, 0.12);
        --db-radius: 16px;
        --db-input-h: 3.125rem;
        --db-focus: rgba(249, 115, 22, 0.2);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        font-feature-settings: "kern" 1, "liga" 1;
        margin: 0 -1rem;
        padding: 0 1rem 3rem;
        background:
            radial-gradient(ellipse 100% 80% at 0% -20%, rgba(251, 146, 60, 0.11) 0%, transparent 55%),
            radial-gradient(ellipse 80% 60% at 100% 0%, rgba(99, 102, 241, 0.07) 0%, transparent 50%),
            radial-gradient(ellipse 60% 40% at 50% 100%, rgba(14, 165, 233, 0.05) 0%, transparent 45%),
            linear-gradient(180deg, #f8fafc 0%, #f4f6f9 28%, #f8fafc 100%);
        border-radius: 0;
    }
    @media (min-width: 992px) {
        .daybook-page {
            border-radius: 0 0 20px 20px;
        }
    }
    .daybook-page .daybook-metrics {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 0.55rem;
        margin-bottom: 1.25rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        align-items: stretch;
    }
    @media (max-width: 991.98px) {
        .daybook-page .daybook-metrics {
            grid-template-columns: repeat(5, minmax(9.5rem, 1fr));
        }
    }
    .daybook-page .daybook-metric {
        position: relative;
        padding: 0.55rem 0.65rem 0.6rem;
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 10px;
        box-shadow:
            0 1px 8px rgba(15, 23, 42, 0.05),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        min-width: 0;
    }
    @media (hover: hover) and (pointer: fine) {
        .daybook-page .daybook-metric:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(15, 23, 42, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.95);
        }
    }
    .daybook-page .daybook-metric::after {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        border-radius: 10px 10px 0 0;
        opacity: 0.85;
    }
    .daybook-page .daybook-metric--prior::after { background: linear-gradient(90deg, #334155, #64748b); }
    .daybook-page .daybook-metric--open::after { background: linear-gradient(90deg, #64748b, #94a3b8); }
    .daybook-page .daybook-metric--balances::after { background: linear-gradient(90deg, #334155, #94a3b8); }
    .daybook-page .daybook-metric--petty::after { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .daybook-page .daybook-metric--in::after { background: linear-gradient(90deg, #16a34a, #22c55e); }
    .daybook-page .daybook-metric--out::after { background: linear-gradient(90deg, #dc2626, #f87171); }
    .daybook-page .daybook-metric--payments::after { background: linear-gradient(90deg, #16a34a, #f87171); }
    .daybook-page .daybook-metric--close::after { background: linear-gradient(90deg, #ea580c, #fb923c); }
    .daybook-page .daybook-metric--cash::after { background: linear-gradient(90deg, #0d9488, #2dd4bf); }
    .daybook-page .daybook-metric--bank::after { background: linear-gradient(90deg, #2563eb, #60a5fa); }
    .daybook-page .daybook-metric--settlement::after { background: linear-gradient(90deg, #0d9488, #60a5fa); }
    .daybook-page .daybook-metric__stack {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .daybook-page .daybook-metric__row + .daybook-metric__row {
        padding-top: 0.45rem;
        border-top: 1px solid rgba(15, 23, 42, 0.07);
    }
    .daybook-page .daybook-metric--combined {
        padding-top: 0.5rem;
        padding-bottom: 0.55rem;
    }
    .daybook-page .daybook-metric__label {
        display: block;
        font-size: 0.58rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.15rem;
        line-height: 1.2;
    }
    .daybook-page .daybook-metric__val {
        font-size: 0.88rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
        line-height: 1.15;
    }
    .daybook-page .daybook-metric--in .daybook-metric__val,
    .daybook-page .daybook-metric__row--in .daybook-metric__val { color: #15803d; }
    .daybook-page .daybook-metric--out .daybook-metric__val,
    .daybook-page .daybook-metric__row--out .daybook-metric__val { color: #b91c1c; }
    .daybook-page .daybook-metric--close .daybook-metric__val { color: #c2410c; }
    .daybook-page .daybook-metric--cash .daybook-metric__val,
    .daybook-page .daybook-metric__row--cash .daybook-metric__val { color: #0f766e; }
    .daybook-page .daybook-metric--bank .daybook-metric__val,
    .daybook-page .daybook-metric__row--bank .daybook-metric__val { color: #1d4ed8; }
    .daybook-page .daybook-metric__sub {
        display: block;
        font-size: 0.62rem;
        color: #94a3b8;
        margin-top: 0.1rem;
        line-height: 1.25;
    }
    .daybook-page .daybook-metric__breakdown {
        margin-top: 0.2rem;
        display: flex;
        flex-direction: column;
        gap: 0.08rem;
    }
    .daybook-page .daybook-metric__breakdown li {
        display: flex;
        justify-content: space-between;
        gap: 0.35rem;
        font-size: 0.6rem;
        color: #64748b;
        line-height: 1.2;
    }
    .daybook-page .daybook-metric__breakdown li span:last-child {
        font-weight: 600;
        color: #475569;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    /* overflow: visible so searchable combos (e.g. bank) aren’t clipped at the card edge */
    .daybook-page .daybook-card {
        position: relative;
        border: none;
        border-radius: var(--db-radius);
        box-shadow: var(--db-shadow);
        background: var(--db-surface);
        overflow: visible;
        transition: box-shadow 0.3s ease;
    }
    @media (hover: hover) and (pointer: fine) {
        .daybook-page .daybook-card:hover {
            box-shadow: var(--db-shadow-hover);
        }
    }
    .daybook-page .daybook-card__accent {
        height: 4px;
        background: linear-gradient(90deg, #fb923c 0%, #f97316 35%, #ea580c 100%);
    }
    .daybook-page .daybook-card .card-body {
        padding: 0;
        overflow: visible;
    }
    .daybook-page .daybook-main-tab-content,
    .daybook-page .daybook-main-tab-content > .tab-pane {
        overflow: visible;
    }
    .daybook-page .daybook-page-heading {
        margin-bottom: 1.25rem;
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-page-heading {
            margin-bottom: 1.75rem;
        }
    }
    .daybook-page .daybook-card-heading {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem 1.25rem;
        padding: 1.125rem 1.5rem 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-card-heading {
            padding: 1.25rem 1.75rem 1.1rem;
            align-items: center;
        }
    }
    .daybook-page .daybook-page-heading .daybook-card-heading {
        border-radius: var(--db-radius);
        border: 1px solid var(--db-border);
        box-shadow: var(--db-shadow);
    }
    .daybook-page .daybook-card-heading__title {
        flex: 1 1 auto;
        min-width: min(100%, 10rem);
    }
    .daybook-page .daybook-card-title {
        font-size: clamp(1.625rem, 4.5vw, 2rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        color: #0f172a;
        line-height: 1.15;
        margin: 0;
    }
    .daybook-page .daybook-card-heading__title::after {
        content: "";
        display: block;
        width: 4rem;
        height: 4px;
        margin-top: 0.75rem;
        border-radius: 2px;
        background: linear-gradient(90deg, #fb923c, #f97316, transparent);
        opacity: 0.9;
    }
    .daybook-page .daybook-card-heading__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 0 1 auto;
        justify-content: flex-end;
        max-width: 100%;
    }
    .daybook-page .daybook-card-heading .form-control,
    .daybook-page .daybook-card-heading .form-select {
        min-height: 2.875rem;
        font-size: 1rem;
        border-radius: 10px;
        border-color: var(--db-border-strong);
    }
    .daybook-page .daybook-card-heading .btn-outline-theme {
        border-color: var(--db-border-strong) !important;
        color: #334155 !important;
        background: #fff !important;
        min-width: 3rem;
        border-radius: 10px;
    }
    .daybook-page .daybook-card-heading .btn-outline-theme:hover,
    .daybook-page .daybook-card-heading .btn-outline-theme:focus-visible {
        background: rgba(249, 115, 22, 0.12) !important;
        border-color: #f97316 !important;
        color: #0f172a !important;
    }
    .daybook-page .daybook-date-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #fff;
        border: 1px solid var(--db-border);
        border-radius: 999px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #475569;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
    }
    .daybook-page .daybook-pdf-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.35rem;
        border-radius: 10px;
        line-height: 0;
        transition: background 0.15s ease;
    }
    .daybook-page .daybook-pdf-link:hover {
        background: rgba(220, 38, 38, 0.1);
    }
    .daybook-page .daybook-pdf-link:focus-visible {
        outline: 2px solid #dc2626;
        outline-offset: 2px;
    }
    .daybook-page .daybook-section-title {
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 1rem;
    }
    .daybook-page .daybook-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
    }
    .daybook-page .daybook-panel {
        background: var(--db-surface-muted);
        border: 1px solid var(--db-border);
        border-radius: 12px;
        padding: 1.25rem 1.35rem 1.35rem;
        margin-bottom: 1.25rem;
        overflow: visible;
    }
    .daybook-page #daybook_form_file_wrap {
        position: relative;
        z-index: 1;
    }
    .daybook-page .daybook-panel .daybook-form-combo {
        position: relative;
        z-index: 2;
    }
    .daybook-page .daybook-panel .daybook-form-combo.is-open {
        z-index: 80;
    }
    .daybook-page .daybook-panel.is-combo-open {
        position: relative;
        z-index: 30;
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-panel {
            padding: 1.35rem 1.5rem 1.5rem;
        }
    }
    .daybook-page .daybook-card .form-control.form-control-theme,
    .daybook-page .daybook-card .form-select.form-select-theme {
        min-height: var(--db-input-h);
        padding: 0.65rem 1.1rem;
        font-size: 1.0625rem;
        line-height: 1.45;
        border-radius: 10px;
        border: 1.5px solid var(--db-border-strong);
        background: #fff !important;
        transition: border-color 0.15s ease, box-shadow 0.2s ease;
    }
    .daybook-page .daybook-card .form-control.form-control-theme:hover,
    .daybook-page .daybook-card .form-select.form-select-theme:hover {
        border-color: #94a3b8;
    }
    .daybook-page .daybook-card .form-control.form-control-theme:focus,
    .daybook-page .daybook-card .form-select.form-select-theme:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 4px var(--db-focus);
    }
    .daybook-page .daybook-card .form-select.form-select-theme {
        background-position: right 1rem center;
        padding-right: 2.75rem;
    }
    .daybook-page .daybook-tabs-row {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        justify-content: space-between;
        gap: 0.5rem 1rem;
        padding: 0.75rem 1.25rem 0;
        border-bottom: 1px solid var(--db-border);
        background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-tabs-row {
            padding: 1rem 1.75rem 0;
        }
    }
    .daybook-page .daybook-inner-tabs {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 auto;
        min-width: 0;
        padding: 0;
        margin: 0;
        list-style: none;
        border-bottom: none;
        background: none;
    }
    .daybook-page .daybook-inner-tabs .nav-link {
        margin-bottom: -1px;
        border-radius: 10px 10px 0 0;
        padding: 0.55rem 1.15rem;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b !important;
        border: 1px solid transparent;
        border-bottom: none;
        background: transparent;
        transition: color 0.15s ease, background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .daybook-page .daybook-inner-tabs .nav-link:hover {
        color: var(--accent-orange) !important;
        background: rgba(249, 115, 22, 0.06);
        border-color: rgba(249, 115, 22, 0.15);
    }
    .daybook-page .daybook-inner-tabs .nav-link.active {
        color: #0f172a !important;
        background: #fff !important;
        border-color: var(--db-border);
        border-bottom-color: #fff;
        box-shadow: 0 -2px 12px rgba(15, 23, 42, 0.06);
        position: relative;
        z-index: 2;
    }
    .daybook-page .daybook-tabs-row-save {
        flex-shrink: 0;
        margin-bottom: 0.35rem;
    }
    .daybook-page .daybook-tabs-row:has(#daybook-tab-records-btn.active) .daybook-tabs-row-save {
        display: none;
    }
    .daybook-page .daybook-main-tab-content {
        background: #fff;
    }
    .daybook-page .daybook-form-inner {
        padding: 1.5rem 1.25rem 1.75rem;
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-form-inner {
            padding: 1.75rem 2rem 2rem;
        }
    }
    .daybook-page .daybook-records-panel {
        padding: 1.5rem 1.25rem 1.75rem;
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-records-panel {
            padding: 1.75rem 2rem 2rem;
        }
    }
    .daybook-page .daybook-table-head {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
    }
    .daybook-page .daybook-table-head h2 {
        font-size: 1.25rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        margin: 0;
        line-height: 1.3;
    }
    .daybook-page .daybook-table-shell {
        border-radius: 10px;
        overflow-x: auto;
        overflow-y: visible;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior-x: contain;
        border: 1px solid var(--db-border);
        background: #fff;
    }
    .daybook-page .daybook-records-panel .table-theme {
        margin: 0;
        min-width: 42rem;
        --bs-table-border-color: #e2e8f0;
    }
    @media (max-width: 575.98px) {
        .daybook-page .daybook-records-panel .table-theme thead th,
        .daybook-page .daybook-records-panel .table-theme tbody td {
            padding: 0.6rem 0.65rem !important;
            font-size: 0.8125rem !important;
        }
        .daybook-page .daybook-records-panel .table-theme thead th {
            font-size: 0.625rem !important;
            letter-spacing: 0.05em;
        }
    }
    .daybook-page .daybook-records-panel .table-theme thead th {
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: 700;
        color: #64748b !important;
        background: #f8fafc !important;
        border: none !important;
        border-bottom: 1px solid var(--db-border) !important;
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
    }
    .daybook-page .daybook-records-panel .table-theme thead th:first-child {
        border-radius: 0;
    }
    .daybook-page .daybook-records-panel .table-theme tbody td {
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
        border-color: #f1f5f9 !important;
        font-size: 0.875rem;
        background: #fff !important;
    }
    .daybook-page .daybook-records-panel .table-theme tbody tr:not(:last-child) td {
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .daybook-page .daybook-table-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem;
    }
    @media (max-width: 575.98px) {
        .daybook-page .daybook-table-actions {
            flex-direction: column;
            align-items: stretch;
            gap: 0.35rem;
            min-width: 6.5rem;
        }
        .daybook-page .daybook-table-actions .daybook-table-action-btn {
            width: 100%;
            justify-content: center;
        }
        .daybook-page .daybook-table-actions form {
            display: block;
            width: 100%;
        }
        .daybook-page .daybook-table-actions form .daybook-table-action-btn {
            width: 100%;
        }
    }
    .daybook-page .daybook-records-actions-col {
        min-width: 7.5rem;
        width: 1%;
    }
    .daybook-page .daybook-records-panel thead .daybook-records-actions-col {
        white-space: nowrap;
    }
    @media (min-width: 576px) {
        .daybook-page .daybook-records-actions-col {
            min-width: 11rem;
        }
    }
    @media (min-width: 768px) {
        .daybook-page .daybook-records-actions-col {
            min-width: 13.75rem;
        }
    }
    .daybook-page .daybook-pill {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
    }
    .daybook-page .daybook-pill--in {
        color: #166534;
        background: #dcfce7;
    }
    .daybook-page .daybook-pill--out {
        color: #991b1b;
        background: #fee2e2;
    }
    .daybook-page .daybook-records-panel .table-theme .font-monospace {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }
    .daybook-page .daybook-count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.75rem;
        height: 1.75rem;
        padding: 0 0.45rem;
        margin-left: 0.35rem;
        font-size: 0.75rem;
        font-weight: 800;
        vertical-align: middle;
        color: #fff;
        background: linear-gradient(135deg, #475569 0%, #334155 100%);
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
    }
    .daybook-page .daybook-id-cell {
        color: #94a3b8 !important;
        font-size: 0.8125rem !important;
        font-weight: 700 !important;
        font-variant-numeric: tabular-nums;
        width: 3.5rem;
    }
    .daybook-page .daybook-empty {
        padding: 3rem 2rem !important;
        text-align: center;
        background: linear-gradient(180deg, #fafbfc 0%, #f8fafc 100%) !important;
        border: none !important;
    }
    .daybook-page .daybook-empty__icon {
        font-size: 2.5rem;
        line-height: 1;
        margin-bottom: 0.75rem;
        opacity: 0.35;
    }
    .daybook-page .daybook-empty p {
        margin: 0;
        font-size: 1rem;
        color: #64748b;
        max-width: 22rem;
        margin-left: auto;
        margin-right: auto;
        line-height: 1.55;
    }
    @media (prefers-reduced-motion: reduce) {
        .daybook-page .daybook-metric,
        .daybook-page .daybook-card {
            transition: none !important;
        }
        .daybook-page .daybook-metric:hover {
            transform: none !important;
        }
    }
    #daybookCreateProjectModal .form-control.form-control-theme,
    #daybookCreateProjectModal .form-select.form-select-theme,
    #daybookCreatePurchaseFileModal .form-control.form-control-theme {
        min-height: 3.125rem;
        padding: 0.65rem 1.1rem;
        font-size: 1.0625rem;
        line-height: 1.45;
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        background: #fff !important;
        transition: border-color 0.15s ease, box-shadow 0.2s ease;
    }
    #daybookCreateProjectModal .form-control.form-control-theme:hover,
    #daybookCreateProjectModal .form-select.form-select-theme:hover,
    #daybookCreatePurchaseFileModal .form-control.form-control-theme:hover {
        border-color: #94a3b8;
    }
    #daybookCreateProjectModal .form-control.form-control-theme:focus,
    #daybookCreateProjectModal .form-select.form-select-theme:focus,
    #daybookCreatePurchaseFileModal .form-control.form-control-theme:focus {
        border-color: #f97316;
        box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
    }
    #daybookCreateProjectModal .form-select.form-select-theme {
        background-position: right 1rem center;
        padding-right: 2.75rem;
    }
    #daybookCreatePartyModal .form-control.form-control-theme {
        min-height: 2.75rem;
        font-size: 1rem;
        border-radius: 10px;
    }

    /* Global daybook entries sidebar (index) */
    .daybook-page.daybook-page--with-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    @media (min-width: 992px) {
        .daybook-page.daybook-page--with-sidebar {
            flex-direction: row;
            align-items: flex-start;
        }
        .daybook-entries-sidebar {
            width: min(400px, 36vw);
            flex-shrink: 0;
            max-height: calc(100vh - 2.5rem);
            position: sticky;
            top: 0.35rem;
        }
        .daybook-page-main {
            flex: 1;
            min-width: 0;
        }
    }
    .daybook-sidebar-heading {
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #64748b;
        margin: 0 0 0.35rem 0;
    }
    .daybook-sidebar-scroll {
        overflow-x: auto;
        overflow-y: auto;
        max-height: min(52vh, 420px);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.5);
    }
    @media (min-width: 992px) {
        .daybook-sidebar-scroll {
            max-height: calc(100vh - 15rem);
        }
    }
    .daybook-sidebar-table thead th {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        border-bottom-width: 1px;
        white-space: nowrap;
        padding: 0.45rem 0.5rem;
    }
    .daybook-sidebar-table tbody td {
        padding: 0.45rem 0.5rem;
        vertical-align: top;
        border-color: rgba(15, 23, 42, 0.06);
    }
    .daybook-sidebar-table tbody td.daybook-amount--in {
        color: #16a34a !important;
    }
    .daybook-sidebar-table tbody td.daybook-amount--out {
        color: #dc2626 !important;
    }
    /* Daybook entries page: match /sale table look */
    .daybook-entries-table tbody td {
        vertical-align: middle;
    }
    .daybook-entries-table tbody tr.daybook-sidebar-row--selected-day td {
        background: rgba(37, 99, 235, 0.08) !important;
    }
    .daybook-amount .daybook-amount-tag {
        font-size: 0.62rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        opacity: 0.7;
        margin-top: 1px;
    }
    .daybook-voucher-chip {
        display: inline-block;
        padding: 0.12rem 0.5rem;
        border-radius: 999px;
        background: rgba(37, 99, 235, 0.1);
        color: #1d4ed8 !important;
        font-size: 0.72rem;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    /* Daybook entry details modal */
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
    .daybook-entries-page .daybook-entries-global-scroll {
        max-height: min(70vh, 640px);
    }
    @media (min-width: 992px) {
        .daybook-entries-page .daybook-entries-global-scroll {
            max-height: calc(100vh - 16rem);
        }
    }

    /* Sale wizard — full-size modal */
    .daybook-sale-open-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-weight: 600;
        line-height: 1.4;
    }
    .daybook-sale-wizard-dialog {
        margin: 0;
        max-width: none;
        width: 100%;
        height: 100%;
    }
    @media (min-width: 576px) {
        .daybook-sale-wizard-dialog {
            margin: 0.75rem auto;
            width: calc(100% - 1.5rem);
            height: calc(100% - 1.5rem);
            max-width: 1280px;
        }
    }
    @media (min-width: 1200px) {
        .daybook-sale-wizard-dialog {
            margin: 1rem auto;
            width: calc(100% - 2rem);
            height: calc(100% - 2rem);
            max-width: 1400px;
        }
    }
    #daybookSaleWizardModal .modal-content.daybook-sale-wizard {
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 0;
        border-radius: 0;
        box-shadow: none;
        background:
            radial-gradient(1200px 420px at 0% -10%, rgba(249, 115, 22, 0.12), transparent 55%),
            radial-gradient(900px 360px at 100% 0%, rgba(14, 165, 233, 0.08), transparent 50%),
            #f8fafc;
        overflow: hidden;
    }
    @media (min-width: 576px) {
        #daybookSaleWizardModal .modal-content.daybook-sale-wizard {
            border-radius: 18px;
            border: 1px solid rgba(15, 23, 42, 0.1);
            box-shadow: 0 28px 80px rgba(15, 23, 42, 0.28);
        }
    }
    .daybook-sale-wizard__header {
        flex: 0 0 auto;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 55%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .daybook-sale-wizard__brand {
        display: flex;
        align-items: flex-start;
        gap: 0.9rem;
        min-width: 0;
    }
    .daybook-sale-wizard__icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 14px;
        background: linear-gradient(145deg, #fb923c, #ea580c);
        color: #fff;
        font-size: 1.25rem;
        box-shadow: 0 10px 22px rgba(234, 88, 12, 0.28);
        flex-shrink: 0;
    }
    #daybookSaleWizardModal .modal-title {
        font-weight: 800;
        color: #0f172a;
        font-size: 1.45rem;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }
    .daybook-sale-wizard__subtitle {
        margin-top: 0.25rem;
        color: #64748b;
        font-size: 0.92rem;
    }
    .daybook-sale-wizard__body {
        flex: 1 1 auto;
        overflow: auto;
        padding: 1.35rem 1.5rem 1.5rem;
        position: relative;
        z-index: 2;
    }
    .daybook-sale-wizard__footer {
        flex: 0 0 auto;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.5rem;
        background: rgba(255, 255, 255, 0.92);
        border-top: 1px solid rgba(15, 23, 42, 0.08);
        backdrop-filter: blur(8px);
    }
    .daybook-sale-wizard__footer-actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem;
        margin-left: auto;
    }
    .daybook-sale-wizard__primary {
        min-width: 10rem;
    }
    .daybook-sale-wizard__error {
        margin: 1rem 0 0;
        padding: 0.75rem 1rem;
        border-radius: 12px;
        background: #fef2f2;
        border: 1px solid #fecaca;
        color: #b91c1c;
        font-size: 0.92rem;
        font-weight: 600;
    }

    #daybookSaleWizardModal .daybook-form-combo {
        position: relative;
        z-index: 5;
    }
    #daybookSaleWizardModal .daybook-form-combo-list {
        z-index: 30;
        max-height: min(360px, 42vh);
    }
    #daybookSaleWizardModal .daybook-form-combo-list button {
        padding: 0.7rem 1rem;
        font-size: 0.98rem;
    }
    #daybookSaleWizardModal .daybook-form-combo-list button.is-disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .daybook-sale-combo .form-control-lg,
    #daybookSaleWizardModal .form-control-lg,
    #daybookSaleWizardModal .form-select-lg {
        min-height: 3.1rem;
        font-size: 1.05rem;
        border-radius: 12px;
    }

    .daybook-sale-steps {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto 1fr;
        align-items: stretch;
        gap: 0.55rem;
        list-style: none;
        margin: 0 0 1.35rem;
        padding: 0.85rem;
        border-radius: 16px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
    }
    .daybook-sale-steps__connector {
        align-self: center;
        width: 1.25rem;
        height: 2px;
        border-radius: 999px;
        background: #e2e8f0;
    }
    .daybook-sale-steps__item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
        padding: 0.55rem 0.7rem;
        border-radius: 12px;
        border: 1px solid transparent;
        background: transparent;
        color: #64748b;
    }
    .daybook-sale-steps__num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.85rem;
        height: 1.85rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        font-size: 0.85rem;
        font-weight: 800;
        flex-shrink: 0;
    }
    .daybook-sale-steps__text {
        display: flex;
        flex-direction: column;
        min-width: 0;
        line-height: 1.2;
    }
    .daybook-sale-steps__title {
        font-size: 0.92rem;
        font-weight: 700;
        color: inherit;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .daybook-sale-steps__desc {
        font-size: 0.72rem;
        font-weight: 500;
        color: #94a3b8;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .daybook-sale-steps__item.is-active {
        border-color: rgba(249, 115, 22, 0.28);
        background: rgba(249, 115, 22, 0.1);
        color: #9a3412;
    }
    .daybook-sale-steps__item.is-active .daybook-sale-steps__num {
        background: #f97316;
        color: #fff;
        box-shadow: 0 6px 14px rgba(249, 115, 22, 0.35);
    }
    .daybook-sale-steps__item.is-active .daybook-sale-steps__desc {
        color: #c2410c;
    }
    .daybook-sale-steps__item.is-done {
        border-color: #bbf7d0;
        background: #f0fdf4;
        color: #166534;
    }
    .daybook-sale-steps__item.is-done .daybook-sale-steps__num {
        background: #16a34a;
        color: #fff;
    }
    .daybook-sale-steps__item.is-done .daybook-sale-steps__desc {
        color: #15803d;
    }
    @media (max-width: 767.98px) {
        .daybook-sale-steps {
            grid-template-columns: 1fr;
            gap: 0.4rem;
        }
        .daybook-sale-steps__connector {
            display: none;
        }
        .daybook-sale-steps__desc {
            display: none;
        }
    }

    .daybook-sale-card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 18px;
        padding: 1.35rem 1.4rem 1.5rem;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        min-height: min(52vh, 520px);
    }
    .daybook-sale-card__head {
        margin-bottom: 1.15rem;
        padding-bottom: 0.95rem;
        border-bottom: 1px solid #f1f5f9;
    }
    .daybook-sale-card__title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.01em;
    }
    .daybook-sale-card__hint {
        margin-top: 0.35rem;
        color: #64748b;
        font-size: 0.9rem;
    }
    .daybook-sale-card__foot {
        margin-top: 1.25rem;
        padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
        color: #64748b;
        font-size: 0.88rem;
    }
    .daybook-sale-mode-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #475569;
        white-space: nowrap;
    }
    .daybook-sale-mode-badge.is-dha {
        border-color: rgba(22, 163, 74, 0.35);
        background: #f0fdf4;
        color: #166534;
    }
    .daybook-sale-mode-badge.is-plot {
        border-color: rgba(234, 179, 8, 0.4);
        background: #fefce8;
        color: #854d0e;
    }
    .daybook-sale-item-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (max-width: 767.98px) {
        .daybook-sale-item-meta {
            grid-template-columns: 1fr;
        }
        .daybook-sale-card {
            min-height: 0;
            padding: 1.1rem 1.05rem 1.2rem;
        }
    }
    .daybook-sale-stat {
        padding: 0.9rem 1rem;
        border-radius: 14px;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #e2e8f0;
    }
    .daybook-sale-stat__label {
        display: block;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
        margin-bottom: 0.3rem;
    }
    .daybook-sale-stat__value {
        display: block;
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
    }
    .daybook-sale-fill-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        width: 100%;
        justify-content: center;
        min-height: 3.1rem;
        border-radius: 12px;
        font-weight: 650;
    }
    .daybook-sale-option-hint {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
        margin-top: 0.2rem;
    }
    .daybook-sale-land-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 768px) {
        .daybook-sale-land-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    .daybook-sale-land-stat {
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 12px;
        padding: 0.7rem 0.85rem;
        min-width: 0;
    }
    .daybook-sale-land-stat__label {
        display: block;
        font-size: 0.7rem;
        font-weight: 650;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.2rem;
    }
    .daybook-sale-land-stat__value {
        display: block;
        font-size: 0.92rem;
        font-weight: 650;
        color: #0f172a;
        word-break: break-word;
    }
    .daybook-sale-plot-options {
        display: grid;
        grid-template-columns: repeat(1, minmax(0, 1fr));
        gap: 0.65rem;
    }
    @media (min-width: 576px) {
        .daybook-sale-plot-options {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }
    @media (min-width: 992px) {
        .daybook-sale-plot-options {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    .daybook-sale-plot-option {
        text-align: left;
        border: 1px solid rgba(15, 23, 42, 0.12);
        background: #fff;
        border-radius: 12px;
        padding: 0.85rem 0.95rem;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
    }
    .daybook-sale-plot-option:hover:not(:disabled) {
        border-color: rgba(190, 24, 93, 0.35);
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
    }
    .daybook-sale-plot-option.is-selected {
        border-color: rgba(190, 24, 93, 0.55);
        background: rgba(251, 232, 241, 0.55);
        box-shadow: 0 0 0 1px rgba(190, 24, 93, 0.2);
    }
    .daybook-sale-plot-option:disabled,
    .daybook-sale-plot-option.is-disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }
    .daybook-sale-plot-option__title {
        display: block;
        font-weight: 700;
        color: #0f172a;
        font-size: 0.95rem;
    }
    .daybook-sale-plot-option__meta {
        display: block;
        margin-top: 0.25rem;
        font-size: 0.78rem;
        color: #64748b;
        font-weight: 600;
    }
    .daybook-sale-plot-options__empty {
        grid-column: 1 / -1;
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 12px;
        padding: 1rem;
        color: #64748b;
        font-size: 0.9rem;
        background: #f8fafc;
    }
    .daybook-sale-selected-plot {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        padding: 0.65rem 0.85rem;
        background: #f8fafc;
        min-height: 3.1rem;
    }
    .daybook-sale-selected-plot__label {
        display: block;
        font-size: 0.7rem;
        font-weight: 650;
        text-transform: uppercase;
        letter-spacing: 0.02em;
        color: #64748b;
    }
    .daybook-sale-selected-plot__value {
        display: block;
        font-weight: 650;
        color: #0f172a;
        font-size: 0.92rem;
    }
    #daybook_sale_details_file .form-control[readonly] {
        background: #f8fafc;
        color: #334155;
    }
    .daybook-sale-docs {
        display: flex;
        flex-direction: column;
        gap: 0.65rem;
    }
    .daybook-sale-docs__picker {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        margin: 0;
        padding: 1rem 1rem;
        border: 1.5px dashed rgba(15, 23, 42, 0.18);
        border-radius: 12px;
        background: #f8fafc;
        cursor: pointer;
        text-align: center;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .daybook-sale-docs__picker:hover {
        border-color: rgba(190, 24, 93, 0.45);
        background: rgba(251, 232, 241, 0.35);
    }
    .daybook-sale-docs__picker i {
        font-size: 1.35rem;
        color: #be185d;
        margin-bottom: 0.15rem;
    }
    .daybook-sale-docs__picker-title {
        font-weight: 700;
        font-size: 0.92rem;
        color: #0f172a;
    }
    .daybook-sale-docs__picker-hint {
        font-size: 0.78rem;
        color: #64748b;
        font-weight: 500;
    }
    .daybook-sale-docs__list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }
    .daybook-sale-docs__item {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        padding: 0.55rem 0.65rem 0.55rem 0.75rem;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 10px;
        background: #fff;
        min-width: 0;
    }
    .daybook-sale-docs__icon {
        flex: 0 0 auto;
        width: 2rem;
        height: 2rem;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        color: #475569;
        font-size: 1rem;
    }
    .daybook-sale-docs__meta {
        flex: 1 1 auto;
        min-width: 0;
    }
    .daybook-sale-docs__name {
        display: block;
        font-weight: 650;
        font-size: 0.88rem;
        color: #0f172a;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .daybook-sale-docs__size {
        display: block;
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 600;
    }
    .daybook-sale-docs__remove {
        flex: 0 0 auto;
        width: 1.85rem;
        height: 1.85rem;
        border: 0;
        border-radius: 999px;
        background: #fef2f2;
        color: #b91c1c;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        padding: 0;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .daybook-sale-docs__remove:hover {
        background: #fee2e2;
        color: #991b1b;
    }
    .daybook-sale-docs__remove:focus-visible {
        outline: 2px solid rgba(185, 28, 28, 0.35);
        outline-offset: 2px;
    }
    #daybookSaleWizardModal.is-busy .daybook-save-record__idle {
        display: none;
    }
    #daybookSaleWizardModal.is-busy .daybook-save-record__busy {
        display: inline-flex;
    }
    #daybookSaleWizardModal .daybook-save-record__busy {
        display: none;
        align-items: center;
        gap: 0.4rem;
    }

    /* Construction / Builder section */
    .daybook-construction-section__card {
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        padding: 1rem 1.15rem 1.15rem;
        box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
        overflow: hidden;
    }
    .daybook-construction-section__check {
        display: flex;
        align-items: flex-start;
        gap: 0.7rem;
        margin: 0;
        padding: 0.15rem 0 0.35rem;
        min-width: 0;
    }
    .daybook-construction-section__check .form-check-input {
        float: none;
        position: relative;
        margin: 0.2rem 0 0;
        flex: 0 0 auto;
        width: 1.15rem;
        height: 1.15rem;
        border-color: rgba(15, 23, 42, 0.25);
    }
    .daybook-construction-section__check .form-check-input:checked {
        background-color: var(--accent-orange, #f97316);
        border-color: var(--accent-orange, #f97316);
    }
    .daybook-construction-section__check-body {
        flex: 1 1 auto;
        min-width: 0;
    }
    .daybook-construction-section__check .form-check-label {
        color: #0f172a;
        font-size: 0.98rem;
        padding-left: 0;
    }
</style>
