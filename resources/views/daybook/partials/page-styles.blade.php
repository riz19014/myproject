<style>
    #daybookCreateProjectModal .modal-content {
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
    #daybookCreatePartyModal .modal-header {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
        padding: 1.1rem 1.25rem;
    }
    #daybookCreateProjectModal .modal-title,
    #daybookCreatePartyModal .modal-title {
        font-weight: 700;
        color: #0f172a;
        font-size: 1.15rem;
    }
    #daybookCreateProjectModal .modal-body,
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
    #daybookCreateProjectModal .daybook-project-modal-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1.25rem 1.35rem 1.35rem;
    }
    #daybookCreateProjectModal .daybook-modal-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        display: block;
    }
    #daybookCreateProjectModal .modal-footer,
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
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
        margin-bottom: 1.75rem;
    }
    @media (min-width: 576px) {
        .daybook-page .daybook-metrics {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (min-width: 1200px) {
        .daybook-page .daybook-metrics {
            grid-template-columns: repeat(6, 1fr);
            gap: 0.85rem;
        }
    }
    .daybook-page .daybook-metric {
        position: relative;
        padding: 1rem 1rem 1.1rem;
        background: rgba(255, 255, 255, 0.72);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 14px;
        box-shadow:
            0 2px 12px rgba(15, 23, 42, 0.06),
            inset 0 1px 0 rgba(255, 255, 255, 0.95);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
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
        height: 3px;
        border-radius: 14px 14px 0 0;
        opacity: 0.85;
    }
    .daybook-page .daybook-metric--prior::after { background: linear-gradient(90deg, #334155, #64748b); }
    .daybook-page .daybook-metric--open::after { background: linear-gradient(90deg, #64748b, #94a3b8); }
    .daybook-page .daybook-metric--petty::after { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .daybook-page .daybook-metric--in::after { background: linear-gradient(90deg, #16a34a, #22c55e); }
    .daybook-page .daybook-metric--out::after { background: linear-gradient(90deg, #dc2626, #f87171); }
    .daybook-page .daybook-metric--close::after { background: linear-gradient(90deg, #ea580c, #fb923c); }
    .daybook-page .daybook-metric__label {
        display: block;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 0.35rem;
    }
    .daybook-page .daybook-metric__val {
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
        font-variant-numeric: tabular-nums;
        line-height: 1.2;
    }
    .daybook-page .daybook-metric--in .daybook-metric__val { color: #15803d; }
    .daybook-page .daybook-metric--out .daybook-metric__val { color: #b91c1c; }
    .daybook-page .daybook-metric--close .daybook-metric__val { color: #c2410c; }
    .daybook-page .daybook-metric__sub {
        display: block;
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.25rem;
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
    .daybook-page .daybook-card-heading .btn-outline-theme:hover {
        background: #0f172a !important;
        color: #fff !important;
        border-color: #0f172a !important;
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
        overflow: hidden;
        border: 1px solid var(--db-border);
        background: #fff;
    }
    .daybook-page .daybook-records-panel .table-theme {
        margin: 0;
        --bs-table-border-color: #e2e8f0;
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
    #daybookCreateProjectModal .form-select.form-select-theme {
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
    #daybookCreateProjectModal .form-select.form-select-theme:hover {
        border-color: #94a3b8;
    }
    #daybookCreateProjectModal .form-control.form-control-theme:focus,
    #daybookCreateProjectModal .form-select.form-select-theme:focus {
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
</style>
