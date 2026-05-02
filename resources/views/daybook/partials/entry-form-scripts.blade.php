<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js"></script>
<script>
(function () {
    /**
     * Focus a text input and set a collapsed selection so the caret shows.
     * Do not re-focus while the field already has focus — that resets the blink timer and looks “stuck”.
     * Use scheduleEnsure when opening from the project/party dropdown so we win any focus race after it closes.
     */
    function daybookModalFocusText(el, options) {
        if (!el || el.disabled) return;
        options = options || {};
        function attach() {
            el.focus();
            try {
                if (typeof el.setSelectionRange === 'function') {
                    var n = (el.value || '').length;
                    el.setSelectionRange(n, n);
                }
            } catch (ignore) {}
        }
        function ensure() {
            if (document.activeElement !== el) {
                attach();
            }
        }
        attach();
        if (options.scheduleEnsure) {
            requestAnimationFrame(function () {
                ensure();
                requestAnimationFrame(ensure);
            });
            setTimeout(ensure, 0);
            setTimeout(ensure, 80);
            setTimeout(ensure, 250);
            setTimeout(ensure, 450);
        }
    }
    window.daybookModalFocusText = daybookModalFocusText;
})();

(function () {
    var form = document.getElementById('daybook-entry-form');
    var saveBtn = document.getElementById('daybook-save-record-btn');
    if (!form || !saveBtn) return;
    form.addEventListener('submit', function () {
        if (saveBtn.classList.contains('is-loading')) return;
        saveBtn.classList.add('is-loading');
        saveBtn.disabled = true;
        saveBtn.setAttribute('aria-busy', 'true');
        saveBtn.setAttribute('aria-label', 'Saving…');
    });
})();

