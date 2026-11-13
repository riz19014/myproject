{{-- Shared daybook entry / voucher detail modal (ledger, ledger summary, entries). --}}
<div class="modal fade" id="daybookEntryModal" tabindex="-1" aria-labelledby="daybookEntryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content daybook-entry-modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0" id="daybookEntryModalLabel">Daybook entry</h5>
                    <p class="small text-muted mb-0" id="daybook-modal-subtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daybook-modal-grid">
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Voucher no.</span><span id="daybook-modal-voucher" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Date</span><span id="daybook-modal-date" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Created at</span><span id="daybook-modal-created-at" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Payment</span><span id="daybook-modal-type" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Amount</span><span id="daybook-modal-amount" class="daybook-modal-val font-monospace fw-semibold"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Settlement</span><span id="daybook-modal-settlement" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Linked to</span><span id="daybook-modal-link" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Project</span><span id="daybook-modal-project" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Sale file</span><span id="daybook-modal-file" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Sold area</span><span id="daybook-modal-sold-area" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item"><span class="daybook-modal-key">Party sub category</span><span id="daybook-modal-subcat" class="daybook-modal-val"></span></div>
                    <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Description</span><span id="daybook-modal-description" class="daybook-modal-val"></span></div>
                </div>

                <div id="daybook-modal-factory" class="mt-3 d-none">
                    <h6 class="text-uppercase text-muted small mb-2 d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam" aria-hidden="true"></i>
                        <span>Factory expense</span>
                    </h6>
                    <div class="daybook-modal-grid">
                        <div class="daybook-modal-item daybook-modal-item--wide"><span class="daybook-modal-key">Sub category</span><span id="daybook-modal-fsubcat" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Unit</span><span id="daybook-modal-unit" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Quantity</span><span id="daybook-modal-qty" class="daybook-modal-val"></span></div>
                        <div class="daybook-modal-item"><span class="daybook-modal-key">Unit price</span><span id="daybook-modal-price" class="daybook-modal-val"></span></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="daybook-modal-print-voucher" class="btn btn-outline-theme btn-sm" target="_blank" rel="noopener">Print voucher</a>
                <a href="#" id="daybook-modal-open-full" class="btn btn-outline-theme btn-sm">Open full page</a>
                <button type="button" class="btn btn-pink btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
