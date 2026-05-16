{{-- Expects $partySubCategories collection --}}
@include('partials.party-sc-combo-styles')
<div class="modal fade" id="purchaseFileAddDealerModal" tabindex="-1" aria-labelledby="purchaseFileAddDealerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="background: var(--card-bg); border: 1px solid var(--border-dark); color: var(--text-dark);">
            <div class="modal-header border-secondary">
                <h2 class="modal-title h5 mb-0" id="purchaseFileAddDealerModalLabel">Add dealer (party)</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Category is set from the sub category you choose. Saved dealers appear in the list and are selected for this file.</p>
                <div class="mb-3">
                    <label for="pf_dealer_name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme" id="pf_dealer_name" placeholder="e.g. Ali Traders" autocomplete="off" maxlength="255">
                </div>
                <div class="mb-3 party-sc-combo" id="pf_dealer_sc_wrap">
                    <label class="form-label" for="pf_dealer_sub_search">Party sub category <span class="text-danger">*</span></label>
                    <input type="hidden" id="pf_dealer_sub_category_id" value="">
                    <input type="text"
                        class="form-control form-control-theme"
                        id="pf_dealer_sub_search"
                        placeholder="Search sub category…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="pf_dealer_sc_listbox"
                        aria-autocomplete="list">
                    <ul class="party-sc-listbox d-none" id="pf_dealer_sc_listbox" role="listbox" hidden></ul>
                </div>
                <script type="application/json" id="pf_dealer_sub_json">@json($partySubCategories->map(function ($sc) {
                    return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
                })->values())</script>
                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="pf_dealer_phone" class="form-label">Phone</label>
                        <input type="text" class="form-control form-control-theme" id="pf_dealer_phone" maxlength="11" inputmode="numeric" placeholder="11 digits">
                    </div>
                    <div class="col-sm-6">
                        <label for="pf_dealer_cnic" class="form-label">CNIC</label>
                        <input type="text" class="form-control form-control-theme" id="pf_dealer_cnic" maxlength="15" inputmode="numeric" placeholder="23012-2321373-1">
                    </div>
                </div>
                <div class="mb-0">
                    <label for="pf_dealer_address" class="form-label">Address</label>
                    <textarea class="form-control form-control-theme" id="pf_dealer_address" rows="2" maxlength="2000" placeholder="Optional"></textarea>
                </div>
                <p class="text-danger small mt-3 mb-0 d-none" id="pf_dealer_error" role="alert"></p>
            </div>
            <div class="modal-footer border-secondary flex-nowrap gap-2">
                <button type="button" class="btn btn-outline-theme" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-pink" id="pf_dealer_save_btn">
                    <span class="pf-dealer-save-text">Save dealer</span>
                    <span class="spinner-border spinner-border-sm ms-1 d-none" id="pf_dealer_save_spinner" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </div>
</div>
