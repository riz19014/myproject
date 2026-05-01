@extends('layouts.app')

@section('title', 'Daybook')

@section('main_class', 'container-fluid px-3 pb-4 pt-0')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css">
@endpush

@section('content')
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
    .daybook-page .daybook-card {
        position: relative;
        border: none;
        border-radius: var(--db-radius);
        box-shadow: var(--db-shadow);
        background: var(--db-surface);
        overflow: hidden;
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
<div class="daybook-page">
    <div class="daybook-page-heading">
        <div class="daybook-card-heading">
            <div class="daybook-card-heading__title">
                <h1 class="daybook-card-title">Daybook</h1>
            </div>
            <div class="daybook-card-heading__actions">
                <div class="daybook-date-nav" role="group" aria-label="Daybook date navigation">
                    <a
                        href="{{ route('daybook.index', ['date' => $day->copy()->subDay()->toDateString()]) }}"
                        class="daybook-nav-btn"
                        aria-label="Previous day"
                        title="Previous day"
                    >
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </a>

                    <form method="get" action="{{ route('daybook.index') }}" class="m-0">
                        <label class="visually-hidden" for="daybook-filter-date">Select date</label>
                        <div class="daybook-date-picker">
                            <i class="bi bi-calendar3" aria-hidden="true"></i>
                            <input
                                type="text"
                                id="daybook-filter-date"
                                name="date"
                                class="daybook-date-input"
                                value="{{ $day->toDateString() }}"
                                inputmode="none"
                                autocomplete="off"
                                readonly
                                aria-label="View date"
                            >
                        </div>
                    </form>

                    <a
                        href="{{ route('daybook.index', ['date' => $day->copy()->addDay()->toDateString()]) }}"
                        class="daybook-nav-btn"
                        aria-label="Next day"
                        title="Next day"
                    >
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>

                <span class="daybook-date-chip" title="{{ $day->toDateString() }}">{{ $day->format('l, j M Y') }}</span>
                <a href="{{ route('daybook.report.pdf', ['date' => $day->toDateString()]) }}" class="daybook-pdf-link" title="Download PDF" aria-label="Download PDF for this day">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 16 16" fill="#dc2626" aria-hidden="true">
                        <path d="M14 14V4.5L9.5 0H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2zM9.5 3A1.5 1.5 0 0 0 11 4.5h2V14a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1h5.5v2z"/>
                        <path d="M4.603 14.087a1 1 0 0 0-.757-.429H2.5a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1h1a1 1 0 0 0 .997-.867l.922-5.638h.001a.5.5 0 0 0-.497-.5H3.522a.5.5 0 0 0-.486.345l-.433 4.113zm5.21-1.57a.5.5 0 0 0-.498.453l-.922 5.638a1 1 0 0 1-.997.872h-1.003a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h1.345a1 1 0 0 1 .757.429l.486.606a.5.5 0 0 0 .498.453zM9.5 12.5a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1a.5.5 0 0 0-.5-.5h-1zm1.5-3.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1zm-2-3a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1a.5.5 0 0 0 .5.5h1a.5.5 0 0 0 .5-.5v-1z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>

<section class="daybook-metrics" aria-label="Day financial summary">
    <div class="daybook-metric daybook-metric--prior">
        <span class="daybook-metric__label">Previous close</span>
        <span class="daybook-metric__val">Rs {{ number_format($previousDayClosing, 2) }}</span>
        <span class="daybook-metric__sub">{{ $prevDay->format('D, j M') }}</span>
    </div>
    <div class="daybook-metric daybook-metric--open">
        <span class="daybook-metric__label">Opening</span>
        <span class="daybook-metric__val">Rs {{ number_format($openingAmount, 2) }}</span>
        <span class="daybook-metric__sub">Carried balance</span>
    </div>
    <div class="daybook-metric daybook-metric--petty">
        <span class="daybook-metric__label">Petty cash</span>
        <span class="daybook-metric__val">Rs {{ number_format($pettyCashAmount, 2) }}</span>
        <span class="daybook-metric__sub">Added today</span>
    </div>
    <div class="daybook-metric daybook-metric--in">
        <span class="daybook-metric__label">Payment in</span>
        <span class="daybook-metric__val">Rs {{ number_format($cashIn, 2) }}</span>
        <span class="daybook-metric__sub">This day</span>
    </div>
    <div class="daybook-metric daybook-metric--out">
        <span class="daybook-metric__label">Payment out</span>
        <span class="daybook-metric__val">Rs {{ number_format($cashOut, 2) }}</span>
        <span class="daybook-metric__sub">This day</span>
    </div>
    <div class="daybook-metric daybook-metric--close">
        <span class="daybook-metric__label">Closing</span>
        <span class="daybook-metric__val">Rs {{ number_format($closingBalance, 2) }}</span>
        <span class="daybook-metric__sub">End of day</span>
    </div>
</section>

<div class="card daybook-card mb-4">
    <div class="daybook-card__accent" aria-hidden="true"></div>
    <div class="card-body p-0">
        <div class="daybook-tabs-row">
        <ul class="nav nav-pills daybook-inner-tabs ps-0 mb-0" id="daybookMainTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="daybook-tab-new-btn" data-bs-toggle="tab" data-bs-target="#daybook-tab-new" type="button" role="tab" aria-controls="daybook-tab-new" aria-selected="true">New Entry</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="daybook-tab-records-btn" data-bs-toggle="tab" data-bs-target="#daybook-tab-records" type="button" role="tab" aria-controls="daybook-tab-records" aria-selected="false">Records</button>
            </li>
        </ul>
        <div class="daybook-tabs-row-save">
            <button type="submit" form="daybook-add-form" class="daybook-save-record text-nowrap" id="daybook-save-record-btn" aria-label="Save record">
                <span class="daybook-save-record__idle">
                    <i class="bi bi-floppy2 app-sidebar-logout__icon" aria-hidden="true"></i>
                    <span>Save record</span>
                </span>
                <span class="daybook-save-record__busy" aria-hidden="true">
                    <span class="daybook-save-spinner" role="status" aria-hidden="true"></span>
                    <span>Saving…</span>
                </span>
            </button>
        </div>
        </div>
        <div class="tab-content daybook-main-tab-content" id="daybookMainTabsContent">
            <div class="tab-pane fade show active" id="daybook-tab-new" role="tabpanel" aria-labelledby="daybook-tab-new-btn" tabindex="0">
                <div class="daybook-form-inner">
        <form method="post" action="{{ route('daybook.store') }}" id="daybook-add-form">
            @csrf
            <input type="hidden" name="return_date" value="{{ $day->toDateString() }}">

            <div class="daybook-panel">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label daybook-label mb-0" for="daybook_form_project_search">Project</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="daybook_form_project_create">+ Create new project</button>
                        </div>
                        <div class="daybook-form-combo @error('project_id') is-invalid @enderror">
                            <input type="hidden" name="project_id" id="daybook_form_project_id" value="{{ old('project_id') }}">
                            <input
                                type="text"
                                class="form-control form-control-theme @error('project_id') is-invalid @enderror"
                                id="daybook_form_project_search"
                                placeholder="Search project…"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_form_project_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-form-combo-list d-none" id="daybook_form_project_listbox" role="listbox" hidden></ul>
                        </div>
                        @error('project_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                            <label class="form-label daybook-label mb-0" for="daybook_form_party_search">Party</label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="daybook_form_party_create">+ Create new party</button>
                        </div>
                        <div class="daybook-form-combo @error('party_id') is-invalid @enderror">
                            <input type="hidden" name="party_id" id="daybook_form_party_id" value="{{ old('party_id') }}">
                            <input
                                type="text"
                                class="form-control form-control-theme @error('party_id') is-invalid @enderror"
                                id="daybook_form_party_search"
                                placeholder="Search party…"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_form_party_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-form-combo-list d-none" id="daybook_form_party_listbox" role="listbox" hidden></ul>
                        </div>
                        @error('party_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <script type="application/json" id="daybook-form-projects-json">@json($projects->map(function ($p) {
                    return ['id' => $p->id, 'label' => $p->name];
                })->values())</script>
                <script type="application/json" id="daybook-form-parties-json">@json($parties->map(function ($p) {
                    return ['id' => $p->id, 'label' => $p->name];
                })->values())</script>
            </div>

            <div class="daybook-panel mb-0">
                <div class="row g-4 mb-0">
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label daybook-label" for="entry_type">Payment</label>
                        <select id="entry_type" name="type" class="form-select form-select-theme" required>
                            <option value="cash_in" @selected(old('type', 'cash_out') === 'cash_in')>Payment in</option>
                            <option value="cash_out" @selected(old('type', 'cash_out') === 'cash_out')>Payment out</option>
                        </select>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label daybook-label" for="entry_date_input">Date</label>
                        <input
                            id="entry_date_input"
                            type="text"
                            name="entry_date"
                            class="form-control form-control-theme"
                            value="{{ old('entry_date', now()->toDateString()) }}"
                            inputmode="none"
                            autocomplete="off"
                            readonly
                            required
                        >
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label daybook-label" for="entry_description">Description <span class="text-muted fw-normal">(optional)</span></label>
                        <input id="entry_description" type="text" name="description" class="form-control form-control-theme" placeholder="e.g. Office supplies" value="{{ old('description') }}" autocomplete="off">
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <label class="form-label daybook-label daybook-amount-label" for="entry_amount">
                            <span>Amount (Rs)</span>
                            <span class="daybook-amount-words" id="entry_amount_words" aria-live="polite"></span>
                        </label>
                        <input
                            id="entry_amount"
                            type="text"
                            name="amount"
                            class="form-control form-control-theme"
                            placeholder="0.00"
                            inputmode="decimal"
                            autocomplete="off"
                            value="{{ old('amount') }}"
                            required
                        >
                    </div>
                </div>
            </div>
        </form>
                </div>
            </div>
            <div class="tab-pane fade" id="daybook-tab-records" role="tabpanel" aria-labelledby="daybook-tab-records-btn" tabindex="0">
                <div class="daybook-records-panel">
        <div class="daybook-table-head">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="mb-0 d-flex align-items-center flex-wrap">Entries <span class="daybook-count-badge" title="Lines this day">{{ $entries->count() }}</span></h2>
            </div>
            <span class="text-muted small fw-medium">{{ $day->format('l, j M Y') }}</span>
        </div>
        <div class="table-responsive daybook-table-shell">
            <table class="table table-theme mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Payment</th>
                        <th class="text-end">Amount (Rs)</th>
                        <th>Linked to</th>
                        <th width="220">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td class="daybook-id-cell">{{ $e->id }}</td>
                            <td>{{ $e->description ?: '—' }}</td>
                            <td>
                                @if($e->type === 'cash_in')
                                    <span class="daybook-pill daybook-pill--in">Payment in</span>
                                @else
                                    <span class="daybook-pill daybook-pill--out">Payment out</span>
                                @endif
                            </td>
                            <td class="text-end font-monospace">
                                @if($e->type === 'cash_in')
                                    +{{ number_format($e->amount, 0) }}
                                @else
                                    −{{ number_format($e->amount, 0) }}
                                @endif
                            </td>
                            <td class="small">{{ $e->getLinkLabel() }}</td>
                            <td>
                                <div class="daybook-table-actions">
                                    <a href="{{ route('daybook.show', $e) }}" class="daybook-table-action-btn">View</a>
                                    <a href="{{ route('daybook.edit', $e) }}" class="daybook-table-action-btn">Edit</a>
                                    <form action="{{ route('daybook.destroy', $e) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="daybook-table-action-btn btn-delete-confirm" data-title="Delete entry?" data-text="This will remove this daybook line.">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="daybook-empty">
                                <div class="daybook-empty__icon" aria-hidden="true">◇</div>
                                <p><strong>No lines yet</strong> for this date. Add a payment in <strong>New Entry</strong> — project, party, and amount — to build your ledger.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>{{-- .daybook-page --}}
