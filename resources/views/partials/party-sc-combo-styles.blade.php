<style>
    .party-sc-combo {
        position: relative;
        z-index: 4;
    }
    #purchaseFileAddDealerModal .modal-content {
        display: flex;
        flex-direction: column;
        max-height: min(90vh, calc(100dvh - 2rem));
    }
    #purchaseFileAddDealerModal .modal-body {
        overflow: visible;
        position: relative;
        z-index: 2;
        flex: 1 1 auto;
        min-height: 0;
    }
    #purchaseFileAddDealerModal .modal-footer {
        position: relative;
        z-index: 20;
        flex-shrink: 0;
        background: var(--card-bg, #fff);
    }
    #purchaseFileAddDealerModal .party-sc-combo {
        position: relative;
        z-index: 6;
    }
    #purchaseFileAddDealerModal .party-sc-listbox {
        max-height: min(160px, 28vh);
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
