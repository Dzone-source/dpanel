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

    htmx.on("htmx:afterRequest", function(evt) {
        if (evt.detail.xhr.getResponseHeader('HX-Refresh') === 'true' ||
            evt.detail.xhr.getResponseHeader('HX-Redirect') ||
            evt.detail.xhr.getResponseHeader('HX-Trigger'))
        {
            return;
        }

        let res = JSON.parse(evt.detail.xhr.response);

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
        if (res.ret === 1) {
            document.getElementById("success-message").innerHTML = res.msg;
            successDialog.show();
        } else {
            document.getElementById("fail-message").innerHTML = res.msg;
            failDialog.show();
        }
    });

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

<script src="/assets/js/sakura.js?v=20260728sakura1" defer></script>

</body>

</html>
