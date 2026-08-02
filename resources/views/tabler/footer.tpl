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
    function getModalConstructor() {
        if (window.bootstrap && bootstrap.Modal) {
            return bootstrap.Modal;
        }
        if (window.tabler && tabler.bootstrap && tabler.bootstrap.Modal) {
            return tabler.bootstrap.Modal;
        }
        if (window.tabler && tabler.Modal) {
            return tabler.Modal;
        }
        return null;
    }

    function showToast(message, type) {
        const toast = document.createElement('div');
        toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 px-4 py-2 rounded text-white '
            + (type === 'danger' ? 'bg-danger' : 'bg-success');
        toast.style.zIndex = '2000';
        toast.setAttribute('role', 'status');
        toast.textContent = message || (type === 'danger' ? 'Thất bại' : 'Thành công');
        document.body.appendChild(toast);
        setTimeout(function () {
            toast.remove();
        }, 3500);
    }

    try {
        const ModalCtor = getModalConstructor();
        if (ModalCtor) {
            window.successDialog = new ModalCtor(document.getElementById('success-dialog'));
            window.failDialog = new ModalCtor(document.getElementById('fail-dialog'));
        }
    } catch (e) {
        console.warn('Modal init skipped', e);
    }

    window.gopassShowFail = function (message) {
        const el = document.getElementById('fail-message');
        if (el) el.textContent = message || 'Thất bại';
        if (window.failDialog) {
            try { failDialog.show(); return; } catch (e) {}
        }
        showToast(message || 'Thất bại', 'danger');
    };

    window.gopassShowSuccess = function (message) {
        const el = document.getElementById('success-message');
        if (el) el.textContent = message || 'Thành công';
        if (window.successDialog) {
            try { successDialog.show(); return; } catch (e) {}
        }
        showToast(message || 'Thành công', 'success');
    };

    if (typeof htmx === 'undefined') {
        console.warn('htmx not loaded');
        return;
    }

    htmx.on('htmx:afterRequest', function (evt) {
        const redirect = evt.detail.xhr.getResponseHeader('HX-Redirect');
        if (redirect) {
            window.location.href = redirect;
            return;
        }

        if (!evt.detail.successful) {
            window.gopassShowFail('Yêu cầu thất bại, vui lòng thử lại.');
            return;
        }

        let res;
        try {
            res = JSON.parse(evt.detail.xhr.response || '{}');
        } catch (e) {
            window.gopassShowFail('Phản hồi không hợp lệ từ máy chủ, vui lòng thử lại.');
            return;
        }

        if (evt.detail.elt && evt.detail.elt.id === 'send-verify-email') {
            const btn = document.getElementById('send-verify-email');
            if (btn) btn.disabled = true;
        }

        if (res.redir) {
            window.location.href = res.redir;
            return;
        }

        if (res.ret === 1) {
            window.gopassShowSuccess(res.msg || 'Thành công');
        } else if (typeof res.ret !== 'undefined') {
            window.gopassShowFail(res.msg || 'Thất bại');
        }
    });

    htmx.on('htmx:responseError', function () {
        window.gopassShowFail('Máy chủ phản hồi lỗi, vui lòng thử lại.');
    });

    htmx.on('htmx:sendError', function () {
        window.gopassShowFail('Không thể kết nối máy chủ, vui lòng kiểm tra mạng.');
    });
})();
</script>

{include file='live_chat.tpl'}

{include file='telemetry.tpl'}

<script src="/assets/js/sakura.js?v=20260802sakura3" defer></script>

</body>

</html>