@endsection

@push('modals')
<div class="modal fade" id="daybookCreateProjectModal" tabindex="-1" aria-labelledby="daybookCreateProjectModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreateProjectModalLabel">Create project</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daybook-project-modal-step mb-0" data-project-modal-step="0">
                    <div class="daybook-project-modal-panel">
                        <label class="daybook-modal-label" for="daybook_modal_project_name">Project name</label>
                        <input type="text" class="form-control form-control-theme" id="daybook_modal_project_name" placeholder="e.g. DHA Phase 2" autocomplete="off">
                    </div>
                </div>
                <div class="daybook-project-modal-step mb-0 d-none" data-project-modal-step="1">
                    <div class="daybook-project-modal-panel">
                        <label class="daybook-modal-label" for="daybook_modal_project_field_type">Type</label>
                        <select class="form-select form-select-theme" id="daybook_modal_project_field_type">
                            <option value="">Select sale or purchase</option>
                            <option value="sale">Sale</option>
                            <option value="purchase">Purchase</option>
                        </select>
                    </div>
                </div>
                <div class="daybook-project-modal-step mb-0 d-none" data-project-modal-step="2">
                    <div class="daybook-project-modal-panel">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <label class="daybook-modal-label mb-0" for="daybook_modal_project_land_type_search">Land type</label>
                            <a href="{{ route('land-types.index') }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-none fw-medium">Manage land types</a>
                        </div>
                        <div class="daybook-project-lt-combo">
                            <input type="hidden" id="daybook_modal_project_land_type_id" value="">
                            <input
                                type="text"
                                class="form-control form-control-theme"
                                id="daybook_modal_project_land_type_search"
                                placeholder="Search land type…"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_modal_project_land_type_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-project-lt-listbox d-none" id="daybook_modal_project_land_type_listbox" role="listbox" hidden></ul>
                        </div>
                        <script type="application/json" id="daybook-land-types-json">@json($landTypes->map(function ($lt) {
                            return ['id' => $lt->id, 'label' => $lt->name];
                        })->values())</script>
                        @if($landTypes->isEmpty())
                            <p class="text-muted small mt-3 mb-0">No land types yet. Open <strong>Manage land types</strong> and add at least one (e.g. Factory, House, Plot).</p>
                        @endif
                    </div>
                </div>
                <div class="daybook-project-modal-step mb-0 d-none" data-project-modal-step="3">
                    <div class="daybook-project-modal-panel">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <label class="daybook-modal-label mb-0" for="daybook_modal_project_party_search">Parties <span class="text-muted fw-normal">(optional)</span></label>
                            <button type="button" class="btn btn-outline-theme btn-sm" data-bs-target="#daybookCreatePartyModal" data-bs-toggle="modal">Create party</button>
                        </div>
                        <div class="daybook-project-party-combo">
                            <input type="hidden" id="daybook_modal_project_party_id" value="">
                            <input
                                type="text"
                                class="form-control form-control-theme"
                                id="daybook_modal_project_party_search"
                                placeholder="Search party…"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_modal_project_party_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-project-party-listbox d-none" id="daybook_modal_project_party_listbox" role="listbox" hidden></ul>
                        </div>
                        <script type="application/json" id="daybook-parties-json">@json($parties->map(function ($p) {
                            return ['id' => $p->id, 'label' => $p->name];
                        })->values())</script>

                        <div class="mt-3">
                            <div class="text-muted small fw-semibold text-uppercase" style="letter-spacing:.08em;">Selected parties</div>
                            <div class="daybook-project-party-selected mt-2" id="daybook_modal_project_party_selected"></div>
                        </div>
                    </div>
                </div>
                <div class="daybook-project-modal-step mb-0 d-none" data-project-modal-step="4">
                    <div class="daybook-project-modal-panel">
                        <label class="daybook-modal-label mb-2">Area (enter only one)</label>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="daybook-modal-label" for="daybook_modal_project_area_acre">Acre</label>
                                <input type="number" class="form-control form-control-theme" id="daybook_modal_project_area_acre" placeholder="0" step="1" min="0" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="col-sm-6">
                                <label class="daybook-modal-label" for="daybook_modal_project_area_kanal">Kanal</label>
                                <input type="number" class="form-control form-control-theme" id="daybook_modal_project_area_kanal" placeholder="0" step="1" min="0" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="col-sm-6">
                                <label class="daybook-modal-label" for="daybook_modal_project_area_marla">Marla</label>
                                <input type="number" class="form-control form-control-theme" id="daybook_modal_project_area_marla" placeholder="0" step="1" min="0" inputmode="numeric" autocomplete="off">
                            </div>
                            <div class="col-sm-6">
                                <label class="daybook-modal-label" for="daybook_modal_project_area_sqft">Sq ft</label>
                                <input type="number" class="form-control form-control-theme" id="daybook_modal_project_area_sqft" placeholder="0" step="1" min="0" inputmode="numeric" autocomplete="off">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="daybook-project-modal-step mb-0 d-none" data-project-modal-step="5">
                    <div class="daybook-project-modal-panel">
                        <label class="daybook-modal-label" for="daybook_modal_project_total_amount">Total amount (Rs)</label>
                        <input type="number" class="form-control form-control-theme" id="daybook_modal_project_total_amount" placeholder="0" step="0.01" min="0" inputmode="decimal" autocomplete="off">
                    </div>
                </div>
                <p class="text-danger small mt-3 mb-0 d-none" id="daybook_modal_project_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2 align-items-center">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0 d-none" id="daybook_modal_project_back" aria-label="Previous step">Back</button>
                <button type="button" class="daybook-save-record text-nowrap ms-sm-auto" id="daybook_modal_project_primary" aria-label="Next step">
                    <span class="daybook-save-record__idle">
                        <span id="daybook_modal_project_primary_label">Next</span>
                    </span>
                    <span class="daybook-save-record__busy" aria-hidden="true">
                        <span class="daybook-save-spinner" role="status" aria-hidden="true"></span>
                        <span>Saving…</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="daybookCreatePartyModal" tabindex="-1" aria-labelledby="daybookCreatePartyModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreatePartyModalLabel">Create party</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Category is set from the sub category you choose.</p>
                <div class="mb-3">
                    <label class="form-label" for="daybook_modal_party_name">Party name</label>
                    <input type="text" class="form-control form-control-theme" id="daybook_modal_party_name" placeholder="e.g. Ali Traders" autocomplete="off">
                </div>
                <div class="mb-0 daybook-party-sc-combo">
                    <label class="form-label" for="daybook_modal_party_sub_search">Sub category</label>
                    <input type="hidden" id="daybook_modal_party_sub_category" value="">
                    <input type="text"
                        class="form-control form-control-theme"
                        id="daybook_modal_party_sub_search"
                        placeholder="Search sub category…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="daybook_party_sc_listbox"
                        aria-autocomplete="list">
                    <ul class="daybook-party-sc-listbox d-none" id="daybook_party_sc_listbox" role="listbox" hidden></ul>
                </div>
                <script type="application/json" id="daybook-party-sub-json">@json($partySubCategories->map(function ($sc) {
                    return ['id' => $sc->id, 'label' => $sc->category->name . ' — ' . $sc->name];
                })->values())</script>
                <p class="text-danger small mt-2 mb-0 d-none" id="daybook_modal_party_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-pink flex-grow-1 flex-sm-grow-0" id="daybook_modal_party_submit">Save</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    /**
     * Focus a text input and set a collapsed selection so the caret shows.
     * Do not re-focus while the field already has focus — that resets the blink timer and looks “stuck”.
     * Use scheduleEnsure when opening from the project/party dropdown so we win any focus race after it closes.
     */
    function daybookModalFocusText(el, options) {
        if (!el || el.disabled) return;
        options = options || {};
        function attach() {
            el.focus();
            try {
                if (typeof el.setSelectionRange === 'function') {
                    var n = (el.value || '').length;
                    el.setSelectionRange(n, n);
                }
            } catch (ignore) {}
        }
        function ensure() {
            if (document.activeElement !== el) {
                attach();
            }
        }
        attach();
        if (options.scheduleEnsure) {
            requestAnimationFrame(function () {
                ensure();
                requestAnimationFrame(ensure);
            });
            setTimeout(ensure, 0);
            setTimeout(ensure, 80);
            setTimeout(ensure, 250);
            setTimeout(ensure, 450);
        }
    }
    window.daybookModalFocusText = daybookModalFocusText;
})();

