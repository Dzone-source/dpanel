{include file='header.tpl'}

<body class="gopass-auth border-top-wide border-primary d-flex flex-column">
<span class="gopass-ong-sao gopass-ong-sao--xl gopass-ong-sao-decor gopass-ong-sao-decor--tl" aria-hidden="true"></span>
<span class="gopass-ong-sao gopass-ong-sao--lg gopass-ong-sao--gold gopass-ong-sao-decor gopass-ong-sao-decor--tr" aria-hidden="true"></span>
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
                    <input id="email" type="email" class="form-control" autocomplete="email">
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
    if (!btn || !emailInput) return;

    const originalHtml = btn.innerHTML;
    let sending = false;

    function setBusy(busy) {
        sending = busy;
        btn.disabled = busy;
        btn.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang gửi...'
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
        alertBox.classList.add('d-none');

        const email = (emailInput.value || '').trim();
        if (!email) {
            showAlert('Chưa nhập email', false);
            emailInput.focus();
            return;
        }

        setBusy(true);
        try {
            const body = new URLSearchParams();
            body.set('email', email);
            {if $public_setting['enable_reset_password_captcha']|default:false}
                {if $public_setting['captcha_provider'] === 'turnstile'}
                body.set('turnstile', document.querySelector('[name=cf-turnstile-response]')?.value || '');
                {/if}
                {if $public_setting['captcha_provider'] === 'hcaptcha'}
                body.set('hcaptcha', typeof hcaptcha !== 'undefined' ? hcaptcha.getResponse() : '');
                {/if}
            {/if}

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

            let data = {};
            try {
                data = JSON.parse(await res.text());
            } catch (e) {
                showAlert('Máy chủ trả về phản hồi không hợp lệ (' + res.status + ')', false);
                return;
            }

            if (data.ret === 1) {
                showAlert(data.msg || 'Đã gửi yêu cầu đặt lại mật khẩu', true);
            } else {
                showAlert(data.msg || 'Gửi email thất bại', false);
            }
        } catch (err) {
            console.error(err);
            showAlert('Không kết nối được máy chủ. Kiểm tra mạng / HTTPS.', false);
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
