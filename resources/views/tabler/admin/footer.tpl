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

<div class="modal modal-blur fade" id="notice-dialog" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-yellow"></div>
            <div class="modal-body text-center py-4">
                <i class="ti ti-alert-circle icon mb-2 text-yellow icon-lg" style="font-size:3.5rem;"></i>
                <p id="notice-message" class="text-secondary">Lưu ý</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                <button id="notice-confirm" type="button" class="btn btn-yellow" data-bs-dismiss="modal">Xác nhận</button>
            </div>
        </div>
    </div>
</div>

<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="col-lg-auto ms-lg-auto">
                <ul class="list-inline list-inline-dots mb-0">
                    <li class="list-inline-item">
                        Powered by <a href="/staff" class="link-secondary">DPanel</a>
<!-- Không xóa trang staff — đó là sự tôn trọng với các nhà phát triển -->
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
</div>
</div>
<!-- js -->
<script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
<script>
    let successDialog = new tabler.bootstrap.Modal(document.getElementById('success-dialog'));
    let failDialog = new tabler.bootstrap.Modal(document.getElementById('fail-dialog'));

    window.successDialog = successDialog;
    window.failDialog = failDialog;

    window.dpAdmin = (function () {
        // Confirm actions must survive the live-refresh poller, which reloads the
        // page as soon as it sees the change the action itself just made.
        let pending = 0;

        function showResult(ok, message) {
            const id = ok ? 'success-message' : 'fail-message';
            const el = document.getElementById(id);
            if (el) {
                el.textContent = message;
            }
            (ok ? successDialog : failDialog).show();
        }

        function hideModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            const instance = tabler.bootstrap.Modal.getInstance(el);
            if (instance) {
                instance.hide();
            }
        }

        function setBusy(button, busy, label) {
            if (!button) return;
            if (busy) {
                button.dataset.dpOriginalHtml = button.innerHTML;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
                button.classList.add('is-gopass-busy');
                button.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                    (label || 'Đang xử lý...');
            } else {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.classList.remove('is-gopass-busy');
                if (button.dataset.dpOriginalHtml) {
                    button.innerHTML = button.dataset.dpOriginalHtml;
                }
            }
        }

        /**
         * POST a confirm action and report the real outcome.
         *
         * options: { url, button, busyLabel, closeModal, reloadOnSuccess }
         */
        async function post(options) {
            const button = options.button || null;

            if (button && button.getAttribute('aria-busy') === 'true') {
                return;
            }

            setBusy(button, true, options.busyLabel);
            pending += 1;

            try {
                const res = await fetch(options.url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const body = await res.text();
                let data = null;
                try {
                    data = JSON.parse(body);
                } catch (e) {
                    data = null;
                }

                // Close the confirm modal first: stacking the result dialog on top
                // of it leaves the page dimmed once both are dismissed.
                if (options.closeModal) {
                    hideModal(options.closeModal);
                }

                if (data && typeof data.ret !== 'undefined') {
                    if (data.ret === 1) {
                        showResult(true, data.msg || 'Thành công');
                        if (options.reloadOnSuccess !== false) {
                            window.setTimeout(function () {
                                window.location.reload();
                            }, 1200);
                        }
                        return;
                    }

                    setBusy(button, false);
                    showResult(false, data.msg || 'Thất bại');
                    return;
                }

                // No usable JSON: the action may still have gone through, so send
                // the admin back to the freshly rendered state instead of guessing.
                showResult(
                    false,
                    'Máy chủ trả về phản hồi không hợp lệ (HTTP ' + res.status +
                    '). Trang sẽ tải lại để hiển thị trạng thái thực tế.'
                );
                window.setTimeout(function () {
                    window.location.reload();
                }, 2000);
            } catch (e) {
                if (options.closeModal) {
                    hideModal(options.closeModal);
                }
                setBusy(button, false);
                showResult(false, 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.');
            } finally {
                pending -= 1;
            }
        }

        return {
            post: post,
            showResult: showResult,
            setBusy: setBusy,
            isPending: function () {
                return pending > 0;
            }
        };
    })();

    htmx.on("htmx:beforeRequest", function (evt) {
        const el = evt.detail.elt;
        if (el && el.classList && el.classList.contains('gopass-busy-submit')) {
            window.dpAdmin.setBusy(el, true, el.getAttribute('data-gopass-busy-text'));
        }
    });

    htmx.on("htmx:afterRequest", function(evt) {
        const el = evt.detail.elt;
        const isBusySubmit = el && el.classList && el.classList.contains('gopass-busy-submit');

        if (evt.detail.xhr.getResponseHeader('HX-Refresh') === 'true' ||
            evt.detail.xhr.getResponseHeader('HX-Redirect') ||
            evt.detail.xhr.getResponseHeader('HX-Trigger'))
        {
            return;
        }

        let res = null;
        try {
            res = JSON.parse(evt.detail.xhr.response);
        } catch (e) {
            res = null;
        }

        // A 500 or an HTML error page used to throw here, leaving the admin with
        // a stuck button and no feedback at all.
        if (res === null || typeof res.ret === 'undefined') {
            if (isBusySubmit) {
                window.dpAdmin.setBusy(el, false);
            }
            if (!evt.detail.successful) {
                window.dpAdmin.showResult(false, 'Máy chủ trả về lỗi (HTTP ' + evt.detail.xhr.status + ')');
            }
            return;
        }

        if (typeof res.data !== 'undefined') {
            for (let key in res.data) {
                if (res.data.hasOwnProperty(key)) {
                    let element = document.getElementById(key);

                    if (element) {
                        if (element.tagName === "INPUT" || element.tagName === "TEXTAREA") {
                            element.value = res.data[key];
                        } else {
                            element.innerHTML = res.data[key];
                        }
                    }
                }
            }
        }

        if (isBusySubmit && res.ret !== 1) {
            window.dpAdmin.setBusy(el, false);
        }

        window.dpAdmin.showResult(res.ret === 1, res.msg || (res.ret === 1 ? 'Thành công' : 'Thất bại'));
    });

    htmx.on("htmx:responseError", function (evt) {
        const el = evt.detail.elt;
        if (el && el.classList && el.classList.contains('gopass-busy-submit')) {
            window.dpAdmin.setBusy(el, false);
        }
    });

    htmx.on("htmx:sendError", function (evt) {
        const el = evt.detail.elt;
        if (el && el.classList && el.classList.contains('gopass-busy-submit')) {
            window.dpAdmin.setBusy(el, false);
            window.dpAdmin.showResult(false, 'Không gửi được yêu cầu. Kiểm tra kết nối rồi thử lại.');
        }
    });

    (function markActiveNav() {
        const path = window.location.pathname.replace(/\/+$/, '') || '/admin';

        function score(href) {
            if (!href) return -1;
            const target = href.replace(/\/+$/, '');
            if (target === '' || target === '#') return -1;
            if (path === target) return target.length + 1;
            return path.startsWith(target + '/') ? target.length : -1;
        }

        let best = null;
        let bestScore = 0;

        document.querySelectorAll('#navbar-menu a[href]').forEach(function (link) {
            const value = score(link.getAttribute('href'));
            if (value > bestScore) {
                best = link;
                bestScore = value;
            }
        });

        if (!best) {
            return;
        }

        if (best.classList.contains('dropdown-item')) {
            best.classList.add('is-active');
        }

        const item = best.closest('.nav-item');
        if (item) {
            item.classList.add('is-active');
        }
    })();

    (function initAdminLiveRefresh() {
        const POLL_MS = 10000;
        let previous = null;
        let busy = false;

        function showLiveToast(message, href) {
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-0 end-0 m-3 alert alert-important alert-info shadow';
            toast.style.zIndex = '2000';
            toast.style.maxWidth = '360px';
            toast.setAttribute('role', 'status');

            const text = document.createElement('div');
            text.className = 'mb-2';
            text.textContent = message;
            toast.appendChild(text);

            if (href) {
                const link = document.createElement('a');
                link.href = href;
                link.className = 'btn btn-sm btn-primary';
                link.textContent = 'Xem ngay';
                toast.appendChild(link);
            }

            document.body.appendChild(toast);
            setTimeout(function () {
                toast.remove();
            }, 8000);
        }

        function setBadge(el, count) {
            if (!el) return;
            if (count > 0) {
                el.style.display = '';
                el.textContent = count > 99 ? '99+' : String(count);
            } else {
                el.style.display = 'none';
                el.textContent = '0';
            }
        }

        function updateBadges(data) {
            const wait = data.tickets && data.tickets.wait_admin ? data.tickets.wait_admin : 0;
            setBadge(document.getElementById('gopass-live-badge'), wait);
            setBadge(document.getElementById('gopass-live-ticket-badge'), wait);

            const bell = document.getElementById('gopass-live-bell');
            if (bell && data.tickets && data.tickets.url) {
                bell.setAttribute('href', data.tickets.url);
            }
        }

        function notifyChanges(prev, next) {
            if (!prev || !next) return;

            if (next.users.latest_id > prev.users.latest_id) {
                showLiveToast(
                    'Khách mới đăng ký: ' + (next.users.latest_email || ('#' + next.users.latest_id)),
                    '/admin/user/' + next.users.latest_id + '/edit'
                );
            }

            if (next.tickets.latest_id > prev.tickets.latest_id) {
                const title = next.tickets.latest_title || ('Phiếu #' + next.tickets.latest_id);
                showLiveToast('Khách xác nhận / phiếu mới: ' + title, next.tickets.url);
            } else if (next.tickets.wait_admin > prev.tickets.wait_admin) {
                showLiveToast(
                    'Có ' + next.tickets.wait_admin + ' phiếu đang chờ xử lý',
                    '/admin/ticket'
                );
            }

            if (next.orders.latest_id > prev.orders.latest_id) {
                showLiveToast(
                    'Đơn hàng mới: #' + next.orders.latest_id + (next.orders.latest_name ? ' · ' + next.orders.latest_name : ''),
                    next.orders.url
                );
            }

            if (next.invoices.latest_id !== prev.invoices.latest_id ||
                next.invoices.open !== prev.invoices.open) {
                // Avoid noisy toast when only counts shift; tickets cover payment confirms.
            }
        }

        function refreshVisibleData() {
            const path = window.location.pathname;

            // Reloading mid-confirm aborts the request and makes a successful
            // action report a network failure.
            if (window.dpAdmin && window.dpAdmin.isPending()) {
                return;
            }

            if (document.querySelector('.modal.show')) {
                return;
            }

            if (path === '/admin' || path === '/admin/') {
                window.location.reload();
                return;
            }

            if (typeof reloadTableAjax === 'function') {
                reloadTableAjax();
                return;
            }

            if (typeof table !== 'undefined' && table && typeof table.ajax !== 'undefined' && table.ajax.reload) {
                table.ajax.reload(null, false);
                return;
            }

            if (/^\/admin\/(invoice|order|ticket)\/\d+/.test(path)) {
                window.location.reload();
            }
        }

        async function poll() {
            if (busy || document.hidden) return;
            if (window.dpAdmin && window.dpAdmin.isPending()) return;
            busy = true;
            try {
                const res = await fetch('/admin/live/status', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (!data || data.ret !== 1) return;

                updateBadges(data);

                if (previous && previous.fingerprint !== data.fingerprint) {
                    notifyChanges(previous, data);
                    refreshVisibleData();
                    window.dispatchEvent(new CustomEvent('gopass:admin-live', { detail: data }));
                }

                previous = data;
            } catch (e) {
                // ignore transient network errors
            } finally {
                busy = false;
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                poll();
            }
        });

        poll();
        setInterval(poll, POLL_MS);
    })();
</script>
<script>console.table([['Truy vấn cơ sở dữ liệu', 'Thời gian thực thi'], ['{count($queryLog)} lần', '{$optTime} ms']])</script>

{include file='telemetry.tpl'}

</body>

</html>
