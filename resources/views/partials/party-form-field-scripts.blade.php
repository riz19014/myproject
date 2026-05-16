<script>
(function () {
    var CNIC_SEGMENTS = [5, 7, 1];
    var CNIC_MAX_DIGITS = CNIC_SEGMENTS.reduce(function (a, b) { return a + b; }, 0);

    function cnicDigits(value) {
        return String(value || '').replace(/\D/g, '').slice(0, CNIC_MAX_DIGITS);
    }

    function formatCnicDisplay(value) {
        var digits = cnicDigits(value);
        if (!digits) return '';
        var parts = [];
        var offset = 0;
        for (var i = 0; i < CNIC_SEGMENTS.length; i++) {
            if (offset >= digits.length) break;
            var len = CNIC_SEGMENTS[i];
            var chunk = digits.slice(offset, offset + len);
            if (chunk) parts.push(chunk);
            offset += len;
        }
        return parts.join('-');
    }

    window.PartyFormFields = window.PartyFormFields || {};
    window.PartyFormFields.cnicDigits = cnicDigits;
    window.PartyFormFields.formatCnicDisplay = formatCnicDisplay;
    window.PartyFormFields.cnicMaxDigits = CNIC_MAX_DIGITS;

    window.PartyFormFields.bindCnicInput = function (el) {
        if (!el || el.dataset.cnicBound === '1') return;
        el.dataset.cnicBound = '1';
        el.setAttribute('maxlength', '15');
        el.setAttribute('placeholder', '23012-2321373-1');
        el.setAttribute('inputmode', 'numeric');
        el.addEventListener('input', function () {
            el.value = formatCnicDisplay(el.value);
        });
        if (el.value) {
            el.value = formatCnicDisplay(el.value);
        }
    };

    window.PartyFormFields.bindPhoneInput = function (el, maxLen) {
        maxLen = maxLen || 11;
        if (!el || el.dataset.phoneBound === '1') return;
        el.dataset.phoneBound = '1';
        el.addEventListener('input', function () {
            el.value = String(el.value || '').replace(/\D/g, '').slice(0, maxLen);
        });
    };

    window.PartyFormFields.initSubCategoryCombo = function (cfg) {
        if (!cfg || !cfg.wrap || !cfg.hidden || !cfg.search || !cfg.list || cfg.wrap.dataset.scComboBound === '1') return;
        cfg.wrap.dataset.scComboBound = '1';
        var rows = cfg.rows || [];

        function norm(s) {
            return String(s || '').toLowerCase();
        }

        function filterRows(q) {
            var nq = norm(q);
            if (!nq) return rows.slice();
            return rows.filter(function (row) {
                return norm(row.label).indexOf(nq) !== -1;
            });
        }

        function hideList() {
            cfg.list.classList.add('d-none');
            cfg.list.setAttribute('hidden', '');
            cfg.search.setAttribute('aria-expanded', 'false');
        }

        function showList() {
            cfg.list.classList.remove('d-none');
            cfg.list.removeAttribute('hidden');
            cfg.search.setAttribute('aria-expanded', 'true');
        }

        function renderList(filtered) {
            cfg.list.innerHTML = '';
            if (!filtered.length) {
                var li0 = document.createElement('li');
                li0.className = 'party-sc-empty';
                li0.textContent = cfg.emptyText || 'No matches.';
                cfg.list.appendChild(li0);
                showList();
                return;
            }
            filtered.forEach(function (row) {
                var li = document.createElement('li');
                li.setAttribute('role', 'none');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.dataset.id = String(row.id);
                btn.textContent = row.label;
                btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
                btn.addEventListener('click', function () {
                    cfg.hidden.value = String(row.id);
                    cfg.search.value = row.label;
                    hideList();
                });
                li.appendChild(btn);
                cfg.list.appendChild(li);
            });
            showList();
        }

        function openFiltered() {
            renderList(filterRows(cfg.search.value));
        }

        function setFromId(id) {
            var match = rows.find(function (r) { return String(r.id) === String(id); });
            cfg.hidden.value = id ? String(id) : '';
            cfg.search.value = match ? match.label : '';
        }

        if (cfg.initialId) {
            setFromId(cfg.initialId);
        }

        cfg.search.addEventListener('focus', openFiltered);
        cfg.search.addEventListener('input', function () {
            var still = rows.some(function (r) {
                return String(r.id) === cfg.hidden.value && r.label === cfg.search.value;
            });
            if (!still) cfg.hidden.value = '';
            openFiltered();
        });
        cfg.search.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideList();
            }
        });

        document.addEventListener('click', function (e) {
            if (cfg.list.classList.contains('d-none')) return;
            if (!cfg.wrap.contains(e.target)) hideList();
        });

        return {
            clear: function () {
                cfg.hidden.value = '';
                cfg.search.value = '';
                hideList();
            },
            setFromId: setFromId,
            hideList: hideList
        };
    };
})();
</script>
