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
    var projectResetBtn = document.getElementById('daybook_form_project_reset');
    var purchaseFileSelect = document.getElementById('daybook_form_purchase_file_id');
    var fileResetBtn = document.getElementById('daybook_form_file_reset');
    var purchaseFileDefaultEl = document.getElementById('daybook-form-purchase-file-default');

    var partyHidden = document.getElementById('daybook_form_party_id');
    var partySearch = document.getElementById('daybook_form_party_search');
    var partyList = document.getElementById('daybook_form_party_listbox');
    var partyWrap = partySearch ? partySearch.closest('.daybook-form-combo') : null;
    var partyJsonEl = document.getElementById('daybook-form-parties-json');
    var partyCreateBtn = document.getElementById('daybook_form_party_create');
    var partyResetBtn = document.getElementById('daybook_form_party_reset');

    var subHidden = document.getElementById('daybook_form_party_sub_category_id');
    var subSearch = document.getElementById('daybook_form_party_sub_search');
    var subList = document.getElementById('daybook_form_party_sub_listbox');
    var subWrap = subSearch ? subSearch.closest('.daybook-form-combo') : null;
    var subJsonEl = document.getElementById('daybook-form-party-sub-json');
    var subResetBtn = document.getElementById('daybook_form_party_sub_reset');

    if (!projectHidden || !projectSearch || !projectList || !partyHidden || !partySearch || !partyList) return;

    function toggleResetBtn(btn, visible) {
        if (!btn) return;
        btn.classList.toggle('d-none', !visible);
        btn.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function syncProjectResetVisibility() {
        if (!projectResetBtn || !projectHidden) return;
        toggleResetBtn(projectResetBtn, String(projectHidden.value || '').trim() !== '');
    }

    function syncPartyResetVisibility() {
        if (!partyResetBtn || !partyHidden) return;
        toggleResetBtn(partyResetBtn, String(partyHidden.value || '').trim() !== '');
    }

    function syncFileResetVisibility() {
        if (!fileResetBtn || !purchaseFileSelect) return;
        var hasProject = projectHidden && String(projectHidden.value || '').trim() !== '';
        var hasFile = hasProject && !purchaseFileSelect.disabled && String(purchaseFileSelect.value || '').trim() !== '';
        toggleResetBtn(fileResetBtn, hasFile);
    }

    function syncSubResetVisibility() {
        if (!subResetBtn || !subHidden) return;
        toggleResetBtn(subResetBtn, String(subHidden.value || '').trim() !== '');
    }

    function syncAllFieldResetVisibility() {
        syncProjectResetVisibility();
        syncPartyResetVisibility();
        syncFileResetVisibility();
        syncSubResetVisibility();
    }

    window.__daybookSyncAllFieldResetVisibility = syncAllFieldResetVisibility;

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

    var purchaseFileDefault = '';
    if (purchaseFileDefaultEl) {
        try {
            purchaseFileDefault = JSON.parse(purchaseFileDefaultEl.textContent) || '';
        } catch (e) {
            purchaseFileDefault = '';
        }
    }

    function syncPurchaseFileSelect(projectId, selectedFileId) {
        if (!purchaseFileSelect) return;
        var pid = String(projectId || '').trim();
        var selected = String(selectedFileId != null ? selectedFileId : '').trim();
        purchaseFileSelect.innerHTML = '';
        if (!pid) {
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Select project first —';
            purchaseFileSelect.appendChild(opt0);
            purchaseFileSelect.value = '';
            purchaseFileSelect.disabled = true;
            syncSaleFileMeta(null);
            syncFileResetVisibility();
            return;
        }
        var project = formProjectRows.find(function (r) { return String(r.id) === pid; });
        var files = (project && (project.sale_files || project.purchase_files)) ? (project.sale_files || project.purchase_files) : [];
        var optBlank = document.createElement('option');
        optBlank.value = '';
        optBlank.textContent = files.length ? '— No sale file —' : '— No sale files for this project —';
        purchaseFileSelect.appendChild(optBlank);
        files.forEach(function (f) {
            var opt = document.createElement('option');
            opt.value = String(f.id);
            var label = f.label || f.file_name || ('File #' + f.id);
            if (f.is_fully_sold) {
                label += ' — Fully Sold';
            } else if (f.remaining_label && f.remaining_label !== '—') {
                label += ' (Avail: ' + f.remaining_label + ')';
            }
            opt.textContent = label;
            if (f.is_fully_sold && String(f.id) !== selected) {
                opt.disabled = true;
            }
            purchaseFileSelect.appendChild(opt);
        });
        purchaseFileSelect.disabled = false;
        if (selected && files.some(function (f) { return String(f.id) === selected; })) {
            purchaseFileSelect.value = selected;
        } else {
            purchaseFileSelect.value = '';
        }
        syncSaleFileMeta(currentSaleFile());
        syncFileResetVisibility();
    }

    function currentSaleFile() {
        if (!purchaseFileSelect || !projectHidden) return null;
        var pid = String(projectHidden.value || '').trim();
        var fid = String(purchaseFileSelect.value || '').trim();
        if (!pid || !fid) return null;
        var project = formProjectRows.find(function (r) { return String(r.id) === pid; });
        var files = (project && (project.sale_files || project.purchase_files)) ? (project.sale_files || project.purchase_files) : [];
        return files.find(function (f) { return String(f.id) === fid; }) || null;
    }

    function syncSaleFileMeta(file) {
        var meta = document.getElementById('daybook_sale_file_meta');
        var areaWrap = document.getElementById('daybook_sold_area_wrap');
        var remainingEl = document.getElementById('daybook_sale_file_remaining');
        var totalsEl = document.getElementById('daybook_sale_file_totals');
        var statusEl = document.getElementById('daybook_sale_file_status');
        var sellersWrap = document.getElementById('daybook_sale_file_sellers_wrap');
        var sellersEl = document.getElementById('daybook_sale_file_sellers');
        var qtyInput = document.getElementById('daybook_sold_area_qty');
        if (!meta || !areaWrap) return;

        if (!file) {
            meta.classList.add('d-none');
            areaWrap.classList.add('d-none');
            if (qtyInput && !qtyInput.dataset.keepValue) {
                // leave existing edit values alone until cleared by reset
            }
            return;
        }

        if (!file.is_file_sale) {
            meta.classList.add('d-none');
            areaWrap.classList.add('d-none');
            clearSoldAreaInputs();
            return;
        }

        meta.classList.remove('d-none');
        areaWrap.classList.remove('d-none');
        if (remainingEl) remainingEl.textContent = file.remaining_label || '—';
        if (totalsEl) {
            totalsEl.textContent = (file.total_label || '—') + ' / ' + (file.sold_label || '—');
        }
        if (statusEl) statusEl.textContent = file.status || '—';
        if (sellersWrap && sellersEl) {
            var sellers = Array.isArray(file.sellers) ? file.sellers : [];
            if (sellers.length) {
                sellersWrap.classList.remove('d-none');
                sellersEl.textContent = sellers.join(', ');
            } else {
                sellersWrap.classList.add('d-none');
                sellersEl.textContent = '—';
            }
        }
        if (file.is_fully_sold && qtyInput && !String(qtyInput.value || '').trim()) {
            qtyInput.placeholder = 'Fully sold — no further area';
            qtyInput.disabled = true;
        } else if (qtyInput) {
            qtyInput.disabled = false;
            qtyInput.placeholder = '0';
        }
    }

    window.__daybookSyncPurchaseFileSelect = syncPurchaseFileSelect;

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
                clearSoldAreaInputs();
                syncPurchaseFileSelect(row.id, '');
                hideProjectList();
                syncProjectResetVisibility();
                if (typeof window.__daybookSyncFactoryMode === 'function') window.__daybookSyncFactoryMode();
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
                syncPartyResetVisibility();
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
            syncPurchaseFileSelect(projectHidden.value, purchaseFileDefault);
        } else {
            syncPurchaseFileSelect('', '');
        }
        if (partyHidden.value) {
            var py = formPartyRows.find(function (r) { return String(r.id) === String(partyHidden.value); });
            if (py) partySearch.value = py.label;
        }
        if (subHidden && subHidden.value && subSearch) {
            var sb = formSubRows.find(function (r) { return String(r.id) === String(subHidden.value); });
            if (sb) subSearch.value = sb.label;
        }
        syncAllFieldResetVisibility();
        if (typeof window.__daybookSyncFactoryMode === 'function') window.__daybookSyncFactoryMode();
    })();

    if (purchaseFileSelect) {
        purchaseFileSelect.addEventListener('change', function () {
            clearSoldAreaInputs();
            syncSaleFileMeta(currentSaleFile());
            syncFileResetVisibility();
        });
    }

    function clearSoldAreaInputs() {
        var qtyInput = document.getElementById('daybook_sold_area_qty');
        var unitSelect = document.getElementById('daybook_sold_area_unit');
        if (qtyInput) {
            qtyInput.value = '';
            qtyInput.disabled = false;
            qtyInput.placeholder = '0';
        }
        if (unitSelect) unitSelect.value = 'marla';
    }

    var soldAreaFillBtn = document.getElementById('daybook_sold_area_fill_remaining');
    if (soldAreaFillBtn) {
        soldAreaFillBtn.addEventListener('click', function () {
            var file = currentSaleFile();
            var qtyInput = document.getElementById('daybook_sold_area_qty');
            var unitSelect = document.getElementById('daybook_sold_area_unit');
            if (!file || !qtyInput || !unitSelect) return;
            if (file.is_fully_sold || !(file.remaining_marla > 0)) return;
            unitSelect.value = 'marla';
            var rem = Number(file.remaining_marla) || 0;
            qtyInput.value = String(Math.round(rem * 10000) / 10000);
            qtyInput.disabled = false;
            qtyInput.focus();
        });
    }

    projectSearch.addEventListener('focus', function () {
        openFilteredProjectList();
    });
    projectSearch.addEventListener('input', function () {
        projectHidden.value = '';
        clearSoldAreaInputs();
        syncPurchaseFileSelect('', '');
        syncProjectResetVisibility();
        openFilteredProjectList();
        if (typeof window.__daybookSyncFactoryMode === 'function') window.__daybookSyncFactoryMode();
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
        syncPartyResetVisibility();
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

    if (projectResetBtn && projectHidden && projectSearch) {
        projectResetBtn.addEventListener('click', function () {
            projectHidden.value = '';
            projectSearch.value = '';
            clearSoldAreaInputs();
            syncPurchaseFileSelect('', '');
            hideProjectList();
            syncProjectResetVisibility();
            if (typeof window.__daybookSyncFactoryMode === 'function') window.__daybookSyncFactoryMode();
            projectSearch.focus();
        });
    }

    if (partyResetBtn && partyHidden && partySearch) {
        partyResetBtn.addEventListener('click', function () {
            partyHidden.value = '';
            partySearch.value = '';
            if (subHidden && subSearch) {
                subHidden.value = '';
                subSearch.value = '';
                syncSubResetVisibility();
            }
            hidePartyFormList();
            syncPartyResetVisibility();
            partySearch.focus();
        });
    }

    if (fileResetBtn && purchaseFileSelect) {
        fileResetBtn.addEventListener('click', function () {
            if (!purchaseFileSelect.disabled) {
                purchaseFileSelect.value = '';
            }
            clearSoldAreaInputs();
            syncSaleFileMeta(null);
            syncFileResetVisibility();
            purchaseFileSelect.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (projectWrap && !projectWrap.contains(e.target)) hideProjectList();
        if (partyWrap && !partyWrap.contains(e.target)) hidePartyFormList();
        if (subWrap && !subWrap.contains(e.target)) hideSubFormList();
    });

    window.__daybookPushPartySubCategory = function (row) {
        if (!row || row.id == null || !row.label) return;
        var nid = String(row.id);
        if (!formSubRows.some(function (r) { return String(r.id) === nid; })) {
            formSubRows.push({ id: row.id, label: row.label });
        }
        if (subHidden && subSearch) {
            subHidden.value = nid;
            subSearch.value = row.label;
            syncSubResetVisibility();
        }
        if (typeof window.__daybookPartyModalSubRowsPush === 'function') {
            window.__daybookPartyModalSubRowsPush(row);
        }
    };
})();

(function () {
    var projectFormHidden = document.getElementById('daybook_form_project_id');
    var projectFormSearch = document.getElementById('daybook_form_project_search');
    var projectFormCreateBtn = document.getElementById('daybook_form_project_create');
    var modalEl = document.getElementById('daybookCreateProjectModal');
    var nameInput = document.getElementById('daybook_modal_project_name');
    var landTypeHidden = document.getElementById('daybook_modal_project_land_type_id');
    var landTypeSearch = document.getElementById('daybook_modal_project_land_type_search');
    var landTypeList = document.getElementById('daybook_modal_project_land_type_listbox');
    var landTypesJsonEl = document.getElementById('daybook-land-types-json');
    var primaryBtn = document.getElementById('daybook_modal_project_primary');
    var primaryLabel = document.getElementById('daybook_modal_project_primary_label');
    var errEl = document.getElementById('daybook_modal_project_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!projectFormHidden || !projectFormSearch || !projectFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });

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

    function resetProjectModalFields() {
        if (nameInput) nameInput.value = '';
        clearLandTypePicker();
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
        }
    }

    function showModalErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function validateModal() {
        var name = (nameInput && (nameInput.value || '').trim()) || '';
        if (!name) {
            showModalErr('Please enter a project name.');
            if (nameInput && window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
            return false;
        }
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

    projectFormCreateBtn.addEventListener('click', function () {
        showModalErr('');
        resetProjectModalFields();
        if (primaryLabel) primaryLabel.textContent = 'Create project';
        projectFormCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (nameInput && window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        } else if (nameInput) {
            nameInput.focus();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        showModalErr('');
        resetProjectModalFields();
        clearPrimaryLoading();
    });

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

    modalEl.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (e.target && e.target.closest && e.target.closest('a')) return;
        if (primaryBtn && !primaryBtn.disabled) primaryBtn.click();
    });

    if (primaryBtn) {
        primaryBtn.addEventListener('click', function () {
            if (primaryBtn.classList.contains('is-loading')) return;
            showModalErr('');
            if (!validateModal()) return;

            var payload = {
                simple: true,
                name: (nameInput.value || '').trim(),
                land_type_id: parseInt(landTypeHidden.value, 10)
            };

            setPrimaryLoading(true);
            fetch('{{ route('projects.quick-store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token.getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
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
                        var row = {
                            id: result.data.id,
                            label: result.data.name,
                            purchase_files: [],
                            party_areas: result.data.party_areas || {},
                            parties_total_marla: result.data.parties_total_marla || 0,
                            parties_total_label: result.data.parties_total_label || ''
                        };
                        if (!rows.some(function (r) { return String(r.id) === nid; })) {
                            rows.push(row);
                        } else {
                            rows.forEach(function (r) {
                                if (String(r.id) === nid) {
                                    r.label = result.data.name;
                                    r.party_areas = row.party_areas;
                                    r.parties_total_marla = row.parties_total_marla;
                                    r.parties_total_label = row.parties_total_label;
                                }
                            });
                        }
                        projectFormHidden.value = nid;
                        projectFormSearch.value = result.data.name;
                        if (typeof window.__daybookSyncPurchaseFileSelect === 'function') {
                            window.__daybookSyncPurchaseFileSelect(nid, '');
                        }
                        if (typeof window.__daybookSyncAllFieldResetVisibility === 'function') {
                            window.__daybookSyncAllFieldResetVisibility();
                        }
                        resetProjectModalFields();
                        showModalErr('');
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
    var phoneInput = document.getElementById('daybook_modal_party_phone');
    var cnicInput = document.getElementById('daybook_modal_party_cnic');
    var addressInput = document.getElementById('daybook_modal_party_address');
    var saveBtn = document.getElementById('daybook_modal_party_submit');
    var errEl = document.getElementById('daybook_modal_party_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    var PF = window.PartyFormFields;
    if (!partyFormHidden || !partyFormSearch || !partyFormCreateBtn || !modalEl || !token || typeof bootstrap === 'undefined') return;

    if (PF) {
        PF.bindCnicInput(cnicInput);
        PF.bindPhoneInput(phoneInput, 11);
    }

    var partySubRows = [];
    if (subJsonEl) {
        try {
            partySubRows = JSON.parse(subJsonEl.textContent) || [];
        } catch (e) {
            partySubRows = [];
        }
    }

    window.__daybookPartyModalSubRowsPush = function (row) {
        if (!row || row.id == null || !row.label) return;
        var nid = String(row.id);
        if (!partySubRows.some(function (r) { return String(r.id) === nid; })) {
            partySubRows.push({ id: row.id, label: row.label });
        }
    };

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

    function clearPartyModalFields() {
        if (nameInput) nameInput.value = '';
        clearSubCategoryPicker();
        if (phoneInput) phoneInput.value = '';
        if (cnicInput) cnicInput.value = '';
        if (addressInput) addressInput.value = '';
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
        clearPartyModalFields();
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
        clearPartyModalFields();
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
            var phone = (phoneInput && phoneInput.value || '').trim();
            var cnicDigitsVal = PF ? PF.cnicDigits(cnicInput && cnicInput.value) : (cnicInput && cnicInput.value || '').replace(/\D/g, '');
            var address = (addressInput && addressInput.value || '').trim();
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
            if (phone && phone.length !== 11) {
                showPartyErr('Phone must be exactly 11 digits.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(phoneInput);
                return;
            }
            if (cnicDigitsVal && PF && cnicDigitsVal.length !== PF.cnicMaxDigits) {
                showPartyErr('CNIC must be 13 digits in format 23012-2321373-1.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(cnicInput);
                return;
            }
            if (cnicDigitsVal && !PF && cnicDigitsVal.length !== 13) {
                showPartyErr('CNIC must be 13 digits in format 23012-2321373-1.');
                if (window.daybookModalFocusText) window.daybookModalFocusText(cnicInput);
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
                        if (typeof window.__daybookSyncAllFieldResetVisibility === 'function') {
                            window.__daybookSyncAllFieldResetVisibility();
                        }
                        clearPartyModalFields();
                        showPartyErr('');
                        modal.hide();
                    } else {
                        var msg = 'Could not create party.';
                        if (result.data && result.data.errors) {
                            var parts = [];
                            Object.keys(result.data.errors).forEach(function (k) {
                                parts = parts.concat(result.data.errors[k]);
                            });
                            if (parts.length) msg = parts.join(' ');
                            else if (result.data.errors.name) msg = result.data.errors.name[0];
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
    var formSubCreateBtn = document.getElementById('daybook_form_party_sub_create');
    var modalEl = document.getElementById('daybookCreatePartySubCategoryModal');
    var categorySelect = document.getElementById('daybook_modal_party_sub_cat_category_id');
    var nameInput = document.getElementById('daybook_modal_party_sub_cat_name');
    var saveBtn = document.getElementById('daybook_modal_party_sub_cat_submit');
    var errEl = document.getElementById('daybook_modal_party_sub_cat_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!formSubCreateBtn || !modalEl || !categorySelect || !nameInput || !saveBtn || !token || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });

    function showErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function clearFields() {
        categorySelect.value = '';
        nameInput.value = '';
        showErr('');
    }

    formSubCreateBtn.addEventListener('click', function () {
        clearFields();
        formSubCreateBtn.blur();
        modal.show();
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        if (window.daybookModalFocusText) {
            window.daybookModalFocusText(nameInput, { scheduleEnsure: true });
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        saveBtn.disabled = false;
        clearFields();
    });

    if (nameInput) {
        nameInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (!saveBtn.disabled) saveBtn.click();
            }
        });
    }

    saveBtn.addEventListener('click', function () {
        showErr('');
        var categoryId = categorySelect.value;
        var name = (nameInput.value || '').trim();
        if (!categoryId) {
            showErr('Please select a party category.');
            categorySelect.focus();
            return;
        }
        if (!name) {
            showErr('Please enter a name.');
            if (window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
            return;
        }
        saveBtn.disabled = true;
        fetch('{{ route('party-sub-categories.quick-store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                category_id: parseInt(categoryId, 10),
                name: name
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
                saveBtn.disabled = false;
                if (result.ok && result.data && result.data.id) {
                    if (typeof window.__daybookPushPartySubCategory === 'function') {
                        window.__daybookPushPartySubCategory({
                            id: result.data.id,
                            label: result.data.label
                        });
                    }
                    clearFields();
                    modal.hide();
                } else {
                    var msg = 'Could not create sub category.';
                    if (result.data && result.data.errors) {
                        var parts = [];
                        Object.keys(result.data.errors).forEach(function (k) {
                            parts = parts.concat(result.data.errors[k]);
                        });
                        if (parts.length) msg = parts.join(' ');
                    } else if (result.data && result.data.message) {
                        msg = result.data.message;
                    }
                    showErr(msg);
                }
            })
            .catch(function () {
                saveBtn.disabled = false;
                showErr('Something went wrong. Try again.');
            });
    });
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

(function () {
    var methodEl = document.getElementById('entry_payment_method');
    var bankRow = document.getElementById('entry_payment_bank_row');
    var refRow = document.getElementById('entry_payment_reference_row');
    var refLabel = document.getElementById('entry_payment_reference_label');
    var refInput = document.getElementById('entry_payment_reference');
    if (!methodEl || !bankRow || !refRow) return;

    function sync() {
        var m = methodEl.value;
        var showBank = m === 'online' || m === 'cheque' || m === 'payorder';
        var showRef = m === 'cheque' || m === 'payorder';
        bankRow.classList.toggle('d-none', !showBank);
        refRow.classList.toggle('d-none', !showRef);
        if (refLabel) {
            refLabel.textContent = m === 'payorder' ? 'Pay order reference #' : 'Cheque #';
        }
        if (refInput) {
            refInput.placeholder = m === 'payorder' ? 'Reference number' : 'Cheque number';
        }
    }

    methodEl.addEventListener('change', sync);
    sync();
})();

(function () {
    var hidden = document.getElementById('entry_payment_bank');
    var search = document.getElementById('entry_payment_bank_search');
    var list = document.getElementById('entry_payment_bank_listbox');
    var wrap = search ? search.closest('.daybook-form-combo') : null;
    var jsonEl = document.getElementById('daybook-form-banks-json');
    var methodEl = document.getElementById('entry_payment_method');
    if (!hidden || !search || !list || !jsonEl) return;

    var bankRows = [];
    try {
        bankRows = JSON.parse(jsonEl.textContent) || [];
    } catch (e) {
        bankRows = [];
    }

    function hideBankList() {
        list.classList.add('d-none');
        list.setAttribute('hidden', '');
        search.setAttribute('aria-expanded', 'false');
    }

    function showBankList() {
        list.classList.remove('d-none');
        list.removeAttribute('hidden');
        search.setAttribute('aria-expanded', 'true');
    }

    function filterBankRows(q) {
        var nq = (q || '').toLowerCase().trim();
        if (!nq) return bankRows.slice();
        return bankRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderBankList(rows) {
        list.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = bankRows.length ? 'No banks match.' : 'No banks configured.';
            list.appendChild(li0);
            showBankList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                hidden.value = row.id;
                search.value = row.label;
                hideBankList();
            });
            li.appendChild(btn);
            list.appendChild(li);
        });
        showBankList();
    }

    function openFilteredBankList() {
        renderBankList(filterBankRows(search.value));
    }

    function syncBankSearchFromHidden() {
        if (!hidden.value) {
            search.value = '';
            return;
        }
        var match = bankRows.find(function (r) {
            return String(r.id) === String(hidden.value);
        });
        search.value = match ? match.label : hidden.value;
    }

    syncBankSearchFromHidden();

    search.addEventListener('focus', function () {
        openFilteredBankList();
    });
    search.addEventListener('input', function () {
        hidden.value = '';
        openFilteredBankList();
    });
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideBankList();
        }
    });

    document.addEventListener('click', function (e) {
        if (!wrap || wrap.contains(e.target)) return;
        hideBankList();
    });

    if (methodEl) {
        methodEl.addEventListener('change', function () {
            if (methodEl.value === 'cash') {
                hidden.value = '';
                search.value = '';
                hideBankList();
            }
        });
    }
})();

(function () {
    var hidden = document.getElementById('entry_paid_by_party_id');
    var search = document.getElementById('entry_paid_by_party_search');
    var list = document.getElementById('entry_paid_by_party_listbox');
    var wrap = search ? search.closest('.daybook-form-combo') : null;
    var resetBtn = document.getElementById('entry_paid_by_party_reset');
    var jsonEl = document.getElementById('daybook-form-parties-json');
    if (!hidden || !search || !list || !jsonEl) return;

    var partyRows = [];
    try {
        partyRows = JSON.parse(jsonEl.textContent) || [];
    } catch (e) {
        partyRows = [];
    }

    function toggleReset(visible) {
        if (!resetBtn) return;
        resetBtn.classList.toggle('d-none', !visible);
        resetBtn.setAttribute('aria-hidden', visible ? 'false' : 'true');
    }

    function syncReset() {
        toggleReset(String(hidden.value || '').trim() !== '');
    }

    function hideList() {
        list.classList.add('d-none');
        list.setAttribute('hidden', '');
        search.setAttribute('aria-expanded', 'false');
    }

    function showList() {
        list.classList.remove('d-none');
        list.removeAttribute('hidden');
        search.setAttribute('aria-expanded', 'true');
    }

    function filterRows(q) {
        var nq = (q || '').toLowerCase().trim();
        if (!nq) return partyRows.slice();
        return partyRows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderList(rows) {
        list.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = partyRows.length ? 'No parties match.' : 'No parties yet.';
            list.appendChild(li0);
            showList();
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
                hidden.value = String(row.id);
                search.value = row.label;
                hideList();
                syncReset();
            });
            li.appendChild(btn);
            list.appendChild(li);
        });
        showList();
    }

    function openFiltered() {
        renderList(filterRows(search.value));
    }

    function syncFromHidden() {
        if (!hidden.value) {
            search.value = '';
            syncReset();
            return;
        }
        var match = partyRows.find(function (r) {
            return String(r.id) === String(hidden.value);
        });
        search.value = match ? match.label : '';
        if (!match) {
            hidden.value = '';
        }
        syncReset();
    }

    syncFromHidden();

    search.addEventListener('focus', openFiltered);
    search.addEventListener('input', function () {
        hidden.value = '';
        syncReset();
        openFiltered();
    });
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideList();
        }
    });

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            hidden.value = '';
            search.value = '';
            hideList();
            syncReset();
            search.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (!wrap || wrap.contains(e.target)) return;
        if (resetBtn && resetBtn.contains(e.target)) return;
        hideList();
    });
})();

