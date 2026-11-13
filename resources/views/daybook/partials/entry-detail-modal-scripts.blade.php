<script>
(function () {
    var modalEl = document.getElementById('daybookEntryModal');
    var editModalEl = document.getElementById('daybookEntryEditModal');
    if (typeof bootstrap === 'undefined') return;

    if (modalEl && modalEl.parentNode !== document.body) {
        document.body.appendChild(modalEl);
    }
    if (editModalEl && editModalEl.parentNode !== document.body) {
        document.body.appendChild(editModalEl);
    }

    var modal = modalEl ? bootstrap.Modal.getOrCreateInstance(modalEl) : null;
    var editModal = editModalEl ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
    var editIframe = document.getElementById('daybook-edit-modal-iframe');
    var editLoading = document.getElementById('daybook-edit-modal-loading');
    var editSubtitle = document.getElementById('daybook-edit-modal-subtitle');
    var reloadAfterSave = false;

    function setRow(id, value) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = (value === null || value === undefined || value === '') ? '—' : value;
        var item = el.closest('.daybook-modal-item');
        if (item) item.classList.toggle('d-none', el.textContent === '—');
    }

    function openFromPayload(data) {
        if (!modal) return;
        data = data || {};
        var subtitle = document.getElementById('daybook-modal-subtitle');
        if (subtitle) subtitle.textContent = data.voucher ? ('Voucher ' + data.voucher) : ('Entry #' + (data.id || ''));

        setRow('daybook-modal-voucher', data.voucher);
        setRow('daybook-modal-date', data.date);
        setRow('daybook-modal-created-at', data.created_at);
        setRow('daybook-modal-type', data.type_label);
        setRow('daybook-modal-settlement', data.settlement === '—' ? '' : data.settlement);
        setRow('daybook-modal-description', data.description === '—' ? '' : data.description);
        setRow('daybook-modal-link', data.link_label);

        var projectText = data.project_name || '';
        if (projectText) {
            projectText = (data.project_is_dha ? '🟢 ' : '🟡 ') + projectText;
        }
        if (projectText && data.land_type) projectText += ' (' + data.land_type + ')';
        setRow('daybook-modal-project', projectText);

        setRow('daybook-modal-file', data.purchase_file);
        setRow('daybook-modal-sold-area', (!data.sold_area || data.sold_area === '—') ? '' : data.sold_area);

        var subcat = '';
        if (data.sub_category && data.sub_category !== '—') {
            subcat = (data.category ? data.category + ' · ' : '') + data.sub_category;
        }
        setRow('daybook-modal-subcat', subcat);

        var factoryWrap = document.getElementById('daybook-modal-factory');
        if (factoryWrap) {
            if (data.is_factory) {
                factoryWrap.classList.remove('d-none');
                setRow('daybook-modal-fsubcat', data.factory_sub_category === '—' ? '' : data.factory_sub_category);
                setRow('daybook-modal-unit', data.unit);
                setRow('daybook-modal-qty', data.quantity);
                setRow('daybook-modal-price', data.unit_price ? ('Rs ' + data.unit_price) : '');
            } else {
                factoryWrap.classList.add('d-none');
            }
        }

        var amountEl = document.getElementById('daybook-modal-amount');
        if (amountEl) {
            amountEl.textContent = data.amount || '—';
            amountEl.classList.remove('daybook-amount--in', 'daybook-amount--out');
            amountEl.classList.add(data.is_cash_in ? 'daybook-amount--in' : 'daybook-amount--out');
        }

        var openFull = document.getElementById('daybook-modal-open-full');
        if (openFull) openFull.setAttribute('href', data.url || '#');

        var printVoucher = document.getElementById('daybook-modal-print-voucher');
        if (printVoucher) {
            if (data.voucher_url) {
                printVoucher.classList.remove('d-none');
                printVoucher.setAttribute('href', data.voucher_url);
            } else {
                printVoucher.classList.add('d-none');
                printVoucher.setAttribute('href', '#');
            }
        }

        modal.show();
    }

    function showEditLoading(show) {
        if (editLoading) editLoading.classList.toggle('d-none', !show);
        if (editIframe) editIframe.classList.toggle('d-none', !!show);
    }

    function openEditModal(data) {
        if (!editModal || !editIframe || !data || !data.edit_url) return;
        if (modal) modal.hide();
        reloadAfterSave = false;
        if (editSubtitle) {
            editSubtitle.textContent = data.voucher
                ? ('Voucher ' + data.voucher)
                : ('Entry #' + (data.id || ''));
        }
        showEditLoading(true);
        editIframe.onload = function () {
            showEditLoading(false);
        };
        editIframe.src = data.edit_url;
        editModal.show();
    }

    function closeEditModal(reload) {
        if (editModal) editModal.hide();
        if (editIframe) {
            editIframe.onload = null;
            editIframe.src = 'about:blank';
        }
        showEditLoading(true);
        if (reload) {
            window.location.reload();
        }
    }

    if (editModalEl) {
        editModalEl.addEventListener('hidden.bs.modal', function () {
            if (editIframe) {
                editIframe.onload = null;
                editIframe.src = 'about:blank';
            }
            showEditLoading(true);
            if (reloadAfterSave) {
                reloadAfterSave = false;
                window.location.reload();
            }
        });
    }

    function parsePayload(el) {
        try {
            return JSON.parse(el.getAttribute('data-daybook-entry') || '') || {};
        } catch (e) {
            return null;
        }
    }

    document.addEventListener('click', function (e) {
        var editBtn = e.target.closest('[data-daybook-edit]');
        if (editBtn) {
            var editData = parsePayload(editBtn);
            if (!editData) return;
            e.preventDefault();
            e.stopPropagation();
            openEditModal(editData);
            return;
        }

        var btn = e.target.closest('[data-daybook-entry]');
        if (!btn || btn.hasAttribute('data-daybook-edit')) return;
        var data = parsePayload(btn);
        if (!data) return;
        e.preventDefault();
        openFromPayload(data);
    });

    window.addEventListener('message', function (e) {
        if (e.origin !== window.location.origin) return;
        var msg = e.data || {};
        if (msg.type === 'daybook-entry-saved') {
            reloadAfterSave = true;
            closeEditModal(true);
        } else if (msg.type === 'daybook-entry-edit-cancel') {
            closeEditModal(false);
        }
    });
})();
</script>
