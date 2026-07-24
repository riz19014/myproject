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
    var purchaseFileHidden = document.getElementById('daybook_form_purchase_file_id');
    var purchaseFileSearch = document.getElementById('daybook_form_purchase_file_search');
    var purchaseFileList = document.getElementById('daybook_form_purchase_file_listbox');
    var purchaseFileWrap = purchaseFileSearch ? purchaseFileSearch.closest('.daybook-form-combo') : null;
    var fileResetBtn = document.getElementById('daybook_form_file_reset');
    var fileCreateBtn = document.getElementById('daybook_form_file_create');
    var purchaseFileDefaultEl = document.getElementById('daybook-form-purchase-file-default');
    var currentSaleFileRows = [];

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
        if (!fileResetBtn || !purchaseFileHidden) return;
        var hasProject = projectHidden && String(projectHidden.value || '').trim() !== '';
        var hasFile = hasProject && purchaseFileSearch && !purchaseFileSearch.disabled && String(purchaseFileHidden.value || '').trim() !== '';
        toggleResetBtn(fileResetBtn, hasFile);
    }

    function syncFileCreateVisibility() {
        if (!fileCreateBtn) return;
        var hasProject = projectHidden && String(projectHidden.value || '').trim() !== '';
        fileCreateBtn.classList.toggle('d-none', !hasProject);
        fileCreateBtn.disabled = !hasProject;
        fileCreateBtn.setAttribute('aria-hidden', hasProject ? 'false' : 'true');
    }

    function syncSubResetVisibility() {
        if (!subResetBtn || !subHidden) return;
        toggleResetBtn(subResetBtn, String(subHidden.value || '').trim() !== '');
    }

    function syncAllFieldResetVisibility() {
        syncProjectResetVisibility();
        syncPartyResetVisibility();
        syncFileResetVisibility();
        syncFileCreateVisibility();
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

    function saleFileLabel(file) {
        if (!file) return '';
        var label = file.label || file.file_name || ('File #' + file.id);
        if (file.is_fully_sold) {
            label += ' — Fully Sold';
        } else if (file.remaining_label && file.remaining_label !== '—') {
            label += ' (Avail: ' + file.remaining_label + ')';
        }
        return label;
    }

    function hidePurchaseFileList() {
        if (!purchaseFileList || !purchaseFileSearch) return;
        purchaseFileList.classList.add('d-none');
        purchaseFileList.setAttribute('hidden', '');
        purchaseFileSearch.setAttribute('aria-expanded', 'false');
        setComboOpen(purchaseFileWrap, false);
    }

    function showPurchaseFileList() {
        if (!purchaseFileList || !purchaseFileSearch) return;
        purchaseFileList.classList.remove('d-none');
        purchaseFileList.removeAttribute('hidden');
        purchaseFileSearch.setAttribute('aria-expanded', 'true');
        setComboOpen(purchaseFileWrap, true);
    }

    function filterSaleFileRows(q) {
        var nq = (q || '').toLowerCase();
        if (!nq) return currentSaleFileRows.slice();
        return currentSaleFileRows.filter(function (row) {
            return saleFileLabel(row).toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderPurchaseFileList(rows) {
        if (!purchaseFileList || !purchaseFileSearch || !purchaseFileHidden) return;
        purchaseFileList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = currentSaleFileRows.length ? 'No sale files match.' : 'No sale files for this project.';
            purchaseFileList.appendChild(li0);
            showPurchaseFileList();
            return;
        }
        rows.forEach(function (row) {
            var selectedId = String(purchaseFileHidden.value || '').trim();
            var isFullySold = !!row.is_fully_sold;
            var isSelected = selectedId !== '' && String(row.id) === selectedId;
            if (isFullySold && !isSelected) {
                var liDisabled = document.createElement('li');
                liDisabled.setAttribute('role', 'presentation');
                liDisabled.className = 'daybook-form-combo-empty';
                liDisabled.textContent = saleFileLabel(row);
                purchaseFileList.appendChild(liDisabled);
                return;
            }
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = saleFileLabel(row);
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                purchaseFileHidden.value = String(row.id);
                purchaseFileSearch.value = saleFileLabel(row);
                clearSoldAreaInputs();
                syncSaleFileMeta(currentSaleFile());
                hidePurchaseFileList();
                syncFileResetVisibility();
            });
            li.appendChild(btn);
            purchaseFileList.appendChild(li);
        });
        showPurchaseFileList();
    }

    function openFilteredPurchaseFileList() {
        if (!purchaseFileSearch || purchaseFileSearch.disabled) return;
        renderPurchaseFileList(filterSaleFileRows(purchaseFileSearch.value));
    }

    function syncPurchaseFileSelect(projectId, selectedFileId) {
        if (!purchaseFileHidden || !purchaseFileSearch) return;
        var pid = String(projectId || '').trim();
        var selected = String(selectedFileId != null ? selectedFileId : '').trim();
        hidePurchaseFileList();

        if (!pid) {
            currentSaleFileRows = [];
            purchaseFileHidden.value = '';
            purchaseFileSearch.value = '';
            purchaseFileSearch.placeholder = 'Select project first…';
            purchaseFileSearch.disabled = true;
            syncSaleFileMeta(null);
            syncFileResetVisibility();
            syncFileCreateVisibility();
            return;
        }

        var project = formProjectRows.find(function (r) { return String(r.id) === pid; });
        currentSaleFileRows = (project && (project.sale_files || project.purchase_files))
            ? (project.sale_files || project.purchase_files).slice()
            : [];

        purchaseFileSearch.disabled = false;
        purchaseFileSearch.placeholder = currentSaleFileRows.length ? 'Search sale file…' : 'No sale files for this project';

        var match = selected
            ? currentSaleFileRows.find(function (f) { return String(f.id) === selected; })
            : null;

        if (match) {
            purchaseFileHidden.value = String(match.id);
            purchaseFileSearch.value = saleFileLabel(match);
        } else {
            purchaseFileHidden.value = '';
            purchaseFileSearch.value = '';
        }

        syncSaleFileMeta(currentSaleFile());
        syncFileResetVisibility();
        syncFileCreateVisibility();
    }
    window.__daybookSyncPurchaseFileSelect = syncPurchaseFileSelect;

    /**
     * Apply Sale wizard result onto the New Entry form
     * (project, optional file + sold area, amount, description, type).
     */
    window.__daybookApplySaleToForm = function (payload) {
        payload = payload || {};
        var project = formProjectRows.find(function (r) { return String(r.id) === String(payload.projectId); });
        if (!project || !projectHidden || !projectSearch) return false;

        projectHidden.value = String(project.id);
        projectSearch.value = projectLabeledName(project);
        clearSoldAreaInputs();
        syncPurchaseFileSelect(project.id, payload.purchaseFileId || '');
        syncProjectResetVisibility();
        if (typeof window.__daybookSyncFactoryMode === 'function') window.__daybookSyncFactoryMode();
        if (typeof window.__daybookSyncAllFieldResetVisibility === 'function') window.__daybookSyncAllFieldResetVisibility();

        var typeSelect = document.getElementById('entry_type');
        if (typeSelect) {
            typeSelect.value = payload.type || 'cash_in';
            typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        if (payload.soldAreaQty != null && payload.soldAreaQty !== '') {
            var qtyEl = document.getElementById('daybook_sold_area_qty');
            var unitEl = document.getElementById('daybook_sold_area_unit');
            if (qtyEl) qtyEl.value = String(payload.soldAreaQty);
            if (unitEl && payload.soldAreaUnit) unitEl.value = String(payload.soldAreaUnit);
            syncSaleFileMeta(currentSaleFile());
        }

        if (payload.amount != null && payload.amount !== '') {
            var amountEl = document.getElementById('entry_amount');
            if (amountEl) {
                amountEl.value = String(payload.amount);
                amountEl.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }

        if (payload.description != null && payload.description !== '') {
            var descEl = document.getElementById('entry_description');
            if (descEl) descEl.value = String(payload.description);
        }

        return true;
    };

    function currentSaleFile() {
        if (!purchaseFileHidden || !projectHidden) return null;
        var pid = String(projectHidden.value || '').trim();
        var fid = String(purchaseFileHidden.value || '').trim();
        if (!pid || !fid) return null;
        return currentSaleFileRows.find(function (f) { return String(f.id) === fid; }) || null;
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

    function setComboOpen(wrap, open) {
        if (!wrap) return;
        wrap.classList.toggle('is-open', !!open);
        var panel = wrap.closest('.daybook-panel');
        if (!panel) return;
        if (open) {
            panel.classList.add('is-combo-open');
            return;
        }
        if (!panel.querySelector('.daybook-form-combo.is-open')) {
            panel.classList.remove('is-combo-open');
        }
    }

    function hideProjectList() {
        projectList.classList.add('d-none');
        projectList.setAttribute('hidden', '');
        projectSearch.setAttribute('aria-expanded', 'false');
        setComboOpen(projectWrap, false);
    }

    function showProjectList() {
        projectList.classList.remove('d-none');
        projectList.removeAttribute('hidden');
        projectSearch.setAttribute('aria-expanded', 'true');
        setComboOpen(projectWrap, true);
    }

    function projectIsDha(row) {
        return !!(row && (row.is_dha === true || row.is_dha === 1 || row.is_dha === '1'));
    }

    function projectLabeledName(row) {
        if (!row) return '';
        return (projectIsDha(row) ? '🟢 ' : '🟡 ') + (row.label || '');
    }

    function appendProjectNameNode(parent, row) {
        var wrap = document.createElement('span');
        wrap.className = 'project-name-with-dot';
        var dot = document.createElement('span');
        dot.className = 'project-dha-dot ' + (projectIsDha(row) ? 'is-dha' : 'is-not-dha');
        dot.setAttribute('aria-hidden', 'true');
        var text = document.createElement('span');
        text.className = 'project-name-with-dot__text';
        text.textContent = row.label || '';
        wrap.appendChild(dot);
        wrap.appendChild(text);
        parent.appendChild(wrap);
    }

    function filterProjectRows(q) {
        var nq = (q || '').toLowerCase().replace(/[🟢🟡]/g, '').trim();
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
            appendProjectNameNode(btn, row);
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                projectHidden.value = String(row.id);
                projectSearch.value = projectLabeledName(row);
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
        setComboOpen(partyWrap, false);
    }

    function showPartyFormList() {
        partyList.classList.remove('d-none');
        partyList.removeAttribute('hidden');
        partySearch.setAttribute('aria-expanded', 'true');
        setComboOpen(partyWrap, true);
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
        setComboOpen(subWrap, false);
    }

    function showSubFormList() {
        if (!subList || !subSearch) return;
        subList.classList.remove('d-none');
        subList.removeAttribute('hidden');
        subSearch.setAttribute('aria-expanded', 'true');
        setComboOpen(subWrap, true);
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
            if (pr) projectSearch.value = projectLabeledName(pr);
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

    if (purchaseFileHidden && purchaseFileSearch && purchaseFileList) {
        purchaseFileSearch.addEventListener('focus', function () {
            openFilteredPurchaseFileList();
        });
        purchaseFileSearch.addEventListener('input', function () {
            purchaseFileHidden.value = '';
            clearSoldAreaInputs();
            syncSaleFileMeta(null);
            syncFileResetVisibility();
            openFilteredPurchaseFileList();
        });
        purchaseFileSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hidePurchaseFileList();
            }
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

    if (fileResetBtn && purchaseFileHidden && purchaseFileSearch) {
        fileResetBtn.addEventListener('click', function () {
            if (!purchaseFileSearch.disabled) {
                purchaseFileHidden.value = '';
                purchaseFileSearch.value = '';
            }
            clearSoldAreaInputs();
            syncSaleFileMeta(null);
            hidePurchaseFileList();
            syncFileResetVisibility();
            purchaseFileSearch.focus();
        });
    }

    document.addEventListener('click', function (e) {
        if (projectWrap && !projectWrap.contains(e.target)) hideProjectList();
        if (partyWrap && !partyWrap.contains(e.target)) hidePartyFormList();
        if (subWrap && !subWrap.contains(e.target)) hideSubFormList();
        if (purchaseFileWrap && !purchaseFileWrap.contains(e.target)) hidePurchaseFileList();
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
                            is_dha: result.data.is_dha !== undefined ? !!result.data.is_dha : true,
                            purchase_files: [],
                            sale_files: [],
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
                                    r.is_dha = row.is_dha;
                                    r.party_areas = row.party_areas;
                                    r.parties_total_marla = row.parties_total_marla;
                                    r.parties_total_label = row.parties_total_label;
                                }
                            });
                        }
                        projectFormHidden.value = nid;
                        projectFormSearch.value = (row.is_dha ? '🟢 ' : '🟡 ') + result.data.name;
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
    var projectHidden = document.getElementById('daybook_form_project_id');
    var projectSearch = document.getElementById('daybook_form_project_search');
    var fileCreateBtn = document.getElementById('daybook_form_file_create');
    var modalEl = document.getElementById('daybookCreatePurchaseFileModal');
    var projectIdInput = document.getElementById('daybook_modal_file_project_id');
    var projectNameInput = document.getElementById('daybook_modal_file_project_name');
    var nameInput = document.getElementById('daybook_modal_file_name');
    var dateInput = document.getElementById('daybook_modal_file_date');
    var primaryBtn = document.getElementById('daybook_modal_file_primary');
    var errEl = document.getElementById('daybook_modal_file_error');
    var token = document.querySelector('meta[name="csrf-token"]');
    if (!projectHidden || !fileCreateBtn || !modalEl || !nameInput || !dateInput || !primaryBtn || !token || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });

    function showModalErr(msg) {
        if (!errEl) return;
        errEl.textContent = msg || '';
        errEl.classList.toggle('d-none', !msg);
    }

    function resetFileModalFields() {
        if (nameInput) nameInput.value = '';
        if (dateInput) dateInput.value = "{{ now()->toDateString() }}";
        if (projectIdInput) projectIdInput.value = '';
        if (projectNameInput) projectNameInput.value = '';
    }

    function clearPrimaryLoading() {
        primaryBtn.classList.remove('is-loading');
        primaryBtn.disabled = false;
        primaryBtn.removeAttribute('aria-busy');
        primaryBtn.setAttribute('aria-label', 'Create sale file');
    }

    function setPrimaryLoading(loading) {
        if (loading) {
            primaryBtn.classList.add('is-loading');
            primaryBtn.disabled = true;
            primaryBtn.setAttribute('aria-busy', 'true');
            primaryBtn.setAttribute('aria-label', 'Saving…');
        } else {
            clearPrimaryLoading();
        }
    }

    function validateModal() {
        var projectId = (projectIdInput && (projectIdInput.value || '').trim()) || '';
        if (!projectId) {
            showModalErr('Select a project first.');
            return false;
        }
        var name = (nameInput.value || '').trim();
        if (!name) {
            showModalErr('Please enter a file name.');
            if (window.daybookModalFocusText) window.daybookModalFocusText(nameInput);
            else nameInput.focus();
            return false;
        }
        if (!(dateInput.value || '').trim()) {
            showModalErr('Please select a file date.');
            dateInput.focus();
            return false;
        }
        return true;
    }

    function pushSaleFileToProject(projectId, filePayload) {
        var rows = window.__daybookFormProjectRows || [];
        var project = rows.find(function (r) { return String(r.id) === String(projectId); });
        if (!project || !filePayload || filePayload.id == null) return;
        if (!Array.isArray(project.sale_files)) project.sale_files = [];
        if (!Array.isArray(project.purchase_files)) project.purchase_files = [];
        var fid = String(filePayload.id);
        if (!project.sale_files.some(function (f) { return String(f.id) === fid; })) {
            project.sale_files.push(filePayload);
        }
        if (!project.purchase_files.some(function (f) { return String(f.id) === fid; })) {
            project.purchase_files.push({
                id: filePayload.id,
                label: filePayload.label || filePayload.file_name || ('File #' + filePayload.id)
            });
        }
    }

    fileCreateBtn.addEventListener('click', function () {
        showModalErr('');
        var pid = String(projectHidden.value || '').trim();
        if (!pid) {
            showModalErr('Select a project first.');
            return;
        }
        var rows = window.__daybookFormProjectRows || [];
        var project = rows.find(function (r) { return String(r.id) === pid; });
        if (!project) {
            showModalErr('Select a valid project first.');
            return;
        }
        resetFileModalFields();
        if (projectIdInput) projectIdInput.value = pid;
        if (projectNameInput) projectNameInput.value = project.label || (projectSearch ? projectSearch.value : '') || ('Project #' + pid);
        fileCreateBtn.blur();
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
        resetFileModalFields();
        clearPrimaryLoading();
    });

    modalEl.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter') return;
        if (e.target && e.target.closest && e.target.closest('a')) return;
        if (primaryBtn && !primaryBtn.disabled) primaryBtn.click();
    });

    primaryBtn.addEventListener('click', function () {
        if (primaryBtn.classList.contains('is-loading')) return;
        showModalErr('');
        if (!validateModal()) return;

        var payload = {
            project_id: parseInt(projectIdInput.value, 10),
            file_name: (nameInput.value || '').trim(),
            file_date: (dateInput.value || '').trim()
        };

        setPrimaryLoading(true);
        fetch(@json(route('purchase.files.quick-store')), {
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
                    var pid = String(payload.project_id);
                    pushSaleFileToProject(pid, result.data);
                    if (typeof window.__daybookSyncPurchaseFileSelect === 'function') {
                        window.__daybookSyncPurchaseFileSelect(pid, result.data.id);
                    }
                    if (typeof window.__daybookSyncAllFieldResetVisibility === 'function') {
                        window.__daybookSyncAllFieldResetVisibility();
                    }
                    resetFileModalFields();
                    showModalErr('');
                    modal.hide();
                } else {
                    var msg = 'Could not create sale file.';
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
        var showBank = m === 'online' || m === 'cheque' || m === 'payorder' || m === 'cash_deposit';
        var showRef = m === 'cheque' || m === 'payorder' || m === 'cash_deposit';
        bankRow.classList.toggle('d-none', !showBank);
        refRow.classList.toggle('d-none', !showRef);
        if (refLabel) {
            if (m === 'payorder') {
                refLabel.textContent = 'Pay order reference #';
            } else if (m === 'cash_deposit') {
                refLabel.textContent = 'Deposit reference #';
            } else {
                refLabel.textContent = 'Cheque #';
            }
        }
        if (refInput) {
            if (m === 'payorder') {
                refInput.placeholder = 'Reference number';
            } else if (m === 'cash_deposit') {
                refInput.placeholder = 'Deposit slip / reference number';
            } else {
                refInput.placeholder = 'Cheque number';
            }
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
        if (wrap) {
            wrap.classList.remove('is-open');
            var panel = wrap.closest('.daybook-panel');
            if (panel && !panel.querySelector('.daybook-form-combo.is-open')) {
                panel.classList.remove('is-combo-open');
            }
        }
    }

    function showBankList() {
        list.classList.remove('d-none');
        list.removeAttribute('hidden');
        search.setAttribute('aria-expanded', 'true');
        if (wrap) {
            wrap.classList.add('is-open');
            var panel = wrap.closest('.daybook-panel');
            if (panel) panel.classList.add('is-combo-open');
        }
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

/* Paid by temporarily hidden from daybook UI
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
*/
(function () {
    var projectHidden = document.getElementById('daybook_form_project_id');
    var partySubWrap = document.getElementById('daybook_party_sub_category_wrap');
    var factoryWrap = document.getElementById('daybook_factory_fields');
    var subHidden = document.getElementById('daybook_factory_sub_category_id');
    var subSearch = document.getElementById('daybook_factory_sub_search');
    var subList = document.getElementById('daybook_factory_sub_listbox');
    var subWrap = subSearch ? subSearch.closest('.daybook-form-combo') : null;
    var unitInput = document.getElementById('daybook_factory_unit');
    var unitList = document.getElementById('daybook_factory_unit_listbox');
    var unitWrap = unitInput ? unitInput.closest('.daybook-form-combo') : null;
    var unitJsonEl = document.getElementById('daybook-form-units-json');
    var qtyInput = document.getElementById('daybook_factory_quantity');
    var priceInput = document.getElementById('daybook_factory_unit_price');
    var amountInput = document.getElementById('entry_amount');
    var factoryJsonEl = document.getElementById('daybook-form-factory-sub-json');
    if (!projectHidden || !factoryWrap || !subHidden || !subSearch || !subList || !qtyInput || !priceInput || !amountInput) return;

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

    function setFactoryComboOpen(wrap, open) {
        if (!wrap) return;
        wrap.classList.toggle('is-open', !!open);
        var panel = wrap.closest('.daybook-panel');
        if (!panel) return;
        if (open) {
            panel.classList.add('is-combo-open');
            return;
        }
        if (!panel.querySelector('.daybook-form-combo.is-open')) {
            panel.classList.remove('is-combo-open');
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

    function hideFactorySubList() {
        subList.classList.add('d-none');
        subList.setAttribute('hidden', '');
        subSearch.setAttribute('aria-expanded', 'false');
        setFactoryComboOpen(subWrap, false);
    }

    function showFactorySubList() {
        subList.classList.remove('d-none');
        subList.removeAttribute('hidden');
        subSearch.setAttribute('aria-expanded', 'true');
        setFactoryComboOpen(subWrap, true);
    }

    function filterFactorySubRows(q) {
        var nq = (q || '').toLowerCase().trim();
        if (!nq) return factorySubRows.slice();
        return factorySubRows.filter(function (row) {
            return String(row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function renderFactorySubList(rows) {
        subList.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'daybook-form-combo-empty';
            li0.setAttribute('role', 'presentation');
            li0.textContent = factorySubRows.length ? 'No sub categories match.' : 'No sub categories yet.';
            subList.appendChild(li0);
            showFactorySubList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.dataset.id = String(row.id);
            btn.textContent = row.label || ('#' + row.id);
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
            });
            btn.addEventListener('click', function () {
                subHidden.value = String(row.id);
                subSearch.value = row.label || ('#' + row.id);
                hideFactorySubList();
                setUnitFromSubCategoryId(row.id, true);
            });
            li.appendChild(btn);
            subList.appendChild(li);
        });
        showFactorySubList();
    }

    function openFilteredFactorySubList() {
        renderFactorySubList(filterFactorySubRows(subSearch.value));
    }

    function syncFactoryOptionList(selectedId) {
        var selected = String(selectedId || '').trim();
        var match = selected
            ? factorySubRows.find(function (r) { return String(r.id) === selected; })
            : null;
        if (match) {
            subHidden.value = String(match.id);
            subSearch.value = match.label || ('#' + match.id);
        } else {
            subHidden.value = '';
            subSearch.value = '';
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
        setFactoryComboOpen(unitWrap, false);
    }

    function showUnitList() {
        if (!unitList || !unitInput) return;
        unitList.classList.remove('d-none');
        unitList.removeAttribute('hidden');
        unitInput.setAttribute('aria-expanded', 'true');
        setFactoryComboOpen(unitWrap, true);
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

        setRequired(subHidden, on);
        setRequired(qtyInput, on);
        setRequired(priceInput, on);
        setReadOnly(amountInput, on);
        subSearch.disabled = !on;

        if (!on) {
            hideFactorySubList();
            if (subHidden) subHidden.value = '';
            if (subSearch) subSearch.value = '';
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

        var keepSelected = String(subHidden.value || '').trim() || oldSelected;
        syncFactoryOptionList(keepSelected);
        if (String(qtyInput.value || '').trim() === '' && oldQty !== '') qtyInput.value = oldQty;
        if (String(priceInput.value || '').trim() === '' && oldPrice !== '') priceInput.value = oldPrice;
        // On init keep any existing/old unit; only fill from default when empty.
        setUnitFromSubCategoryId(subHidden.value, false);
        updateAmountFromQtyPrice();
    }

    window.__daybookSyncFactoryMode = syncMode;

    syncMode();

    subSearch.addEventListener('focus', function () {
        if (subSearch.disabled) return;
        openFilteredFactorySubList();
    });
    subSearch.addEventListener('input', function () {
        subHidden.value = '';
        openFilteredFactorySubList();
    });
    subSearch.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideFactorySubList();
        }
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
    }

    document.addEventListener('click', function (e) {
        if (subWrap && !subWrap.contains(e.target)) hideFactorySubList();
        if (unitWrap && !unitWrap.contains(e.target)) hideUnitList();
    });

    qtyInput.addEventListener('input', function () {
        enforceQtyInput();
        updateAmountFromQtyPrice();
    });
    priceInput.addEventListener('input', function () {
        updateAmountFromQtyPrice();
    });
})();

(function () {
    var openBtn = document.getElementById('daybook_form_sale_open');
    var modalEl = document.getElementById('daybookSaleWizardModal');
    if (!openBtn || !modalEl || typeof bootstrap === 'undefined') return;

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl, { focus: false });
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = csrf ? csrf.getAttribute('content') : '';

    var step = 1;
    var selectedProject = null;
    var selectedItem = null;
    var itemRows = [];
    var busy = false;

    var projectHidden = document.getElementById('daybook_sale_project_id');
    var projectSearch = document.getElementById('daybook_sale_project_search');
    var projectList = document.getElementById('daybook_sale_project_listbox');
    var projectWrap = projectSearch ? projectSearch.closest('.daybook-form-combo') : null;

    var itemHidden = document.getElementById('daybook_sale_item_id');
    var itemSearch = document.getElementById('daybook_sale_item_search');
    var itemList = document.getElementById('daybook_sale_item_listbox');
    var itemWrap = itemSearch ? itemSearch.closest('.daybook-form-combo') : null;

    var errEl = document.getElementById('daybook_sale_wizard_error');
    var subtitleEl = document.getElementById('daybook_sale_wizard_subtitle');
    var step2Label = document.getElementById('daybook_sale_step2_label');
    var itemLabel = document.getElementById('daybook_sale_item_label');
    var modeBadge = document.getElementById('daybook_sale_mode_badge');
    var backBtn = document.getElementById('daybook_sale_wizard_back');
    var primaryBtn = document.getElementById('daybook_sale_wizard_primary');
    var primaryLabel = document.getElementById('daybook_sale_wizard_primary_label');

    function projectRows() {
        return Array.isArray(window.__daybookFormProjectRows) ? window.__daybookFormProjectRows : [];
    }

    function isDha(row) {
        return !!(row && (row.is_dha === true || row.is_dha === 1 || row.is_dha === '1'));
    }

    function projectLabeledName(row) {
        if (!row) return '';
        return (isDha(row) ? '🟢 ' : '🟡 ') + (row.label || '');
    }

    function appendProjectNameNode(parent, row) {
        var wrap = document.createElement('span');
        wrap.className = 'project-name-with-dot';
        var dot = document.createElement('span');
        dot.className = 'project-dha-dot ' + (isDha(row) ? 'is-dha' : 'is-not-dha');
        dot.setAttribute('aria-hidden', 'true');
        var text = document.createElement('span');
        text.className = 'project-name-with-dot__text';
        text.textContent = row.label || '';
        wrap.appendChild(dot);
        wrap.appendChild(text);
        parent.appendChild(wrap);
    }

    function setBusy(on) {
        busy = !!on;
        modalEl.classList.toggle('is-busy', busy);
        if (primaryBtn) primaryBtn.disabled = busy;
        if (backBtn) backBtn.disabled = busy || step <= 1;
    }

    function showError(msg) {
        if (!errEl) return;
        if (!msg) {
            errEl.textContent = '';
            errEl.classList.add('d-none');
            return;
        }
        errEl.textContent = msg;
        errEl.classList.remove('d-none');
    }

    function setComboOpen(wrap, open) {
        if (!wrap) return;
        wrap.classList.toggle('is-open', !!open);
    }

    function hideList(list, search, wrap) {
        if (!list || !search) return;
        list.classList.add('d-none');
        list.setAttribute('hidden', '');
        search.setAttribute('aria-expanded', 'false');
        setComboOpen(wrap, false);
    }

    function showList(list, search, wrap) {
        if (!list || !search) return;
        list.classList.remove('d-none');
        list.removeAttribute('hidden');
        search.setAttribute('aria-expanded', 'true');
        setComboOpen(wrap, true);
    }

    function resetWizard() {
        step = 1;
        selectedProject = null;
        selectedItem = null;
        itemRows = [];
        if (projectHidden) projectHidden.value = '';
        if (projectSearch) projectSearch.value = '';
        if (itemHidden) itemHidden.value = '';
        if (itemSearch) itemSearch.value = '';
        var soldQty = document.getElementById('daybook_sale_sold_qty');
        var soldUnit = document.getElementById('daybook_sale_sold_unit');
        var fileAmount = document.getElementById('daybook_sale_file_amount');
        var fileNote = document.getElementById('daybook_sale_file_note');
        var customer = document.getElementById('daybook_sale_customer_id');
        var acre = document.getElementById('daybook_sale_plot_acre');
        var kanal = document.getElementById('daybook_sale_plot_kanal');
        var marla = document.getElementById('daybook_sale_plot_marla');
        var sqft = document.getElementById('daybook_sale_plot_sqft');
        var plotAmount = document.getElementById('daybook_sale_plot_amount');
        if (soldQty) soldQty.value = '';
        if (soldUnit) soldUnit.value = 'marla';
        if (fileAmount) fileAmount.value = '';
        if (fileNote) fileNote.value = '';
        if (customer) customer.value = '';
        if (acre) acre.value = '0';
        if (kanal) kanal.value = '0';
        if (marla) marla.value = '0';
        if (sqft) sqft.value = '0';
        if (plotAmount) plotAmount.value = '';
        hideList(projectList, projectSearch, projectWrap);
        hideList(itemList, itemSearch, itemWrap);
        showError('');
        renderStep();
    }

    function syncModeLabels() {
        var dha = isDha(selectedProject);
        if (step2Label) step2Label.textContent = dha ? 'File' : 'Plot';
        if (itemLabel) itemLabel.textContent = dha ? 'Sale file' : 'Plot file';
        if (modeBadge) {
            modeBadge.textContent = dha ? 'File sale (DHA)' : 'Plot sale';
            modeBadge.className = 'daybook-sale-mode-badge ' + (dha ? 'is-dha' : 'is-plot');
        }
        if (itemSearch) {
            itemSearch.placeholder = dha ? 'Search sale file…' : 'Search plot file…';
        }
    }

    function loadItemRows() {
        if (!selectedProject) {
            itemRows = [];
            return;
        }
        if (isDha(selectedProject)) {
            itemRows = (selectedProject.sale_files || selectedProject.purchase_files || []).slice();
        } else {
            itemRows = (selectedProject.plot_files || []).slice();
        }
    }

    function currentItem() {
        if (!itemHidden) return null;
        var id = String(itemHidden.value || '').trim();
        if (!id) return null;
        return itemRows.find(function (r) { return String(r.id) === id; }) || null;
    }

    function itemLabelText(row) {
        if (!row) return '';
        var base = row.label || row.file_name || row.file_number || ('#' + row.id);
        if (row.is_fully_sold) return base + ' · Fully sold';
        if (isDha(selectedProject) && row.is_file_sale === false) return base + ' · Not in File Sale';
        return base;
    }

    function syncItemMeta(row) {
        var meta = document.getElementById('daybook_sale_item_meta');
        var remainingEl = document.getElementById('daybook_sale_item_remaining');
        var totalsEl = document.getElementById('daybook_sale_item_totals');
        var statusWrap = document.getElementById('daybook_sale_item_status_wrap');
        var statusEl = document.getElementById('daybook_sale_item_status');
        if (!meta) return;
        if (!row) {
            meta.classList.add('d-none');
            return;
        }
        meta.classList.remove('d-none');
        if (remainingEl) remainingEl.textContent = row.remaining_label || '—';
        if (totalsEl) totalsEl.textContent = (row.total_label || '—') + ' / ' + (row.sold_label || '—');
        if (statusWrap && statusEl) {
            if (row.status) {
                statusWrap.classList.remove('d-none');
                statusEl.textContent = row.status;
            } else if (isDha(selectedProject)) {
                statusWrap.classList.remove('d-none');
                statusEl.textContent = row.is_file_sale ? 'In File Sale' : 'Not moved to File Sale';
            } else {
                statusWrap.classList.add('d-none');
                statusEl.textContent = '—';
            }
        }
    }

    function syncDetailsSummaries() {
        var fileSum = document.getElementById('daybook_sale_file_summary');
        var plotSum = document.getElementById('daybook_sale_plot_summary');
        var filePanel = document.getElementById('daybook_sale_details_file');
        var plotPanel = document.getElementById('daybook_sale_details_plot');
        var dha = isDha(selectedProject);
        if (filePanel) filePanel.classList.toggle('d-none', !dha);
        if (plotPanel) plotPanel.classList.toggle('d-none', dha);
        var projName = selectedProject ? (selectedProject.label || '') : '—';
        var itemName = selectedItem ? (selectedItem.label || selectedItem.file_name || selectedItem.file_number || '') : '—';
        var avail = selectedItem ? (selectedItem.remaining_label || '—') : '—';
        if (fileSum) {
            fileSum.innerHTML = 'Project: <strong></strong> · File: <strong></strong> · Available: <strong></strong>';
            var strongs = fileSum.querySelectorAll('strong');
            if (strongs[0]) strongs[0].textContent = projName;
            if (strongs[1]) strongs[1].textContent = itemName;
            if (strongs[2]) strongs[2].textContent = avail;
        }
        if (plotSum) {
            plotSum.innerHTML = 'Project: <strong></strong> · Plot file: <strong></strong> · Available: <strong></strong>';
            var ps = plotSum.querySelectorAll('strong');
            if (ps[0]) ps[0].textContent = projName;
            if (ps[1]) ps[1].textContent = itemName;
            if (ps[2]) ps[2].textContent = avail;
        }
    }

    function renderStep() {
        modalEl.querySelectorAll('[data-sale-panel]').forEach(function (panel) {
            var n = parseInt(panel.getAttribute('data-sale-panel'), 10);
            panel.classList.toggle('d-none', n !== step);
        });
        modalEl.querySelectorAll('.daybook-sale-steps__item').forEach(function (el) {
            var n = parseInt(el.getAttribute('data-step'), 10);
            el.classList.toggle('is-active', n === step);
            el.classList.toggle('is-done', n < step);
        });
        if (backBtn) backBtn.disabled = busy || step <= 1;
        if (primaryLabel) {
            primaryLabel.textContent = step < 3 ? 'Next' : (isDha(selectedProject) ? 'Apply to entry' : 'Save plot sale');
        }
        if (subtitleEl) {
            if (step === 1) subtitleEl.textContent = 'Select a project to begin.';
            else if (step === 2) subtitleEl.textContent = isDha(selectedProject)
                ? 'Choose a sale file for this DHA project.'
                : 'Choose a plot file for this non-DHA project.';
            else subtitleEl.textContent = isDha(selectedProject)
                ? 'Enter sold area and amount for the file sale.'
                : 'Enter customer, plot size, and amount.';
        }
        syncModeLabels();
        syncItemMeta(selectedItem);
        syncDetailsSummaries();
    }

    function filterProjects(q) {
        var nq = (q || '').toLowerCase().replace(/[🟢🟡]/g, '').trim();
        var rows = projectRows();
        if (!nq) return rows.slice();
        return rows.filter(function (row) {
            return (row.label || '').toLowerCase().indexOf(nq) !== -1;
        });
    }

    function filterItems(q) {
        var nq = (q || '').toLowerCase().trim();
        if (!nq) return itemRows.slice();
        return itemRows.filter(function (row) {
            var label = (row.label || row.file_name || row.file_number || '').toLowerCase();
            return label.indexOf(nq) !== -1;
        });
    }

    function renderProjectList(rows) {
        if (!projectList) return;
        projectList.innerHTML = '';
        if (!rows.length) {
            var empty = document.createElement('li');
            empty.className = 'daybook-form-combo-empty';
            empty.setAttribute('role', 'presentation');
            empty.textContent = projectRows().length ? 'No projects match.' : 'No projects yet.';
            projectList.appendChild(empty);
            showList(projectList, projectSearch, projectWrap);
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            appendProjectNameNode(btn, row);
            var hint = document.createElement('span');
            hint.className = 'daybook-sale-option-hint';
            hint.textContent = isDha(row) ? 'File sale' : 'Plot sale';
            btn.appendChild(hint);
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function () {
                selectedProject = row;
                selectedItem = null;
                if (projectHidden) projectHidden.value = String(row.id);
                if (projectSearch) projectSearch.value = projectLabeledName(row);
                if (itemHidden) itemHidden.value = '';
                if (itemSearch) itemSearch.value = '';
                loadItemRows();
                hideList(projectList, projectSearch, projectWrap);
                showError('');
                syncModeLabels();
            });
            li.appendChild(btn);
            projectList.appendChild(li);
        });
        showList(projectList, projectSearch, projectWrap);
    }

    function renderItemList(rows) {
        if (!itemList) return;
        itemList.innerHTML = '';
        if (!rows.length) {
            var empty = document.createElement('li');
            empty.className = 'daybook-form-combo-empty';
            empty.setAttribute('role', 'presentation');
            empty.textContent = itemRows.length
                ? 'No matches.'
                : (isDha(selectedProject) ? 'No purchase files on this project.' : 'No plot files on this project.');
            itemList.appendChild(empty);
            showList(itemList, itemSearch, itemWrap);
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            var disabled = !!row.is_fully_sold || (isDha(selectedProject) && row.is_file_sale === false);
            if (disabled) btn.classList.add('is-disabled');
            btn.textContent = itemLabelText(row);
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function () {
                if (row.is_fully_sold) {
                    showError('This item is fully sold.');
                    return;
                }
                if (isDha(selectedProject) && row.is_file_sale === false) {
                    showError('Move this file to File Sale first before recording a file sale here.');
                    return;
                }
                selectedItem = row;
                if (itemHidden) itemHidden.value = String(row.id);
                if (itemSearch) itemSearch.value = row.label || row.file_name || row.file_number || '';
                hideList(itemList, itemSearch, itemWrap);
                showError('');
                syncItemMeta(row);
            });
            li.appendChild(btn);
            itemList.appendChild(li);
        });
        showList(itemList, itemSearch, itemWrap);
    }

    function goNext() {
        showError('');
        if (step === 1) {
            if (!selectedProject && projectHidden && projectHidden.value) {
                selectedProject = projectRows().find(function (r) { return String(r.id) === String(projectHidden.value); }) || null;
            }
            if (!selectedProject) {
                showError('Select a project first.');
                return;
            }
            loadItemRows();
            selectedItem = null;
            if (itemHidden) itemHidden.value = '';
            if (itemSearch) itemSearch.value = '';
            syncItemMeta(null);
            step = 2;
            renderStep();
            return;
        }
        if (step === 2) {
            selectedItem = currentItem() || selectedItem;
            if (!selectedItem) {
                showError(isDha(selectedProject) ? 'Select a sale file.' : 'Select a plot file.');
                return;
            }
            if (selectedItem.is_fully_sold) {
                showError('This item is fully sold.');
                return;
            }
            if (isDha(selectedProject) && selectedItem.is_file_sale === false) {
                showError('Move this file to File Sale first before recording a file sale here.');
                return;
            }
            step = 3;
            renderStep();
            return;
        }
        if (step === 3) {
            if (isDha(selectedProject)) {
                applyFileSale();
            } else {
                savePlotSale();
            }
        }
    }

    function goBack() {
        showError('');
        if (step <= 1) return;
        step -= 1;
        renderStep();
    }

    function applyFileSale() {
        var qtyEl = document.getElementById('daybook_sale_sold_qty');
        var unitEl = document.getElementById('daybook_sale_sold_unit');
        var amountEl = document.getElementById('daybook_sale_file_amount');
        var noteEl = document.getElementById('daybook_sale_file_note');
        var qty = qtyEl ? parseFloat(qtyEl.value) : 0;
        var unit = unitEl ? unitEl.value : 'marla';
        var amount = amountEl ? parseFloat(amountEl.value) : 0;
        if (!(qty > 0)) {
            showError('Enter area sold greater than zero.');
            return;
        }
        if (!(amount > 0)) {
            showError('Enter a sale amount greater than zero.');
            return;
        }
        var remaining = selectedItem && selectedItem.remaining_marla != null ? Number(selectedItem.remaining_marla) : null;
        // Soft check in marla approx for marla unit only; server validates on save.
        if (unit === 'marla' && remaining != null && qty > remaining + 0.0001) {
            showError('Area sold exceeds available balance (' + (selectedItem.remaining_label || remaining) + ').');
            return;
        }
        var note = noteEl && noteEl.value ? noteEl.value.trim() : '';
        var ok = typeof window.__daybookApplySaleToForm === 'function' && window.__daybookApplySaleToForm({
            projectId: selectedProject.id,
            purchaseFileId: selectedItem.id,
            soldAreaQty: qty,
            soldAreaUnit: unit,
            amount: amount.toFixed(2),
            description: note || ('File sale — ' + (selectedItem.label || selectedItem.file_name || '')),
            type: 'cash_in'
        });
        if (!ok) {
            showError('Could not apply sale to the entry form.');
            return;
        }
        modal.hide();
        var amountField = document.getElementById('entry_amount');
        if (amountField) amountField.focus();
    }

    function savePlotSale() {
        var customerEl = document.getElementById('daybook_sale_customer_id');
        var acreEl = document.getElementById('daybook_sale_plot_acre');
        var kanalEl = document.getElementById('daybook_sale_plot_kanal');
        var marlaEl = document.getElementById('daybook_sale_plot_marla');
        var sqftEl = document.getElementById('daybook_sale_plot_sqft');
        var amountEl = document.getElementById('daybook_sale_plot_amount');
        var customerId = customerEl ? customerEl.value : '';
        var acre = acreEl ? parseInt(acreEl.value, 10) || 0 : 0;
        var kanal = kanalEl ? parseInt(kanalEl.value, 10) || 0 : 0;
        var marla = marlaEl ? parseInt(marlaEl.value, 10) || 0 : 0;
        var sqft = sqftEl ? parseInt(sqftEl.value, 10) || 0 : 0;
        var amount = amountEl ? parseFloat(amountEl.value) : 0;
        if (!customerId) {
            showError('Select a customer.');
            return;
        }
        if (acre + kanal + marla + sqft <= 0) {
            showError('Enter at least one positive area value (acre, kanal, marla, or sq ft).');
            return;
        }
        if (!(amount > 0)) {
            showError('Enter a sale amount greater than zero.');
            return;
        }

        setBusy(true);
        showError('');
        fetch(@json(route('daybook.sale.plot')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                project_id: selectedProject.id,
                project_file_id: selectedItem.id,
                customer_id: parseInt(customerId, 10),
                area_acre: acre,
                area_kanal: kanal,
                area_marla: marla,
                area_sqft: sqft,
                total_amount: amount
            })
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        }).then(function (result) {
            setBusy(false);
            if (!result.ok) {
                var msg = 'Could not save plot sale.';
                if (result.data && result.data.errors) {
                    var firstKey = Object.keys(result.data.errors)[0];
                    if (firstKey && result.data.errors[firstKey] && result.data.errors[firstKey][0]) {
                        msg = result.data.errors[firstKey][0];
                    }
                } else if (result.data && result.data.message) {
                    msg = result.data.message;
                }
                showError(msg);
                return;
            }

            // Refresh plot file remaining in memory
            if (result.data && result.data.plot_file && selectedProject && Array.isArray(selectedProject.plot_files)) {
                var updated = result.data.plot_file;
                var idx = selectedProject.plot_files.findIndex(function (f) { return String(f.id) === String(updated.id); });
                if (idx >= 0) selectedProject.plot_files[idx] = updated;
                else selectedProject.plot_files.push(updated);
            }

            var customerName = '';
            if (customerEl && customerEl.selectedIndex >= 0) {
                customerName = customerEl.options[customerEl.selectedIndex].text || '';
            }
            var areaParts = [];
            if (acre) areaParts.push(acre + ' acre');
            if (kanal) areaParts.push(kanal + ' kanal');
            if (marla) areaParts.push(marla + ' marla');
            if (sqft) areaParts.push(sqft + ' sqft');
            var desc = 'Plot sale — ' + (selectedItem.label || selectedItem.file_number || '') +
                (customerName ? ' · ' + customerName : '') +
                (areaParts.length ? ' · ' + areaParts.join(' ') : '');

            var applied = typeof window.__daybookApplySaleToForm === 'function' && window.__daybookApplySaleToForm({
                projectId: selectedProject.id,
                purchaseFileId: null,
                amount: amount.toFixed(2),
                description: desc,
                type: 'cash_in'
            });
            if (!applied) {
                showError('Plot sale saved, but could not fill the entry form.');
                return;
            }
            modal.hide();
            var amountField = document.getElementById('entry_amount');
            if (amountField) amountField.focus();
        }).catch(function () {
            setBusy(false);
            showError('Network error while saving plot sale.');
        });
    }

    openBtn.addEventListener('click', function () {
        resetWizard();
        modal.show();
        if (projectSearch && window.daybookModalFocusText) {
            window.daybookModalFocusText(projectSearch, { scheduleEnsure: true });
        }
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        setBusy(false);
        hideList(projectList, projectSearch, projectWrap);
        hideList(itemList, itemSearch, itemWrap);
    });

    if (backBtn) backBtn.addEventListener('click', goBack);
    if (primaryBtn) primaryBtn.addEventListener('click', goNext);

    var fillRemainingBtn = document.getElementById('daybook_sale_fill_remaining');
    if (fillRemainingBtn) {
        fillRemainingBtn.addEventListener('click', function () {
            if (!selectedItem) return;
            var qtyEl = document.getElementById('daybook_sale_sold_qty');
            var unitEl = document.getElementById('daybook_sale_sold_unit');
            if (!qtyEl || !unitEl) return;
            unitEl.value = 'marla';
            var rem = selectedItem.remaining_marla != null ? Number(selectedItem.remaining_marla) : 0;
            qtyEl.value = rem > 0 ? String(rem) : '';
        });
    }

    if (projectSearch) {
        projectSearch.addEventListener('focus', function () {
            renderProjectList(filterProjects(projectSearch.value));
        });
        projectSearch.addEventListener('input', function () {
            selectedProject = null;
            if (projectHidden) projectHidden.value = '';
            renderProjectList(filterProjects(projectSearch.value));
        });
        projectSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideList(projectList, projectSearch, projectWrap);
            }
        });
    }

    if (itemSearch) {
        itemSearch.addEventListener('focus', function () {
            loadItemRows();
            renderItemList(filterItems(itemSearch.value));
        });
        itemSearch.addEventListener('input', function () {
            selectedItem = null;
            if (itemHidden) itemHidden.value = '';
            syncItemMeta(null);
            renderItemList(filterItems(itemSearch.value));
        });
        itemSearch.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideList(itemList, itemSearch, itemWrap);
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (projectWrap && !projectWrap.contains(e.target)) hideList(projectList, projectSearch, projectWrap);
        if (itemWrap && !itemWrap.contains(e.target)) hideList(itemList, itemSearch, itemWrap);
    });
})();
</script>
