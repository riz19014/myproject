<script>
(function () {
    function scale2(x) {
        return (Math.round(x * 100) / 100).toFixed(2);
    }

    function compactMainLabel(intPart) {
        if (intPart < 0) return '';
        if (intPart >= 10000000) return scale2(intPart / 10000000) + ' cr';
        if (intPart >= 100000) return scale2(intPart / 100000) + ' lac';
        if (intPart >= 1000) return scale2(intPart / 1000) + ' k';
        return intPart > 0 ? String(intPart) : '';
    }

    function compactLabel(raw) {
        var v = parseFloat(String(raw || '').replace(/,/g, ''));
        if (!isFinite(v) || v <= 0) return '';
        return compactMainLabel(Math.floor(v + 1e-9));
    }

    function updateInput(input) {
        var block = input.closest('.purchase-line-block');
        var hint = block && block.querySelector('.js-amount-per-acre-hint');
        if (!hint) return;
        var text = compactLabel(input.value);
        hint.textContent = text;
        hint.classList.toggle('d-none', !text);
    }

    function refresh(container) {
        if (!container) return;
        container.querySelectorAll('[data-line-field="amount_per_acre"]').forEach(updateInput);
    }

    function bind(container) {
        if (!container || container.dataset.amountHintsBound === '1') return;
        container.dataset.amountHintsBound = '1';
        container.addEventListener('input', function (e) {
            if (e.target && e.target.getAttribute('data-line-field') === 'amount_per_acre') {
                updateInput(e.target);
            }
        });
        container.addEventListener('change', function (e) {
            if (e.target && e.target.getAttribute('data-line-field') === 'amount_per_acre') {
                updateInput(e.target);
            }
        });
        refresh(container);
    }

    window.PurchaseLineAmountHints = { bind: bind, refresh: refresh };
})();
</script>