(function () {
    var projectHidden = document.getElementById('daybook_form_project_id');
    var projectSearch = document.getElementById('daybook_form_project_search');
    var projectList = document.getElementById('daybook_form_project_listbox');
    var projectWrap = projectSearch ? projectSearch.closest('.daybook-form-combo') : null;
    var projectJsonEl = document.getElementById('daybook-form-projects-json');
    var projectCreateBtn = document.getElementById('daybook_form_project_create');

    var partyHidden = document.getElementById('daybook_form_party_id');
    var partySearch = document.getElementById('daybook_form_party_search');
    var partyList = document.getElementById('daybook_form_party_listbox');
    var partyWrap = partySearch ? partySearch.closest('.daybook-form-combo') : null;
    var partyJsonEl = document.getElementById('daybook-form-parties-json');
    var partyCreateBtn = document.getElementById('daybook_form_party_create');

    var subHidden = document.getElementById('daybook_form_party_sub_category_id');
    var subSearch = document.getElementById('daybook_form_party_sub_search');
    var subList = document.getElementById('daybook_form_party_sub_listbox');
    var subWrap = subSearch ? subSearch.closest('.daybook-form-combo') : null;
    var subJsonEl = document.getElementById('daybook-form-party-sub-json');
    var subResetBtn = document.getElementById('daybook_form_party_sub_reset');

    if (!projectHidden || !projectSearch || !projectList || !partyHidden || !partySearch || !partyList) return;

    function syncSubResetVisibility() {
        if (!subResetBtn || !subHidden) return;
        var has = String(subHidden.value || '').trim() !== '';
        subResetBtn.classList.toggle('d-none', !has);
        subResetBtn.setAttribute('aria-hidden', has ? 'false' : 'true');
    }

    var formProjectRows = [];
    if (projectJsonEl) {
        try {
            formProjectRows = JSON.parse(projectJsonEl.textContent) || [];
        } catch (e) {
            formProjectRows = [];
        }
    }
    var formPartyRows = [];
    if (partyJsonEl) {
        try {
            formPartyRows = JSON.parse(partyJsonEl.textContent) || [];
        } catch (e) {
            formPartyRows = [];
        }
    }

    var formSubRows = [];
    if (subJsonEl && subHidden && subSearch && subList) {
        try {
            formSubRows = JSON.parse(subJsonEl.textContent) || [];
        } catch (e) {
            formSubRows = [];
        }
    }

    window.__daybookFormProjectRows = formProjectRows;
    window.__daybookFormPartyRows = formPartyRows;

    function hideProjectList() {
        projectList.classList.add('d-none');
        projectList.setAttribute('hidden', '');
        projectSearch.setAttribute('aria-expanded', 'false');
    }

    function showProjectList() {
        projectList.classList.remove('d-none');
        projectList.removeAttribute('hidden');
        projectSearch.setAttribute('aria-expanded', 'true');
    }

    function filterProjectRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formProjectRows.slice();
        return formProjectRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderProjectList(rows) {
        projectList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formProjectRows.length ? 'No projects match.' : 'No projects yet.';
            projectList.appendChild(li0);
            showProjectList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                projectHidden.value = String(row.id);
                projectSearch.value = row.label;
                hideProjectList();
            });
            li.appendChild(btn);
            projectList.appendChild(li);
        });
        showProjectList();
    }

    function openFilteredProjectList() {
        renderProjectList(filterProjectRows(projectSearch.value));
    }

    function hidePartyFormList() {
        partyList.classList.add('d-none');
        partyList.setAttribute('hidden', '');
        partySearch.setAttribute('aria-expanded', 'false');
    }

    function showPartyFormList() {
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        partySearch.setAttribute('aria-expanded', 'true');
    }

    function filterPartyFormRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formPartyRows.slice();
        return formPartyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderPartyFormList(rows) {
        partyList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formPartyRows.length ? 'No parties match.' : 'No parties yet.';
            partyList.appendChild(li0);
            showPartyFormList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                partyHidden.value = String(row.id);
                partySearch.value = row.label;
                hidePartyFormList();
                if (subHidden && subSearch) {
                    if (row.sub_category_id) {
                        var sc = formSubRows.find(function (r) { return String(r.id) === String(row.sub_category_id); });
                        if (sc) {
                            subHidden.value = String(sc.id);
                            subSearch.value = sc.label;
                        } else {
                            subHidden.value = '';
                            subSearch.value = '';
                        }
                    } else {
                        subHidden.value = '';
                        subSearch.value = '';
                    }
                    syncSubResetVisibility();
                }
            });
            li.appendChild(btn);
            partyList.appendChild(li);
        });
        showPartyFormList();
    }

    function openFilteredPartyFormList() {
        renderPartyFormList(filterPartyFormRows(partySearch.value));
    }

    function hideSubFormList() {
        if (!subList || !subSearch) return;
        subList.classList.add('d-none');
        subList.setAttribute('hidden', '');
        subSearch.setAttribute('aria-expanded', 'false');
    }

    function showSubFormList() {
        if (!subList || !subSearch) return;
        subList.classList.remove('d-none');
        subList.removeAttribute('hidden');
        subSearch.setAttribute('aria-expanded', 'true');
    }

    function filterSubFormRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return formSubRows.slice();
        return formSubRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderSubFormList(rows) {
        if (!subList || !subSearch || !subHidden) return;
        subList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = formSubRows.length ? 'No sub categories match.' : 'No sub categories yet.';
            subList.appendChild(li0);
            showSubFormList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                subHidden.value = String(row.id);
                subSearch.value = row.label;
                hideSubFormList();
                syncSubResetVisibility();
            });
            li.appendChild(btn);
            subList.appendChild(li);
        });
        showSubFormList();
    }

    function openFilteredSubFormList() {
        renderSubFormList(filterSubFormRows(subSearch.value));
    }

    (function syncOldValues() {
        if (projectHidden.value) {
            var pr = formProjectRows.find(function (r) { return String(r.id) === String(projectHidden.value); });
            if (pr) projectSearch.value = pr.label;
        }
        if (partyHidden.value) {
            var py = formPartyRows.find(function (r) { return String(r.id) === String(partyHidden.value); });
            if (py) partySearch.value = py.label;
        }
        if (subHidden && subHidden.value && subSearch) {
            var sb = formSubRows.find(function (r) { return String(r.id) === String(subHidden.value); });
            if (sb) subSearch.value = sb.label;
        }
        syncSubResetVisibility();
    })();

    projectSearch.addEventListener('focus', function () {
        openFilteredProjectList();
    });
    projectSearch.addEventListener('input', function () {
        projectHidden.value = '';
        openFilteredProjectList();
    });
    projectSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideProjectList();
        }
    });

    partySearch.addEventListener('focus', function () {
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('input', function () {
        partyHidden.value = '';
        if (subHidden && subSearch) {
            subHidden.value = '';
            subSearch.value = '';
            syncSubResetVisibility();
        }
        openFilteredPartyFormList();
    });
    partySearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hidePartyFormList();
        }
    });

    if (subHidden && subSearch && subList) {
        subSearch.addEventListener('focus', function () {
            openFilteredSubFormList();
        });
        subSearch.addEventListener('input', function () {
            subHidden.value = '';
            syncSubResetVisibility();
            openFilteredSubFormList();
        });
        subSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideSubFormList();
            }
        });
    }

    if (subResetBtn && subHidden && subSearch) {
        subResetBtn.addEventListener('click', function () {
            subHidden.value = '';
            subSearch.value = '';
            hideSubFormList();
            syncSubResetVisibility();
            subSearch.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (projectWrap && !projectWrap.contains(e.target)) hideProjectList();
        if (partyWrap && !partyWrap.contains(e.target)) hidePartyFormList();
        if (subWrap && !subWrap.contains(e.target)) hideSubFormList();
    });
})();

