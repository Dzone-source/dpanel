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
                <h2 class="card-title text-center mb-4">Quên mật khẩu</h2>
                <p class="text-secondary mb-4">
                    Chúng tôi sẽ gửi email đến địa chỉ đăng ký của bạn với liên kết đặt lại mật khẩu
                </p>
                <div id="reset-alert" class="alert d-none" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email đăng ký</label>
                    <input id="email" type="email" class="form-control" autocomplete="email" required>
                </div>
                <div class="mb-3">
                    <div class="input-group mb-3">
                    {if $public_setting['enable_reset_password_captcha']|default:false}
                        {include file='captcha/div.tpl'}
                    {/if}
                    </div>
                </div>
                <div class="form-footer">
                    <button id="send" type="button" class="btn btn-primary w-100">
                        <i class="ti ti-brand-telegram icon"></i>
                        Gửi email
                    </button>
                </div>
            </div>
        </div>
        <div class="text-center text-secondary mt-3">
            Đã có tài khoản? <a href="/auth/login" tabindex="-1">Nhấn để đăng nhập</a>
        </div>
    </div>
</div>

{if $public_setting['enable_reset_password_captcha']|default:false}
    {include file='captcha/js.tpl'}
{/if}
{include file='footer.tpl'}

<script>
(function () {
    const btn = document.getElementById('send');
    const emailInput = document.getElementById('email');
    const alertBox = document.getElementById('reset-alert');
    if (!btn || !emailInput) {
        return;
    }

    const originalHtml = btn.innerHTML;
    let sending = false;

    function setBusy(busy) {
        sending = busy;
        btn.disabled = busy;
        if (busy) {
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang gửi...';
        } else {
            btn.innerHTML = originalHtml;
        }
    }

    function showAlert(message, ok) {
        if (!alertBox) {
            return;
        }
        alertBox.textContent = message || (ok ? 'Thành công' : 'Thất bại');
        alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
        alertBox.classList.add(ok ? 'alert-success' : 'alert-danger');
        alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function showOk(message) {
        showAlert(message, true);
        if (typeof window.gopassShowSuccess === 'function') {
            window.gopassShowSuccess(message);
        }
    }

    function showErr(message) {
        showAlert(message, false);
        if (typeof window.gopassShowFail === 'function') {
            window.gopassShowFail(message);
        }
    }

    function collectPayload() {
        const payload = {
            email: (emailInput.value || '').trim()
        };

        {if $public_setting['enable_reset_password_captcha']|default:false}
            {if $public_setting['captcha_provider'] === 'turnstile'}
            payload.turnstile = document.querySelector('[name=cf-turnstile-response]')
                ? document.querySelector('[name=cf-turnstile-response]').value
                : '';
            {/if}
            {if $public_setting['captcha_provider'] === 'geetest'}
            payload.geetest = typeof geetest_result !== 'undefined' ? geetest_result : null;
            {/if}
            {if $public_setting['captcha_provider'] === 'hcaptcha'}
            payload.hcaptcha = typeof hcaptcha !== 'undefined' ? hcaptcha.getResponse() : '';
            {/if}
            {if $public_setting['captcha_provider'] === 'recaptcha_enterprise'}
            payload.recaptcha_enterprise = typeof grecaptcha !== 'undefined'
                ? grecaptcha.enterprise.getResponse()
                : '';
            {/if}
        {/if}

        return payload;
    }

    btn.addEventListener('click', async function () {
        if (sending) {
            return;
        }

        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }

        const email = (emailInput.value || '').trim();
        if (!email) {
            showErr('Chưa nhập email');
            emailInput.focus();
            return;
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showErr('Email không hợp lệ');
            emailInput.focus();
            return;
        }

        setBusy(true);

        try {
            const payload = collectPayload();
            const body = new URLSearchParams();
            Object.keys(payload).forEach(function (key) {
                const value = payload[key];
                if (value === null || typeof value === 'undefined') {
                    return;
                }
                if (typeof value === 'object') {
                    body.set(key, JSON.stringify(value));
                } else {
                    body.set(key, String(value));
                }
            });

            const res = await fetch('/password/reset', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: body.toString()
            });

            const text = await res.text();
            let data = {};
            try {
                data = JSON.parse(text);
            } catch (err) {
                showErr('Máy chủ trả về phản hồi không hợp lệ (' + res.status + ')');
                return;
            }

            if (data.ret === 1) {
                showOk(data.msg || 'Đã gửi yêu cầu đặt lại mật khẩu');
            } else {
                showErr(data.msg || 'Gửi email thất bại');
            }
        } catch (err) {
            console.error(err);
            showErr('Không kết nối được máy chủ. Kiểm tra mạng / HTTPS.');
        } finally {
            setBusy(false);
        }
    });

    emailInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            btn.click();
        }
    });
})();
</script>
