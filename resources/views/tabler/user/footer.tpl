        </main>

        <div class="gopass-footer">
            Powered by <a href="/staff" class="link-secondary">DPanel</a>
            &nbsp;·&nbsp; GoPass Style
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="success-dialog" role="dialog">
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
                            <button type="button" id="success-confirm" class="btn w-100" data-bs-dismiss="modal">
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="fail-dialog" role="dialog">
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
<script src="/assets/js/gopass.js?v=20260717c"></script>
<script>
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        const bgColor = type === 'danger' ? 'bg-danger' : 'bg-success';
        toast.className = 'position-fixed top-0 start-50 translate-middle-x mt-3 ' + bgColor + ' text-white px-4 py-2 rounded';
        toast.style.zIndex = '9999';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() {
            toast.remove();
        }, 2000);
    }

    window.addEventListener('load', function() {
        if (typeof tabler !== 'undefined' && tabler.bootstrap) {
            window.successDialog = new tabler.bootstrap.Modal(document.getElementById('success-dialog'));
            window.failDialog = new tabler.bootstrap.Modal(document.getElementById('fail-dialog'));
        }

        try {
            const flashMsg = sessionStorage.getItem('gopassFlashMsg');
            const flashType = sessionStorage.getItem('gopassFlashType') || 'success';
            if (flashMsg) {
                sessionStorage.removeItem('gopassFlashMsg');
                sessionStorage.removeItem('gopassFlashType');
                const isSuccess = flashType !== 'danger';
                const messageId = isSuccess ? 'success-message' : 'fail-message';
                const dialog = isSuccess ? window.successDialog : window.failDialog;
                const messageEl = document.getElementById(messageId);
                if (messageEl) {
                    messageEl.textContent = flashMsg;
                }
                const zaloWrap = document.getElementById('success-zalo-wrap');
                if (zaloWrap) {
                    zaloWrap.style.display = (isSuccess && /zalo|0796969444/i.test(flashMsg)) ? '' : 'none';
                }
                if (dialog) {
                    dialog.show();
                } else {
                    showToast(flashMsg, flashType);
                }
            }
        } catch (e) {}
    });

    if (typeof ClipboardJS !== 'undefined' && document.querySelector('.copy')) {
        let clipboard = new ClipboardJS('.copy');
        clipboard.on('success', function(e) {
            showToast('Đã sao chép vào clipboard');
            e.clearSelection();
        });

        clipboard.on('error', function(e) {
            const text = e.trigger.getAttribute('data-clipboard-text');
            if (text && navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showToast('Đã sao chép vào clipboard');
                }).catch(function() {
                    prompt('Vui lòng sao chép:', text);
                });
            }
        });
    }

    htmx.on("htmx:afterRequest", function(evt) {
        const redirect = evt.detail.xhr.getResponseHeader('HX-Redirect');
        if (redirect) {
            window.location.href = redirect;
            return;
        }

        if (evt.detail.xhr.getResponseHeader('HX-Refresh') === 'true' ||
            evt.detail.xhr.getResponseHeader('HX-Trigger'))
        {
            return;
        }

        try {
            let res = JSON.parse(evt.detail.xhr.response || '{}');

            if (typeof res.data !== 'undefined') {
                for (let key in res.data) {
                    if (!res.data.hasOwnProperty(key)) continue;
                    if (key === "ga-url" && typeof qrcode !== 'undefined') {
                        qrcode.clear();
                        qrcode.makeCode(res.data[key]);
                        continue;
                    }
                    if (key === "last-checkin-time") {
                        const checkInBtn = document.getElementById("check-in");
                        if (checkInBtn) {
                            checkInBtn.textContent = "Đã điểm danh";
                            checkInBtn.disabled = true;
                        }
                        continue;
                    }
                    const element = document.getElementById(key);
                    if (element) {
                        if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
                            element.value = res.data[key];
                        } else {
                            element.textContent = res.data[key];
                        }
                    }
                }
            }

            if (typeof res.ret === 'undefined' && res.redir) {
                window.location.href = res.redir;
                return;
            }

            if (typeof res.ret === 'undefined') {
                return;
            }

            const busyBtn = evt.detail.elt;
            const isSuccess = res.ret === 1;

            if (isGopassBusySubmit(busyBtn)) {
                if (!isSuccess) {
                    restoreGopassBusySubmit(busyBtn);
                } else if (
                    !res.redir &&
                    !busyBtn.classList.contains('gopass-pay-submit') &&
                    busyBtn.getAttribute('data-gopass-keep-busy') !== '1'
                ) {
                    restoreGopassBusySubmit(busyBtn);
                }
            }

            if (isSuccess && res.redir) {
                try {
                    sessionStorage.setItem('gopassFlashMsg', res.msg || 'Thành công');
                    sessionStorage.setItem('gopassFlashType', 'success');
                } catch (e) {}
                window.location.href = res.redir;
                return;
            }

            const messageId = isSuccess ? "success-message" : "fail-message";
            const dialog = isSuccess ? window.successDialog : window.failDialog;

            document.getElementById(messageId).textContent = res.msg || (isSuccess ? 'Thành công' : 'Thất bại');
            if (dialog) {
                dialog.show();
            } else {
                showToast(res.msg, isSuccess ? 'success' : 'danger');
            }

            if (isSuccess && isGopassBusySubmit(busyBtn) && busyBtn.classList.contains('gopass-pay-submit')) {
                window.setTimeout(function () {
                    window.location.reload();
                }, 800);
            }
        } catch (e) {
            console.error("Failed to parse HTMX response:", e);
        }
    });

    function isGopassBusySubmit(el) {
        return !!(el && el.classList && (
            el.classList.contains('gopass-busy-submit') ||
            el.classList.contains('gopass-pay-submit')
        ));
    }

    function setGopassBusySubmit(el, waitingText) {
        if (!isGopassBusySubmit(el) || el.getAttribute('aria-busy') === 'true') {
            return;
        }
        el.dataset.gopassOriginalHtml = el.innerHTML;
        el.disabled = true;
        el.setAttribute('aria-busy', 'true');
        el.classList.add('is-gopass-busy');
        el.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
            (waitingText || 'Đang xử lý...');
    }

    function restoreGopassBusySubmit(el) {
        if (!el) {
            return;
        }
        el.disabled = false;
        el.removeAttribute('aria-busy');
        el.classList.remove('is-gopass-busy');
        if (el.dataset.gopassOriginalHtml) {
            el.innerHTML = el.dataset.gopassOriginalHtml;
        }
    }

    htmx.on('htmx:beforeRequest', function (evt) {
        const el = evt.detail.elt;
        if (!isGopassBusySubmit(el)) {
            return;
        }
        setGopassBusySubmit(el, el.getAttribute('data-gopass-busy-text') || 'Đang xử lý...');
    });

    htmx.on('htmx:responseError', function (evt) {
        restoreGopassBusySubmit(evt.detail.elt);
    });

    htmx.on('htmx:sendError', function (evt) {
        restoreGopassBusySubmit(evt.detail.elt);
    });