(function () {
    var projectFormHidden = document.getElementById('daybook_form_project_id');
    var projectFormSearch = document.getElementById('daybook_form_project_search');
    var projectFormCreateBtn = document.getElementById('daybook_form_project_create');
    var modalEl = document.getElementById('daybookCreateProjectModal');
    var nameInput = document.getElementById('daybook_modal_project_name');
    var fieldTypeSelect = document.getElementById('daybook_modal_project_field_type');
    var landTypeHidden = document.getElementById('daybook_modal_project_land_type_id');
    var landTypeSearch = document.getElementById('daybook_modal_project_land_type_search');
    var landTypeList = document.getElementById('daybook_modal_project_land_type_listbox');
    var landTypesJsonEl = document.getElementById('daybook-land-types-json');
    var partyHidden = document.getElementById('daybook_modal_project_party_id');
    var partySearch = document.getElementById('daybook_modal_project_party_search');
    var partyList = document.getElementById('daybook_modal_project_party_listbox');
    var partiesJsonEl = document.getElementById('daybook-parties-json');
    var partySelectedWrap = document.getElementById('daybook_modal_project_party_selected');
    var areaAcreInput = document.getElementById('daybook_modal_project_area_acre');
    var areaKanalInput = document.getElementById('daybook_modal_project_area_kanal');
    var areaMarlaInput = document.getElementById('daybook_modal_project_area_marla');
    var areaSqftInput = document.getElementById('daybook_modal_project_area_sqft');
    var totalAmountInput = document.getElementById('daybook_modal_project_total_amount');
    var primaryBtn = document.getElementById('daybook_modal_project_primary');
    var primaryLabel = document.getElementById('daybook_modal_project_primary_label');
    var backBtn = document.getElementById('daybook_modal_project_back');
    var errEl = document.getElementById('daybook_modal_project_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!projectFormHidden || !projectFormSearch || !projectFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });
    var maxStep = 5;
    var step = 0;

    var landTypeRows = [];
    if (landTypesJsonEl) {
        try {
            landTypeRows = JSON.parse(landTypesJsonEl.textContent) || [];
        } catch (e) {
            landTypeRows = [];
        }
    }

    function hideLandTypeList() {
        if (!landTypeList) return;
        landTypeList.classList.add('d-none');
        landTypeList.setAttribute('hidden', '');
        if (landTypeSearch) landTypeSearch.setAttribute('aria-expanded', 'false');
    }

    function showLandTypeList() {
        if (!landTypeList) return;
        landTypeList.classList.remove('d-none');
        landTypeList.removeAttribute('hidden');
        if (landTypeSearch) landTypeSearch.setAttribute('aria-expanded', 'true');
    }

    function filterLandTypeRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return landTypeRows.slice();
        return landTypeRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderLandTypeList(rows) {
        if (!landTypeList) return;
        landTypeList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-project-lt-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = landTypeRows.length ? 'No land types match.' : 'No land types yet. Add them under Manage land types.';
            landTypeList.appendChild(li0);
            showLandTypeList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (landTypeHidden) landTypeHidden.value = String(row.id);
                if (landTypeSearch) landTypeSearch.value = row.label;
                hideLandTypeList();
            });
            li.appendChild(btn);
            landTypeList.appendChild(li);
        });
        showLandTypeList();
    }

    function openFilteredLandTypeList() {
        renderLandTypeList(filterLandTypeRows(landTypeSearch ? landTypeSearch.value : ''));
    }

    function clearLandTypePicker() {
        if (landTypeHidden) landTypeHidden.value = '';
        if (landTypeSearch) {
            landTypeSearch.value = '';
            landTypeSearch.setAttribute('aria-expanded', 'false');
        }
        hideLandTypeList();
    }

    var partyRows = [];
    if (partiesJsonEl) {
        try {
            partyRows = JSON.parse(partiesJsonEl.textContent) || [];
        } catch (e) {
            partyRows = [];
        }
    }

    var selectedPartyIds = [];

    window.__daybookProjectModalPartyRowsPush = function (id, name) {
        var idStr = String(id);
        if (!partyRows.some(function (r) { return String(r.id) === idStr; })) {
            partyRows.push({ id: parseInt(id, 10), label: name });
        }
    };

    function hidePartyList() {
        if (!partyList) return;
        partyList.classList.add('d-none');
        partyList.setAttribute('hidden', '');
        if (partySearch) partySearch.setAttribute('aria-expanded', 'false');
    }

    function showPartyList() {
        if (!partyList) return;
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        if (partySearch) partySearch.setAttribute('aria-expanded', 'true');
    }

    function filterPartyRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return partyRows.slice();
        return partyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderSelectedParties() {
        if (!partySelectedWrap) return;
        partySelectedWrap.innerHTML = '';
        if (!selectedPartyIds.length) {
            var empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = 'No parties selected.';
            partySelectedWrap.appendChild(empty);
            return;
        }
        selectedPartyIds.forEach(function (id) {
            var row = partyRows.find(function (r) { return String(r.id) === String(id); });
            var label = row ? row.label : ('Party #' + id);
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'daybook-project-party-chip';
            chip.textContent = label + ' ×';
            chip.addEventListener('click', function () {
                selectedPartyIds = selectedPartyIds.filter(function (x) { return String(x) !== String(id); });
                renderSelectedParties();
            });
            partySelectedWrap.appendChild(chip);
        });
    }

    function renderPartyList(rows) {
        if (!partyList) return;
        partyList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-project-party-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = partyRows.length ? 'No parties match.' : 'No parties yet. Create one first.';
            partyList.appendChild(li0);
            showPartyList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (partyHidden) partyHidden.value = String(row.id);
                if (partySearch) partySearch.value = row.label;
                hidePartyList();
                var id = String(row.id);
                if (!selectedPartyIds.some(function (x) { return String(x) === id; })) {
                    selectedPartyIds.push(row.id);
                    renderSelectedParties();
                }
                if (partyHidden) partyHidden.value = '';
                if (partySearch) partySearch.value = '';
            });
            li.appendChild(btn);
            partyList.appendChild(li);
        });
        showPartyList();
    }

    function openFilteredPartyList() {
        renderPartyList(filterPartyRows(partySearch ? partySearch.value : ''));
    }

    function clearPartyPicker() {
        if (partyHidden) partyHidden.value = '';
        if (partySearch) {
            partySearch.value = '';
            partySearch.setAttribute('aria-expanded', 'false');
        }
        hidePartyList();
        selectedPartyIds = [];
        renderSelectedParties();
    }

    function resetProjectModalFields() {
        if (nameInput) nameInput.value = '';
        if (fieldTypeSelect) fieldTypeSelect.value = '';
        clearLandTypePicker();
        clearPartyPicker();
        if (areaAcreInput) areaAcreInput.value = '';
        if (areaKanalInput) areaKanalInput.value = '';
        if (areaMarlaInput) areaMarlaInput.value = '';
        if (areaSqftInput) areaSqftInput.value = '';
        if (totalAmountInput) totalAmountInput.value = '';
    }

    function clearPrimaryLoading() {
        if (!primaryBtn) return;
        primaryBtn.classList.remove('is-loading');
        primaryBtn.disabled = false;
        primaryBtn.removeAttribute('aria-busy');
    }

    function setPrimaryLoading(loading) {
        if (!primaryBtn) return;
        if (loading) {
            primaryBtn.classList.add('is-loading');
            primaryBtn.disabled = true;
            primaryBtn.setAttribute('aria-busy', 'true');
            primaryBtn.setAttribute('aria-label', 'Saving…');
        } else {
            clearPrimaryLoading();
            updatePrimaryChrome();
        }
    }

    function updatePrimaryChrome() {
        if (!primaryBtn || !primaryLabel) return;
        var isLast = step >= maxStep;
        primaryLabel.textContent = isLast ? 'Create project' : 'Next';
        primaryBtn.setAttribute('aria-label', isLast ? 'Create project' : 'Next step');
    }

    function goToStep(n) {
        var was = step;
        step = Math.max(0, Math.min(maxStep, n));
        if (was === 2 && step !== 2) hideLandTypeList();
        if (was === 3 && step !== 3) hidePartyList();
        modalEl.querySelectorAll('.daybook-project-modal-step').forEach(function (el) {
            var sn = parseInt(el.getAttribute('data-project-modal-step'), 10);
            el.classList.toggle('d-none', sn !== step);
        });
        if (backBtn) backBtn.classList.toggle('d-none', step === 0);
        updatePrimaryChrome();
        if (!modalEl.classList.contains('show')) return;
        if (step === 0 && nameInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        } else if (step === 1 && fieldTypeSelect) {
            fieldTypeSelect.focus();
        } else if (step === 2 && landTypeSearch) {
            if (window.daybookModalFocusText) {
                window.daybookModalFocusText(landTypeSearch, { scheduleEnsure: true });
            } else {
                landTypeSearch.focus();
            }
            openFilteredLandTypeList();
        } else if (step === 3 && partySearch) {
            if (window.daybookModalFocusText) {
                window.daybookModalFocusText(partySearch, { scheduleEnsure: true });
            } else {
                partySearch.focus();
            }
            renderSelectedParties();
            openFilteredPartyList();
        } else if (step === 4 && areaSqftInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(areaSqftInput, { scheduleEnsure: true });
        } else if (step === 5 && totalAmountInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(totalAmountInput, { scheduleEnsure: true });
        }
    }

    function parseUnsignedInt(raw) {
        var s = (raw || '').trim();
        if (!s) return null;
        if (!/^\d+$/.test(s)) return NaN;
        var n = parseInt(s, 10);
        if (!isFinite(n) || n < 0) return NaN;
        return n;
    }

    function getSelectedArea() {
        var pairs = [
            ['acre', areaAcreInput ? areaAcreInput.value : ''],
            ['kanal', areaKanalInput ? areaKanalInput.value : ''],
            ['marla', areaMarlaInput ? areaMarlaInput.value : ''],
            ['sqft', areaSqftInput ? areaSqftInput.value : '']
        ];
        var filled = pairs
            .map(function (p) { return [p[0], parseUnsignedInt(p[1])]; })
            .filter(function (p) { return p[1] !== null; });

        if (filled.length === 0) return { ok: false, msg: 'Please enter area in one unit (Acre, Kanal, Marla, or Sq ft).', unit: null, area: null };
        if (filled.length > 1) return { ok: false, msg: 'Please enter area in only one unit. Clear the other fields.', unit: null, area: null };
        if (isNaN(filled[0][1])) return { ok: false, msg: 'Area must be an unsigned integer (0 or greater).', unit: null, area: null };
        return { ok: true, unit: filled[0][0], area: filled[0][1], msg: '' };
    }

    function validateStep(s) {
        if (s === 0) {
            var name = (nameInput && (nameInput.value || '').trim()) || '';
            if (!name) {
                showModalErr('Please enter a project name.');
                if (nameInput && window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
                return false;
            }
            return true;
        }
        if (s === 1) {
            if (!fieldTypeSelect || !fieldTypeSelect.value) {
                showModalErr('Please select type (sale or purchase).');
                return false;
            }
            return true;
        }
        if (s === 2) {
            if (!landTypeHidden || !landTypeHidden.value) {
                showModalErr('Please select a land type from the list.');
                if (landTypeSearch && window.daybookModalFocusText) window.daybookModalFocusText(landTypeSearch);
                openFilteredLandTypeList();
                return false;
            }
            var still = landTypeRows.some(function (r) {
                return String(r.id) === landTypeHidden.value && landTypeSearch && r.label === landTypeSearch.value;
            });
            if (!still) {
                showModalErr('Choose a valid land type from the search results.');
                if (landTypeSearch && window.daybookModalFocusText) window.daybookModalFocusText(landTypeSearch);
                openFilteredLandTypeList();
                return false;
            }
            return true;
        }
        if (s === 3) {
            return true; // parties optional
        }
        if (s === 4) {
            var sel = getSelectedArea();
            if (!sel.ok) {
                showModalErr(sel.msg);
                if (areaSqftInput && window.daybookModalFocusText) window.daybookModalFocusText(areaSqftInput);
                return false;
            }
            return true;
        }
        if (s === 5) {
            var raw = totalAmountInput ? (totalAmountInput.value || '').trim() : '';
            if (!raw) {
                showModalErr('Please enter total amount.');
                if (totalAmountInput && window.daybookModalFocusText) window.daybookModalFocusText(totalAmountInput);
                return false;
            }
            var val = parseFloat(raw);
            if (isNaN(val) || val < 0) {
                showModalErr('Total amount must be 0 or greater.');
                if (totalAmountInput && window.daybookModalFocusText) window.daybookModalFocusText(totalAmountInput);
                return false;
            }
            return true;
        }
        return true;
    }

    if (landTypeSearch && landTypeList) {
        landTypeSearch.addEventListener('focus', function () {
            openFilteredLandTypeList();
        });
        landTypeSearch.addEventListener('input', function () {
            if (landTypeHidden) {
                var still = landTypeRows.some(function (r) {
                    return String(r.id) === landTypeHidden.value && r.label === landTypeSearch.value;
                });
                if (!still) landTypeHidden.value = '';
            }
            openFilteredLandTypeList();
        });
        landTypeSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideLandTypeList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !landTypeList || landTypeList.classList.contains('d-none')) return;
        var ltWrap = modalEl.querySelector('.daybook-project-lt-combo');
        if (ltWrap && !ltWrap.contains(e.target)) hideLandTypeList();
    });

    if (partySearch && partyList) {
        partySearch.addEventListener('focus', function () {
            openFilteredPartyList();
        });
        partySearch.addEventListener('input', function () {
            openFilteredPartyList();
        });
        partySearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hidePartyList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !partyList || partyList.classList.contains('d-none')) return;
        var wrap = modalEl.querySelector('.daybook-project-party-combo');
        if (wrap && !wrap.contains(e.target)) hidePartyList();
    });

    function showModalErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    projectFormCreateBtn.addEventListener('click', function () {
        showModalErr('');
        resetProjectModalFields();
        goToStep(0);
        projectFormCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        goToStep(step);
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        showModalErr('');
        resetProjectModalFields();
        clearPrimaryLoading();
        goToStep(0);
    });

    if (backBtn) {
        backBtn.addEventListener('click', function () {
            if (step <= 0) return;
            showModalErr('');
            goToStep(step - 1);
        });
    }

    modalEl.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (e.target && e.target.closest && e.target.closest('a')) return;
        var inStep = e.target && e.target.closest && e.target.closest('.daybook-project-modal-step');
        if (!inStep) return;
        e.preventDefault();
        if (primaryBtn && !primaryBtn.disabled) primaryBtn.click();
    });

    if (primaryBtn) {
        primaryBtn.addEventListener('click', function () {
            if (primaryBtn.classList.contains('is-loading')) return;
            showModalErr('');
            if (step < maxStep) {
                if (!validateStep(step)) return;
                goToStep(step + 1);
                return;
            }
            if (!validateStep(5)) return;
            var name = (nameInput.value || '').trim();
            var sel = getSelectedArea();
            var totalAmount = parseFloat((totalAmountInput.value || '').trim());
            setPrimaryLoading(true);
            fetch('{{ route('projects.quick-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    name: name,
                    field_type: fieldTypeSelect.value,
                    land_area: sel.area,
                    land_area_unit: sel.unit,
                    land_type_id: parseInt(landTypeHidden.value, 10),
                    total_amount: totalAmount,
                    party_ids: selectedPartyIds
                })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    setPrimaryLoading(false);
                    if (result.ok && result.data && result.data.id) {
                        var rows = window.__daybookFormProjectRows || [];
                        var nid = String(result.data.id);
                        if (!rows.some(function (r) { return String(r.id) === nid; })) {
                            rows.push({ id: result.data.id, label: result.data.name });
                        }
                        projectFormHidden.value = nid;
                        projectFormSearch.value = result.data.name;
                        resetProjectModalFields();
                        showModalErr('');
                        goToStep(0);
                        modal.hide();
                    } else {
                        var msg = 'Could not create project.';
                        if (result.data && result.data.errors) {
                            var keys = Object.keys(result.data.errors);
                            if (keys.length && result.data.errors[keys[0]] && result.data.errors[keys[0]][0]) {
                                msg = result.data.errors[keys[0]][0];
                            }
                        } else if (result.data && result.data.message) {
                            msg = result.data.message;
                        }
                        showModalErr(msg);
                    }
                })
                .catch(function () {
                    setPrimaryLoading(false);
                    showModalErr('Something went wrong. Try again.');
                });
        });
    }
})();

