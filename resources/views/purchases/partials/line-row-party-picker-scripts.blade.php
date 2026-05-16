@include('partials.party-form-field-scripts')
<script>
(function () {
    var PF = window.PartyFormFields;
    if (!PF) return;

    var partyRowsCache = null;

    function partyRows() {
        if (partyRowsCache) return partyRowsCache;
        var el = document.getElementById('purchase_line_parties_json');
        if (!el) return [];
        try {
            partyRowsCache = JSON.parse(el.textContent) || [];
        } catch (e) {
            partyRowsCache = [];
        }
        return partyRowsCache;
    }

    function initIn(root) {
        if (!root) return;
        root.querySelectorAll('.js-party-picker').forEach(function (wrap) {
            if (wrap.dataset.partyPickerBound === '1') return;
            var hidden = wrap.querySelector('[data-line-field="party_id"]');
            var search = wrap.querySelector('.js-party-picker-search');
            var list = wrap.querySelector('.js-party-picker-list');
            if (!hidden || !search || !list) return;
            wrap.dataset.partyPickerBound = '1';
            PF.initSubCategoryCombo({
                wrap: wrap,
                hidden: hidden,
                search: search,
                list: list,
                rows: partyRows(),
                initialId: hidden.value || '',
                emptyText: 'No parties match.'
            });
        });
    }

    window.PurchaseLinePartyPickers = {
        init: initIn,
        refresh: function (root) {
            initIn(root || document);
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        initIn(document);
    });
})();
</script>
