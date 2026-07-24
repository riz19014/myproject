{{-- Shared new/edit entry fields; expects $daybookProjectsJson, $projects, $parties, $partySubCategories --}}
<form method="post" action="{{ $daybookFormAction }}" id="daybook-entry-form">
    @csrf
    @if(!empty($daybookFormUsePut))
        @method('PUT')
    @endif
    @if(!empty($daybookReturnDate))
        <input type="hidden" name="return_date" value="{{ $daybookReturnDate }}">
    @endif

    <div class="daybook-panel">
        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="daybook_form_project_search">Project</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-pink daybook-sale-open-btn px-2 py-0" id="daybook_form_sale_open" aria-label="Record sale" title="Sale — pick project, then file or plot">
                            <i class="bi bi-tag-fill" aria-hidden="true"></i>
                            <span>Sale</span>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-theme daybook-field-add-btn px-2 py-0" id="daybook_form_project_create" aria-label="Create project" title="Create project"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="daybook_form_project_reset" aria-label="Clear project">Reset</button>
                    </div>
                </div>
                <div class="daybook-form-combo @error('project_id') is-invalid @enderror">
                    <input type="hidden" name="project_id" id="daybook_form_project_id" value="{{ old('project_id', $daybookProjectIdDefault ?? '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('project_id') is-invalid @enderror"
                        id="daybook_form_project_search"
                        placeholder="Search project…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="daybook_form_project_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="daybook_form_project_listbox" role="listbox" hidden></ul>
                </div>
                @error('project_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <div class="mt-2" id="daybook_form_file_wrap">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                        <label class="form-label daybook-label mb-0" for="daybook_form_purchase_file_search">Sale file <span class="text-muted fw-normal">(optional)</span></label>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-theme daybook-field-add-btn px-2 py-0 d-none" id="daybook_form_file_create" aria-label="Create sale file" title="Create sale file" disabled><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="daybook_form_file_reset" aria-label="Clear sale file">Reset</button>
                        </div>
                    </div>
                    <div class="daybook-form-combo @error('purchase_file_id') is-invalid @enderror">
                        <input type="hidden" name="purchase_file_id" id="daybook_form_purchase_file_id" value="{{ old('purchase_file_id', $daybookPurchaseFileIdDefault ?? '') }}">
                        <input
                            type="text"
                            class="form-control form-control-theme @error('purchase_file_id') is-invalid @enderror"
                            id="daybook_form_purchase_file_search"
                            placeholder="Select project first…"
                            autocomplete="off"
                            role="combobox"
                            aria-expanded="false"
                            aria-controls="daybook_form_purchase_file_listbox"
                            aria-autocomplete="list"
                            disabled
                        >
                        <ul class="daybook-form-combo-list d-none" id="daybook_form_purchase_file_listbox" role="listbox" hidden></ul>
                    </div>
                    @error('purchase_file_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div id="daybook_sale_file_meta" class="small text-muted mt-2 d-none">
                        <div><span class="fw-semibold text-body">Available:</span> <span id="daybook_sale_file_remaining">—</span></div>
                        <div><span class="fw-semibold text-body">Total / Sold:</span> <span id="daybook_sale_file_totals">—</span></div>
                        <div><span class="fw-semibold text-body">Status:</span> <span id="daybook_sale_file_status">—</span></div>
                        <div id="daybook_sale_file_sellers_wrap" class="d-none"><span class="fw-semibold text-body">Sellers:</span> <span id="daybook_sale_file_sellers">—</span></div>
                    </div>
                    <div id="daybook_sold_area_wrap" class="row g-2 mt-2 d-none">
                        <div class="col-7">
                            <label class="form-label daybook-label" for="daybook_sold_area_qty">Area sold</label>
                            <input
                                type="number"
                                name="sold_area_qty"
                                id="daybook_sold_area_qty"
                                class="form-control form-control-theme @error('sold_area_qty') is-invalid @enderror"
                                value="{{ old('sold_area_qty', $daybookSoldAreaQtyDefault ?? '') }}"
                                min="0"
                                step="0.0001"
                                inputmode="decimal"
                                placeholder="0"
                                autocomplete="off"
                            >
                            @error('sold_area_qty')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-5">
                            <label class="form-label daybook-label" for="daybook_sold_area_unit">Unit</label>
                            @php($daybookSoldAreaUnitOld = old('sold_area_unit', $daybookSoldAreaUnitDefault ?? 'marla'))
                            <select
                                name="sold_area_unit"
                                id="daybook_sold_area_unit"
                                class="form-select form-select-theme @error('sold_area_unit') is-invalid @enderror"
                            >
                                <option value="marla" @selected($daybookSoldAreaUnitOld === 'marla')>Marla</option>
                                <option value="kanal" @selected($daybookSoldAreaUnitOld === 'kanal')>Kanal</option>
                                <option value="acre" @selected($daybookSoldAreaUnitOld === 'acre')>Acre</option>
                                <option value="sqft" @selected($daybookSoldAreaUnitOld === 'sqft')>Sq. Ft.</option>
                            </select>
                            @error('sold_area_unit')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-12">
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="daybook_sold_area_fill_remaining">Sell full available balance</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="daybook_form_party_search">Party</label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-theme daybook-field-add-btn px-2 py-0" id="daybook_form_party_create" aria-label="Create party" title="Create party"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="daybook_form_party_reset" aria-label="Clear party">Reset</button>
                    </div>
                </div>
                <div class="daybook-form-combo @error('party_id') is-invalid @enderror">
                    <input type="hidden" name="party_id" id="daybook_form_party_id" value="{{ old('party_id', $daybookPartyIdDefault ?? '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('party_id') is-invalid @enderror"
                        id="daybook_form_party_search"
                        placeholder="Search party…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="daybook_form_party_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="daybook_form_party_listbox" role="listbox" hidden></ul>
                </div>
                @error('party_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-12 col-lg-4" id="daybook_party_sub_category_wrap">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="daybook_form_party_sub_search">Sub category <span class="text-muted fw-normal">(optional)</span></label>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-theme daybook-field-add-btn px-2 py-0" id="daybook_form_party_sub_create" aria-label="Create sub category" title="Create sub category"><i class="bi bi-plus-lg" aria-hidden="true"></i></button>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="daybook_form_party_sub_reset" aria-label="Clear sub category">Reset</button>
                    </div>
                </div>
                <div class="daybook-form-combo @error('party_sub_category_id') is-invalid @enderror">
                    <input type="hidden" name="party_sub_category_id" id="daybook_form_party_sub_category_id" value="{{ old('party_sub_category_id', $daybookPartySubCategoryIdDefault ?? '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('party_sub_category_id') is-invalid @enderror"
                        id="daybook_form_party_sub_search"
                        placeholder="Category — sub category…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="daybook_form_party_sub_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="daybook_form_party_sub_listbox" role="listbox" hidden></ul>
                </div>
                @error('party_sub_category_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
        <script type="application/json" id="daybook-form-projects-json">@json($daybookProjectsJson)</script>
        <script type="application/json" id="daybook-form-factory-sub-json">@json($factoryConstructionSubCategoriesJson ?? collect())</script>
        <script type="application/json" id="daybook-form-purchase-file-default">@json(old('purchase_file_id', $daybookPurchaseFileIdDefault ?? ''))</script>
        <script type="application/json" id="daybook-form-parties-json">@json($parties->map(function ($p) {
            return ['id' => $p->id, 'label' => $p->name, 'sub_category_id' => $p->sub_category_id];
        })->values())</script>
        <script type="application/json" id="daybook-form-party-sub-json">@json($partySubCategories->map(function ($sc) {
            return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
        })->values())</script>
        <script type="application/json" id="daybook-form-banks-json">@json(collect(config('pakistan_banks'))->values()->map(function ($name) {
            return ['id' => $name, 'label' => $name];
        })->values())</script>
    </div>

    <div class="daybook-panel mb-0">
        <div class="row g-4 mb-0">
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label" for="entry_type">Payment</label>
                <select id="entry_type" name="type" class="form-select form-select-theme" required>
                    <option value="cash_in" @selected(old('type', $daybookTypeDefault ?? 'cash_out') === 'cash_in')>Payment in</option>
                    <option value="cash_out" @selected(old('type', $daybookTypeDefault ?? 'cash_out') === 'cash_out')>Payment out</option>
                </select>
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label" for="entry_date_input">Date</label>
                <input
                    id="entry_date_input"
                    type="text"
                    name="entry_date"
                    class="form-control form-control-theme"
                    value="{{ old('entry_date', $daybookEntryDate ?? now()->toDateString()) }}"
                    inputmode="none"
                    autocomplete="off"
                    readonly
                    required
                >
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label" for="entry_description">Description <span class="text-muted fw-normal">(optional)</span></label>
                <input id="entry_description" type="text" name="description" class="form-control form-control-theme" placeholder="e.g. Office supplies" value="{{ old('description', $daybookDescriptionDefault ?? '') }}" autocomplete="off">
            </div>
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label daybook-amount-label" for="entry_amount">
                    <span>Amount (Rs)</span>
                    <span class="daybook-amount-words" id="entry_amount_words" aria-live="polite"></span>
                </label>
                <input
                    id="entry_amount"
                    type="text"
                    name="amount"
                    class="form-control form-control-theme"
                    placeholder="0.00"
                    inputmode="decimal"
                    autocomplete="off"
                    value="{{ old('amount', $daybookAmountDefault ?? '') }}"
                    required
                >
            </div>
        </div>

        {{-- Optional Construction / Builder expense fields (any project) --}}
        @php($daybookConstructionBuilderOld = old('construction_builder', (!empty($daybookFactorySubCategoryIdDefault ?? null) || !empty($daybookFactoryQuantityDefault ?? null) || !empty($daybookFactoryUnitPriceDefault ?? null)) ? '1' : ''))
        <div class="daybook-construction-section d-none mt-1 pt-3 border-top border-secondary border-opacity-25" id="daybook_factory_fields">
            <div class="daybook-construction-section__card">
                <div class="daybook-construction-section__check">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        value="1"
                        id="daybook_construction_builder"
                        name="construction_builder"
                        @checked((string) $daybookConstructionBuilderOld === '1')
                    >
                    <div class="daybook-construction-section__check-body">
                        <label class="form-check-label fw-semibold" for="daybook_construction_builder">
                            Construction / Builder
                        </label>
                        <div class="form-text mb-0">Enable for material / construction lines (sub category, unit, quantity &amp; unit price).</div>
                    </div>
                </div>

                <div class="row g-4 mt-1 {{ (string) $daybookConstructionBuilderOld === '1' ? '' : 'd-none' }}" id="daybook_construction_builder_fields">
                    <div class="col-md-6">
                        <label class="form-label daybook-label" for="daybook_factory_sub_search">Sub Category</label>
                        <div class="daybook-form-combo @error('sub_category_id') is-invalid @enderror">
                            <input type="hidden" name="sub_category_id" id="daybook_factory_sub_category_id" value="{{ old('sub_category_id', $daybookFactorySubCategoryIdDefault ?? '') }}">
                            <input
                                type="text"
                                class="form-control form-control-theme @error('sub_category_id') is-invalid @enderror"
                                id="daybook_factory_sub_search"
                                placeholder="Search sub category…"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_factory_sub_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-form-combo-list d-none" id="daybook_factory_sub_listbox" role="listbox" hidden></ul>
                        </div>
                        @error('sub_category_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label daybook-label" for="daybook_factory_unit">Unit <span class="text-muted fw-normal">(auto-filled, editable)</span></label>
                        <div class="daybook-form-combo @error('unit') is-invalid @enderror">
                            <input
                                id="daybook_factory_unit"
                                name="unit"
                                type="text"
                                class="form-control form-control-theme @error('unit') is-invalid @enderror"
                                value="{{ old('unit', $daybookFactoryUnitDefault ?? '') }}"
                                placeholder="e.g. Bag, Kg, CFT"
                                autocomplete="off"
                                role="combobox"
                                aria-expanded="false"
                                aria-controls="daybook_factory_unit_listbox"
                                aria-autocomplete="list"
                            >
                            <ul class="daybook-form-combo-list d-none" id="daybook_factory_unit_listbox" role="listbox" hidden></ul>
                        </div>
                        <script type="application/json" id="daybook-form-units-json">@json(array_values(config('construction_units', [])))</script>
                        @error('unit')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label daybook-label" for="daybook_factory_quantity">Quantity</label>
                        <input
                            id="daybook_factory_quantity"
                            type="number"
                            name="quantity"
                            class="form-control form-control-theme @error('quantity') is-invalid @enderror"
                            value="{{ old('quantity', $daybookFactoryQuantityDefault ?? '') }}"
                            min="1"
                            step="1"
                            inputmode="numeric"
                            autocomplete="off"
                        >
                        @error('quantity')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label daybook-label" for="daybook_factory_unit_price">Unit Price</label>
                        <input
                            id="daybook_factory_unit_price"
                            type="number"
                            name="unit_price"
                            class="form-control form-control-theme @error('unit_price') is-invalid @enderror"
                            value="{{ old('unit_price', $daybookFactoryUnitPriceDefault ?? '') }}"
                            min="0"
                            step="0.01"
                            inputmode="decimal"
                            autocomplete="off"
                        >
                        @error('unit_price')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text mb-0">Amount is filled as Quantity × Unit Price (you can still edit Amount).</div>
                    </div>
                </div>
            </div>
        </div>
        @php($daybookPaymentMethodOld = old('payment_method', $daybookPaymentMethodDefault ?? 'cash'))
        @php($daybookPaymentReferenceLabel = $daybookPaymentMethodOld === 'payorder' ? 'Pay order reference #' : ($daybookPaymentMethodOld === 'cash_deposit' ? 'Deposit reference #' : 'Cheque #'))
        @php($daybookPaymentReferencePlaceholder = $daybookPaymentMethodOld === 'payorder' ? 'Reference number' : ($daybookPaymentMethodOld === 'cash_deposit' ? 'Deposit slip / reference number' : 'Cheque number'))
        {{-- Paid by temporarily hidden from daybook UI
        @php($daybookPaidByPartyIdOld = old('paid_by_party_id', $daybookPaidByPartyIdDefault ?? ''))
        --}}
        <div class="row g-4 mt-1 pt-3 border-top border-secondary border-opacity-25">
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label" for="entry_payment_method">Settlement</label>
                <select id="entry_payment_method" name="payment_method" class="form-select form-select-theme @error('payment_method') is-invalid @enderror" required>
                    <option value="cash" @selected($daybookPaymentMethodOld === 'cash')>Cash payment</option>
                    <option value="online" @selected($daybookPaymentMethodOld === 'online')>Online payment</option>
                    <option value="cheque" @selected($daybookPaymentMethodOld === 'cheque')>Cheque</option>
                    <option value="payorder" @selected($daybookPaymentMethodOld === 'payorder')>Pay order</option>
                    <option value="cash_deposit" @selected($daybookPaymentMethodOld === 'cash_deposit')>Cash Deposit to Bank</option>
                </select>
                @error('payment_method')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            {{-- Paid by temporarily hidden from daybook UI
            <div class="col-md-6 col-xl-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="entry_paid_by_party_search">Paid by <span class="text-muted fw-normal">(optional)</span></label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="entry_paid_by_party_reset" aria-label="Clear paid by">Reset</button>
                </div>
                <div class="daybook-form-combo @error('paid_by_party_id') is-invalid @enderror">
                    <input type="hidden" name="paid_by_party_id" id="entry_paid_by_party_id" value="{{ $daybookPaidByPartyIdOld }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('paid_by_party_id') is-invalid @enderror"
                        id="entry_paid_by_party_search"
                        placeholder="Who paid? Search party…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="entry_paid_by_party_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="entry_paid_by_party_listbox" role="listbox" hidden></ul>
                </div>
                @error('paid_by_party_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            --}}
            <div class="col-md-6 col-xl-3 {{ in_array($daybookPaymentMethodOld, ['online', 'cheque', 'payorder', 'cash_deposit'], true) ? '' : 'd-none' }}" id="entry_payment_bank_row">
                <label class="form-label daybook-label" for="entry_payment_bank_search">Bank</label>
                <div class="daybook-form-combo @error('payment_bank') is-invalid @enderror">
                    <input type="hidden" name="payment_bank" id="entry_payment_bank" value="{{ old('payment_bank', $daybookPaymentBankDefault ?? '') }}">
                    <input
                        type="text"
                        class="form-control form-control-theme @error('payment_bank') is-invalid @enderror"
                        id="entry_payment_bank_search"
                        placeholder="Search bank…"
                        autocomplete="off"
                        role="combobox"
                        aria-expanded="false"
                        aria-controls="entry_payment_bank_listbox"
                        aria-autocomplete="list"
                    >
                    <ul class="daybook-form-combo-list d-none" id="entry_payment_bank_listbox" role="listbox" hidden></ul>
                </div>
                @error('payment_bank')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 col-xl-3 {{ in_array($daybookPaymentMethodOld, ['cheque', 'payorder', 'cash_deposit'], true) ? '' : 'd-none' }}" id="entry_payment_reference_row">
                <label class="form-label daybook-label" for="entry_payment_reference" id="entry_payment_reference_label">{{ $daybookPaymentReferenceLabel }}</label>
                <input type="text" id="entry_payment_reference" name="payment_reference" class="form-control form-control-theme @error('payment_reference') is-invalid @enderror" placeholder="{{ $daybookPaymentReferencePlaceholder }}" value="{{ old('payment_reference', $daybookPaymentReferenceDefault ?? '') }}" maxlength="100" autocomplete="off">
                @error('payment_reference')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</form>
