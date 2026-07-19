<script>
(function () {
    function scale2(x) {
        return (Math.round(x * 100) / 100).toFixed(2);
    }

    function compactMainLabel(amount) {
        if (amount < 0) return '';
        if (amount >= 10000000) return scale2(amount / 10000000) + ' cr';
        if (amount >= 100000) return scale2(amount / 100000) + ' lac';
        if (amount >= 1000) return scale2(amount / 1000) + ' k';
        return amount > 0 ? String(amount) : '';
    }

    function compactLabel(raw) {
        var cleaned = String(raw || '').replace(/[^\d.]/g, '');
        if (!cleaned || cleaned === '.') return '';
        var v = parseFloat(cleaned);
        if (!isFinite(v) || v <= 0) return '';
        return compactMainLabel(v);
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