(function () {
    var partyFormHidden = document.getElementById('daybook_form_party_id');
    var partyFormSearch = document.getElementById('daybook_form_party_search');
    var partyFormCreateBtn = document.getElementById('daybook_form_party_create');
    var modalEl = document.getElementById('daybookCreatePartyModal');
    var nameInput = document.getElementById('daybook_modal_party_name');
    var subHidden = document.getElementById('daybook_modal_party_sub_category');
    var subSearch = document.getElementById('daybook_modal_party_sub_search');
    var subList = document.getElementById('daybook_party_sc_listbox');
    var subJsonEl = document.getElementById('daybook-party-sub-json');
    var saveBtn = document.getElementById('daybook_modal_party_submit');
    var errEl = document.getElementById('daybook_modal_party_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!partyFormHidden || !partyFormSearch || !partyFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    var partySubRows = [];
    if (subJsonEl) {
        try {
            partySubRows = JSON.parse(subJsonEl.textContent) || [];
        } catch (e) {
            partySubRows = [];
        }
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });

    function showPartyErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function clearSubCategoryPicker() {
        if (subHidden) subHidden.value = '';
        if (subSearch) {
            subSearch.value = '';
            subSearch.setAttribute('aria-expanded', 'false');
        }
        hideSubList();
    }

    function hideSubList() {
        if (!subList) return;
        subList.classList.add('d-none');
        subList.setAttribute('hidden', '');
        if (subSearch) subSearch.setAttribute('aria-expanded', 'false');
    }

    function showSubList() {
        if (!subList) return;
        subList.classList.remove('d-none');
        subList.removeAttribute('hidden');
        if (subSearch) subSearch.setAttribute('aria-expanded', 'true');
    }

    function norm(s) {
        return (s || '').toLowerCase();
    }

    function filterSubRows(q) {
        var nq = norm(q);
        if (!nq) return partySubRows.slice();
        return partySubRows.filter(function (row) {
            return norm(row.label).indexOf(nq) !== -1;
        });
    }

    function renderSubList(rows) {
        if (!subList) return;
        subList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-party-sc-empty';
            li0.textContent = 'No sub categories match.';
            subList.appendChild(li0);
            showSubList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                if (subHidden) subHidden.value = String(row.id);
                if (subSearch) subSearch.value = row.label;
                hideSubList();
            });
            li.appendChild(btn);
            subList.appendChild(li);
        });
        showSubList();
    }

    function openFilteredList() {
        renderSubList(filterSubRows(subSearch ? subSearch.value : ''));
    }

    if (subSearch && subList) {
        subSearch.addEventListener('focus', function () {
            openFilteredList();
        });
        subSearch.addEventListener('input', function () {
            if (subHidden) {
                var still = partySubRows.some(function (r) {
                    return String(r.id) === subHidden.value && r.label === subSearch.value;
                });
                if (!still) subHidden.value = '';
            }
            openFilteredList();
        });
        subSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideSubList();
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (!modalEl.classList.contains('show') || !subList || subList.classList.contains('d-none')) return;
        var wrap = modalEl.querySelector('.daybook-party-sc-combo');
        if (wrap && !wrap.contains(e.target)) hideSubList();
    });

    partyFormCreateBtn.addEventListener('click', function () {
        showPartyErr('');
        if (nameInput) nameInput.value = '';
        clearSubCategoryPicker();
        partyFormCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (nameInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        showPartyErr('');
        if (saveBtn) saveBtn.disabled = false;
        clearSubCategoryPicker();
    });

    if (nameInput) {
        nameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (saveBtn && !saveBtn.disabled) saveBtn.click();
            }
        });
    }

    if (saveBtn && nameInput && subHidden && subSearch) {
        saveBtn.addEventListener('click', function () {
            showPartyErr('');
            var name = (nameInput.value || '').trim();
            var subId = subHidden.value;
            if (!name) {
                showPartyErr('Please enter a party name.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
                return;
            }
            if (!subId) {
                showPartyErr('Please select a sub category.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(subSearch);
                openFilteredList();
                return;
            }
            saveBtn.disabled = true;
            fetch('{{ route('parties.quick-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ name: name, sub_category_id: parseInt(subId, 10) })
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    }).catch(function () {
                        return { ok: false, data: {} };
                    });
                })
                .then(function (result) {
                    saveBtn.disabled = false;
                    if (result.ok && result.data && result.data.id) {
                        var rows = window.__daybookFormPartyRows || [];
                        var nid = String(result.data.id);
                        if (!rows.some(function (r) { return String(r.id) === nid; })) {
                            rows.push({ id: result.data.id, label: result.data.name });
                        }
                        partyFormHidden.value = nid;
                        partyFormSearch.value = result.data.name;
                        if (typeof window.__daybookProjectModalPartyRowsPush === 'function') {
                            window.__daybookProjectModalPartyRowsPush(result.data.id, result.data.name);
                        }
                        nameInput.value = '';
                        clearSubCategoryPicker();
                        showPartyErr('');
                        modal.hide();
                    } else {
                        var msg = 'Could not create party.';
                        if (result.data && result.data.errors) {
                            if (result.data.errors.name) msg = result.data.errors.name[0];
                            else if (result.data.errors.sub_category_id) msg = result.data.errors.sub_category_id[0];
                        } else if (result.data && result.data.message) {
                            msg = result.data.message;
                        }
                        showPartyErr(msg);
                    }
                })
                .catch(function () {
                    saveBtn.disabled = false;
                    showPartyErr('Something went wrong. Try again.');
                });
        });
    }
})();

