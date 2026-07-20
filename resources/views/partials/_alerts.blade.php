{{--
    System-wide toast notifications — top-right, auto-dismiss. Replaces the
    old inline Bootstrap .alert blocks. This partial is included once,
    globally, from layouts/app.blade.php; individual views must NOT render
    their own session('success')/session('error') alert markup — route
    everything through this one mechanism so messages never double-render.
--}}
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080;">
    @if(session('success'))
    <div class="toast align-items-center text-bg-success border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-check-circle me-2"></i>{{ session('success') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="toast align-items-center text-bg-danger border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="7000">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if(session('warning'))
    <div class="toast align-items-center text-bg-warning border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="6000">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if(session('info'))
    <div class="toast align-items-center text-bg-info border-0 shadow" role="alert" data-bs-autohide="true" data-bs-delay="5000">
        <div class="d-flex">
            <div class="toast-body"><i class="bi bi-info-circle me-2"></i>{{ session('info') }}</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="toast align-items-center text-bg-danger border-0 shadow" role="alert" data-bs-autohide="false">
        <div class="d-flex">
            <div class="toast-body">
                <strong><i class="bi bi-exclamation-circle me-2"></i>Please fix the following errors:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif
</div>