(function () {
    var form = document.getElementById('daybook-add-form');
    var saveBtn = document.getElementById('daybook-save-record-btn');
    if (!form || !saveBtn) return;
    form.addEventListener('submit', function () {
        if (saveBtn.classList.contains('is-loading')) return;
        saveBtn.classList.add('is-loading');
        saveBtn.disabled = true;
        saveBtn.setAttribute('aria-busy', 'true');
        saveBtn.setAttribute('aria-label', 'Saving…');
    });
})();

(function () {
    var projectHidden = document.getElementById('daybook_form_project_id');
    var projectSearch = document.getElementById('daybook_form_project_search');
    var projectList = document.getElementById('daybook_form_project_listbox');
    var projectWrap = projectSearch ? projectSearch.closest('.daybook-form-combo') : null;
    var projectJsonEl = document.getElementById('daybook-form-projects-json');
    var projectCreateBtn = document.getElementById('daybook_form_project_create');

    var partyHidden = document.getElementById('daybook_form_party_id');
    var partySearch = document.getElementById('daybook_form_party_search');
    var partyList = document.getElementById('daybook_form_party_listbox');
    var partyWrap = partySearch ? partySearch.closest('.daybook-form-combo') : null;
    var partyJsonEl = document.getElementById('daybook-form-parties-json');
    var partyCreateBtn = document.getElementById('daybook_form_party_create');

    if (!projectHidden || !projectSearch || !projectList || !partyHidden || !partySearch || !partyList) return;

    var formProjectRows = [];
    if (projectJsonEl) {
        try {
            formProjectRows = JSON.parse(projectJsonEl.textContent) || [];
        } catch (e) {
            formProjectRows = [];
        }
    }
    var formPartyRows = [];
    if (partyJsonEl) {
        try {
            formPartyRows = JSON.parse(partyJsonEl.textContent) || [];
        } catch (e) {
            formPartyRows = [];
        }
    }

    window.__daybookFormProjectRows = formProjectRows;
    window.__daybookFormPartyRows = formPartyRows;

    function hideProjectList() {
        projectList.classList.add('d-none');
        projectList.setAttribute('hidden', '');
        projectSearch.setAttribute('aria-expanded', 'false');
    }

    function showProjectList() {
        projectList.classList.remove('d-none');
        projectList.removeAttribute('hidden');
        projectSearch.setAttribute('aria-expanded', 'true');
    }

    function filterProjectRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formProjectRows.slice();
        return formProjectRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderProjectList(rows) {
        projectList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formProjectRows.length ? 'No projects match.' : 'No projects yet.';
            projectList.appendChild(li0);
            showProjectList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                projectHidden.value = String(row.id);
                projectSearch.value = row.label;
                hideProjectList();
            });
            li.appendChild(btn);
            projectList.appendChild(li);
        });
        showProjectList();
    }

    function openFilteredProjectList() {
        renderProjectList(filterProjectRows(projectSearch.value));
    }

    function hidePartyFormList() {
        partyList.classList.add('d-none');
        partyList.setAttribute('hidden', '');
        partySearch.setAttribute('aria-expanded', 'false');
    }

    function showPartyFormList() {
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        partySearch.setAttribute('aria-expanded', 'true');
    }

    function filterPartyFormRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formPartyRows.slice();
        return formPartyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderPartyFormList(rows) {
        partyList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formPartyRows.length ? 'No parties match.' : 'No parties yet.';
            partyList.appendChild(li0);
            showPartyFormList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                partyHidden.value = String(row.id);
                partySearch.value = row.label;
                hidePartyFormList();
            });
            li.appendChild(btn);
            partyList.appendChild(li);
        });
        showPartyFormList();
    }

    function openFilteredPartyFormList() {
        renderPartyFormList(filterPartyFormRows(partySearch.value));
    }

    (function syncOldValues() {
        if (projectHidden.value) {
            var pr = formProjectRows.find(function (r) { return String(r.id) === String(projectHidden.value); });
            if (pr) projectSearch.value = pr.label;
        }
        if (partyHidden.value) {
            var py = formPartyRows.find(function (r) { return String(r.id) === String(partyHidden.value); });
            if (py) partySearch.value = py.label;
        }
    })();

    projectSearch.addEventListener('focus', function () {
        openFilteredProjectList();
    });
    projectSearch.addEventListener('input', function () {
        projectHidden.value = '';
        openFilteredProjectList();
    });
    projectSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideProjectList();
        }
    });

    partySearch.addEventListener('focus', function () {
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('input', function () {
        partyHidden.value = '';
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hidePartyFormList();
        }
    });

    document.addEventListener('click', function (e) {
        if (projectWrap && !projectWrap.contains(e.target)) hideProjectList();
        if (partyWrap && !partyWrap.contains(e.target)) hidePartyFormList();
    });
})();