(function () {
    var input = document.getElementById('entry_date_input');
    if (!input || typeof flatpickr === 'undefined') return;
    flatpickr(input, {
        dateFormat: 'Y-m-d',
        defaultDate: input.value || null,
        allowInput: false,
        disableMobile: true,
        clickOpens: true
    });
})();

(function () {
    var input = document.getElementById('entry_amount');
    var wordsEl = document.getElementById('entry_amount_words');
    var form = document.getElementById('daybook-entry-form');
    if (!input) return;

    /** Compact scale: cr (crore), lac, k (thousand); two decimals; no "rupees" suffix. */
    function scale2(x) {
        return (Math.round(x * 100) / 100).toFixed(2);
    }

    function compactMainLabel(intPart) {
        if (intPart < 0) return '';
        if (intPart >= 10000000) {
            return scale2(intPart / 10000000) + ' cr';
        }
        if (intPart >= 100000) {
            return scale2(intPart / 100000) + ' lac';
        }
        if (intPart >= 1000) {
            return scale2(intPart / 1000) + ' k';
        }
        return String(intPart);
    }

    function paiseLabel(p) {
        if (p <= 0) return '';
        return String(p) + (p === 1 ? ' paisa' : ' paise');
    }

    function sanitizeAmountString(raw) {
        var s = String(raw || '').replace(/,/g, '').replace(/[^\d.]/g, '');
        if (!s) return '';
        var firstDot = s.indexOf('.');
        if (firstDot === -1) {
            s = s.replace(/^0+(\d)/, '$1');
            return s;
        }
        var intp = s.slice(0, firstDot).replace(/\./g, '');
        intp = intp.replace(/^0+(\d)/, '$1');
        if (intp === '') intp = '0';
        var frac = s.slice(firstDot + 1).replace(/\./g, '').replace(/\D/g, '').slice(0, 2);
        if (frac.length === 0) return intp + '.';
        return intp + '.' + frac;
    }

    /** Indian-style grouping: last 3 digits, then groups of 2 (e.g. 12,34,567). */
    function addIndianCommas(intDigits) {
        var s = String(intDigits || '').replace(/\D/g, '');
        if (s === '') s = '0';
        s = s.replace(/^0+(?=\d)/, '') || '0';
        if (s === '0') return '0';
        if (s.length <= 3) return s;
        var last3 = s.slice(-3);
        var head = s.slice(0, -3);
        while (head.length > 2) {
            last3 = head.slice(-2) + ',' + last3;
            head = head.slice(0, -2);
        }
        if (head.length) {
            last3 = head + ',' + last3;
        }
        return last3;
    }

    /** Pretty-print amount with commas (integer part only); `sanitized` must be from sanitizeAmountString. */
    function formatIndianDisplay(sanitized) {
        if (!sanitized) return '';
        var dot = sanitized.indexOf('.');
        var intRaw = dot === -1 ? sanitized : sanitized.slice(0, dot);
        var fracRaw = dot === -1 ? '' : sanitized.slice(dot + 1);
        intRaw = intRaw.replace(/\D/g, '');
        if (intRaw === '') intRaw = '0';
        var frac = fracRaw.replace(/\D/g, '').slice(0, 2);
        var out = addIndianCommas(intRaw);
        if (dot !== -1 && frac.length > 0) return out + '.' + frac;
        if (dot !== -1 && (fracRaw.length === 0 || sanitized.endsWith('.'))) return out + '.';
        return out;
    }

    function parseAmount(s) {
        var t = sanitizeAmountString(s);
        if (!t || t === '.') return null;
        if (t.endsWith('.')) t = t.slice(0, -1);
        var v = parseFloat(t);
        if (!isFinite(v) || v < 0) return null;
        return v;
    }

    function updateWords() {
        if (!wordsEl) return;
        var v = parseAmount(input.value);
        if (v === null || input.value.trim() === '') {
            wordsEl.textContent = '';
            return;
        }
        var intPart = Math.floor(v + 1e-9);
        var dec = Math.round((v - intPart) * 100);
        if (dec >= 100) {
            intPart += 1;
            dec -= 100;
        }
        var bits = [];
        if (intPart > 0 || dec === 0) {
            bits.push(compactMainLabel(intPart));
        }
        if (dec > 0) {
            bits.push(paiseLabel(dec));
        }
        wordsEl.textContent = bits.join(', ');
    }

    input.addEventListener('keydown', function (e) {
        if (e.ctrlKey || e.metaKey || e.altKey) return;
        var k = e.key;
        if (k === 'Backspace' || k === 'Delete' || k === 'Tab' || k === 'Escape' || k === 'Enter' || k === 'ArrowLeft' || k === 'ArrowRight' || k === 'Home' || k === 'End') return;
        if (k === '.' || k === ',') {
            if (k === ',') e.preventDefault();
            if (input.value.indexOf('.') !== -1) e.preventDefault();
            return;
        }
        if (/\d/.test(k)) return;
        e.preventDefault();
    });

    input.addEventListener('input', function () {
        var cur = input.value;
        var next = sanitizeAmountString(cur);
        var display = formatIndianDisplay(next);
        if (display !== cur) {
            input.value = display;
            try {
                input.setSelectionRange(display.length, display.length);
            } catch (ignore) {}
        }
        updateWords();
    });

    input.addEventListener('paste', function (e) {
        e.preventDefault();
        var paste = (e.clipboardData || window.clipboardData).getData('text') || '';
        var next = sanitizeAmountString(paste);
        input.value = formatIndianDisplay(next);
        try {
            input.setSelectionRange(input.value.length, input.value.length);
        } catch (ignore) {}
        updateWords();
    });

    input.addEventListener('blur', function () {
        var t = sanitizeAmountString(input.value);
        if (t.endsWith('.')) t = t.slice(0, -1);
        input.value = formatIndianDisplay(t);
        updateWords();
    });

    if (form) {
        form.addEventListener('submit', function () {
            var t = sanitizeAmountString(input.value);
            if (t.endsWith('.')) t = t.slice(0, -1);
            input.value = t;
        });
    }

    (function initAmountDisplay() {
        var t = sanitizeAmountString(input.value);
        if (t) input.value = formatIndianDisplay(t);
    })();

    updateWords();
})();
</script>
