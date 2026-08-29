@extends('layouts.app')

@section('title', 'File Sale — '.$project->name)

@push('head')
<style>
    .file-sale-strip__files {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        min-width: 5.5rem;
        padding: 0.15rem 0.25rem 0.15rem 0;
    }
    .file-sale-strip__count-btn {
        border: 1px solid rgba(251, 146, 60, 0.4);
        background: linear-gradient(180deg, rgba(251, 146, 60, 0.22) 0%, rgba(15, 23, 42, 0.55) 100%);
        border-radius: 14px;
        padding: 0.55rem 0.85rem;
        cursor: pointer;
        line-height: 1.2;
        min-width: 4.5rem;
        transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .file-sale-strip__count-btn:hover,
    .file-sale-strip__count-btn:focus {
        background: rgba(251, 146, 60, 0.28);
        border-color: rgba(251, 146, 60, 0.7);
        box-shadow: 0 0 0 3px rgba(251, 146, 60, 0.18);
        outline: none;
    }
    .file-sale-strip__count-num {
        display: block;
        font-size: 1.45rem;
        font-weight: 800;
        color: #fdba74;
        line-height: 1.1;
        letter-spacing: -0.02em;
    }
    .file-sale-strip__count-text {
        display: block;
        font-size: 0.66rem;
        font-weight: 800;
        color: #fed7aa;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-top: 0.15rem;
    }
    .file-sale-strip__files-hint {
        font-size: 0.66rem;
        font-weight: 650;
        color: #94a3b8;
    }
    .file-sale-popover {
        max-width: min(320px, calc(100vw - 2rem));
    }
    .file-sale-popover .popover-header {
        font-size: 0.85rem;
        font-weight: 700;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .file-sale-popover .popover-body {
        font-size: 0.875rem;
        color: #334155;
        padding: 0.65rem 0.85rem;
    }
    .file-sale-popover__list {
        margin: 0;
        padding-left: 1.1rem;
    }
    .file-sale-popover__list li + li {
        margin-top: 0.3rem;
    }
    .file-sale-leftover-table__left {
        color: #047857;
    }
    .leftover-land-balance__list {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }
    .leftover-land-balance__item {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 16px;
        overflow: hidden;
        background: #fff;
    }
    .leftover-land-balance__item--summary {
        border: 0;
        border-radius: 0;
        background: transparent;
        box-shadow: none;
    }
    .leftover-land-balance__item--nested {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .leftover-land-balance__summary {
        width: 100%;
        border: 0;
        background: transparent;
        text-align: left;
        padding: 1.15rem 1.2rem;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .leftover-land-balance__summary--nested {
        align-items: center;
        flex-wrap: wrap;
        gap: 0.55rem 0.85rem;
        padding: 0.8rem 0.95rem;
    }
    .leftover-land-balance__summary:hover,
    .leftover-land-balance__summary:focus {
        background: rgba(255, 247, 237, 0.55);
        outline: none;
    }
    .leftover-land-balance__summary[aria-expanded="true"] {
        background: linear-gradient(180deg, #fff7ed 0%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .leftover-land-balance__summary-block {
        flex: 1 1 auto;
        min-width: 0;
    }
    .leftover-land-balance__summary-top {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1.25rem;
        margin-bottom: 0.85rem;
    }
    .leftover-land-balance__summary-title-wrap {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        gap: 0.2rem;
        min-width: 0;
        flex: 1 1 12rem;
    }
    .leftover-land-balance__eyebrow {
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #c2410c;
    }
    .leftover-land-balance__summary-title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
    }
    .leftover-land-balance__summary-title {
        display: inline-block;
        font-size: 1.2rem;
        font-weight: 800;
        letter-spacing: -0.025em;
        text-transform: none;
        color: #0f172a;
        margin-bottom: 0;
        line-height: 1.25;
    }
    .leftover-land-balance__summary-sub {
        font-size: 0.82rem;
        font-weight: 650;
        color: #64748b;
    }
    .leftover-land-balance__status {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.55rem;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .leftover-land-balance__status.is-open {
        background: #dcfce7;
        color: #166534;
    }
    .leftover-land-balance__status.is-done {
        background: #e2e8f0;
        color: #475569;
    }
    .leftover-land-balance__hero {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.1rem;
        padding: 0.55rem 0.8rem;
        border-radius: 14px;
        background: linear-gradient(180deg, #ecfdf5 0%, #fff 100%);
        border: 1px solid rgba(5, 150, 105, 0.22);
        min-width: 7.5rem;
        text-align: right;
    }
    .leftover-land-balance__hero-label {
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #059669;
    }
    .leftover-land-balance__hero-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: #047857;
        letter-spacing: -0.02em;
        line-height: 1.25;
    }
    .leftover-land-balance__summary-list {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.55rem 0.75rem;
    }
    @media (min-width: 768px) {
        .leftover-land-balance__summary-list {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
    }
    .leftover-land-balance__summary-list li {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
        padding: 0.6rem 0.7rem;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.9);
        border: 1px solid rgba(15, 23, 42, 0.07);
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .leftover-land-balance__metric-label {
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .leftover-land-balance__summary-list strong {
        color: #0f172a;
        font-weight: 750;
        font-size: 0.92rem;
    }
    .leftover-land-balance__metric-sold {
        color: #c2410c !important;
    }
    .leftover-land-balance__metric-note {
        font-size: 0.72rem;
        color: #64748b;
        line-height: 1.35;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .leftover-land-balance__chevron {
        width: 0.55rem;
        height: 0.55rem;
        border-right: 2px solid #94a3b8;
        border-bottom: 2px solid #94a3b8;
        transform: rotate(-45deg);
        transition: transform 0.15s ease;
        flex: 0 0 auto;
        margin-top: 0.55rem;
    }
    .leftover-land-balance__summary[aria-expanded="true"] .leftover-land-balance__chevron {
        transform: rotate(45deg);
        margin-top: 0.65rem;
        border-color: #c2410c;
    }
    .leftover-land-balance__land {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.9rem;
        line-height: 1.3;
    }
    .leftover-land-balance__hint {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        margin-top: 0.8rem;
        font-size: 0.74rem;
        font-weight: 650;
        color: #94a3b8;
    }
    .leftover-land-balance__hint i {
        font-size: 0.7rem;
        transition: transform 0.15s ease;
    }
    .leftover-land-balance__summary[aria-expanded="true"] .leftover-land-balance__hint i {
        transform: rotate(180deg);
    }
    .leftover-land-balance__meta {
        font-size: 0.84rem;
        color: #334155;
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.2rem 0.35rem;
    }
    .leftover-land-balance__meta-sep {
        color: #cbd5e1;
    }
    .leftover-land-balance__meta-left {
        color: #047857;
        font-weight: 700;
    }
    .leftover-land-balance__plots {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
    }
    .leftover-land-balance__plots--summary {
        margin-top: 0.75rem;
    }
    .leftover-land-balance__plot-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.28rem 0.6rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
        color: #334155;
        font-size: 0.74rem;
        font-weight: 650;
        white-space: nowrap;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    .leftover-land-balance__plot-code {
        color: #9a3412;
        font-weight: 800;
    }
    .leftover-land-balance__plot-left {
        color: #047857;
    }
    .leftover-land-balance__detail-inner {
        padding: 1.05rem 1.15rem 1.2rem;
        background: #f8fafc;
    }
    .leftover-land-balance__detail-inner--nested {
        background: #fff;
        padding-top: 0.85rem;
    }
    .leftover-land-balance__stats {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.7rem;
        margin-bottom: 1.15rem;
    }
    @media (min-width: 768px) {
        .leftover-land-balance__stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .leftover-land-balance__stats--nested {
            grid-template-columns: repeat(5, minmax(0, 1fr));
        }
    }
    .leftover-land-balance__stat {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 14px;
        padding: 0.85rem 0.95rem;
        background: #fff;
        min-height: 100%;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
    }
    .leftover-land-balance__stat.is-sold {
        background: linear-gradient(180deg, #fff7ed 0%, #fff 80%);
        border-color: rgba(234, 88, 12, 0.2);
    }
    .leftover-land-balance__stat.is-left {
        background: linear-gradient(180deg, #ecfdf5 0%, #fff 80%);
        border-color: rgba(5, 150, 105, 0.2);
    }
    .leftover-land-balance__stat.is-left .leftover-land-balance__stat-value {
        color: #047857;
    }
    .leftover-land-balance__stat.is-sold .leftover-land-balance__stat-value {
        color: #c2410c;
    }
    .leftover-land-balance__stat.is-total {
        background: linear-gradient(180deg, #f8fafc 0%, #fff 80%);
    }
    .leftover-land-balance__stat-label {
        display: block;
        font-size: 0.68rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #94a3b8;
        margin-bottom: 0.3rem;
    }
    .leftover-land-balance__stat-value {
        display: block;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.35;
    }
    .leftover-land-balance__section {
        margin-bottom: 1.15rem;
    }
    .leftover-land-balance__section:last-child {
        margin-bottom: 0;
    }
    .leftover-land-balance__section-title {
        margin: 0 0 0.55rem;
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #64748b;
    }
    .leftover-land-balance__table {
        --llb-border: rgba(15, 23, 42, 0.08);
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        border: 1px solid var(--llb-border);
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        margin: 0;
    }
    .leftover-land-balance__table > :not(caption) > * > * {
        border-bottom-width: 0;
    }
    .leftover-land-balance__table thead th {
        padding: 0.7rem 0.85rem;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        border-bottom: 2px solid transparent;
        vertical-align: middle;
    }
    .leftover-land-balance__table tbody td {
        padding: 0.7rem 0.85rem;
        font-size: 0.88rem;
        font-weight: 650;
        vertical-align: middle;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
    }
    .leftover-land-balance__table tbody tr:hover td {
        filter: brightness(0.985);
    }
    .leftover-land-balance__table .llb-col-plot {
        background: #f8fafc;
        border-right: 1px solid rgba(15, 23, 42, 0.08);
        color: #0f172a;
        min-width: 11rem;
    }
    .leftover-land-balance__table thead .llb-col-plot {
        color: #475569;
        border-bottom-color: #94a3b8;
    }
    .leftover-land-balance__table .llb-col-available {
        background: #eff6ff;
        border-right: 1px solid rgba(37, 99, 235, 0.18);
        color: #1e3a8a;
    }
    .leftover-land-balance__table thead .llb-col-available {
        color: #1d4ed8;
        border-bottom-color: #3b82f6;
    }
    .leftover-land-balance__table .llb-col-sold {
        background: #fff7ed;
        border-right: 1px solid rgba(234, 88, 12, 0.2);
        color: #9a3412;
    }
    .leftover-land-balance__table thead .llb-col-sold {
        color: #c2410c;
        border-bottom-color: #f97316;
    }
    .leftover-land-balance__table .llb-col-left {
        background: #ecfdf5;
        color: #047857;
        font-weight: 800;
    }
    .leftover-land-balance__table thead .llb-col-left {
        color: #059669;
        border-bottom-color: #10b981;
    }
    .leftover-land-balance__table .llb-col-left.is-depleted {
        color: #94a3b8;
        font-weight: 650;
    }
    .llb-plot-inline {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.4rem 0.5rem;
        white-space: nowrap;
    }
    .llb-plot-code {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.1rem;
        padding: 0.12rem 0.45rem;
        border-radius: 8px;
        font-size: 0.82rem;
        font-weight: 800;
        letter-spacing: 0.02em;
        line-height: 1.2;
        flex: 0 0 auto;
    }
    .llb-plot-code.is-residential {
        background: #dbeafe;
        color: #1d4ed8;
        border: 1px solid rgba(37, 99, 235, 0.25);
    }
    .llb-plot-code.is-commercial {
        background: #ffedd5;
        color: #c2410c;
        border: 1px solid rgba(234, 88, 12, 0.28);
    }
    .llb-plot-sub {
        display: inline;
        font-size: 0.78rem;
        font-weight: 650;
        color: #64748b;
        line-height: 1.3;
        white-space: nowrap;
    }
    .leftover-land-balance__left-cell {
        color: #047857;
    }
    .saved-sale-files-card__header {
        background: linear-gradient(135deg, #fff7ed 0%, #fff 55%, #f8fafc 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.06);
    }
    .saved-sale-files-card__count {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.55rem;
        height: 1.55rem;
        padding: 0 0.4rem;
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid rgba(194, 65, 12, 0.2);
        color: #c2410c;
        font-size: 0.75rem;
        font-weight: 800;
    }
    .saved-sale-files-card__body {
        background: #f8fafc;
    }
    .saved-sale-files-empty {
        text-align: center;
        padding: 1.75rem 1rem;
        border: 1px dashed rgba(15, 23, 42, 0.14);
        border-radius: 16px;
        background: #fff;
    }
    .saved-sale-files-empty__icon {
        width: 2.75rem;
        height: 2.75rem;
        margin: 0 auto 0.75rem;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 1.25rem;
    }
    .saved-sale-files-empty__title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.98rem;
    }
    .saved-sale-files-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .saved-sale-panel {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 18px;
        padding: 0;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.045);
        transition: box-shadow 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
    }
    .saved-sale-panel:hover {
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.08);
    }
    .saved-sale-panel.is-open {
        border-color: rgba(22, 163, 74, 0.28);
    }
    .saved-sale-panel.is-done {
        border-color: rgba(100, 116, 139, 0.28);
        opacity: 0.97;
    }
    .saved-sale-panel .leftover-land-balance--sale-file > .leftover-land-balance__item--summary {
        background: linear-gradient(145deg, #fffaf5 0%, #ffffff 42%, #f8fafc 100%);
    }
    .ssf-toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-bottom: 1.1rem;
        padding: 0;
        border: 0;
        background: transparent;
    }
    .ssf-toolbar__exemption {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        padding: 0.95rem 1rem;
        border-radius: 16px;
        border: 1px solid rgba(194, 65, 12, 0.16);
        background: linear-gradient(180deg, #fff7ed 0%, #fff 78%);
    }
    .ssf-toolbar__exemption-copy {
        min-width: 0;
        flex: 1 1 14rem;
    }
    .ssf-toolbar__kicker {
        display: block;
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #c2410c;
        margin-bottom: 0.25rem;
    }
    .ssf-toolbar__exemption-value {
        display: block;
        width: 100%;
        margin: 0;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
        cursor: pointer;
        font: inherit;
        font-size: 0.95rem;
        font-weight: 750;
        color: #0f172a;
        line-height: 1.4;
    }
    .ssf-toolbar__exemption-value:hover,
    .ssf-toolbar__exemption-value:focus-visible {
        color: #9a3412;
        text-decoration: underline;
    }
    .ssf-toolbar__exemption-meta {
        display: inline-flex;
        align-items: center;
        color: #64748b;
        font-size: 0.78rem;
        font-weight: 650;
        white-space: nowrap;
    }
    .ssf-toolbar__exemption-meta-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem 0.65rem;
        margin-top: 0.35rem;
    }
    .ssf-toolbar__locked {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }
    .ssf-toolbar__plots {
        display: inline-flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.3rem;
    }
    .ssf-toolbar__plot-chip {
        display: inline-flex;
        align-items: center;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        background: #fff;
        border: 1px solid rgba(194, 65, 12, 0.22);
        color: #9a3412;
        font-size: 0.74rem;
        font-weight: 750;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .ssf-toolbar__btns {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .ssf-toolbar__btns--primary {
        flex: 0 0 auto;
    }
    .ssf-toolbar__btns .btn i {
        margin-right: 0.15rem;
    }
    .ssf-toolbar__groups {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.55rem;
    }
    @media (min-width: 768px) {
        .ssf-toolbar__groups {
            grid-template-columns: 1.25fr 0.9fr;
        }
    }
    .ssf-toolbar__group {
        padding: 0.7rem 0.8rem;
        border-radius: 14px;
        background: #fff;
        border: 1px solid rgba(15, 23, 42, 0.08);
    }
    .ssf-toolbar__group-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin-bottom: 0.45rem;
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .file-sale-summary-card {
        border: 1px solid rgba(148, 163, 184, 0.18) !important;
        background: #0f172a !important;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.28);
    }
    .file-sale-summary-card__header {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 70%) !important;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16) !important;
        color: #e2e8f0;
    }
    .file-sale-summary-card__title {
        color: #f8fafc !important;
        font-weight: 800;
        letter-spacing: -0.01em;
    }
    .file-sale-summary-card__subtitle {
        font-size: 0.82rem;
        color: #94a3b8;
    }
    .file-sale-summary-card__pdf {
        border-color: rgba(226, 232, 240, 0.35) !important;
        color: #e2e8f0 !important;
    }
    .file-sale-summary-card__pdf:hover,
    .file-sale-summary-card__pdf:focus {
        background: rgba(248, 250, 252, 0.1) !important;
        color: #fff !important;
        border-color: rgba(248, 250, 252, 0.55) !important;
    }
    .file-sale-summary-card__body {
        background: #0b1220 !important;
        color: #e2e8f0;
    }
    .file-sale-strip {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
        border: 0;
        border-radius: 0;
        overflow: visible;
        background: transparent;
    }
    .file-sale-strip__main {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.85rem 1rem;
        padding: 1rem;
        border: 1px solid rgba(148, 163, 184, 0.16);
        border-radius: 16px;
        background: linear-gradient(160deg, #1e293b 0%, #111827 100%);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }
    .file-sale-strip__plots {
        flex: 1 1 14rem;
        min-width: 0;
    }
    .file-sale-strip__section-label {
        margin: 0 0 0.5rem;
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .file-sale-strip__columns {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
        min-width: 0;
    }
    .file-sale-strip__col {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.15rem;
        min-width: 4.6rem;
        padding: 0.55rem 0.6rem;
        border-radius: 12px;
        border: 1px solid rgba(148, 163, 184, 0.18);
        background: rgba(15, 23, 42, 0.65);
        text-align: center;
    }
    .file-sale-strip__col.is-residential {
        background: linear-gradient(180deg, rgba(37, 99, 235, 0.28) 0%, rgba(15, 23, 42, 0.7) 90%);
        border-color: rgba(96, 165, 250, 0.35);
    }
    .file-sale-strip__col.is-commercial {
        background: linear-gradient(180deg, rgba(234, 88, 12, 0.28) 0%, rgba(15, 23, 42, 0.7) 90%);
        border-color: rgba(251, 146, 60, 0.4);
    }
    .file-sale-strip__col-code {
        font-weight: 800;
        font-size: 0.88rem;
        letter-spacing: 0.02em;
        color: #f8fafc;
        line-height: 1.2;
    }
    .file-sale-strip__col.is-residential .file-sale-strip__col-code {
        color: #93c5fd;
    }
    .file-sale-strip__col.is-commercial .file-sale-strip__col-code {
        color: #fdba74;
    }
    .file-sale-strip__col-type {
        font-weight: 650;
        font-size: 0.72rem;
        color: #94a3b8;
        line-height: 1.2;
        white-space: nowrap;
    }
    .file-sale-strip__col-count {
        margin-top: 0.15rem;
        font-weight: 800;
        font-size: 0.95rem;
        color: #f1f5f9;
        line-height: 1.2;
    }
    .file-sale-strip__metrics {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.65rem;
    }
    @media (min-width: 768px) {
        .file-sale-strip__metrics {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    .file-sale-strip__metric {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.85rem 0.95rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.16);
        background: #1e293b;
        min-width: 0;
    }
    .file-sale-strip__metric.is-total {
        background: linear-gradient(180deg, #1e293b 0%, #111827 100%);
    }
    .file-sale-strip__metric.is-sold {
        background: linear-gradient(180deg, rgba(154, 52, 18, 0.35) 0%, #1e293b 85%);
        border-color: rgba(251, 146, 60, 0.35);
    }
    .file-sale-strip__metric.is-left {
        background: linear-gradient(180deg, rgba(4, 120, 87, 0.35) 0%, #1e293b 85%);
        border-color: rgba(52, 211, 153, 0.3);
    }
    .file-sale-strip__metric-label {
        font-size: 0.66rem;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #94a3b8;
    }
    .file-sale-strip__metric.is-sold .file-sale-strip__metric-label {
        color: #fdba74;
    }
    .file-sale-strip__metric.is-left .file-sale-strip__metric-label {
        color: #6ee7b7;
    }
    .file-sale-strip__metric-value {
        font-size: 0.95rem;
        font-weight: 800;
        color: #f8fafc;
        line-height: 1.35;
        word-break: break-word;
    }
    .file-sale-strip__metric.is-sold .file-sale-strip__metric-value {
        color: #fdba74;
    }
    .file-sale-strip__metric.is-left .file-sale-strip__metric-value {
        color: #6ee7b7;
    }
    .file-sale-strip__amounts {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.65rem;
        padding: 0;
    }
    @media (min-width: 768px) {
        .file-sale-strip__amounts {
            grid-template-columns: 1fr 1fr;
        }
    }
    .file-sale-strip__amount-card {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        padding: 0.95rem 1.05rem;
        border-radius: 14px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: #0f172a;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
    }
    .file-sale-strip__amount-card.is-sale {
        background: linear-gradient(145deg, #7c2d12 0%, #9a3412 42%, #1e293b 100%);
        border-color: rgba(253, 186, 116, 0.55);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08), 0 8px 20px rgba(154, 52, 18, 0.25);
    }
    .file-sale-strip__amount-label {
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #cbd5e1;
    }
    .file-sale-strip__amount-card.is-sale .file-sale-strip__amount-label {
        color: #ffedd5;
    }
    .file-sale-strip__amount-row {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        justify-content: space-between;
        gap: 0.35rem 0.75rem;
    }
    .file-sale-strip__amount-value {
        font-size: 1.12rem;
        font-weight: 800;
        color: #ffffff !important;
        letter-spacing: -0.01em;
        line-height: 1.3;
    }
    .file-sale-strip__amount-short {
        display: inline-flex;
        align-items: center;
        padding: 0.22rem 0.6rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.55);
        border: 1px solid rgba(226, 232, 240, 0.28);
        color: #e2e8f0;
        font-size: 0.84rem;
        font-weight: 800;
        letter-spacing: 0.01em;
        white-space: nowrap;
    }
    .file-sale-strip__amount-card.is-sale .file-sale-strip__amount-value {
        color: #fff7ed !important;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35);
    }
    .file-sale-strip__amount-card.is-sale .file-sale-strip__amount-short {
        background: rgba(15, 23, 42, 0.45);
        border-color: rgba(255, 237, 213, 0.45);
        color: #ffedd5;
    }

    .collective-sheet {
        border: 2px solid #334155;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .collective-sheet__head {
        display: grid;
        grid-template-columns: minmax(5.5rem, auto) 1fr minmax(9rem, 1.2fr);
        gap: 0;
        border-bottom: 2px solid #334155;
        background: #f8fafc;
    }
    .collective-sheet__head-cell {
        padding: 0.75rem 0.9rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 0.15rem;
        min-width: 0;
    }
    .collective-sheet__head-cell + .collective-sheet__head-cell {
        border-left: 2px solid #334155;
    }
    .collective-sheet__head-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
    }
    .collective-sheet__head-value {
        font-size: 1rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.25;
        word-break: break-word;
    }
    .collective-sheet__table {
        width: 100%;
        margin: 0;
        border-collapse: collapse;
    }
    .collective-sheet__table th,
    .collective-sheet__table td {
        border: 1px solid #cbd5e1;
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }
    .collective-sheet__table thead th {
        background: #f1f5f9;
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #334155;
        text-align: center;
    }
    .collective-sheet__files-cell {
        font-weight: 700;
        color: #0f172a;
        white-space: nowrap;
    }
    .collective-sheet__files-cell .collective-sheet__eq {
        color: #64748b;
        font-weight: 600;
        margin: 0 0.25rem;
    }
    .collective-sheet__sold-cell {
        text-align: center;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
    }
    .collective-sheet__sold-cell.is-done {
        color: #15803d;
    }
    .collective-sheet__bal-cell {
        text-align: center;
        font-weight: 700;
        font-variant-numeric: tabular-nums;
        color: #9a3412;
    }
    .collective-sheet__row--residential td:first-child {
        box-shadow: inset 3px 0 0 #2563eb;
    }
    .collective-sheet__row--commercial td:first-child {
        box-shadow: inset 3px 0 0 #c026d3;
    }
    .collective-sheet__next {
        border-top: 2px solid #334155;
        padding: 0.85rem 1rem 1rem;
        background: #fffbeb;
    }
    .collective-sheet__next-title {
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #92400e;
        margin-bottom: 0.45rem;
    }
    .collective-sheet__next-box {
        border: 2px dashed #f59e0b;
        border-radius: 10px;
        min-height: 3.25rem;
        padding: 0.75rem 0.9rem;
        background: #fff;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .collective-sheet__next-land {
        font-size: 1.05rem;
        font-weight: 800;
        color: #0f172a;
    }
    .collective-sheet__next-note {
        font-size: 0.82rem;
        color: #78716c;
    }
    @media (max-width: 767.98px) {
        .collective-sheet__head {
            grid-template-columns: 1fr;
        }
        .collective-sheet__head-cell + .collective-sheet__head-cell {
            border-left: 0;
            border-top: 1px solid #cbd5e1;
        }
    }

    .exemption-modal-content {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    }
    .exemption-modal-header {
        background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 55%, #fff 100%);
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .exemption-pick-hint {
        font-size: 0.88rem;
        color: #64748b;
    }
    .exemption-pick-list {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        max-height: min(58vh, 460px);
        overflow: auto;
        padding-bottom: 0.5rem;
    }
    .exemption-pick-option {
        display: block;
        position: relative;
        cursor: pointer;
        margin: 0;
    }
    .exemption-pick-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .exemption-pick-card {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        padding: 0.85rem 1rem;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }
    .exemption-pick-option:hover .exemption-pick-card {
        border-color: rgba(194, 65, 12, 0.35);
    }
    .exemption-pick-option input:checked + .exemption-pick-card {
        border-color: rgba(194, 65, 12, 0.55);
        box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.15);
    }
    .exemption-pick-card__top {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        align-items: flex-start;
        margin-bottom: 0.35rem;
    }
    .exemption-pick-card__title {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-pick-card__badge {
        font-size: 0.68rem;
        font-weight: 800;
        padding: 0.18rem 0.5rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
    }
    .exemption-pick-card__badge.is-live {
        background: #dcfce7;
        color: #166534;
    }
    .exemption-pick-card__summary {
        font-size: 0.88rem;
        font-weight: 650;
        color: #334155;
        margin-bottom: 0.35rem;
    }
    .exemption-pick-card__meta {
        font-size: 0.76rem;
        color: #64748b;
        margin-bottom: 0.45rem;
    }
    .exemption-pick-breakdown {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .exemption-pick-comp {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 10px;
        background: #f8fafc;
        padding: 0.5rem 0.65rem;
    }
    .exemption-pick-comp__head {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.3rem;
    }
    .exemption-pick-comp__title {
        font-size: 0.8rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-pick-comp__pool {
        font-size: 0.72rem;
        font-weight: 700;
        color: #9a3412;
    }
    .exemption-pick-comp__plots {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }
    .exemption-pick-comp__plots li {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        font-size: 0.74rem;
        color: #475569;
    }
    .exemption-pick-comp__empty,
    .exemption-pick-empty {
        font-size: 0.82rem;
        color: #64748b;
    }
    .exemption-pick-empty {
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
        text-align: center;
    }
    .exemption-view-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.85rem;
        color: #475569;
    }
    .exemption-view-component {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        padding: 0.9rem 1rem;
        margin-bottom: 0.75rem;
        background: #fff;
    }
    .exemption-view-component__head {
        display: flex;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.55rem;
    }
    .exemption-view-component__title {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-view-component__pct {
        font-size: 0.76rem;
        font-weight: 800;
        color: #9a3412;
        background: #ffedd5;
        border-radius: 999px;
        padding: 0.2rem 0.55rem;
    }
    .exemption-view-plots {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.84rem;
    }
    .exemption-view-plots th,
    .exemption-view-plots td {
        padding: 0.4rem 0.35rem;
        border-top: 1px solid #e2e8f0;
        text-align: left;
    }
    .exemption-view-plots th {
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #64748b;
        border-top: 0;
    }
    .exemption-view-plots td:not(:first-child),
    .exemption-view-plots th:not(:first-child) {
        text-align: right;
    }
    .exemption-view-empty {
        padding: 1.1rem;
        text-align: center;
        color: #64748b;
        border: 1px dashed rgba(15, 23, 42, 0.15);
        border-radius: 12px;
        background: #fff;
    }
</style>
@endpush

@section('content')
@php
    $summary = $fileSaleSummary ?? ['totals' => [], 'moved_files' => [], 'files_land_columns' => [], 'leftover_balance' => [], 'collectives' => [], 'separate_files' => [], 'open_collectives' => []];
    $totals = $summary['totals'] ?? [];
    $movedFiles = $summary['moved_files'] ?? [];
    $filesLandColumns = $summary['files_land_columns'] ?? [];
    $collectives = $summary['collectives'] ?? [];
    $separateFiles = $summary['separate_files'] ?? [];
    $openCollectives = $summary['open_collectives'] ?? [];
    $suggestedSaleFileName = 'Collective-'.(count($collectives) + 1);
@endphp

@if(session('success'))
    <div class="alert alert-success small">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger small">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h1 class="mb-1">File Sale</h1>
        <p class="text-muted small mb-0">Project: <strong><x-project-name :project="$project" /></strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('sale.projects.exemption.edit', $project) }}" class="btn btn-outline-theme">Exemption setup</a>
        <a href="{{ route('projects.sale-land', $project) }}" class="btn btn-outline-theme">Sale land</a>
        <a href="{{ route('sale.files.create', $project) }}" class="btn btn-outline-theme">Add project file</a>
        <a href="{{ route('purchase.files.index', ['project' => $project->id]) }}" class="btn btn-outline-theme">Purchase files</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        <a href="{{ route('sale.index') }}" class="btn btn-outline-theme">Sale menu</a>
    </div>
</div>

@if($movedFiles === [])
    <div class="card card-theme mb-4">
        <div class="card-body">
            <p class="text-muted mb-0">
                No sale land files moved to file sale yet. Open
                <a href="{{ route('projects.sale-land', $project) }}">Sale land</a>,
                select files, and click <strong>Move to File Sale</strong>.
            </p>
        </div>
    </div>
@else
    <div class="card card-theme mb-4 file-sale-summary-card">
        @if($filesLandColumns !== [])
            <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap gap-2 file-sale-summary-card__header">
                <div>
                    <h2 class="h6 mb-1 file-sale-summary-card__title">Total land &amp; files</h2>
                    <p class="file-sale-summary-card__subtitle mb-0">Moved sale land overview — plots, balances, and amounts.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($separateFiles !== [])
                        <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#save-sale-file-modal">
                            Save as sale file
                        </button>
                    @endif
                    <a href="{{ route('sale.files.original-formula.pdf', $project) }}" class="btn btn-sm btn-outline-light file-sale-summary-card__pdf">
                        Print PDF
                    </a>
                </div>
            </div>
            <div class="card-body pt-3 file-sale-summary-card__body">
                <div id="file-sale-files-popover-content" class="d-none">
                    <ul class="file-sale-popover__list">
                        @foreach($movedFiles as $file)
                            <li>{{ $file['name'] }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="file-sale-strip">
                    <div class="file-sale-strip__main">
                        <div class="file-sale-strip__files">
                            <button type="button"
                                    class="file-sale-strip__count-btn"
                                    id="file-sale-files-count-btn"
                                    aria-label="{{ count($movedFiles) }} sale land files — click to view names">
                                <span class="file-sale-strip__count-num">{{ count($movedFiles) }}</span>
                                <span class="file-sale-strip__count-text">{{ count($movedFiles) === 1 ? 'File' : 'Files' }}</span>
                            </button>
                            <span class="file-sale-strip__files-hint">Tap to view names</span>
                        </div>

                        <div class="file-sale-strip__plots">
                            <div class="file-sale-strip__section-label">Plot files</div>
                            <div class="file-sale-strip__columns">
                                @foreach($filesLandColumns as $column)
                                    @php
                                        $isCommercial = str_starts_with(strtoupper((string) ($column['column_code'] ?? '')), 'C');
                                    @endphp
                                    <div class="file-sale-strip__col {{ $isCommercial ? 'is-commercial' : 'is-residential' }}">
                                        <span class="file-sale-strip__col-code">{{ $column['column_code'] }}</span>
                                        <span class="file-sale-strip__col-type">{{ $column['short_label'] }}</span>
                                        <span class="file-sale-strip__col-count">{{ $column['file_count'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="file-sale-strip__metrics">
                        <div class="file-sale-strip__metric is-total">
                            <span class="file-sale-strip__metric-label">Total land</span>
                            <span class="file-sale-strip__metric-value">{{ $totals['total_land_area'] ?? '—' }}</span>
                        </div>
                        <div class="file-sale-strip__metric is-sold">
                            <span class="file-sale-strip__metric-label">Sold</span>
                            <span class="file-sale-strip__metric-value">{{ $totals['sold_land_area'] ?? '—' }}</span>
                        </div>
                        <div class="file-sale-strip__metric is-left">
                            <span class="file-sale-strip__metric-label">Leftover</span>
                            <span class="file-sale-strip__metric-value">{{ $totals['remaining_land_area'] ?? '—' }}</span>
                        </div>
                    </div>

                    <div class="file-sale-strip__amounts">
                        <div class="file-sale-strip__amount-card">
                            <span class="file-sale-strip__amount-label">Total amount land</span>
                            <div class="file-sale-strip__amount-row">
                                <strong class="file-sale-strip__amount-value">{{ $totals['grand_total_amount_formatted'] ?? '—' }}</strong>
                                @if(!empty($totals['grand_total_amount_short']))
                                    <span class="file-sale-strip__amount-short">{{ $totals['grand_total_amount_short'] }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="file-sale-strip__amount-card is-sale">
                            <span class="file-sale-strip__amount-label">Sale files amount</span>
                            <div class="file-sale-strip__amount-row">
                                <strong class="file-sale-strip__amount-value">{{ $totals['sale_files_amount_formatted'] ?? '—' }}</strong>
                                @if(!empty($totals['sale_files_amount_short']))
                                    <span class="file-sale-strip__amount-short">{{ $totals['sale_files_amount_short'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted small mb-0">No land area found on the moved sale land files.</p>
            </div>
        @endif
    </div>

    <div class="card card-theme mb-4 saved-sale-files-card">
        <div class="card-header py-3 saved-sale-files-card__header">
            <div class="saved-sale-files-card__heading">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <h2 class="h6 mb-0">Saved sale files</h2>
                    @if($collectives !== [])
                        <span class="saved-sale-files-card__count">{{ count($collectives) }}</span>
                    @endif
                </div>
                <p class="text-muted small mb-0">Tap a sale file to manage leftover land, exemptions, and reports.</p>
            </div>
        </div>
        <div class="card-body saved-sale-files-card__body">
            @if($collectives === [])
                <div class="saved-sale-files-empty">
                    <div class="saved-sale-files-empty__icon" aria-hidden="true"><i class="bi bi-folder2"></i></div>
                    <p class="saved-sale-files-empty__title mb-1">No saved sale files yet</p>
                    <p class="text-muted small mb-0">
                        @if($separateFiles !== [])
                            Select files below and click <strong>Save as sale file</strong> to create one.
                        @else
                            Move files from Sale land first, then save them here.
                        @endif
                    </p>
                </div>
            @else
                <div class="saved-sale-files-list">
                    @foreach($collectives as $collective)
                        @include('sales.partials.collective-ledger', [
                            'project' => $project,
                            'collective' => $collective,
                            'exemptionPickOptions' => $exemptionPickOptions ?? [],
                            'exemptionOptions' => $exemptionOptions ?? [],
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($separateFiles !== [])
        <div class="card card-theme mb-4">
            <div class="card-header py-3">
                <h2 class="h6 mb-0">Separate files (not yet saved)</h2>
                <p class="text-muted small mb-0">Select files and save as a named sale file, or add into an open one.</p>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('sale.files.collectives.group', $project) }}" id="file-sale-group-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-all">Select all</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-none">Clear</button>
                            </div>
                            <div class="border rounded-3 p-2" style="max-height: 220px; overflow: auto;">
                                @foreach($separateFiles as $file)
                                    <label class="d-flex align-items-center gap-2 py-1 px-1 mb-0">
                                        <input type="checkbox" class="form-check-input file-sale-group-check" name="sale_land_ids[]" value="{{ $file['id'] }}">
                                        <span>{{ $file['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Placement</label>
                                <div class="d-flex flex-column gap-2">
                                    <label class="mb-0">
                                        <input type="radio" name="placement" value="new_collective" class="form-check-input me-1" checked>
                                        New sale file
                                    </label>
                                    <label class="mb-0 {{ $openCollectives === [] ? 'opacity-50' : '' }}">
                                        <input type="radio" name="placement" value="existing_collective" class="form-check-input me-1"
                                               @disabled($openCollectives === [])>
                                        Existing open sale file
                                    </label>
                                </div>
                            </div>
                            <div class="mb-3" id="file-sale-group-name-wrap">
                                <label for="file-sale-group-name" class="form-label">Sale file name</label>
                                <input type="text" name="name" id="file-sale-group-name" class="form-control form-control-theme"
                                       value="{{ old('name', $suggestedSaleFileName) }}" maxlength="150">
                            </div>
                            @if($openCollectives !== [])
                                <div class="mb-3">
                                    <label for="file-sale-group-collective-id" class="form-label">Open sale file</label>
                                    <select name="collective_id" id="file-sale-group-collective-id" class="form-select">
                                        @foreach($openCollectives as $collective)
                                            <option value="{{ $collective['id'] }}">
                                                {{ $collective['name'] }} ({{ $collective['file_count'] }} file{{ $collective['file_count'] === 1 ? '' : 's' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <button type="submit" class="btn btn-pink">Save selected</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

@endif

@if($separateFiles !== [])
@push('modals')
<div class="modal fade" id="save-sale-file-modal" tabindex="-1" aria-labelledby="save-sale-file-modal-title" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" action="{{ route('sale.files.collectives.store', $project) }}" class="modal-content card-theme">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="save-sale-file-modal-title">Save as sale file</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Saves <strong>{{ count($separateFiles) }}</strong> separate file(s) as header + lines, and stores the current exemption formula scenario on the header.
                </p>
                <div class="mb-3">
                    <label for="save-sale-file-name" class="form-label">Sale file name</label>
                    <input type="text" name="name" id="save-sale-file-name" class="form-control form-control-theme"
                           value="{{ old('name', $suggestedSaleFileName) }}" required maxlength="150">
                </div>
                @foreach($separateFiles as $file)
                    <input type="hidden" name="sale_land_ids[]" value="{{ $file['id'] }}">
                @endforeach
                <div class="small text-muted">
                    <div class="fw-semibold text-body mb-1">Lines that will be saved</div>
                    <ul class="mb-0 ps-3">
                        @foreach($separateFiles as $file)
                            <li>{{ $file['name'] }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-pink">Save sale file</button>
            </div>
        </form>
    </div>
</div>
@endpush
@endif

@if($movedFiles !== [])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var btn = document.getElementById('file-sale-files-count-btn');
    var content = document.getElementById('file-sale-files-popover-content');
    if (btn && content && typeof bootstrap !== 'undefined' && bootstrap.Popover) {
        var popover = new bootstrap.Popover(btn, {
            title: 'Sale land files',
            content: content.innerHTML,
            html: true,
            trigger: 'click',
            placement: 'bottom',
            customClass: 'file-sale-popover',
            sanitize: false
        });

        document.addEventListener('click', function (e) {
            var pop = document.querySelector('.file-sale-popover');
            if (!btn.contains(e.target) && !(pop && pop.contains(e.target))) {
                popover.hide();
            }
        });
    }

    var checkAll = document.getElementById('file-sale-group-check-all');
    var checkNone = document.getElementById('file-sale-group-check-none');
    var groupForm = document.getElementById('file-sale-group-form');
    if (checkAll) {
        checkAll.addEventListener('click', function () {
            document.querySelectorAll('.file-sale-group-check').forEach(function (cb) { cb.checked = true; });
        });
    }
    if (checkNone) {
        checkNone.addEventListener('click', function () {
            document.querySelectorAll('.file-sale-group-check').forEach(function (cb) { cb.checked = false; });
        });
    }
    if (groupForm) {
        groupForm.addEventListener('submit', function (e) {
            var selected = document.querySelectorAll('.file-sale-group-check:checked');
            if (!selected.length) {
                e.preventDefault();
                alert('Select at least one separate file.');
                return;
            }
            var placement = groupForm.querySelector('input[name="placement"]:checked');
            if (placement && placement.value === 'existing_collective') {
                var select = document.getElementById('file-sale-group-collective-id');
                if (!select || !select.value) {
                    e.preventDefault();
                    alert('Select an open sale file.');
                }
            }
            if (placement && placement.value === 'new_collective') {
                var nameInput = document.getElementById('file-sale-group-name');
                if (!nameInput || !String(nameInput.value || '').trim()) {
                    e.preventDefault();
                    alert('Enter a sale file name.');
                }
            }
        });
    }
});
</script>
@endpush
@endif
@endsection
