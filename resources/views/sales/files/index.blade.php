@extends('layouts.app')

@section('title', 'File Sale — '.$project->name)

@push('head')
<style>

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
    .leftover-land-balance__meta-mouza {
        font-size: 0.7rem;
        font-weight: 800;
        color: #9a3412;
        background: #fff7ed;
        border: 1px solid rgba(234, 88, 12, 0.18);
        border-radius: 999px;
        padding: 0.08rem 0.45rem;
        max-width: 10rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 0 1 auto;
    }
    .leftover-land-balance__meta-left {
        color: #047857;
        font-weight: 700;
    }
    .leftover-land-balance__file-row {
        display: flex;
        flex-wrap: wrap;
        align-items: stretch;
        gap: 0.45rem;
    }
    .leftover-land-balance__file-row .leftover-land-balance__summary--nested {
        flex: 1 1 14rem;
        min-width: 0;
    }
    .leftover-land-balance__exclude-form {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
        padding: 0.15rem 0.15rem 0.15rem 0;
    }
    .leftover-land-balance__exclude-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        white-space: nowrap;
        font-weight: 650;
        border-radius: 999px;
        padding: 0.28rem 0.7rem;
    }
    .leftover-land-balance__exclude-btn i {
        font-size: 0.9rem;
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
    .ssf-simple-row {
        display: flex;
        align-items: center;
        gap: 0.5rem 0.65rem;
        width: 100%;
        min-width: 0;
    }
    .ssf-simple-row__main {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        gap: 0.35rem 0.45rem;
        text-align: left;
        overflow: hidden;
    }
    .leftover-land-balance--sale-file .leftover-land-balance__summary-title {
        font-size: 0.92rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 12rem;
    }
    .leftover-land-balance--sale-file .leftover-land-balance__status {
        padding: 0.1rem 0.4rem;
        font-size: 0.62rem;
        flex: 0 0 auto;
    }
    .leftover-land-balance__summary--simple {
        padding: 0.55rem 0.75rem;
        align-items: center;
    }
    .leftover-land-balance__summary--simple:hover,
    .leftover-land-balance__summary--simple:focus {
        background: #f8fafc;
    }
    .leftover-land-balance__summary--simple[aria-expanded="true"] {
        background: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .ssf-simple-row__meta {
        font-size: 0.72rem;
        color: #94a3b8;
        font-weight: 700;
        flex: 0 0 auto;
    }
    .ssf-simple-row__mouza {
        font-size: 0.7rem;
        font-weight: 700;
        color: #9a3412;
        background: #fff7ed;
        border: 1px solid rgba(234, 88, 12, 0.18);
        border-radius: 999px;
        padding: 0.08rem 0.45rem;
        max-width: 9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        flex: 0 1 auto;
    }
    .leftover-land-balance__merge-pick {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        padding: 0.45rem 0.15rem 0.45rem 0.35rem;
        margin: 0;
        cursor: pointer;
    }
    .leftover-land-balance__merge-pick .form-check-input {
        margin: 0;
        cursor: pointer;
    }
    .ssf-inner-merge-bar {
        position: sticky;
        bottom: 0.75rem;
        z-index: 20;
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-top: 0.85rem;
        padding: 0.7rem 0.85rem;
        border: 1px solid rgba(234, 88, 12, 0.22);
        border-radius: 12px;
        background: #fff7ed;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    }
    .ssf-inner-merge-bar.is-visible {
        display: flex;
    }
    .ssf-inner-merge-bar__text {
        font-size: 0.84rem;
        font-weight: 700;
        color: #9a3412;
        min-width: 0;
    }
    .ssf-inner-merge-bar__text span {
        display: block;
        font-size: 0.74rem;
        font-weight: 600;
        color: #78716c;
        margin-top: 0.1rem;
    }
    .ssf-simple-row__left {
        display: inline-flex;
        align-items: baseline;
        gap: 0.25rem;
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .ssf-simple-row__left-label {
        font-size: 0.68rem;
        font-weight: 700;
        color: #059669;
        text-transform: lowercase;
    }
    .ssf-simple-row__left-value {
        font-size: 0.88rem;
        font-weight: 800;
        color: #047857;
        line-height: 1.2;
    }
    .ssf-simple-row__chevron {
        width: 0.42rem;
        height: 0.42rem;
        border-right: 2px solid #94a3b8;
        border-bottom: 2px solid #94a3b8;
        transform: rotate(-45deg);
        flex: 0 0 auto;
        transition: transform 0.15s ease;
    }
    .leftover-land-balance__summary--simple[aria-expanded="true"] .ssf-simple-row__chevron {
        transform: rotate(45deg);
        border-color: #c2410c;
    }
    .leftover-land-balance__detail-inner--simple {
        padding: 0.55rem 0.7rem 0.7rem;
        background: #f8fafc;
    }
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__section {
        margin-bottom: 0.7rem;
    }
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__section-title {
        margin-bottom: 0.35rem;
        font-size: 0.7rem;
    }
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__table thead th,
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__table tbody td {
        padding: 0.4rem 0.55rem;
        font-size: 0.8rem;
    }
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__summary--nested {
        padding: 0.45rem 0.55rem;
    }
    .leftover-land-balance__detail-inner--simple .leftover-land-balance__item--nested {
        border-radius: 8px;
    }
    .ssf-toolbar--simple {
        margin-bottom: 0.55rem;
    }
    .ssf-simple-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.4rem 0.55rem;
        padding: 0.4rem 0.55rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 8px;
        background: #fff;
    }
    .ssf-simple-bar__value {
        border: 0;
        background: transparent;
        padding: 0;
        margin: 0;
        text-align: left;
        font: inherit;
        font-size: 0.78rem;
        font-weight: 650;
        color: #475569;
        cursor: pointer;
        min-width: 0;
        flex: 1 1 8rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .ssf-simple-bar__value:hover,
    .ssf-simple-bar__value:focus-visible {
        color: #9a3412;
        text-decoration: underline;
    }
    .ssf-simple-bar__locked {
        display: inline-flex;
        padding: 0.08rem 0.35rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        font-size: 0.6rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .ssf-simple-bar__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }
    .ssf-simple-bar__actions .btn {
        padding: 0.15rem 0.45rem;
        font-size: 0.72rem;
        line-height: 1.2;
    }
    .ssf-file-mini {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.2rem 0.3rem;
        font-size: 0.74rem;
        color: #475569;
        font-weight: 650;
    }
    .ssf-file-mini__link {
        font-weight: 750;
        color: #0f172a;
        text-decoration: none;
    }
    .ssf-file-mini__link:hover {
        color: #c2410c;
        text-decoration: underline;
    }
    .ssf-file-mini__sep {
        color: #cbd5e1;
    }
    .saved-sale-files-list {
        gap: 0.45rem !important;
    }
    .saved-sale-panel {
        border-radius: 10px !important;
        box-shadow: none !important;
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

    /* Compact light overview */
    .fs-page-header {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem 1rem;
        margin-bottom: 1.25rem;
    }
    .fs-page-header h1 {
        margin: 0 0 0.2rem;
        font-size: 1.5rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #0f172a;
    }
    .fs-page-header__actions {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
    }
    .fs-empty {
        text-align: center;
        padding: 2rem 1.25rem;
    }
    .fs-empty__title {
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0.35rem;
    }
    .fs-overview {
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #fff;
        border-radius: 10px;
        overflow: hidden;
    }
    .fs-overview .card-body {
        padding: 0.55rem 0.7rem;
    }
    .fs-overview-strip {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem 0.75rem;
    }
    .fs-overview-strip__files {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        flex: 0 0 auto;
    }
    .fs-files-btn {
        border: 1px solid rgba(194, 65, 12, 0.22);
        background: #fff7ed;
        border-radius: 8px;
        padding: 0.25rem 0.5rem;
        cursor: pointer;
        line-height: 1.15;
        display: inline-flex;
        align-items: baseline;
        gap: 0.25rem;
    }
    .fs-files-btn:hover,
    .fs-files-btn:focus {
        border-color: rgba(194, 65, 12, 0.45);
        box-shadow: 0 0 0 2px rgba(249, 115, 22, 0.12);
        outline: none;
    }
    .fs-files-btn__num {
        font-size: 0.95rem;
        font-weight: 800;
        color: #c2410c;
    }
    .fs-files-btn__text {
        font-size: 0.68rem;
        font-weight: 750;
        color: #9a3412;
    }
    .fs-overview-strip__stats {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem 0.65rem;
        flex: 1 1 12rem;
        min-width: 0;
        font-size: 0.78rem;
        font-weight: 650;
        color: #475569;
    }
    .fs-overview-strip__stat strong {
        font-weight: 800;
        color: #0f172a;
    }
    .fs-overview-strip__stat.is-sold strong { color: #c2410c; }
    .fs-overview-strip__stat.is-left strong { color: #047857; }
    .fs-overview-strip__stat.is-amount strong { color: #0f172a; }
    .fs-overview-strip__stat.is-sale strong { color: #9a3412; }
    .fs-overview-strip__sep {
        color: #cbd5e1;
        font-weight: 500;
    }
    .fs-overview-strip__plots {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        width: 100%;
    }
    .fs-plot-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.12rem 0.4rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        background: #f8fafc;
        font-size: 0.7rem;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
    }
    .fs-plot-chip.is-residential {
        background: #eff6ff;
        border-color: rgba(37, 99, 235, 0.2);
        color: #1d4ed8;
    }
    .fs-plot-chip.is-commercial {
        background: #fff7ed;
        border-color: rgba(234, 88, 12, 0.22);
        color: #c2410c;
    }
    .fs-plot-chip__meta {
        color: #64748b;
        font-weight: 650;
    }
    .fs-overview-strip__actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        margin-left: auto;
    }
    .fs-overview-strip__actions .btn {
        padding: 0.15rem 0.45rem;
        font-size: 0.72rem;
        line-height: 1.2;
    }
    .fs-step-card .card-header {
        background: #fff;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .fs-step-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.4rem;
        height: 1.4rem;
        padding: 0 0.35rem;
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid rgba(194, 65, 12, 0.22);
        color: #c2410c;
        font-size: 0.72rem;
        font-weight: 800;
    }
    .fs-file-pick {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 12px;
        padding: 0.35rem;
        max-height: 240px;
        overflow: auto;
        background: #f8fafc;
    }
    .fs-file-pick label {
        display: flex;
        align-items: center;
        gap: 0.55rem;
        padding: 0.45rem 0.55rem;
        margin: 0;
        border-radius: 8px;
        cursor: pointer;
    }
    .fs-file-pick label:hover { background: #fff; }
    .fs-place-option {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 10px;
        background: #fff;
        cursor: pointer;
        margin: 0;
    }
    .fs-place-option + .fs-place-option { margin-top: 0.45rem; }
    .fs-place-option.is-disabled { opacity: 0.5; cursor: not-allowed; }
    .fs-place-option__title { font-weight: 750; color: #0f172a; font-size: 0.88rem; }
    .fs-place-option__hint { display: block; font-size: 0.74rem; color: #64748b; margin-top: 0.1rem; }
    .saved-sale-files-card__header {
        background: #fff !important;
        border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    }
    .saved-sale-files-card__body {
        background: #fff !important;
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
    .saved-sale-files-empty {
        text-align: center;
        padding: 1.5rem 1rem;
        border: 1px dashed rgba(15, 23, 42, 0.14);
        border-radius: 14px;
        background: #f8fafc;
    }
    .saved-sale-files-empty__icon {
        width: 2.5rem;
        height: 2.5rem;
        margin: 0 auto 0.65rem;
        display: grid;
        place-items: center;
        border-radius: 12px;
        background: #fff7ed;
        color: #c2410c;
        font-size: 1.15rem;
    }
    .saved-sale-files-empty__title {
        font-weight: 800;
        color: #0f172a;
        font-size: 0.95rem;
    }
    .saved-sale-files-list {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
    }
    .saved-sale-panel {
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 14px;
        padding: 0;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
    }
    .saved-sale-panel.is-open { border-color: rgba(22, 163, 74, 0.28); }
    .saved-sale-panel.is-done { border-color: rgba(100, 116, 139, 0.28); }

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
    .exemption-pick-list {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        max-height: min(50vh, 320px);
        overflow: auto;
    }
    .exemption-pick-line {
        display: flex;
        align-items: center;
        gap: 0.45rem;
        margin: 0;
        padding: 0.4rem 0.55rem;
        border: 1px solid rgba(15, 23, 42, 0.1);
        border-radius: 8px;
        background: #fff;
        cursor: pointer;
        min-width: 0;
        position: relative;
    }
    .exemption-pick-line:hover {
        border-color: rgba(194, 65, 12, 0.35);
        background: #fffaf5;
    }
    .exemption-pick-line input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .exemption-pick-line__radio {
        width: 0.9rem;
        height: 0.9rem;
        border: 2px solid #94a3b8;
        border-radius: 50%;
        flex: 0 0 auto;
        position: relative;
    }
    .exemption-pick-line input:checked + .exemption-pick-line__radio {
        border-color: #c2410c;
    }
    .exemption-pick-line input:checked + .exemption-pick-line__radio::after {
        content: "";
        position: absolute;
        inset: 2px;
        border-radius: 50%;
        background: #c2410c;
    }
    .exemption-pick-line:has(input:checked) {
        border-color: rgba(194, 65, 12, 0.45);
        background: #fff7ed;
    }
    .exemption-pick-line.is-applied {
        border-color: rgba(22, 163, 74, 0.35);
    }
    .exemption-pick-line__body {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
        min-width: 0;
        flex: 1 1 auto;
    }
    .exemption-pick-line__text {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        min-width: 0;
        font-size: 0.82rem;
        line-height: 1.25;
    }
    .exemption-pick-line__title {
        font-weight: 800;
        color: #0f172a;
        flex: 0 0 auto;
    }
    .exemption-pick-line__meta {
        font-size: 0.74rem;
        font-weight: 650;
        color: #64748b;
        line-height: 1.3;
    }
    .exemption-pick-line__badge {
        flex: 0 0 auto;
        font-size: 0.6rem;
        font-weight: 800;
        padding: 0.1rem 0.35rem;
        border-radius: 999px;
        background: #e2e8f0;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .exemption-pick-line__badge.is-live {
        background: #ffedd5;
        color: #9a3412;
    }
    .exemption-pick-line__badge.is-applied {
        background: #dcfce7;
        color: #166534;
    }
    .exemption-pick-line__comps {
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
    }
    .exemption-pick-line__comp {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 8px;
        background: #f8fafc;
        padding: 0.4rem 0.5rem;
    }
    .exemption-pick-line__comp.is-residential {
        background: #eff6ff;
        border-color: rgba(37, 99, 235, 0.18);
    }
    .exemption-pick-line__comp.is-commercial {
        background: #fff7ed;
        border-color: rgba(234, 88, 12, 0.2);
    }
    .exemption-pick-line__comp-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.25rem;
    }
    .exemption-pick-line__comp-title {
        font-size: 0.78rem;
        font-weight: 800;
        color: #0f172a;
    }
    .exemption-pick-line__comp-pool {
        font-size: 0.7rem;
        font-weight: 750;
        color: #9a3412;
        white-space: nowrap;
    }
    .exemption-pick-line__plot {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        gap: 0.5rem;
        font-size: 0.74rem;
        line-height: 1.3;
        padding: 0.08rem 0;
    }
    .exemption-pick-line__plot + .exemption-pick-line__plot {
        border-top: 1px solid rgba(15, 23, 42, 0.06);
    }
    .exemption-pick-line__plot-name {
        color: #334155;
        font-weight: 650;
        min-width: 0;
    }
    .exemption-pick-line__plot-vals {
        color: #0f172a;
        font-weight: 750;
        white-space: nowrap;
        text-align: right;
    }
    .exemption-pick-line__plot-empty {
        font-size: 0.72rem;
        color: #94a3b8;
    }
    .exemption-view-applied-banner {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem 0.75rem;
        margin-bottom: 0.85rem;
        padding: 0.7rem 0.85rem;
        border-radius: 12px;
        border: 1px solid rgba(22, 163, 74, 0.28);
        background: linear-gradient(180deg, #ecfdf5 0%, #fff 100%);
        color: #166534;
        font-size: 0.86rem;
        font-weight: 650;
    }
    .exemption-view-applied-banner__badge {
        display: inline-flex;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: #166534;
        color: #fff;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .ssf-toolbar__applied-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.15rem 0.5rem;
        border-radius: 999px;
        background: #dcfce7;
        border: 1px solid rgba(22, 163, 74, 0.28);
        color: #166534;
        font-size: 0.68rem;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
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
    $suggestedSaleFileName = $summary['suggested_sale_file_name'] ?? ('Collective-'.(count($collectives) + 1));
    $openMergeCollectives = collect($collectives)
        ->filter(fn ($c) => ! empty($c['is_open']))
        ->values()
        ->all();
    $innerMergeableFileCount = collect($openMergeCollectives)
        ->sum(fn ($c) => collect($c['leftover_balance']['files'] ?? [])->count());
    $canMergeInnerFiles = $innerMergeableFileCount >= 2;
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

<div class="fs-page-header">
    <div>
        <h1>File Sale</h1>
        <p class="text-muted small mb-0">Project: <strong><x-project-name :project="$project" /></strong></p>
    </div>
    <div class="fs-page-header__actions">
        <a href="{{ route('projects.sale-land', $project) }}" class="btn btn-pink">Sale land</a>
        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-theme">Back to project</a>
        @if($separateFiles !== [])
            <button type="button" class="btn btn-outline-theme" data-bs-toggle="modal" data-bs-target="#save-sale-file-modal">
                Save as sale file
            </button>
        @endif
        <div class="dropdown">
            <button class="btn btn-outline-theme dropdown-toggle" type="button" id="file-sale-more-menu" data-bs-toggle="dropdown" aria-expanded="false">
                More
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="file-sale-more-menu">
                <li><a class="dropdown-item" href="{{ route('sale.projects.exemption.edit', $project) }}">Exemption setup</a></li>
                <li><a class="dropdown-item" href="{{ route('sale.files.create', $project) }}">Add project file</a></li>
                <li><a class="dropdown-item" href="{{ route('purchase.files.index', ['project' => $project->id]) }}">Purchase files</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="{{ route('sale.index') }}">Sale menu</a></li>
            </ul>
        </div>
    </div>
</div>

@if($movedFiles === [])
    <div class="card card-theme mb-4">
        <div class="card-body fs-empty">
            <p class="fs-empty__title">No files in File Sale yet</p>
            <p class="text-muted small mb-3">
                Open Sale land, select files, then click <strong>Move to File Sale</strong>.
            </p>
            <a href="{{ route('projects.sale-land', $project) }}" class="btn btn-pink">Go to Sale land</a>
        </div>
    </div>
@else
    <div class="card card-theme mb-3 fs-overview">
        @if($filesLandColumns !== [])
            <div class="card-body">
                <div id="file-sale-files-popover-content" class="d-none">
                    <ul class="file-sale-popover__list">
                        @foreach($movedFiles as $file)
                            <li>{{ $file['name'] }}</li>
                        @endforeach
                    </ul>
                </div>

                <div class="fs-overview-strip">
                    <div class="fs-overview-strip__files">
                        <button type="button"
                                class="fs-files-btn"
                                id="file-sale-files-count-btn"
                                aria-label="{{ count($movedFiles) }} sale land files — click to view names"
                                title="Tap to view file names">
                            <span class="fs-files-btn__num">{{ count($movedFiles) }}</span>
                            <span class="fs-files-btn__text">{{ count($movedFiles) === 1 ? 'file' : 'files' }}</span>
                        </button>
                    </div>

                    <div class="fs-overview-strip__stats">
                        <span class="fs-overview-strip__stat">Total <strong>{{ $totals['total_land_area'] ?? '—' }}</strong></span>
                        <span class="fs-overview-strip__sep">·</span>
                        <span class="fs-overview-strip__stat is-sold">Sold <strong>{{ $totals['sold_land_area'] ?? '—' }}</strong></span>
                        <span class="fs-overview-strip__sep">·</span>
                        <span class="fs-overview-strip__stat is-left">Left <strong>{{ $totals['remaining_land_area'] ?? '—' }}</strong></span>
                        <span class="fs-overview-strip__sep">·</span>
                        <span class="fs-overview-strip__stat is-amount" title="Total amount land">
                            Amt <strong>{{ $totals['grand_total_amount_short'] ?? ($totals['grand_total_amount_formatted'] ?? '—') }}</strong>
                        </span>
                        <span class="fs-overview-strip__sep">·</span>
                        <span class="fs-overview-strip__stat is-sale" title="Sale files amount">
                            Sale <strong>{{ $totals['sale_files_amount_short'] ?? ($totals['sale_files_amount_formatted'] ?? '—') }}</strong>
                        </span>
                    </div>

                    <div class="fs-overview-strip__actions">
                        @if($separateFiles !== [])
                            <button type="button" class="btn btn-sm btn-pink" data-bs-toggle="modal" data-bs-target="#save-sale-file-modal">
                                Save all
                            </button>
                        @endif
                        <a href="{{ route('sale.files.original-formula.pdf', $project) }}" class="btn btn-sm btn-outline-theme">PDF</a>
                    </div>

                    @if($filesLandColumns !== [])
                        <div class="fs-overview-strip__plots">
                            @foreach($filesLandColumns as $column)
                                @php
                                    $isCommercial = str_starts_with(strtoupper((string) ($column['column_code'] ?? '')), 'C');
                                @endphp
                                <span class="fs-plot-chip {{ $isCommercial ? 'is-commercial' : 'is-residential' }}" title="{{ $column['short_label'] }}">
                                    {{ $column['column_code'] }}
                                    <span class="fs-plot-chip__meta">{{ $column['file_count'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="card-body">
                <p class="text-muted small mb-0">No land area on moved files.</p>
            </div>
        @endif
    </div>

    @if($separateFiles !== [])
        <div class="card card-theme mb-4 fs-step-card">
            <div class="card-header py-3">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="fs-step-badge">1</span>
                    <h2 class="h6 mb-0">Save files into a sale file</h2>
                </div>
                <p class="text-muted small mb-0">Select unsaved files, then save as a new sale file or add them to an open one.</p>
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('sale.files.collectives.group', $project) }}" id="file-sale-group-form">
                    @csrf
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="small fw-semibold text-secondary">{{ count($separateFiles) }} unsaved {{ count($separateFiles) === 1 ? 'file' : 'files' }}</span>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-all">Select all</button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="file-sale-group-check-none">Clear</button>
                                </div>
                            </div>
                            <div class="fs-file-pick">
                                @foreach($separateFiles as $file)
                                    <label>
                                        <input type="checkbox" class="form-check-input file-sale-group-check" name="sale_land_ids[]" value="{{ $file['id'] }}">
                                        <span>{{ $file['name'] }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="mb-3">
                                <label class="form-label">Where to save</label>
                                <label class="fs-place-option">
                                    <input type="radio" name="placement" value="new_collective" class="form-check-input mt-1" checked>
                                    <span>
                                        <span class="fs-place-option__title">New sale file</span>
                                        <span class="fs-place-option__hint">Create a named sale file with the selected lines.</span>
                                    </span>
                                </label>
                                <label class="fs-place-option {{ $openCollectives === [] ? 'is-disabled' : '' }}">
                                    <input type="radio" name="placement" value="existing_collective" class="form-check-input mt-1"
                                           @disabled($openCollectives === [])>
                                    <span>
                                        <span class="fs-place-option__title">Existing open sale file</span>
                                        <span class="fs-place-option__hint">
                                            @if($openCollectives === [])
                                                No open sale files yet.
                                            @else
                                                Add selected files into one that is still open.
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <div class="mb-3" id="file-sale-group-name-wrap">
                                <label for="file-sale-group-name" class="form-label">Sale file name</label>
                                <input type="text" name="name" id="file-sale-group-name" class="form-control form-control-theme"
                                       value="{{ old('name', $suggestedSaleFileName) }}" maxlength="150">
                            </div>
                            @if($openCollectives !== [])
                                <div class="mb-3 d-none" id="file-sale-group-collective-wrap">
                                    <label for="file-sale-group-collective-id" class="form-label">Open sale file</label>
                                    <select name="collective_id" id="file-sale-group-collective-id" class="form-select form-select-theme">
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

    <div class="card card-theme mb-4 saved-sale-files-card fs-step-card">
        <div class="card-header py-3 saved-sale-files-card__header">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                <span class="fs-step-badge">{{ $separateFiles !== [] ? '2' : '1' }}</span>
                <h2 class="h6 mb-0">Saved sale files</h2>
                @if($collectives !== [])
                    <span class="saved-sale-files-card__count">{{ count($collectives) }}</span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                Tap to open.
                @if($canMergeInnerFiles)
                    Inside a sale file, tick files of the same mouza to merge them.
                @endif
            </p>
        </div>
        <div class="card-body saved-sale-files-card__body">
            @if($collectives === [])
                <div class="saved-sale-files-empty">
                    <div class="saved-sale-files-empty__icon" aria-hidden="true"><i class="bi bi-folder2"></i></div>
                    <p class="saved-sale-files-empty__title mb-1">No saved sale files yet</p>
                    <p class="text-muted small mb-0">
                        @if($separateFiles !== [])
                            Use step 1 above to save selected files.
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
                @if($canMergeInnerFiles)
                    <div class="ssf-inner-merge-bar" id="ssf-inner-merge-bar" hidden>
                        <div class="ssf-inner-merge-bar__text" id="ssf-inner-merge-bar-text">
                            Select files
                            <span>Merge same mouza · Unmerge to split apart</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="ssf-inner-merge-clear">Clear</button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-theme"
                                id="ssf-inner-unmerge-open"
                                data-bs-toggle="modal"
                                data-bs-target="#unmerge-inner-files-modal"
                                disabled
                            >Unmerge</button>
                            <button
                                type="button"
                                class="btn btn-sm btn-pink"
                                id="ssf-inner-merge-open"
                                data-bs-toggle="modal"
                                data-bs-target="#merge-inner-files-modal"
                                disabled
                            >Merge selected</button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
@endif

@if($canMergeInnerFiles)
@push('modals')
<div class="modal fade" id="merge-inner-files-modal" tabindex="-1" aria-labelledby="merge-inner-files-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('sale.files.collectives.merge-files', $project) }}" class="modal-content card-theme" id="merge-inner-files-form">
            @csrf
            <input type="hidden" name="prefer_collective_id" id="merge-inner-files-prefer-id" value="">
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="merge-inner-files-modal-title">Merge selected files</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Combines selected same-mouza files into <strong>one purchase file</strong>
                    (e.g. 12 kanal + 7 kanal → one 19 kanal file). Other files in the clump stay as they are.
                </p>
                <div class="mb-3">
                    <div class="small fw-semibold text-body mb-1">Will become one file</div>
                    <ul class="mb-0 ps-3 small" id="merge-inner-files-list"></ul>
                    <div id="merge-inner-files-ids"></div>
                </div>
                <div class="mb-0">
                    <label for="merge-inner-files-name" class="form-label">Merged file name</label>
                    <input type="text" name="name" id="merge-inner-files-name" class="form-control form-control-theme"
                           value="" required maxlength="150">
                    <div class="form-text">Suggested from combined land (12 + 7 → 19 kanal). You can edit it.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-pink">Merge files</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="unmerge-inner-files-modal" tabindex="-1" aria-labelledby="unmerge-inner-files-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" action="{{ route('sale.files.collectives.unmerge-files', $project) }}" class="modal-content card-theme" id="unmerge-inner-files-form">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title h5 mb-0" id="unmerge-inner-files-modal-title">Unmerge selected files</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    If the file was merged (e.g. 19 kanal from 12 + 7), this restores the <strong>original purchase files</strong>.
                    Otherwise it only splits a file out of a multi-file clump.
                </p>
                <div class="mb-0">
                    <div class="small fw-semibold text-body mb-1">Selected</div>
                    <ul class="mb-0 ps-3 small" id="unmerge-inner-files-list"></ul>
                    <div id="unmerge-inner-files-ids"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-pink">Unmerge files</button>
            </div>
        </form>
    </div>
</div>
@endpush
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
                    Saves all <strong>{{ count($separateFiles) }}</strong> unsaved file(s) into one sale file.
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
                    <div class="fw-semibold text-body mb-1">Files included</div>
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
    var nameWrap = document.getElementById('file-sale-group-name-wrap');
    var collectiveWrap = document.getElementById('file-sale-group-collective-wrap');

    function syncPlacementUi() {
        if (!groupForm) return;
        var placement = groupForm.querySelector('input[name="placement"]:checked');
        var isExisting = placement && placement.value === 'existing_collective';
        if (nameWrap) nameWrap.classList.toggle('d-none', !!isExisting);
        if (collectiveWrap) collectiveWrap.classList.toggle('d-none', !isExisting);
    }

    if (groupForm) {
        groupForm.querySelectorAll('input[name="placement"]').forEach(function (radio) {
            radio.addEventListener('change', syncPlacementUi);
        });
        syncPlacementUi();
    }

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

    var innerBar = document.getElementById('ssf-inner-merge-bar');
    var innerBarText = document.getElementById('ssf-inner-merge-bar-text');
    var innerOpenBtn = document.getElementById('ssf-inner-merge-open');
    var innerUnmergeBtn = document.getElementById('ssf-inner-unmerge-open');
    var innerClearBtn = document.getElementById('ssf-inner-merge-clear');
    var innerForm = document.getElementById('merge-inner-files-form');
    var unmergeForm = document.getElementById('unmerge-inner-files-form');
    var innerList = document.getElementById('merge-inner-files-list');
    var innerIdsWrap = document.getElementById('merge-inner-files-ids');
    var unmergeList = document.getElementById('unmerge-inner-files-list');
    var unmergeIdsWrap = document.getElementById('unmerge-inner-files-ids');
    var innerNameInput = document.getElementById('merge-inner-files-name');
    var innerPreferId = document.getElementById('merge-inner-files-prefer-id');

    function selectedInnerChecks() {
        return Array.prototype.slice.call(document.querySelectorAll('.ssf-inner-merge-check:checked'));
    }

    function canUnmergeSelection(selected) {
        return selected.some(function (cb) {
            return cb.getAttribute('data-can-file-unmerge') === '1'
                || parseInt(cb.getAttribute('data-collective-file-count') || '0', 10) > 1;
        });
    }

    function marlaToMergeName(totalMarla) {
        var eps = 1e-6;
        if (totalMarla <= eps) return '0 marla';
        var whole = Math.floor(totalMarla + eps);
        var k = Math.floor(whole / 20);
        var m = whole - k * 20;
        var parts = [];
        if (k > 0) parts.push(k + ' kanal');
        if (m > 0) parts.push(m + ' marla');
        return parts.length ? parts.join(' ') : '0 marla';
    }

    function preferredCollectiveId(selected) {
        var counts = {};
        selected.forEach(function (cb) {
            var id = String(cb.getAttribute('data-collective-id') || '');
            if (!id) return;
            counts[id] = (counts[id] || 0) + 1;
        });
        var best = '';
        var bestCount = 0;
        Object.keys(counts).forEach(function (id) {
            if (counts[id] > bestCount) {
                bestCount = counts[id];
                best = id;
            }
        });
        return best;
    }

    function syncInnerMergeBar() {
        var selected = selectedInnerChecks();
        if (!innerBar) return;

        if (selected.length === 0) {
            innerBar.classList.remove('is-visible');
            innerBar.hidden = true;
            if (innerOpenBtn) innerOpenBtn.disabled = true;
            if (innerUnmergeBtn) innerUnmergeBtn.disabled = true;
            return;
        }

        innerBar.hidden = false;
        innerBar.classList.add('is-visible');

        var keys = selected.map(function (cb) { return String(cb.getAttribute('data-mouza-key') || ''); });
        var unique = keys.filter(function (k, i) { return keys.indexOf(k) === i; });
        var sameMouza = unique.length === 1 && !!unique[0];
        var mouzaLabel = sameMouza ? (selected[0].getAttribute('data-mouza') || unique[0]) : '';
        var canUnmerge = canUnmergeSelection(selected);
        var totalMarla = selected.reduce(function (sum, cb) {
            return sum + (parseFloat(cb.getAttribute('data-total-marla') || '0') || 0);
        }, 0);
        var suggested = marlaToMergeName(totalMarla);

        var hints = [];
        if (sameMouza && selected.length >= 2) {
            hints.push('Mouza ' + mouzaLabel + ' · become one file "' + suggested + '"');
        } else if (sameMouza) {
            hints.push('Mouza ' + mouzaLabel + ' · select one more to merge');
        } else if (selected.length >= 2) {
            hints.push('Different mouza — merge disabled');
        }
        if (canUnmerge) {
            var hasFileUnmerge = selected.some(function (cb) {
                return cb.getAttribute('data-can-file-unmerge') === '1';
            });
            hints.push(hasFileUnmerge ? 'Can unmerge back to original files' : 'Can unmerge');
        }

        if (innerBarText) {
            innerBarText.innerHTML = selected.length + ' file' + (selected.length === 1 ? '' : 's') + ' selected'
                + '<span>' + hints.join(' · ') + '</span>';
        }
        if (innerOpenBtn) {
            innerOpenBtn.disabled = !(sameMouza && selected.length >= 2);
        }
        if (innerUnmergeBtn) {
            innerUnmergeBtn.disabled = !canUnmerge;
        }
    }

    function fillIdList(selected, listEl, idsWrap) {
        if (!listEl || !idsWrap) return;
        listEl.innerHTML = '';
        idsWrap.innerHTML = '';
        selected.forEach(function (cb) {
            var li = document.createElement('li');
            var mouza = cb.getAttribute('data-mouza') || '';
            li.textContent = (mouza ? mouza + ' · ' : '') + (cb.getAttribute('data-name') || cb.value);
            listEl.appendChild(li);
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'purchase_file_ids[]';
            hidden.value = cb.value;
            idsWrap.appendChild(hidden);
        });
    }

    function fillInnerMergeModal() {
        var selected = selectedInnerChecks();
        fillIdList(selected, innerList, innerIdsWrap);
        var totalMarla = selected.reduce(function (sum, cb) {
            return sum + (parseFloat(cb.getAttribute('data-total-marla') || '0') || 0);
        }, 0);
        if (innerNameInput) {
            innerNameInput.value = marlaToMergeName(totalMarla);
        }
        if (innerPreferId) {
            innerPreferId.value = preferredCollectiveId(selected);
        }
    }

    function fillInnerUnmergeModal() {
        var selected = selectedInnerChecks().filter(function (cb) {
            return cb.getAttribute('data-can-file-unmerge') === '1'
                || parseInt(cb.getAttribute('data-collective-file-count') || '0', 10) > 1;
        });
        fillIdList(selected, unmergeList, unmergeIdsWrap);
    }

    document.querySelectorAll('.ssf-inner-merge-check').forEach(function (cb) {
        cb.addEventListener('change', syncInnerMergeBar);
        cb.addEventListener('click', function (e) { e.stopPropagation(); });
    });

    if (innerClearBtn) {
        innerClearBtn.addEventListener('click', function () {
            document.querySelectorAll('.ssf-inner-merge-check').forEach(function (cb) { cb.checked = false; });
            syncInnerMergeBar();
        });
    }

    if (innerOpenBtn) {
        innerOpenBtn.addEventListener('click', fillInnerMergeModal);
    }
    if (innerUnmergeBtn) {
        innerUnmergeBtn.addEventListener('click', fillInnerUnmergeModal);
    }

    if (innerForm) {
        innerForm.addEventListener('submit', function (e) {
            var selected = selectedInnerChecks();
            if (selected.length < 2) {
                e.preventDefault();
                alert('Select at least two files of the same mouza.');
                return;
            }
            var keys = selected.map(function (cb) { return String(cb.getAttribute('data-mouza-key') || ''); });
            var unique = keys.filter(function (k, i) { return keys.indexOf(k) === i; });
            if (unique.length !== 1 || !unique[0]) {
                e.preventDefault();
                alert('Selected files must share the same mouza.');
                return;
            }
            fillInnerMergeModal();
            if (!innerNameInput || !String(innerNameInput.value || '').trim()) {
                e.preventDefault();
                alert('Enter a merged sale file name.');
            }
        });
    }

    if (unmergeForm) {
        unmergeForm.addEventListener('submit', function (e) {
            fillInnerUnmergeModal();
            var ids = unmergeIdsWrap ? unmergeIdsWrap.querySelectorAll('input[name="purchase_file_ids[]"]') : [];
            if (!ids.length) {
                e.preventDefault();
                alert('Select a merged file to restore, or a file inside a multi-file sale file.');
            }
        });
    }

    syncInnerMergeBar();
});
</script>
@endpush
@endif
@endsection
