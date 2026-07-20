{{--
    System-themed confirmation modal — replaces the browser's native
    confirm() for destructive actions everywhere. A single shared instance;
    triggered by any <form data-confirm-delete="Message?"> via the JS
    wiring in layouts/app.blade.php. Do not hand-roll a per-page modal for
    this — reuse this one.
--}}
<div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-danger"></i> Confirm Action
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" data-confirm-message>Are you sure?</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" data-confirm-proceed>
                    <i class="bi bi-trash me-1"></i> Confirm
                </button>
            </div>
        </div>
    </div>
</div>