(function () {
    var projectFormHidden = document.getElementById('daybook_form_project_id');
    var projectFormSearch = document.getElementById('daybook_form_project_search');
    var projectFormCreateBtn = document.getElementById('daybook_form_project_create');
    var modalEl = document.getElementById('daybookCreateProjectModal');
    var nameInput = document.getElementById('daybook_modal_project_name');
    var fieldTypeSelect = document.getElementById('daybook_modal_project_field_type');
    var landTypeHidden = document.getElementById('daybook_modal_project_land_type_id');
    var landTypeSearch = document.getElementById('daybook_modal_project_land_type_search');
    var landTypeList = document.getElementById('daybook_modal_project_land_type_listbox');
    var landTypesJsonEl = document.getElementById('daybook-land-types-json');
    var partyHidden = document.getElementById('daybook_modal_project_party_id');
    var partySearch = document.getElementById('daybook_modal_project_party_search');
    var partyList = document.getElementById('daybook_modal_project_party_listbox');
    var partiesJsonEl = document.getElementById('daybook-parties-json');
    var partySelectedWrap = document.getElementById('daybook_modal_project_party_selected');
    var areaAcreInput = document.getElementById('daybook_modal_project_area_acre');
    var areaKanalInput = document.getElementById('daybook_modal_project_area_kanal');
    var areaMarlaInput = document.getElementById('daybook_modal_project_area_marla');
    var areaSqftInput = document.getElementById('daybook_modal_project_area_sqft');
    var totalAmountInput = document.getElementById('daybook_modal_project_total_amount');
    var primaryBtn = document.getElementById('daybook_modal_project_primary');
    var primaryLabel = document.getElementById('daybook_modal_project_primary_label');
    var backBtn = document.getElementById('daybook_modal_project_back');
    var errEl = document.getElementById('daybook_modal_project_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!projectFormHidden || !projectFormSearch || !projectFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });
    var maxStep = 5;
    var step = 0;

    var landTypeRows = [];
    if (landTypesJsonEl) {
        try {
            landTypeRows = JSON.parse(landTypesJsonEl.textContent) || [];
        } catch (e) {
            landTypeRows = [];
        }
    }

    function hideLandTypeList() {
        if (!landTypeList) return;
        landTypeList.classList.add('d-none');
        landTypeList.setAttribute('hidden', '');
        if (landTypeSearch) landTypeSearch.setAttribute('aria-expanded', 'false');
    }

    function showLandTypeList() {
        if (!landTypeList) return;
        landTypeList.classList.remove('d-none');
        landTypeList.removeAttribute('hidden');
        if (landTypeSearch) landTypeSearch.setAttribute('aria-expanded', 'true');
    }

    function filterLandTypeRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return landTypeRows.slice();
        return landTypeRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderLandTypeList(rows) {
        if (!landTypeList) return;
        landTypeList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-project-lt-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = landTypeRows.length ? 'No land types match.' : 'No land types yet. Add them under Manage land types.';
            landTypeList.appendChild(li0);
            showLandTypeList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (landTypeHidden) landTypeHidden.value = String(row.id);
                if (landTypeSearch) landTypeSearch.value = row.label;
                hideLandTypeList();
            });
            li.appendChild(btn);
            landTypeList.appendChild(li);
        });
        showLandTypeList();
    }

    function openFilteredLandTypeList() {
        renderLandTypeList(filterLandTypeRows(landTypeSearch ? landTypeSearch.value : ''));
    }

    function clearLandTypePicker() {
        if (landTypeHidden) landTypeHidden.value = '';
        if (landTypeSearch) {
            landTypeSearch.value = '';
            landTypeSearch.setAttribute('aria-expanded', 'false');
        }
        hideLandTypeList();
    }

    var partyRows = [];
    if (partiesJsonEl) {
        try {
            partyRows = JSON.parse(partiesJsonEl.textContent) || [];
        } catch (e) {
            partyRows = [];
        }
    }

    var selectedPartyIds = [];

    window.__daybookProjectModalPartyRowsPush = function (id, name) {
        var idStr = String(id);
        if (!partyRows.some(function (r) { return String(r.id) === idStr; })) {
            partyRows.push({ id: parseInt(id, 10), label: name });
        }
    };

    function hidePartyList() {
        if (!partyList) return;
        partyList.classList.add('d-none');
        partyList.setAttribute('hidden', '');
        if (partySearch) partySearch.setAttribute('aria-expanded', 'false');
    }

    function showPartyList() {
        if (!partyList) return;
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        if (partySearch) partySearch.setAttribute('aria-expanded', 'true');
    }

    function filterPartyRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return partyRows.slice();
        return partyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderSelectedParties() {
        if (!partySelectedWrap) return;
        partySelectedWrap.innerHTML = '';
        if (!selectedPartyIds.length) {
            var empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = 'No parties selected.';
            partySelectedWrap.appendChild(empty);
            return;
        }
        selectedPartyIds.forEach(function (id) {
            var row = partyRows.find(function (r) { return String(r.id) === String(id); });
            var label = row ? row.label : ('Party #' + id);
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'daybook-project-party-chip';
            chip.textContent = label + ' ×';
            chip.addEventListener('click', function () {
                selectedPartyIds = selectedPartyIds.filter(function (x) { return String(x) !== String(id); });
                renderSelectedParties();
            });
            partySelectedWrap.appendChild(chip);
        });
    }

    function renderPartyList(rows) {
        if (!partyList) return;
        partyList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-project-party-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = partyRows.length ? 'No parties match.' : 'No parties yet. Create one first.';
            partyList.appendChild(li0);
            showPartyList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (partyHidden) partyHidden.value = String(row.id);
                if (partySearch) partySearch.value = row.label;
                hidePartyList();
                var id = String(row.id);
                if (!selectedPartyIds.some(function (x) { return String(x) === id; })) {
                    selectedPartyIds.push(row.id);
                    renderSelectedParties();
                }
                if (partyHidden) partyHidden.value = '';
                if (partySearch) partySearch.value = '';
            });
            li.appendChild(btn);
            partyList.appendChild(li);
        });
        showPartyList();
    }

    function openFilteredPartyList() {
        renderPartyList(filterPartyRows(partySearch ? partySearch.value : ''));
    }

    function clearPartyPicker() {
        if (partyHidden) partyHidden.value = '';
        if (partySearch) {
            partySearch.value = '';
            partySearch.setAttribute('aria-expanded', 'false');
        }
        hidePartyList();
        selectedPartyIds = [];
        renderSelectedParties();
    }

    function resetProjectModalFields() {
        if (nameInput) nameInput.value = '';
        if (fieldTypeSelect) fieldTypeSelect.value = '';
        clearLandTypePicker();
        clearPartyPicker();
        if (areaAcreInput) areaAcreInput.value = '';
        if (areaKanalInput) areaKanalInput.value = '';
        if (areaMarlaInput) areaMarlaInput.value = '';
        if (areaSqftInput) areaSqftInput.value = '';
        if (totalAmountInput) totalAmountInput.value = '';
    }

    function clearPrimaryLoading() {
        if (!primaryBtn) return;
        primaryBtn.classList.remove('is-loading');
        primaryBtn.disabled = false;
        primaryBtn.removeAttribute('aria-busy');
    }

    function setPrimaryLoading(loading) {
        if (!primaryBtn) return;
        if (loading) {
            primaryBtn.classList.add('is-loading');
            primaryBtn.disabled = true;
            primaryBtn.setAttribute('aria-busy', 'true');
            primaryBtn.setAttribute('aria-label', 'Saving…');
        } else {
            clearPrimaryLoading();
            updatePrimaryChrome();
        }
    }

    function updatePrimaryChrome() {
        if (!primaryBtn || !primaryLabel) return;
        var isLast = step >= maxStep;
        primaryLabel.textContent = isLast ? 'Create project' : 'Next';
        primaryBtn.setAttribute('aria-label', isLast ? 'Create project' : 'Next step');
    }

    function goToStep(n) {
        var was = step;
        step = Math.max(0, Math.min(maxStep, n));
        if (was === 2 && step !== 2) hideLandTypeList();
        if (was === 3 && step !== 3) hidePartyList();
        modalEl.querySelectorAll('.daybook-project-modal-step').forEach(function (el) {
            var sn = parseInt(el.getAttribute('data-project-modal-step'), 10);
            el.classList.toggle('d-none', sn !== step);
        });
        if (backBtn) backBtn.classList.toggle('d-none', step === 0);
        updatePrimaryChrome();
        if (!modalEl.classList.contains('show')) return;
        if (step === 0 && nameInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        } else if (step === 1 && fieldTypeSelect) {
            fieldTypeSelect.focus();
        } else if (step === 2 && landTypeSearch) {
            if (window.daybookModalFocusText) {
                window.daybookModalFocusText(landTypeSearch, { scheduleEnsure: true });
            } else {
                landTypeSearch.focus();
            }
            openFilteredLandTypeList();
        } else if (step === 3 && partySearch) {
            if (window.daybookModalFocusText) {
                window.daybookModalFocusText(partySearch, { scheduleEnsure: true });
            } else {
                partySearch.focus();
            }
            renderSelectedParties();
            openFilteredPartyList();
        } else if (step === 4 && areaSqftInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(areaSqftInput, { scheduleEnsure: true });
        } else if (step === 5 && totalAmountInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(totalAmountInput, { scheduleEnsure: true });
        }
    }

    function parseUnsignedInt(raw) {
        var s = (raw || '').trim();
        if (!s) return null;
        if (!/^\d+$/.test(s)) return NaN;
        var n = parseInt(s, 10);
        if (!isFinite(n) || n < 0) return NaN;
        return n;
    }

    function getSelectedArea() {
        var pairs = [
            ['acre', areaAcreInput ? areaAcreInput.value : ''],
            ['kanal', areaKanalInput ? areaKanalInput.value : ''],
            ['marla', areaMarlaInput ? areaMarlaInput.value : ''],
            ['sqft', areaSqftInput ? areaSqftInput.value : '']
        ];
        var filled = pairs
            .map(function (p) { return [p[0], parseUnsignedInt(p[1])]; })
            .filter(function (p) { return p[1] !== null; });

        if (filled.length === 0) return { ok: false, msg: 'Please enter area in one unit (Acre, Kanal, Marla, or Sq ft).', unit: null, area: null };
        if (filled.length > 1) return { ok: false, msg: 'Please enter area in only one unit. Clear the other fields.', unit: null, area: null };
        if (isNaN(filled[0][1])) return { ok: false, msg: 'Area must be an unsigned integer (0 or greater).', unit: null, area: null };
        return { ok: true, unit: filled[0][0], area: filled[0][1], msg: '' };
    }

    function validateStep(s) {
        if (s === 0) {
            var name = (nameInput && (nameInput.value || '').trim()) || '';
            if (!name) {
                showModalErr('Please enter a project name.');
                if (nameInput && window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
                return false;
            }
            return true;
        }
        if (s === 1) {
            if (!fieldTypeSelect || !fieldTypeSelect.value) {
                showModalErr('Please select type (sale or purchase).');
                return false;
            }
            return true;
        }
        if (s === 2) {
            if (!landTypeHidden || !landTypeHidden.value) {
                showModalErr('Please select a land type from the list.');
                if (landTypeSearch && window.daybookModalFocusText) window.daybookModalFocusText(landTypeSearch);
                openFilteredLandTypeList();
                return false;
            }
            var still = landTypeRows.some(function (r) {
                return String(r.id) === landTypeHidden.value && landTypeSearch && r.label === landTypeSearch.value;
            });
            if (!still) {
                showModalErr('Choose a valid land type from the search results.');
                if (landTypeSearch && window.daybookModalFocusText) window.daybookModalFocusText(landTypeSearch);
                openFilteredLandTypeList();
                return false;
            }
            return true;
        }
        if (s === 3) {
            return true; // parties optional
        }
        if (s === 4) {
            var sel = getSelectedArea();
            if (!sel.ok) {
                showModalErr(sel.msg);
                if (areaSqftInput && window.daybookModalFocusText) window.daybookModalFocusText(areaSqftInput);
                return false;
            }
            return true;
        }
        if (s === 5) {
            var raw = totalAmountInput ? (totalAmountInput.value || '').trim() : '';
            if (!raw) {
                showModalErr('Please enter total amount.');
                if (totalAmountInput && window.daybookModalFocusText) window.daybookModalFocusText(totalAmountInput);
                return false;
            }
            var val = parseFloat(raw);
            if (isNaN(val) || val < 0) {
                showModalErr('Total amount must be 0 or greater.');
                if (totalAmountInput && window.daybookModalFocusText) window.daybookModalFocusText(totalAmountInput);
                return false;
            }
            return true;
        }
        return true;
    }

    if (landTypeSearch && landTypeList) {
        landTypeSearch.addEventListener('focus', function () {
            openFilteredLandTypeList();
        });
        landTypeSearch.addEventListener('input', function () {
            if (landTypeHidden) {
                var still = landTypeRows.some(function (r) {
                    return String(r.id) === landTypeHidden.value && r.label === landTypeSearch.value;
                });
                if (!still) landTypeHidden.value = '';
            }
            openFilteredLandTypeList();
        });
        landTypeSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideLandTypeList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !landTypeList || landTypeList.classList.contains('d-none')) return;
        var ltWrap = modalEl.querySelector('.daybook-project-lt-combo');
        if (ltWrap && !ltWrap.contains(e.target)) hideLandTypeList();
    });

    if (partySearch && partyList) {
        partySearch.addEventListener('focus', function () {
            openFilteredPartyList();
        });
        partySearch.addEventListener('input', function () {
            openFilteredPartyList();
        });
        partySearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hidePartyList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !partyList || partyList.classList.contains('d-none')) return;
        var wrap = modalEl.querySelector('.daybook-project-party-combo');
        if (wrap && !wrap.contains(e.target)) hidePartyList();
    });

    function showModalErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    projectFormCreateBtn.addEventListener('click', function () {
        showModalErr('');
        resetProjectModalFields();
        goToStep(0);
        projectFormCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        goToStep(step);
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        showModalErr('');
        resetProjectModalFields();
        clearPrimaryLoading();
        goToStep(0);
    });

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            if (step <= 0) return;
            showModalErr('');
            goToStep(step - 1);
        });
    }

    modalEl.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (e.target && e.target.closest && e.target.closest('a')) return;
        var inStep = e.target && e.target.closest && e.target.closest('.daybook-project-modal-step');
        if (!inStep) return;
        e.preventDefault();
        if (primaryBtn && !primaryBtn.disabled) primaryBtn.click();
    });

    if (primaryBtn) {
        primaryBtn.addEventListener('click', function () {
            if (primaryBtn.classList.contains('is-loading')) return;
            showModalErr('');
            if (step < maxStep) {
                if (!validateStep(step)) return;
                goToStep(step + 1);
                return;
            }
            if (!validateStep(5)) return;
            var name = (nameInput.value || '').trim();
            var sel = getSelectedArea();
            var totalAmount = parseFloat((totalAmountInput.value || '').trim());
            setPrimaryLoading(true);
            fetch('{{ route('projects.quick-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    field_type: fieldTypeSelect.value,
                    land_area: sel.area,
                    land_area_unit: sel.unit,
                    land_type_id: parseInt(landTypeHidden.value, 10),
                    total_amount: totalAmount,
                    party_ids: selectedPartyIds
                })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    setPrimaryLoading(false);
                    if (result.ok && result.data && result.data.id) {
                        var rows = window.__daybookFormProjectRows || [];
                        var nid = String(result.data.id);
                        if (!rows.some(function (r) { return String(r.id) === nid; })) {
                            rows.push({ id: result.data.id, label: result.data.name });
                        }
                        projectFormHidden.value = nid;
                        projectFormSearch.value = result.data.name;
                        resetProjectModalFields();
                        showModalErr('');
                        goToStep(0);
                        modal.hide();
                    } else {
                        var msg = 'Could not create project.';
                        if (result.data && result.data.errors) {
                            var keys = Object.keys(result.data.errors);
                            if (keys.length && result.data.errors[keys[0]] && result.data.errors[keys[0]][0]) {
                                msg = result.data.errors[keys[0]][0];
                            }
                        } else if (result.data && result.data.message) {
                            msg = result.data.message;
                        }
                        showModalErr(msg);
                    }
                })
                .catch(function () {
                    setPrimaryLoading(false);
                    showModalErr('Something went wrong. Try again.');
                });
        });
    }
})();

