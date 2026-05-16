@include('partials.party-form-field-scripts')
<script>
(function () {
    var modalEl = document.getElementById('purchaseFileAddDealerModal');
    var openBtn = document.getElementById('purchase_file_add_dealer_btn');
    var nameInput = document.getElementById('pf_dealer_name');
    var subHidden = document.getElementById('pf_dealer_sub_category_id');
    var subSearch = document.getElementById('pf_dealer_sub_search');
    var subList = document.getElementById('pf_dealer_sc_listbox');
    var subJsonEl = document.getElementById('pf_dealer_sub_json');
    var phoneInput = document.getElementById('pf_dealer_phone');
    var cnicInput = document.getElementById('pf_dealer_cnic');
    var addressInput = document.getElementById('pf_dealer_address');
    var saveBtn = document.getElementById('pf_dealer_save_btn');
    var spinner = document.getElementById('pf_dealer_save_spinner');
    var errEl = document.getElementById('pf_dealer_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    var PF = window.PartyFormFields;

    if (!modalEl || !openBtn || !saveBtn || !token || typeof bootstrap === 'undefined' || !PF) return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var partySubRows = [];
    if (subJsonEl) {
        try {
            partySubRows = JSON.parse(subJsonEl.textContent) || [];
        } catch (e) {
            partySubRows = [];
        }
    }

    var subCombo = PF.initSubCategoryCombo({
        wrap: document.getElementById('pf_dealer_sc_wrap'),
        hidden: subHidden,
        search: subSearch,
        list: subList,
        rows: partySubRows
    });

    PF.bindPhoneInput(phoneInput, 11);
    PF.bindCnicInput(cnicInput);

    function showErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function resetForm() {
        if (nameInput) nameInput.value = '';
        if (subCombo) subCombo.clear();
        if (phoneInput) phoneInput.value = '';
        if (cnicInput) cnicInput.value = '';
        if (addressInput) addressInput.value = '';
        showErr('');
    }

    function setSaving(on) {
        saveBtn.disabled = on;
        if (spinner) spinner.classList.toggle('d-none', !on);
    }

    openBtn.addEventListener('click', function () {
        resetForm();
        modal.show();
        setTimeout(function () { if (nameInput) nameInput.focus(); }, 200);
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        resetForm();
        setSaving(false);
    });

    saveBtn.addEventListener('click', function () {
        showErr('');
        var name = (nameInput && nameInput.value || '').trim();
        var subId = subHidden ? subHidden.value : '';
        var phone = (phoneInput && phoneInput.value || '').trim();
        var cnicDigitsVal = PF.cnicDigits(cnicInput && cnicInput.value);
        var address = (addressInput && addressInput.value || '').trim();

        if (!name) {
            showErr('Please enter a name.');
            if (nameInput) nameInput.focus();
            return;
        }
        if (!subId) {
            showErr('Please select a party sub category.');
            if (subSearch) subSearch.focus();
            return;
        }
        if (phone && phone.length !== 11) {
            showErr('Phone must be exactly 11 digits.');
            if (phoneInput) phoneInput.focus();
            return;
        }
        if (cnicDigitsVal && cnicDigitsVal.length !== PF.cnicMaxDigits) {
            showErr('CNIC must be 13 digits in format 23012-2321373-1.');
            if (cnicInput) cnicInput.focus();
            return;
        }

        setSaving(true);
        fetch(@json(route('parties.quick-store')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                name: name,
                sub_category_id: parseInt(subId, 10),
                phone: phone || null,
                cnic: cnicDigitsVal || null,
                address: address || null
            })
        })
            .then(function (res) {
                return res.json().then(function (data) {
                    return { ok: res.ok, status: res.status, data: data };
                }).catch(function () {
                    return { ok: false, status: 0, data: {} };
                });
            })
            .then(function (result) {
                setSaving(false);
                if (result.ok && result.data && result.data.id) {
                    if (window.PurchaseFileDealersSelect && typeof window.PurchaseFileDealersSelect.addDealer === 'function') {
                        window.PurchaseFileDealersSelect.addDealer(result.data.id, result.data.name);
                    }
                    modal.hide();
                    return;
                }
                var msg = 'Could not save dealer.';
                if (result.data && result.data.message) msg = result.data.message;
                if (result.data && result.data.errors) {
                    var parts = [];
                    Object.keys(result.data.errors).forEach(function (k) {
                        parts = parts.concat(result.data.errors[k]);
                    });
                    if (parts.length) msg = parts.join(' ');
                }
                showErr(msg);
            })
            .catch(function () {
                setSaving(false);
                showErr('Network error. Please try again.');
            });
    });
})();
</script>
