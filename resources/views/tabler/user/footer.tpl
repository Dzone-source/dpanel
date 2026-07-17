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
        if (evt.detail.xhr.getResponseHeader('HX-Refresh') === 'true' ||
            evt.detail.xhr.getResponseHeader('HX-Trigger'))
        {
            return;
        }

        try {
            let res = JSON.parse(evt.detail.xhr.response);

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

            const isSuccess = res.ret === 1;
            const messageId = isSuccess ? "success-message" : "fail-message";
            const dialog = isSuccess ? window.successDialog : window.failDialog;

            document.getElementById(messageId).textContent = res.msg;
            if (dialog) {
                dialog.show();
            } else {
                showToast(res.msg, isSuccess ? 'success' : 'danger');
            }
        } catch (e) {
            console.error("Failed to parse HTMX response:", e);
        }
    });
</script>

{include file='live_chat.tpl'}
{include file='telemetry.tpl'}

<!-- Zalo floating button -->
<a href="https://zalo.me/0796969444" target="_blank" rel="noopener noreferrer"
   class="gopass-zalo-fab" aria-label="Chat Zalo 0796969444">
    <span class="gopass-zalo-fab-icon" aria-hidden="true">Z</span>
    <span class="gopass-zalo-fab-label">Zalo</span>
</a>

</body>
</html>
