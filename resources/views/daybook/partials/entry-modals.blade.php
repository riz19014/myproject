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
                    return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
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
