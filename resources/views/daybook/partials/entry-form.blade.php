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
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="daybook_form_project_create">+ Create new project</button>
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
            </div>
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="daybook_form_party_search">Party</label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold" id="daybook_form_party_create">+ Create new party</button>
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
            <div class="col-12 col-lg-4">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <label class="form-label daybook-label mb-0" for="daybook_form_party_sub_search">Sub category <span class="text-muted fw-normal">(optional)</span></label>
                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold d-none" id="daybook_form_party_sub_reset" aria-label="Clear sub category">Reset</button>
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
        <script type="application/json" id="daybook-form-parties-json">@json($parties->map(function ($p) {
            return ['id' => $p->id, 'label' => $p->name, 'sub_category_id' => $p->sub_category_id];
        })->values())</script>
        <script type="application/json" id="daybook-form-party-sub-json">@json($partySubCategories->map(function ($sc) {
            return ['id' => $sc->id, 'label' => ($sc->category?->name ?? '—').' — '.$sc->name];
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
        @php($daybookPaymentMethodOld = old('payment_method', $daybookPaymentMethodDefault ?? 'cash'))
        <div class="row g-4 mt-1 pt-3 border-top border-secondary border-opacity-25">
            <div class="col-md-6 col-xl-3">
                <label class="form-label daybook-label" for="entry_payment_method">Settlement</label>
                <select id="entry_payment_method" name="payment_method" class="form-select form-select-theme @error('payment_method') is-invalid @enderror" required>
                    <option value="cash" @selected($daybookPaymentMethodOld === 'cash')>Cash payment</option>
                    <option value="online" @selected($daybookPaymentMethodOld === 'online')>Online payment</option>
                    <option value="cheque" @selected($daybookPaymentMethodOld === 'cheque')>Cheque</option>
                    <option value="payorder" @selected($daybookPaymentMethodOld === 'payorder')>Pay order</option>
                </select>
                @error('payment_method')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-6 col-xl-5 {{ in_array($daybookPaymentMethodOld, ['online', 'cheque', 'payorder'], true) ? '' : 'd-none' }}" id="entry_payment_bank_row">
                <label class="form-label daybook-label" for="entry_payment_bank">Bank</label>
                <select id="entry_payment_bank" name="payment_bank" class="form-select form-select-theme @error('payment_bank') is-invalid @enderror">
                    <option value="">Select bank…</option>
                    @foreach(config('pakistan_banks') as $bankName)
                        <option value="{{ $bankName }}" @selected(old('payment_bank', $daybookPaymentBankDefault ?? '') === $bankName)>{{ $bankName }}</option>
                    @endforeach
                </select>
                @error('payment_bank')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-12 col-xl-4 {{ in_array($daybookPaymentMethodOld, ['cheque', 'payorder'], true) ? '' : 'd-none' }}" id="entry_payment_reference_row">
                <label class="form-label daybook-label" for="entry_payment_reference" id="entry_payment_reference_label">{{ $daybookPaymentMethodOld === 'payorder' ? 'Pay order reference #' : 'Cheque #' }}</label>
                <input type="text" id="entry_payment_reference" name="payment_reference" class="form-control form-control-theme @error('payment_reference') is-invalid @enderror" placeholder="{{ $daybookPaymentMethodOld === 'payorder' ? 'Reference number' : 'Cheque number' }}" value="{{ old('payment_reference', $daybookPaymentReferenceDefault ?? '') }}" maxlength="100" autocomplete="off">
                @error('payment_reference')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</form>
