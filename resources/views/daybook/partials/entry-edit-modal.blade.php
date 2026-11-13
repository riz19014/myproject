{{-- Full daybook edit form in iframe (ledger / ledger summary). --}}
<div class="modal fade" id="daybookEntryEditModal" tabindex="-1" aria-labelledby="daybookEntryEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable daybook-entry-edit-modal-dialog">
        <div class="modal-content daybook-entry-edit-modal-content">
            <div class="modal-header py-2">
                <div>
                    <h5 class="modal-title mb-0" id="daybookEntryEditModalLabel">Edit daybook entry</h5>
                    <p class="small text-muted mb-0" id="daybook-edit-modal-subtitle"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="daybook-edit-modal-loading" class="text-center text-muted py-5">
                    <div class="spinner-border text-secondary" role="status" aria-hidden="true"></div>
                    <p class="small mb-0 mt-2">Loading entry…</p>
                </div>
                <iframe
                    id="daybook-edit-modal-iframe"
                    title="Edit daybook entry"
                    class="daybook-edit-modal-iframe d-none"
                    src="about:blank"
                ></iframe>
            </div>
        </div>
    </div>
</div>
