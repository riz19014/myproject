<div class="modal fade" id="daybookCreateProjectModal" tabindex="-1" aria-labelledby="daybookCreateProjectModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreateProjectModalLabel">Create project</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daybook-project-modal-panel mb-0">
                    <label class="daybook-modal-label" for="daybook_modal_project_name">Project name</label>
                    <input type="text" class="form-control form-control-theme" id="daybook_modal_project_name" placeholder="e.g. DHA Phase 2" autocomplete="off">
                </div>
                <div class="daybook-project-modal-panel mt-3 mb-0">
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
                <p class="text-danger small mt-3 mb-0 d-none" id="daybook_modal_project_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2 align-items-center">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="daybook-save-record text-nowrap ms-sm-auto" id="daybook_modal_project_primary" aria-label="Create project">
                    <span class="daybook-save-record__idle">
                        <span id="daybook_modal_project_primary_label">Create project</span>
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreatePartyModalLabel">Create party</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Category is set from the sub category you choose.</p>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="daybook_modal_party_name">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-theme" id="daybook_modal_party_name" placeholder="e.g. Ali Traders" autocomplete="off" maxlength="255">
                    </div>
                    <div class="col-md-6 daybook-party-sc-combo">
                        <label class="form-label" for="daybook_modal_party_sub_search">Party sub category <span class="text-danger">*</span></label>
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
                </div>
                <script type="application/json" id="daybook-party-sub-json">@json($partySubCategories->map(function ($sc) {
                    return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
                })->values())</script>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label" for="daybook_modal_party_phone">Phone</label>
                        <input type="text" class="form-control form-control-theme" id="daybook_modal_party_phone" maxlength="11" inputmode="numeric" placeholder="11 digits e.g. 03001234567" autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="daybook_modal_party_cnic">CNIC</label>
                        <input type="text" class="form-control form-control-theme" id="daybook_modal_party_cnic" maxlength="15" inputmode="numeric" placeholder="23012-2321373-1" autocomplete="off">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label" for="daybook_modal_party_address">Address</label>
                    <textarea class="form-control form-control-theme" id="daybook_modal_party_address" rows="2" maxlength="2000" placeholder="Optional"></textarea>
                </div>
                <p class="text-danger small mt-3 mb-0 d-none" id="daybook_modal_party_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-pink flex-grow-1 flex-sm-grow-0" id="daybook_modal_party_submit">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="daybookCreatePartySubCategoryModal" tabindex="-1" aria-labelledby="daybookCreatePartySubCategoryModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreatePartySubCategoryModalLabel">Create sub category</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <label class="form-label mb-0" for="daybook_modal_party_sub_cat_category_id">Party category <span class="text-danger">*</span></label>
                        <a href="{{ route('party-categories.index') }}" target="_blank" rel="noopener noreferrer" class="small text-decoration-none fw-medium">Manage categories</a>
                    </div>
                    <select class="form-select form-select-theme" id="daybook_modal_party_sub_cat_category_id">
                        <option value="">Select category</option>
                        @foreach($partyCategories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @if($partyCategories->isEmpty())
                        <p class="text-muted small mt-2 mb-0">No party categories yet. Add one under <strong>Manage categories</strong>.</p>
                    @endif
                </div>
                <div class="mb-0">
                    <label class="form-label" for="daybook_modal_party_sub_cat_name">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme" id="daybook_modal_party_sub_cat_name" placeholder="e.g. Seller, Buyer" autocomplete="off" maxlength="255">
                </div>
                <p class="text-danger small mt-3 mb-0 d-none" id="daybook_modal_party_sub_cat_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-pink flex-grow-1 flex-sm-grow-0" id="daybook_modal_party_sub_cat_submit">Save</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="daybookCreatePurchaseFileModal" tabindex="-1" aria-labelledby="daybookCreatePurchaseFileModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="daybookCreatePurchaseFileModalLabel">Create sale file</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="daybook-project-modal-panel mb-0">
                    <label class="daybook-modal-label" for="daybook_modal_file_project_name">Project</label>
                    <input type="text" class="form-control form-control-theme" id="daybook_modal_file_project_name" readonly tabindex="-1">
                    <input type="hidden" id="daybook_modal_file_project_id" value="">
                </div>
                <div class="daybook-project-modal-panel mt-3 mb-0">
                    <label class="daybook-modal-label" for="daybook_modal_file_name">File name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-theme" id="daybook_modal_file_name" placeholder="e.g. 23 kanal 5 marla, DHA block A" autocomplete="off" maxlength="255">
                </div>
                <div class="daybook-project-modal-panel mt-3 mb-0">
                    <label class="daybook-modal-label" for="daybook_modal_file_date">File date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control form-control-theme" id="daybook_modal_file_date" value="{{ now()->toDateString() }}">
                </div>
                <p class="text-muted small mt-3 mb-0">Dealers can be added later from Purchase files.</p>
                <p class="text-danger small mt-2 mb-0 d-none" id="daybook_modal_file_error" role="alert"></p>
            </div>
            <div class="modal-footer flex-nowrap gap-2 align-items-center">
                <button type="button" class="btn btn-outline-theme flex-grow-1 flex-sm-grow-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="daybook-save-record text-nowrap ms-sm-auto" id="daybook_modal_file_primary" aria-label="Create sale file">
                    <span class="daybook-save-record__idle">
                        <span id="daybook_modal_file_primary_label">Create file</span>
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