(function () {
    var partyFormHidden = document.getElementById('daybook_form_party_id');
    var partyFormSearch = document.getElementById('daybook_form_party_search');
    var partyFormCreateBtn = document.getElementById('daybook_form_party_create');
    var modalEl = document.getElementById('daybookCreatePartyModal');
    var nameInput = document.getElementById('daybook_modal_party_name');
    var subHidden = document.getElementById('daybook_modal_party_sub_category');
    var subSearch = document.getElementById('daybook_modal_party_sub_search');
    var subList = document.getElementById('daybook_party_sc_listbox');
    var subJsonEl = document.getElementById('daybook-party-sub-json');
    var saveBtn = document.getElementById('daybook_modal_party_submit');
    var errEl = document.getElementById('daybook_modal_party_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!partyFormHidden || !partyFormSearch || !partyFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    var partySubRows = [];
    if (subJsonEl) {
        try {
            partySubRows = JSON.parse(subJsonEl.textContent) || [];
        } catch (e) {
            partySubRows = [];
        }
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });

    function showPartyErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function clearSubCategoryPicker() {
        if (subHidden) subHidden.value = '';
        if (subSearch) {
            subSearch.value = '';
            subSearch.setAttribute('aria-expanded', 'false');
        }
        hideSubList();
    }

    function hideSubList() {
        if (!subList) return;
        subList.classList.add('d-none');
        subList.setAttribute('hidden', '');
        if (subSearch) subSearch.setAttribute('aria-expanded', 'false');
    }

    function showSubList() {
        if (!subList) return;
        subList.classList.remove('d-none');
        subList.removeAttribute('hidden');
        if (subSearch) subSearch.setAttribute('aria-expanded', 'true');
    }

    function norm(s) {
        return (s || '').toLowerCase();
    }

    function filterSubRows(q) {
        var nq = norm(q);
        if (!nq) return partySubRows.slice();
        return partySubRows.filter(function (row) {
            return norm(row.label).indexOf(nq) !== -1;
        });
    }

    function renderSubList(rows) {
        if (!subList) return;
        subList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-party-sc-empty';
            li0.textContent = 'No sub categories match.';
            subList.appendChild(li0);
            showSubList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (subHidden) subHidden.value = String(row.id);
                if (subSearch) subSearch.value = row.label;
                hideSubList();
            });
            li.appendChild(btn);
            subList.appendChild(li);
        });
        showSubList();
    }

    function openFilteredList() {
        renderSubList(filterSubRows(subSearch ? subSearch.value : ''));
    }

    if (subSearch && subList) {
        subSearch.addEventListener('focus', function () {
            openFilteredList();
        });
        subSearch.addEventListener('input', function () {
            if (subHidden) {
                var still = partySubRows.some(function (r) {
                    return String(r.id) === subHidden.value && r.label === subSearch.value;
                });
                if (!still) subHidden.value = '';
            }
            openFilteredList();
        });
        subSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideSubList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !subList || subList.classList.contains('d-none')) return;
        var wrap = modalEl.querySelector('.daybook-party-sc-combo');
        if (wrap && !wrap.contains(e.target)) hideSubList();
    });

    partyFormCreateBtn.addEventListener('click', function () {
        showPartyErr('');
        if (nameInput) nameInput.value = '';
        clearSubCategoryPicker();
        partyFormCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (nameInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        showPartyErr('');
        if (saveBtn) saveBtn.disabled = false;
        clearSubCategoryPicker();
    });

    if (nameInput) {
        nameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (saveBtn && !saveBtn.disabled) saveBtn.click();
            }
        });
    }

    if (saveBtn && nameInput && subHidden && subSearch) {
        saveBtn.addEventListener('click', function () {
            showPartyErr('');
            var name = (nameInput.value || '').trim();
            var subId = subHidden.value;
            if (!name) {
                showPartyErr('Please enter a party name.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
                return;
            }
            if (!subId) {
                showPartyErr('Please select a sub category.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(subSearch);
                openFilteredList();
                return;
            }
            saveBtn.disabled = true;
            fetch('{{ route('parties.quick-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: name, sub_category_id: parseInt(subId, 10) })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    saveBtn.disabled = false;
                    if (result.ok && result.data && result.data.id) {
                        var rows = window.__daybookFormPartyRows || [];
                        var nid = String(result.data.id);
                        if (!rows.some(function (r) { return String(r.id) === nid; })) {
                            rows.push({ id: result.data.id, label: result.data.name });
                        }
                        partyFormHidden.value = nid;
                        partyFormSearch.value = result.data.name;
                        if (typeof window.__daybookProjectModalPartyRowsPush === 'function') {
                            window.__daybookProjectModalPartyRowsPush(result.data.id, result.data.name);
                        }
                        nameInput.value = '';
                        clearSubCategoryPicker();
                        showPartyErr('');
                        modal.hide();
                    } else {
                        var msg = 'Could not create party.';
                        if (result.data && result.data.errors) {
                            if (result.data.errors.name) msg = result.data.errors.name[0];
                            else if (result.data.errors.sub_category_id) msg = result.data.errors.sub_category_id[0];
                        } else if (result.data && result.data.message) {
                            msg = result.data.message;
                        }
                        showPartyErr(msg);
                    }
                })
                .catch(function () {
                    saveBtn.disabled = false;
                    showPartyErr('Something went wrong. Try again.');
                });
        });
    }
})();