</script>

{include file='live_chat.tpl'}
{include file='telemetry.tpl'}

<!-- Zalo floating button -->
<a href="https://zalo.me/0796969444" target="_blank" rel="noopener noreferrer"
   class="gopass-zalo-fab" aria-label="Chat Zalo 0796969444">
    <span class="gopass-zalo-fab-icon" aria-hidden="true">
        <svg role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" width="22" height="22">
            <path fill="currentColor" d="M12.49 10.2722v-.4496h1.3467v6.3218h-.7704a.576.576 0 01-.5763-.5729l-.0006.0005a3.273 3.273 0 01-1.9372.6321c-1.8138 0-3.2844-1.4697-3.2844-3.2823 0-1.8125 1.4706-3.2822 3.2844-3.2822a3.273 3.273 0 011.9372.6321l.0006.0005zM6.9188 7.7896v.205c0 .3823-.051.6944-.2995 1.0605l-.03.0343c-.0542.0615-.1815.206-.2421.2843L2.024 14.8h4.8948v.7682a.5764.5764 0 01-.5767.5761H0v-.3622c0-.4436.1102-.6414.2495-.8476L4.8582 9.23H.1922V7.7896h6.7266zm8.5513 8.3548a.4805.4805 0 01-.4803-.4798v-7.875h1.4416v8.3548H15.47zM20.6934 9.6C22.52 9.6 24 11.0807 24 12.9044c0 1.8252-1.4801 3.306-3.3066 3.306-1.8264 0-3.3066-1.4808-3.3066-3.306 0-1.8237 1.4802-3.3044 3.3066-3.3044zm-10.1412 5.253c1.0675 0 1.9324-.8645 1.9324-1.9312 0-1.065-.865-1.9295-1.9324-1.9295s-1.9324.8644-1.9324 1.9295c0 1.0667.865 1.9312 1.9324 1.9312zm10.1412-.0033c1.0737 0 1.945-.8707 1.945-1.9453 0-1.073-.8713-1.9436-1.945-1.9436-1.0753 0-1.945.8706-1.945 1.9436 0 1.0746.8697 1.9453 1.945 1.9453z"/>
        </svg>
    </span>
    <span class="gopass-zalo-fab-label">Zalo</span>
</a>

<script src="/assets/js/sakura.js?v=20260728sakura1" defer></script>

</body>
</html>
