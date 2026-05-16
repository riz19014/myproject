<style>
    .party-sc-combo {
        position: relative;
        z-index: 4;
    }
    #purchaseFileAddDealerModal .modal-body {
        overflow: visible;
    }
    #purchaseFileAddDealerModal .party-sc-combo {
        z-index: 6;
    }
    .party-sc-listbox {
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
        background: var(--card-bg, #fff);
        border: 1px solid var(--border-dark, rgba(15, 23, 42, 0.14));
        border-radius: 10px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
    }
    .party-sc-listbox button {
        display: block;
        width: 100%;
        text-align: left;
        padding: 0.45rem 0.85rem;
        border: 0;
        background: transparent;
        font-size: 0.9rem;
        color: var(--text-dark, #0f172a);
        line-height: 1.35;
    }
    .party-sc-listbox button:hover,
    .party-sc-listbox button:focus {
        background: rgba(249, 115, 22, 0.12);
        outline: none;
    }
    .party-sc-listbox .party-sc-empty {
        padding: 0.45rem 0.85rem;
        font-size: 0.875rem;
        color: var(--text-muted, #64748b);
    }
</style>