(function () {
    var input = document.getElementById('daybook-filter-date');
    if (!input || typeof flatpickr === 'undefined') return;
    var form = input.closest('form');
    flatpickr(input, {
        dateFormat: 'Y-m-d',
        defaultDate: input.value || null,
        allowInput: false,
        disableMobile: true,
        clickOpens: true,
        onChange: function () {
            if (form) form.submit();
        }
    });
})();

(function () {
    var input = document.getElementById('entry_date_input');
    if (!input || typeof flatpickr === 'undefined') return;
    flatpickr(input, {
        dateFormat: 'Y-m-d',
        defaultDate: input.value || null,
        allowInput: false,
        disableMobile: true,
        clickOpens: true
    });
})();

(function () {
    var input = document.getElementById('entry_amount');
    var wordsEl = document.getElementById('entry_amount_words');
    var form = document.getElementById('daybook-add-form');
    if (!input) return;

    /** Compact scale: cr (crore), lac, k (thousand); two decimals; no "rupees" suffix. */
    function scale2(x) {
        return (Math.round(x * 100) / 100).toFixed(2);
    }

    function compactMainLabel(intPart) {
        if (intPart < 0) return '';
        if (intPart >= 10000000) {
            return scale2(intPart / 10000000) + ' cr';
        }
        if (intPart >= 100000) {
            return scale2(intPart / 100000) + ' lac';
        }
        if (intPart >= 1000) {
            return scale2(intPart / 1000) + ' k';
        }
        return String(intPart);
    }

    function paiseLabel(p) {
        if (p <= 0) return '';
        return String(p) + (p === 1 ? ' paisa' : ' paise');
    }

    function sanitizeAmountString(raw) {
        var s = String(raw || '').replace(/,/g, '').replace(/[^\d.]/g, '');
        if (!s) return '';
        var firstDot = s.indexOf('.');
        if (firstDot === -1) {
            s = s.replace(/^0+(\d)/, '$1');
            return s;
        }
        var intp = s.slice(0, firstDot).replace(/\./g, '');
        intp = intp.replace(/^0+(\d)/, '$1');
        if (intp === '') intp = '0';
        var frac = s.slice(firstDot + 1).replace(/\./g, '').replace(/\D/g, '').slice(0, 2);
        if (frac.length === 0) return intp + '.';
        return intp + '.' + frac;
    }

    /** Indian-style grouping: last 3 digits, then groups of 2 (e.g. 12,34,567). */
    function addIndianCommas(intDigits) {
        var s = String(intDigits || '').replace(/\D/g, '');
        if (s === '') s = '0';
        s = s.replace(/^0+(?=\d)/, '') || '0';
        if (s === '0') return '0';
        if (s.length <= 3) return s;
        var last3 = s.slice(-3);
        var head = s.slice(0, -3);
        while (head.length > 2) {
            last3 = head.slice(-2) + ',' + last3;
            head = head.slice(0, -2);
        }
        if (head.length) {
            last3 = head + ',' + last3;
        }
        return last3;
    }

    /** Pretty-print amount with commas (integer part only); `sanitized` must be from sanitizeAmountString. */
    function formatIndianDisplay(sanitized) {
        if (!sanitized) return '';
        var dot = sanitized.indexOf('.');
        var intRaw = dot === -1 ? sanitized : sanitized.slice(0, dot);
        var fracRaw = dot === -1 ? '' : sanitized.slice(dot + 1);
        intRaw = intRaw.replace(/\D/g, '');
        if (intRaw === '') intRaw = '0';
        var frac = fracRaw.replace(/\D/g, '').slice(0, 2);
        var out = addIndianCommas(intRaw);
        if (dot !== -1 && frac.length > 0) return out + '.' + frac;
        if (dot !== -1 && (fracRaw.length === 0 || sanitized.endsWith('.'))) return out + '.';
        return out;
    }

    function parseAmount(s) {
        var t = sanitizeAmountString(s);
        if (!t || t === '.') return null;
        if (t.endsWith('.')) t = t.slice(0, -1);
        var v = parseFloat(t);
        if (!isFinite(v) || v < 0) return null;
        return v;
    }

    function updateWords() {
        if (!wordsEl) return;
        var v = parseAmount(input.value);
        if (v === null || input.value.trim() === '') {
            wordsEl.textContent = '';
            return;
        }
        var intPart = Math.floor(v + 1e-9);
        var dec = Math.round((v - intPart) * 100);
        if (dec >= 100) {
            intPart += 1;
            dec -= 100;
        }
        var bits = [];
        if (intPart > 0 || dec === 0) {
            bits.push(compactMainLabel(intPart));
        }
        if (dec > 0) {
            bits.push(paiseLabel(dec));
        }
        wordsEl.textContent = bits.join(', ');
    }

    input.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        var k = e.key;
        if (k === 'Backspace' || k === 'Delete' || k === 'Tab' || k === 'Escape' || k === 'Enter' || k === 'ArrowLeft' || k === 'ArrowRight' || k === 'Home' || k === 'End') return;
        if (k === '.' || k === ',') {
            if (k === ',') e.preventDefault();
            if (input.value.indexOf('.') !== -1) e.preventDefault();
            return;
        }
        if (/\d/.test(k)) return;
        e.preventDefault();
    });

    input.addEventListener('input', function () {
        var cur = input.value;
        var next = sanitizeAmountString(cur);
        var display = formatIndianDisplay(next);
        if (display !== cur) {
            input.value = display;
            try {
                input.setSelectionRange(display.length, display.length);
            } catch (ignore) {}
        }
        updateWords();
    });

    input.addEventListener('paste', function (e) {
        e.preventDefault();
        var paste = (e.clipboardData || window.clipboardData).getData('text') || '';
        var next = sanitizeAmountString(paste);
        input.value = formatIndianDisplay(next);
        try {
            input.setSelectionRange(input.value.length, input.value.length);
        } catch (ignore) {}
        updateWords();
    });

    input.addEventListener('blur', function () {
        var t = sanitizeAmountString(input.value);
        if (t.endsWith('.')) t = t.slice(0, -1);
        input.value = formatIndianDisplay(t);
        updateWords();
    });

    if (form) {
        form.addEventListener('submit', function () {
            var t = sanitizeAmountString(input.value);
            if (t.endsWith('.')) t = t.slice(0, -1);
            input.value = t;
        });
    }

    (function initAmountDisplay() {
        var t = sanitizeAmountString(input.value);
        if (t) input.value = formatIndianDisplay(t);
    })();

    updateWords();
})();
</script>
@endpush
