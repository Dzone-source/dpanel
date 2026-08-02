{include file='header.tpl'}

<body class="gopass-auth border-top-wide border-primary d-flex flex-column">
<div class="page page-center">
    <div class="container-tight my-auto">
        <div class="text-center mb-4">
            <a href="#" class="navbar-brand navbar-brand-autodark">
                <img src="/images/uim-logo-round_96x96.png" height="64" alt="DPanel Logo">
            </a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Đặt mật khẩu mới</h2>
                <div id="token-alert" class="alert d-none" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="password">Mật khẩu mới</label>
                    <input id="password" type="password" class="form-control" placeholder="Nhập mật khẩu mới" autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="confirm_password">Nhập lại mật khẩu mới</label>
                    <input id="confirm_password" type="password" class="form-control" placeholder="Nhập lại mật khẩu mới" autocomplete="new-password">
                </div>
                <div class="form-footer">
                    <button id="reset-password-btn" type="button" class="btn btn-primary w-100">
                        <i class="ti ti-key icon"></i>
                        Đặt lại
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            Đã có tài khoản? <a href="/auth/login" tabindex="-1">Nhấn để đăng nhập</a>
        </div>
    </div>
</div>

{include file='footer.tpl'}

<script>
(function () {
    const btn = document.getElementById('reset-password-btn');
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('confirm_password');
    const alertBox = document.getElementById('token-alert');
    const token = "{$token}";
    if (!btn || !passwordInput || !confirmInput) return;

    const originalHtml = btn.innerHTML;
    let sending = false;

    function setBusy(busy) {
        sending = busy;
        btn.disabled = busy;
        btn.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang xử lý...'
            : originalHtml;
    }

    function showAlert(message, ok) {
        alertBox.textContent = message || (ok ? 'Thành công' : 'Thất bại');
        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertBox.classList.add(ok ? 'alert-success' : 'alert-danger');
        if (ok && typeof window.gopassShowSuccess === 'function') window.gopassShowSuccess(message);
        if (!ok && typeof window.gopassShowFail === 'function') window.gopassShowFail(message);
    }

    btn.addEventListener('click', async function () {
        if (sending) return;

        const password = passwordInput.value || '';
        const confirmPassword = confirmInput.value || '';

        if (password.length < 8) {
            showAlert('Mật khẩu quá ngắn', false);
            return;
        }
        if (password !== confirmPassword) {
            showAlert('Hai lần nhập không khớp', false);
            return;
        }

        setBusy(true);
        try {
            const body = new URLSearchParams();
            body.set('token', token);
            body.set('password', password);
            body.set('confirm_password', confirmPassword);

            const res = await fetch('/password/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: body.toString()
            });

            let data = {};
            try {
                data = JSON.parse(await res.text());
            } catch (e) {
                showAlert('Máy chủ trả về phản hồi không hợp lệ (' + res.status + ')', false);
                return;
            }

            if (data.ret === 1) {
                showAlert(data.msg || 'Đặt lại thành công', true);
                window.setTimeout(function () { window.location.href = '/auth/login'; }, 1200);
            } else {
                showAlert(data.msg || 'Đặt lại thất bại', false);
            }
        } catch (err) {
            console.error(err);
            showAlert('Không kết nối được máy chủ. Kiểm tra mạng / HTTPS.', false);
        } finally {
            setBusy(false);
        }
    });
})();
</script>