(function () {
    var projectHidden = document.getElementById('daybook_form_project_id');
    var partySubWrap = document.getElementById('daybook_party_sub_category_wrap');
    var factoryWrap = document.getElementById('daybook_factory_fields');
    var subSelect = document.getElementById('daybook_factory_sub_category_id');
    var unitInput = document.getElementById('daybook_factory_unit');
    var unitList = document.getElementById('daybook_factory_unit_listbox');
    var unitWrap = unitInput ? unitInput.closest('.daybook-form-combo') : null;
    var unitJsonEl = document.getElementById('daybook-form-units-json');
    var qtyInput = document.getElementById('daybook_factory_quantity');
    var priceInput = document.getElementById('daybook_factory_unit_price');
    var amountInput = document.getElementById('entry_amount');
    var factoryJsonEl = document.getElementById('daybook-form-factory-sub-json');
    if (!projectHidden || !factoryWrap || !subSelect || !qtyInput || !priceInput || !amountInput) return;

    var factorySubRows = [];
    if (factoryJsonEl) {
        try {
            factorySubRows = JSON.parse(factoryJsonEl.textContent) || [];
        } catch (e) {
            factorySubRows = [];
        }
    }

    var unitOptions = [];
    if (unitJsonEl) {
        try {
            unitOptions = JSON.parse(unitJsonEl.textContent) || [];
        } catch (e) {
            unitOptions = [];
        }
    }

    function getSelectedProject() {
        var pid = String(projectHidden.value || '').trim();
        if (!pid) return null;
        var rows = window.__daybookFormProjectRows || [];
        return rows.find(function (r) { return String(r.id) === pid; }) || null;
    }

    function isFactoryProjectSelected() {
        var pr = getSelectedProject();
        if (!pr) return false;
        if (typeof pr.is_factory === 'boolean') return pr.is_factory;
        var lt = (pr.land_type || '').toString().trim().toLowerCase();
        return lt === 'factory';
    }

    function syncFactoryOptionList(selectedId) {
        var selected = String(selectedId || '').trim();
        subSelect.innerHTML = '';
        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '— Select sub category —';
        subSelect.appendChild(opt0);
        factorySubRows.forEach(function (r) {
            var opt = document.createElement('option');
            opt.value = String(r.id);
            opt.textContent = r.label || ('#' + r.id);
            subSelect.appendChild(opt);
        });
        if (selected && factorySubRows.some(function (r) { return String(r.id) === selected; })) {
            subSelect.value = selected;
        } else {
            subSelect.value = '';
        }
    }

    function setRequired(el, on) {
        if (!el) return;
        if (on) el.setAttribute('required', '');
        else el.removeAttribute('required');
    }

    function setReadOnly(el, on) {
        if (!el) return;
        el.readOnly = !!on;
        el.setAttribute('aria-readonly', on ? 'true' : 'false');
    }

    function normalizeUnsignedInt(raw) {
        var m = String(raw || '').trim().match(/^\d+/);
        var n = m ? parseInt(m[0], 10) : NaN;
        if (!isFinite(n) || n < 1) return null;
        return n;
    }

    function normalizePositiveNumber(raw) {
        var s = String(raw || '').trim();
        if (!s) return null;
        var v = parseFloat(s);
        if (!isFinite(v) || v <= 0) return null;
        return v;
    }

    // Auto-fill the unit from the sub category's default. `force` overwrites even a manually-typed unit
    // (used when the user actively picks a sub category); otherwise only fills when empty.
    function setUnitFromSubCategoryId(id, force) {
        if (!unitInput) return;
        var sid = String(id || '').trim();
        var row = factorySubRows.find(function (r) { return String(r.id) === sid; }) || null;
        var unit = row ? (row.unit || '') : '';
        if (force || String(unitInput.value || '').trim() === '') {
            unitInput.value = unit;
        }
    }

    function updateAmountFromQtyPrice() {
        if (!isFactoryProjectSelected()) return;
        var q = normalizeUnsignedInt(qtyInput.value);
        var p = normalizePositiveNumber(priceInput.value);
        if (q === null || p === null) {
            amountInput.value = '';
            amountInput.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }
        var amt = (q * p);
        amountInput.value = amt.toFixed(2);
        amountInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function enforceQtyInput() {
        if (qtyInput.value === '') return;
        var q = normalizeUnsignedInt(qtyInput.value);
        if (q === null) {
            qtyInput.value = '';
            return;
        }
        qtyInput.value = String(q);
    }

    function hideUnitList() {
        if (!unitList || !unitInput) return;
        unitList.classList.add('d-none');
        unitList.setAttribute('hidden', '');
        unitInput.setAttribute('aria-expanded', 'false');
    }

    function showUnitList() {
        if (!unitList || !unitInput) return;
        unitList.classList.remove('d-none');
        unitList.removeAttribute('hidden');
        unitInput.setAttribute('aria-expanded', 'true');
    }

    function filterUnitOptions(q) {
        var nq = (q || '').toLowerCase().trim();
        if (!nq) return unitOptions.slice();
        return unitOptions.filter(function (u) {
            return String(u).toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderUnitList(rows) {
        if (!unitList) return;
        unitList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = unitOptions.length ? 'No units match. Type to add your own.' : 'No units configured.';
            unitList.appendChild(li0);
            showUnitList();
            return;
        }
        rows.forEach(function (u) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.textContent = String(u);
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                unitInput.value = String(u);
                hideUnitList();
            });
            li.appendChild(btn);
            unitList.appendChild(li);
        });
        showUnitList();
    }

    function openFilteredUnitList() {
        renderUnitList(filterUnitOptions(unitInput ? unitInput.value : ''));
    }

    function syncMode() {
        var on = isFactoryProjectSelected();
        factoryWrap.classList.toggle('d-none', !on);
        if (partySubWrap) partySubWrap.classList.toggle('d-none', on);

        setRequired(subSelect, on);
        setRequired(qtyInput, on);
        setRequired(priceInput, on);
        setReadOnly(amountInput, on);

        if (!on) {
            if (subSelect) subSelect.value = '';
            if (qtyInput) qtyInput.value = '';
            if (priceInput) priceInput.value = '';
            if (unitInput) unitInput.value = '';
            return;
        }

        // When switching to Factory mode, avoid accidentally submitting the normal party_sub_category_id.
        var normalSubHidden = document.getElementById('daybook_form_party_sub_category_id');
        var normalSubSearch = document.getElementById('daybook_form_party_sub_search');
        if (normalSubHidden) normalSubHidden.value = '';
        if (normalSubSearch) normalSubSearch.value = '';

        var oldSelected = "{{ old('sub_category_id', $daybookFactorySubCategoryIdDefault ?? '') }}";
        var oldQty = "{{ old('quantity', $daybookFactoryQuantityDefault ?? '') }}";
        var oldPrice = "{{ old('unit_price', $daybookFactoryUnitPriceDefault ?? '') }}";

        var keepSelected = String(subSelect.value || '').trim() || oldSelected;
        syncFactoryOptionList(keepSelected);
        if (String(qtyInput.value || '').trim() === '' && oldQty !== '') qtyInput.value = oldQty;
        if (String(priceInput.value || '').trim() === '' && oldPrice !== '') priceInput.value = oldPrice;
        // On init keep any existing/old unit; only fill from default when empty.
        setUnitFromSubCategoryId(subSelect.value, false);
        updateAmountFromQtyPrice();
    }

    window.__daybookSyncFactoryMode = syncMode;

    syncMode();

    // Picking a sub category overwrites the unit with its default; the user can still edit it afterwards.
    subSelect.addEventListener('change', function () {
        setUnitFromSubCategoryId(subSelect.value, true);
    });

    if (unitInput && unitList) {
        unitInput.addEventListener('focus', function () {
            openFilteredUnitList();
        });
        unitInput.addEventListener('input', function () {
            openFilteredUnitList();
        });
        unitInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideUnitList();
            }
        });
        document.addEventListener('click', function (e) {
            if (unitWrap && !unitWrap.contains(e.target)) hideUnitList();
        });
    }

    qtyInput.addEventListener('input', function () {
        enforceQtyInput();
        updateAmountFromQtyPrice();
    });
    priceInput.addEventListener('input', function () {
        updateAmountFromQtyPrice();
    });
})();
</script>
