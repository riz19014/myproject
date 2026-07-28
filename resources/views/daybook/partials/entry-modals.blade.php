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

{{-- Sale wizard: Project → File (DHA) / Plot (non-DHA) → Details --}}
<div class="modal fade" id="daybookSaleWizardModal" tabindex="-1" aria-labelledby="daybookSaleWizardModalLabel" aria-hidden="true" data-bs-focus="false">
    <div class="modal-dialog daybook-sale-wizard-dialog modal-fullscreen-sm-down">
        <div class="modal-content daybook-sale-wizard">
            <div class="modal-header daybook-sale-wizard__header">
                <div class="daybook-sale-wizard__brand">
                    <span class="daybook-sale-wizard__icon" aria-hidden="true"><i class="bi bi-tag-fill"></i></span>
                    <div>
                        <h2 class="modal-title" id="daybookSaleWizardModalLabel">Record sale</h2>
                        <p class="daybook-sale-wizard__subtitle mb-0" id="daybook_sale_wizard_subtitle">Select a project to begin.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body daybook-sale-wizard__body">
                <ol class="daybook-sale-steps" aria-label="Sale steps">
                    <li class="daybook-sale-steps__item is-active" data-step="1">
                        <span class="daybook-sale-steps__num">1</span>
                        <span class="daybook-sale-steps__text">
                            <span class="daybook-sale-steps__title">Project</span>
                            <span class="daybook-sale-steps__desc">Choose project</span>
                        </span>
                    </li>
                    <li class="daybook-sale-steps__connector" aria-hidden="true"></li>
                    <li class="daybook-sale-steps__item" data-step="2">
                        <span class="daybook-sale-steps__num">2</span>
                        <span class="daybook-sale-steps__text">
                            <span class="daybook-sale-steps__title" id="daybook_sale_step2_label">File / Plot</span>
                            <span class="daybook-sale-steps__desc">Pick what to sell</span>
                        </span>
                    </li>
                    <li class="daybook-sale-steps__connector" aria-hidden="true"></li>
                    <li class="daybook-sale-steps__item" data-step="3">
                        <span class="daybook-sale-steps__num">3</span>
                        <span class="daybook-sale-steps__text">
                            <span class="daybook-sale-steps__title">Details</span>
                            <span class="daybook-sale-steps__desc">Stamp, buyer &amp; plot</span>
                        </span>
                    </li>
                </ol>

                <div class="daybook-sale-panel daybook-sale-card" data-sale-panel="1">
                    <div class="daybook-sale-card__head">
                        <h3 class="daybook-sale-card__title">Select project</h3>
                        <p class="daybook-sale-card__hint mb-0">🟢 DHA sells <strong>files</strong> · 🟡 Non-DHA sells <strong>plots</strong></p>
                    </div>
                    <label class="daybook-modal-label" for="daybook_sale_project_search">Project</label>
                    <div class="daybook-form-combo daybook-sale-combo mb-0">
                        <input type="hidden" id="daybook_sale_project_id" value="">
                        <input
                            type="text"
                            class="form-control form-control-theme form-control-lg"
                            id="daybook_sale_project_search"
                            placeholder="Search project…"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="daybook_sale_project_listbox"
                            aria-autocomplete="list"
                        >
                        <ul class="daybook-form-combo-list d-none" id="daybook_sale_project_listbox" role="listbox" hidden></ul>
                    </div>
                </div>

                <div class="daybook-sale-panel daybook-sale-card d-none" data-sale-panel="2">
                    <div class="daybook-sale-card__head d-flex flex-wrap align-items-start justify-content-between gap-2">
                        <div>
                            <h3 class="daybook-sale-card__title mb-1" id="daybook_sale_item_label">Sale file</h3>
                            <p class="daybook-sale-card__hint mb-0">Search and select an available item</p>
                        </div>
                        <span class="daybook-sale-mode-badge" id="daybook_sale_mode_badge">File sale</span>
                    </div>
                    <label class="daybook-modal-label visually-hidden" for="daybook_sale_item_search">Search</label>
                    <div class="daybook-form-combo daybook-sale-combo mb-3">
                        <input type="hidden" id="daybook_sale_item_id" value="">
                        <input
                            type="text"
                            class="form-control form-control-theme form-control-lg"
                            id="daybook_sale_item_search"
                            placeholder="Search…"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="daybook_sale_item_listbox"
                            aria-autocomplete="list"
                        >
                        <ul class="daybook-form-combo-list d-none" id="daybook_sale_item_listbox" role="listbox" hidden></ul>
                    </div>
                    <div id="daybook_sale_item_meta" class="daybook-sale-item-meta d-none">
                        <div class="daybook-sale-stat">
                            <span class="daybook-sale-stat__label">Available</span>
                            <span class="daybook-sale-stat__value" id="daybook_sale_item_remaining">—</span>
                        </div>
                        <div class="daybook-sale-stat">
                            <span class="daybook-sale-stat__label">Total / Sold</span>
                            <span class="daybook-sale-stat__value" id="daybook_sale_item_totals">—</span>
                        </div>
                        <div class="daybook-sale-stat" id="daybook_sale_item_status_wrap">
                            <span class="daybook-sale-stat__label">Status</span>
                            <span class="daybook-sale-stat__value" id="daybook_sale_item_status">—</span>
                        </div>
                    </div>
                </div>

                <div class="daybook-sale-panel d-none" data-sale-panel="3">
                    <div id="daybook_sale_details_file" class="d-none">
                        <div class="daybook-sale-card mb-3">
                            <div class="daybook-sale-card__head">
                                <h3 class="daybook-sale-card__title">File sale details</h3>
                                <p class="daybook-sale-card__hint mb-0" id="daybook_sale_file_summary">—</p>
                            </div>

                            <div class="daybook-sale-land-grid" id="daybook_sale_land_grid" aria-live="polite">
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Total land</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_total_land">—</span>
                                </div>
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Remaining</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_remaining">—</span>
                                </div>
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Mouza</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_moza">—</span>
                                </div>
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Khewat No.</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_khewat">—</span>
                                </div>
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Khatoni No.</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_khatooni">—</span>
                                </div>
                                <div class="daybook-sale-land-stat">
                                    <span class="daybook-sale-land-stat__label">Khasra No.</span>
                                    <span class="daybook-sale-land-stat__value" id="daybook_sale_info_khasra">—</span>
                                </div>
                            </div>
                        </div>

                        <div class="daybook-sale-card mb-3">
                            <div class="daybook-sale-card__head">
                                <h3 class="daybook-sale-card__title">Parties &amp; stamp</h3>
                                <p class="daybook-sale-card__hint mb-0">Owner and provider load from the selected file</p>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="daybook-modal-label" for="daybook_sale_land_owner">Land owner name</label>
                                    <input type="text" class="form-control form-control-theme form-control-lg" id="daybook_sale_land_owner" readonly tabindex="-1">
                                </div>
                                <div class="col-md-6">
                                    <label class="daybook-modal-label" for="daybook_sale_land_provider">Land provider name</label>
                                    <input type="text" class="form-control form-control-theme form-control-lg" id="daybook_sale_land_provider" readonly tabindex="-1">
                                </div>
                                <div class="col-md-6">
                                    <label class="daybook-modal-label" for="daybook_sale_e_stamp_id">eStamp ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-theme form-control-lg" id="daybook_sale_e_stamp_id" placeholder="Enter eStamp ID" autocomplete="off" maxlength="120">
                                </div>
                                <div class="col-md-6">
                                    <label class="daybook-modal-label" for="daybook_sale_purchaser_name">File purchaser name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-theme form-control-lg" id="daybook_sale_purchaser_name" placeholder="Purchaser full name" autocomplete="off" maxlength="255">
                                </div>
                            </div>
                        </div>

                        <div class="daybook-sale-card mb-3">
                            <div class="daybook-sale-card__head">
                                <h3 class="daybook-sale-card__title">Land sold to purchaser</h3>
                                <p class="daybook-sale-card__hint mb-0">Choose a plot size from the formula options available on this file</p>
                            </div>
                            <div id="daybook_sale_plot_options" class="daybook-sale-plot-options" role="listbox" aria-label="Available plot sizes"></div>
                            <input type="hidden" id="daybook_sale_component" value="">
                            <input type="hidden" id="daybook_sale_plot_type" value="">
                            <div class="row g-3 mt-1">
                                <div class="col-sm-4 col-md-3">
                                    <label class="daybook-modal-label" for="daybook_sale_plot_qty">Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_qty" min="1" max="999" step="1" value="1" inputmode="numeric">
                                    <div class="form-text" id="daybook_sale_plot_qty_hint"></div>
                                </div>
                                <div class="col-sm-8 col-md-4 d-flex align-items-end">
                                    <div class="daybook-sale-selected-plot w-100" id="daybook_sale_selected_plot_meta">
                                        <span class="daybook-sale-selected-plot__label">Selected</span>
                                        <span class="daybook-sale-selected-plot__value" id="daybook_sale_selected_plot_label">—</span>
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="daybook-modal-label" for="daybook_sale_file_amount">Amount (Rs) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_file_amount" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" autocomplete="off">
                                </div>
                                <div class="col-md-4">
                                    <label class="daybook-modal-label" for="daybook_sale_status">Status <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-theme form-select-lg" id="daybook_sale_status">
                                        <option value="complete" selected>Complete</option>
                                        <option value="pending">Pending</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <label class="daybook-modal-label" for="daybook_sale_file_note">Note <span class="text-muted fw-normal">(optional)</span></label>
                                    <input type="text" class="form-control form-control-theme form-control-lg" id="daybook_sale_file_note" placeholder="Optional note" autocomplete="off" maxlength="2000">
                                </div>
                                <div class="col-12">
                                    <label class="daybook-modal-label" for="daybook_sale_documents">Documents <span class="text-muted fw-normal">(optional)</span></label>
                                    <div class="daybook-sale-docs">
                                        <label class="daybook-sale-docs__picker" for="daybook_sale_documents">
                                            <i class="bi bi-cloud-upload" aria-hidden="true"></i>
                                            <span class="daybook-sale-docs__picker-title">Add documents</span>
                                            <span class="daybook-sale-docs__picker-hint">PDF, images, or Word · max 10 MB each · multiple allowed</span>
                                        </label>
                                        <input type="file" class="visually-hidden" id="daybook_sale_documents" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx">
                                        <ul class="daybook-sale-docs__list d-none" id="daybook_sale_documents_list" aria-live="polite"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p class="daybook-sale-card__foot mb-0">Saves to Sold Land Files and reduces formula balance. Then fills Daybook as <strong>Payment in</strong> (choose party and save the entry).</p>
                    </div>

                    <div id="daybook_sale_details_plot" class="daybook-sale-card d-none">
                        <div class="daybook-sale-card__head">
                            <h3 class="daybook-sale-card__title">Plot sale details</h3>
                            <p class="daybook-sale-card__hint mb-0" id="daybook_sale_plot_summary">—</p>
                        </div>
                        <div class="row g-3 g-lg-4">
                            <div class="col-12 col-lg-6">
                                <label class="daybook-modal-label" for="daybook_sale_customer_id">Customer <span class="text-danger">*</span></label>
                                <select class="form-select form-select-theme form-select-lg" id="daybook_sale_customer_id">
                                    <option value="">Select customer</option>
                                    @foreach(($customers ?? collect()) as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @if(($customers ?? collect())->isEmpty())
                                    <div class="form-text">No customers yet. <a href="{{ route('customers.create') }}" target="_blank" rel="noopener">Add a customer</a> first.</div>
                                @endif
                            </div>
                            <div class="col-12 col-lg-6">
                                <label class="daybook-modal-label" for="daybook_sale_plot_amount">Amount (Rs) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_amount" min="0.01" step="0.01" inputmode="decimal" placeholder="0.00" autocomplete="off">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="daybook-modal-label" for="daybook_sale_plot_acre">Acre</label>
                                <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_acre" value="0" min="0" step="1">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="daybook-modal-label" for="daybook_sale_plot_kanal">Kanal</label>
                                <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_kanal" value="0" min="0" step="1">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="daybook-modal-label" for="daybook_sale_plot_marla">Marla</label>
                                <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_marla" value="0" min="0" step="1">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="daybook-modal-label" for="daybook_sale_plot_sqft">Sq ft</label>
                                <input type="number" class="form-control form-control-theme form-control-lg" id="daybook_sale_plot_sqft" value="0" min="0" step="1">
                            </div>
                        </div>
                        <p class="daybook-sale-card__foot mb-0">Saves the plot sale, then fills the entry as <strong>Payment in</strong>. Choose party and save after.</p>
                    </div>
                </div>

                <p class="daybook-sale-wizard__error d-none" id="daybook_sale_wizard_error" role="alert"></p>
            </div>
            <div class="modal-footer daybook-sale-wizard__footer">
                <button type="button" class="btn btn-outline-theme btn-lg" id="daybook_sale_wizard_back" disabled>Back</button>
                <div class="daybook-sale-wizard__footer-actions">
                    <button type="button" class="btn btn-outline-theme btn-lg" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="daybook-save-record daybook-sale-wizard__primary btn-lg" id="daybook_sale_wizard_primary">
                        <span class="daybook-save-record__idle">
                            <span id="daybook_sale_wizard_primary_label">Next</span>
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
</div>
