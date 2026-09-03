{include file='header.tpl'}

<body class="gopass-auth gopass-auth--wallpaper border-top-wide border-primary d-flex flex-column">
{if $login_wallpaper.url|default:'' ne ''}
<div class="gopass-auth-wallpaper"
     data-wallpaper-url="{$login_wallpaper.url|escape:'html'}"
     data-wallpaper-page="{$login_wallpaper.page_url|escape:'html'}"
     data-wallpaper-credit="{$login_wallpaper.credit|escape:'html'}"></div>
{/if}
<div class="page page-center">
    <div class="container-tight my-auto">
        <div class="text-center mb-4">
            <a href="/" class="navbar-brand navbar-brand-autodark">
                <img src="/images/uim-logo-round_96x96.png" height="64" alt="DPanel Logo">
            </a>
        </div>
        <div class="card card-md">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Đăng nhập</h2>
                <p class="text-secondary text-center mb-4" style="margin-top:-0.75rem;font-size:0.9rem">
                    Trung tâm người dùng {$config['appName']}
                </p>
                <form id="login-form" action="/auth/login" method="post" autocomplete="on">
                    <div class="mb-3">
                        <label class="form-label" for="email">Email</label>
                        <input id="email" name="email" type="email" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="password">
                            Mật khẩu đăng nhập
                            <span class="form-label-description">
                                <a href="/password/reset">Quên mật khẩu</a>
                            </span>
                        </label>
                        <div class="input-group input-group-flat">
                            <input id="password" name="password" type="password" class="form-control" autocomplete="current-password" required>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-check">
                            <input id="remember_me" name="remember_me" type="checkbox" class="form-check-input" value="true" checked/>
                            <span class="form-check-label">Ghi nhớ thiết bị</span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <div class="input-group mb-3">
                        {if $public_setting['enable_login_captcha']|default:false}
                            {include file='captcha/div.tpl'}
                        {/if}
                        </div>
                    </div>
                    <div id="login-alert" class="alert alert-danger d-none" role="alert"></div>
                    <div class="form-footer">
                        <button id="login-btn" type="submit" class="btn btn-primary w-100 mb-3">
                            Đăng nhập
                        </button>
                        <button type="button" class="btn btn-outline-primary w-100" id="webauthnLogin">
                            Đăng nhập bằng WebAuthn
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="text-center gopass-auth-aside mt-3">
            Chưa có tài khoản? <a href="/auth/register" tabindex="-1">Nhấn để đăng ký</a>
        </div>
    </div>
</div>
<a class="gopass-auth-pexels" href="{$login_wallpaper.page_url|default:'https://www.pexels.com/search/4k%20wallpaper/'|escape:'html'}" target="_blank" rel="noopener noreferrer">
    Ảnh: {$login_wallpaper.credit|default:'Pexels'|escape:'html'}
</a>

{if $public_setting['enable_login_captcha']|default:false}
    {include file='captcha/js.tpl'}
{/if}

{include file='footer.tpl'}

<script src="/assets/js/login-wallpaper.js?v=20260903pexels2"></script>

<script>
(function () {
    const form = document.getElementById('login-form');
    const btn = document.getElementById('login-btn');
    const alertBox = document.getElementById('login-alert');

    function showError(msg) {
        alertBox.textContent = msg || 'Đăng nhập thất bại';
        alertBox.classList.remove('d-none');
        if (window.failDialog && document.getElementById('fail-message')) {
            document.getElementById('fail-message').textContent = msg;
            try { failDialog.show(); } catch (e) {}
        }
    }

    function showOk(msg) {
        if (window.successDialog && document.getElementById('success-message')) {
            document.getElementById('success-message').textContent = msg;
            try { successDialog.show(); } catch (e) {}
        }
    }

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        alertBox.classList.add('d-none');
        btn.disabled = true;

        const body = new URLSearchParams();
        body.set('email', document.getElementById('email').value.trim());
        body.set('password', document.getElementById('password').value);
        body.set('remember_me', document.getElementById('remember_me').checked ? 'true' : 'false');

        try {
            const res = await fetch('/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'HX-Request': 'true',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: body.toString()
            });

            const redirect = res.headers.get('HX-Redirect');
            if (redirect) {
                window.location.href = redirect;
                return;
            }

            let data = {};
            const text = await res.text();
            try { data = JSON.parse(text); } catch (err) {
                showError('Máy chủ trả về phản hồi không hợp lệ (' + res.status + ')');
                return;
            }

            if (data.ret === 1) {
                showOk(data.msg || 'Thành công');
                window.location.href = data.redir || redirect || '/user';
            } else {
                showError(data.msg || 'Email hoặc mật khẩu không đúng');
            }
        } catch (err) {
            showError('Không kết nối được máy chủ. Kiểm tra mạng / HTTPS.');
            console.error(err);
        } finally {
            btn.disabled = false;
        }
    });
})();
</script>

<script src="https://cdn.jsdelivr.net/npm/@simplewebauthn/browser@13.1.0/dist/bundle/index.umd.min.js"></script>
<script>
(function () {
    const btn = document.getElementById('webauthnLogin');
    if (!btn || typeof SimpleWebAuthnBrowser === 'undefined') {
        if (btn) btn.classList.add('d-none');
        return;
    }
    const { startAuthentication } = SimpleWebAuthnBrowser;
    btn.addEventListener('click', async function () {
        try {
            const resp = await fetch('/auth/webauthn');
            const options = await resp.json();
            const asseResp = await startAuthentication({ optionsJSON: options });
            const verificationResp = await fetch('/auth/webauthn', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(asseResp)
            });
            const verificationJSON = await verificationResp.json();
            if (verificationJSON.ret === 1) {
                window.location.href = verificationJSON.redir || '/user';
            } else {
                const box = document.getElementById('login-alert');
                box.textContent = verificationJSON.msg || 'WebAuthn thất bại';
                box.classList.remove('d-none');
            }
        } catch (error) {
            const box = document.getElementById('login-alert');
            box.textContent = String(error);
            box.classList.remove('d-none');
        }
    });
})();
</script>
