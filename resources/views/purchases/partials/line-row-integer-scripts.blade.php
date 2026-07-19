<script>
(function () {
    function stripDigits(value) {
        return String(value || '').replace(/\D/g, '');
    }

    function bindIntegerInput(el) {
        if (!el || el.dataset.lineIntegerBound === '1') return;
        el.dataset.lineIntegerBound = '1';
        var zeroOnBlur = el.getAttribute('data-line-integer-zero') === '1';

        function applyStrip() {
            el.value = stripDigits(el.value);
        }

        el.addEventListener('input', applyStrip);
        el.addEventListener('keydown', function (e) {
            if (e.key === '.' || e.key === ',' || e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '-') {
                e.preventDefault();
            }
        });
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            el.value = stripDigits(text);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    function stripToDecimal(value) {
        var cleaned = String(value || '').replace(/[^\d.]/g, '');
        var firstDot = cleaned.indexOf('.');
        if (firstDot === -1) return cleaned;
        return cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
    }

    function bindDecimalInput(el) {
        if (!el || el.dataset.lineDecimalBound === '1') return;
        el.dataset.lineDecimalBound = '1';

        el.addEventListener('input', function () {
            var cleaned = stripToDecimal(el.value);
            if (el.value !== cleaned) el.value = cleaned;
        });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'e' || e.key === 'E' || e.key === '+' || e.key === '-' || e.key === ',') {
                e.preventDefault();
            }
        });
        el.addEventListener('paste', function (e) {
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text');
            el.value = stripToDecimal(text);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    var areaFields = ['area_acre', 'area_kanal', 'area_marla', 'area_sqft'];

    function normalizeAreaZeros(root) {
        if (!root) return;
        root.querySelectorAll('[data-line-integer-zero="1"]').forEach(function (el) {
            if (el.value === '') {
                el.value = '0';
            }
        });
    }

    function blockHasPositiveArea(block) {
        if (!block) return false;
        return areaFields.some(function (field) {
            var el = block.querySelector('[data-line-field="' + field + '"]');
            return (parseInt(el && el.value ? el.value : '0', 10) || 0) > 0;
        });
    }

    function showAreaRequiredSwal(firstBad) {
        var message = 'Each line needs at least one positive value in Acre, Kanal, Marla, or Sq ft.';
        function focusLine() {
            if (!firstBad) return;
            firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var focusEl = firstBad.querySelector('.js-line-area-part');
            if (focusEl) focusEl.focus();
        }
        if (typeof Swal === 'undefined') {
            alert(message);
            focusLine();
            return;
        }
        Swal.fire({
            title: 'Area required',
            text: message,
            icon: 'warning',
            confirmButtonText: 'OK',
            confirmButtonColor: '#f97316',
            background: '#fff',
            color: '#0f172a',
            customClass: {
                popup: 'swal-light',
                title: 'swal-title',
                htmlContainer: 'swal-text',
                confirmButton: 'swal-confirm'
            }
        }).then(focusLine);
    }

    function validateAreas(root) {
        if (!root) return true;
        var blocks = root.querySelectorAll('.purchase-line-block');
        var firstBad = null;
        blocks.forEach(function (block) {
            if (!blockHasPositiveArea(block) && !firstBad) {
                firstBad = block;
            }
        });
        if (firstBad) {
            restoreAreaDisplayEmpty(root);
            showAreaRequiredSwal(firstBad);
            return false;
        }
        return true;
    }

    function restoreAreaDisplayEmpty(root) {
        if (!root) return;
        root.querySelectorAll('[data-line-integer-zero="1"]').forEach(function (el) {
            if (String(el.value || '').trim() === '0') {
                el.value = '';
            }
        });
    }

    /** Validate first (empty stays empty); only fill 0s when submitting valid data. */
    function prepareForSubmit(root) {
        if (!validateAreas(root)) {
            return false;
        }
        normalizeAreaZeros(root);
        return true;
    }

    function bindIn(root) {
        if (!root) return;
        root.querySelectorAll('.js-line-integer-only').forEach(bindIntegerInput);
        root.querySelectorAll('.js-line-decimal-only').forEach(bindDecimalInput);
    }

    window.PurchaseLineIntegers = {
        bind: bindIn,
        refresh: bindIn,
        normalizeAreaZeros: normalizeAreaZeros,
        validateAreas: validateAreas,
        restoreAreaDisplayEmpty: restoreAreaDisplayEmpty,
        prepareForSubmit: prepareForSubmit
    };
})();
</script>
