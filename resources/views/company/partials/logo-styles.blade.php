@push('head')
<style>
    .company-logo-card {
        border: 1px solid var(--border-dark);
        background: linear-gradient(145deg, var(--card-bg) 0%, rgba(249, 115, 22, 0.04) 100%);
    }
    .company-logo-card__icon {
        width: 3rem;
        height: 3rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(249, 115, 22, 0.12);
        color: var(--accent-orange, #f97316);
        font-size: 1.35rem;
        flex-shrink: 0;
    }
    .company-logo-dropzone {
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed rgba(249, 115, 22, 0.35);
        border-radius: 12px;
        padding: 2.25rem 1.5rem;
        text-align: center;
        cursor: pointer;
        background: var(--input-bg, rgba(0, 0, 0, 0.02));
        transition: border-color 0.2s ease, background 0.2s ease;
        margin-bottom: 0;
        min-height: 220px;
    }
    .company-logo-dropzone:hover,
    .company-logo-dropzone.company-logo-dropzone--active {
        border-color: var(--accent-orange, #f97316);
        background: rgba(249, 115, 22, 0.06);
    }
    .company-logo-dropzone__inner {
        pointer-events: none;
    }
    .company-logo-dropzone__icon {
        display: block;
        font-size: 2rem;
        opacity: 0.75;
        margin-bottom: 0.75rem;
        color: var(--accent-orange, #f97316);
    }
    .company-logo-file-input {
        position: absolute;
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        z-index: -1;
    }
    .company-logo-preview {
        height: 100%;
        border: 1px solid var(--border-dark);
        border-radius: 12px;
        background: var(--input-bg, rgba(0, 0, 0, 0.02));
        display: flex;
        flex-direction: column;
        min-height: 220px;
    }
    .company-logo-preview__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border-dark);
    }
    .company-logo-preview__title {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .company-logo-preview__frame {
        position: relative;
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1.25rem;
        min-height: 160px;
    }
    .company-logo-preview__img {
        max-width: 100%;
        max-height: 140px;
        object-fit: contain;
        border-radius: 8px;
        background: #fff;
        padding: 0.5rem;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
    }
    .company-logo-preview__empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: var(--text-muted, #64748b);
        text-align: center;
        font-size: 0.9rem;
    }
    .company-logo-preview__empty i {
        font-size: 2.25rem;
        opacity: 0.35;
    }
    .company-logo-preview__spinner {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.82);
        border-radius: 0 0 12px 12px;
        z-index: 2;
    }
    [data-bs-theme="dark"] .company-logo-preview__spinner,
    body.dark .company-logo-preview__spinner {
        background: rgba(15, 23, 42, 0.82);
    }
</style>
@endpush
