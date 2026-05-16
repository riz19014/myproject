<script>
(function () {
    var jsonEl = document.getElementById('purchase_moza_suggestions_json');
    if (!jsonEl) return;

    var allMozas = [];
    try {
        allMozas = JSON.parse(jsonEl.textContent) || [];
    } catch (e) {
        allMozas = [];
    }

    function norm(s) {
        return String(s || '').toLowerCase();
    }

    function filterMozas(q) {
        var nq = norm(q);
        if (!nq) return allMozas.slice(0, 40);
        return allMozas.filter(function (m) {
            return norm(m).indexOf(nq) !== -1;
        }).slice(0, 40);
    }

    function initWrap(wrap) {
        if (!wrap || wrap.dataset.mozaSuggestBound === '1') return;
        var input = wrap.querySelector('[data-line-field="moza"]');
        var list = wrap.querySelector('.js-moza-suggest-list');
        if (!input || !list) return;
        wrap.dataset.mozaSuggestBound = '1';

        function hideList() {
            list.classList.add('d-none');
            list.setAttribute('hidden', '');
            input.setAttribute('aria-expanded', 'false');
        }

        function showList() {
            list.classList.remove('d-none');
            list.removeAttribute('hidden');
            input.setAttribute('aria-expanded', 'true');
        }

        function renderList(items) {
            list.innerHTML = '';
            if (!items.length) {
                var li0 = document.createElement('li');
                li0.className = 'party-sc-empty';
                li0.textContent = allMozas.length ? 'No saved moza matches.' : 'No moza saved in database yet.';
                list.appendChild(li0);
                showList();
                return;
            }
            items.forEach(function (moza) {
                var li = document.createElement('li');
                li.setAttribute('role', 'none');
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.setAttribute('role', 'option');
                btn.textContent = moza;
                btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
                btn.addEventListener('click', function () {
                    input.value = moza;
                    hideList();
                });
                li.appendChild(btn);
                list.appendChild(li);
            });
            showList();
        }

        function openFiltered() {
            renderList(filterMozas(input.value));
        }

        input.addEventListener('focus', openFiltered);
        input.addEventListener('input', openFiltered);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                e.stopPropagation();
                hideList();
            }
        });

        document.addEventListener('click', function (e) {
            if (list.classList.contains('d-none')) return;
            if (!wrap.contains(e.target)) hideList();
        });
    }

    function bind(container) {
        if (!container) return;
        container.querySelectorAll('.js-moza-suggest').forEach(initWrap);
    }

    window.PurchaseLineMozaSuggest = { bind: bind, refresh: bind };
})();
</script>
