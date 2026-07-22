<div class="modal modal-blur fade" id="success-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-circle-check icon mb-2 text-green icon-lg" style="font-size:3.5rem;"></i>
                <p id="success-message" class="text-secondary">Thành công</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a id="success-confirm" href="" class="btn w-100" data-bs-dismiss="modal">
                                OK
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="fail-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-circle-x icon mb-2 text-danger icon-lg" style="font-size:3.5rem;"></i>
                <p id="fail-message" class="text-secondary">Thất bại</p>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col">
                            <a href="" class="btn btn-danger w-100" data-bs-dismiss="modal">
                                Xác nhận
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://{$config['jsdelivr_url']}/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>

<script>
(function () {
    try {
        if (window.tabler && tabler.bootstrap) {
            window.successDialog = new tabler.bootstrap.Modal(document.getElementById('success-dialog'));
            window.failDialog = new tabler.bootstrap.Modal(document.getElementById('fail-dialog'));
        }
    } catch (e) {
        console.warn('Modal init skipped', e);
    }

    if (typeof htmx === 'undefined') {
        console.warn('htmx not loaded');
        return;
    }

    htmx.on("htmx:afterRequest", function(evt) {
        const redirect = evt.detail.xhr.getResponseHeader('HX-Redirect');
        if (redirect) {
            window.location.href = redirect;
            return;
        }

        let res;
        try {
            res = JSON.parse(evt.detail.xhr.response || '{}');
        } catch (e) {
            return;
        }

        if (evt.detail.elt && evt.detail.elt.id === 'send-verify-email') {
            document.getElementById('send-verify-email').disabled = true;
        }

        if (res.redir) {
            window.location.href = res.redir;
            return;
        }

        if (res.ret === 1) {
            const el = document.getElementById("success-message");
            if (el) el.innerHTML = res.msg || 'Thành công';
            if (window.successDialog) successDialog.show();
        } else if (typeof res.ret !== 'undefined') {
            const el = document.getElementById("fail-message");
            if (el) el.innerHTML = res.msg || 'Thất bại';
            if (window.failDialog) failDialog.show();
        }
    });
})();
</script>

{include file='live_chat.tpl'}

{include file='telemetry.tpl'}

</body>

</html>
