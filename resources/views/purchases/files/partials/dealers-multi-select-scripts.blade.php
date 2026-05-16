<script>
(function () {
    var wrap = document.getElementById('purchase_file_dealers_wrap');
    var searchEl = document.getElementById('purchase_file_dealers_search');
    var listEl = document.getElementById('purchase_file_dealers_listbox');
    var chipsEl = document.getElementById('purchase_file_dealers_chips');
    var hiddenEl = document.getElementById('purchase_file_dealers_hidden');
    var jsonEl = document.getElementById('purchase_file_dealers_json');
    var selectedJsonEl = document.getElementById('purchase_file_dealers_selected_json');
    if (!wrap || !searchEl || !listEl || !chipsEl || !hiddenEl) return;

    var partyRows = [];
    if (jsonEl) {
        try { partyRows = JSON.parse(jsonEl.textContent) || []; } catch (e) { partyRows = []; }
    }
    var selectedIds = [];
    if (selectedJsonEl) {
        try { selectedIds = JSON.parse(selectedJsonEl.textContent) || []; } catch (e) { selectedIds = []; }
    }

    function norm(s) {
        return String(s || '').toLowerCase();
    }

    function labelFor(id) {
        var row = partyRows.find(function (r) { return String(r.id) === String(id); });
        return row ? row.label : ('Party #' + id);
    }

    function isSelected(id) {
        return selectedIds.some(function (sid) { return String(sid) === String(id); });
    }

    function syncHiddenInputs() {
        hiddenEl.innerHTML = '';
        selectedIds.forEach(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'dealer_party_ids[]';
            input.value = String(id);
            hiddenEl.appendChild(input);
        });
    }

    function renderChips() {
        chipsEl.innerHTML = '';
        selectedIds.forEach(function (id) {
            var chip = document.createElement('span');
            chip.className = 'pf-dealer-chip';
            chip.dataset.id = String(id);
            var label = document.createElement('span');
            label.textContent = labelFor(id);
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Remove ' + labelFor(id));
            btn.innerHTML = '&times;';
            btn.addEventListener('click', function () {
                selectedIds = selectedIds.filter(function (sid) { return String(sid) !== String(id); });
                renderChips();
                syncHiddenInputs();
            });
            chip.appendChild(label);
            chip.appendChild(btn);
            chipsEl.appendChild(chip);
        });
    }

    function hideList() {
        listEl.classList.add('d-none');
        listEl.setAttribute('hidden', '');
        searchEl.setAttribute('aria-expanded', 'false');
    }

    function showList() {
        listEl.classList.remove('d-none');
        listEl.removeAttribute('hidden');
        searchEl.setAttribute('aria-expanded', 'true');
    }

    function filterRows(q) {
        var nq = norm(q);
        return partyRows.filter(function (row) {
            if (isSelected(row.id)) return false;
            if (!nq) return true;
            return norm(row.label).indexOf(nq) !== -1;
        });
    }

    function renderList(rows) {
        listEl.innerHTML = '';
        if (!rows.length) {
            var li0 = document.createElement('li');
            li0.className = 'party-sc-empty';
            li0.textContent = partyRows.length ? 'No matching dealers or all already selected.' : 'No parties yet. Use Add dealer.';
            listEl.appendChild(li0);
            showList();
            return;
        }
        rows.forEach(function (row) {
            var li = document.createElement('li');
            li.setAttribute('role', 'none');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('role', 'option');
            btn.textContent = row.label;
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
            btn.addEventListener('click', function () {
                addDealer(row.id, row.label);
                searchEl.value = '';
                hideList();
            });
            li.appendChild(btn);
            listEl.appendChild(li);
        });
        showList();
    }

    function openFiltered() {
        renderList(filterRows(searchEl.value));
    }

    function addDealer(id, label) {
        if (id == null || isSelected(id)) return;
        var idNum = parseInt(id, 10);
        if (!partyRows.some(function (r) { return String(r.id) === String(idNum); })) {
            partyRows.push({ id: idNum, label: label || ('Party #' + idNum) });
        }
        selectedIds.push(idNum);
        renderChips();
        syncHiddenInputs();
    }

    window.PurchaseFileDealersSelect = {
        addDealer: addDealer,
        getPartyRows: function () { return partyRows; }
    };

    searchEl.addEventListener('focus', openFiltered);
    searchEl.addEventListener('input', openFiltered);
    searchEl.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            e.stopPropagation();
            hideList();
        }
    });

    document.addEventListener('click', function (e) {
        if (listEl.classList.contains('d-none')) return;
        if (!wrap.contains(e.target)) hideList();
    });

    var initialIds = selectedIds.slice();
    selectedIds = [];
    initialIds.forEach(function (id) {
        addDealer(id, labelFor(id));
    });
})();
</script>
