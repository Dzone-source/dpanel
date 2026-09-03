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
            {if ($public_setting['reg_mode']|default:'open') !== 'close'}
                <div class="card-body">
                    <h2 class="card-title text-center mb-4">Đăng ký</h2>
                    <p class="text-secondary text-center mb-4" style="margin-top:-0.75rem;font-size:0.9rem">
                        Tạo tài khoản {$config['appName']}
                    </p>
                    <div id="register-alert" class="alert alert-danger d-none" role="alert"></div>
                    <div class="mb-3">
                        <input id="name" type="text" class="form-control" placeholder="Biệt danh" autocomplete="nickname">
                    </div>
                    <div class="mb-3">
                        <input id="email" type="email" class="form-control" placeholder="Email" autocomplete="email">
                    </div>
                    {if $public_setting['reg_email_verify']|default:false}
                    <div class="mb-3">
                        <div class="input-group mb-2">
                            <input id="emailcode" type="text" class="form-control" placeholder="Mã xác minh email">
                            <button id="send-verify-email" class="btn text-blue" type="button"
                                    hx-post="/auth/send" hx-swap="none" hx-disabled-elt="this"
                                    hx-vals='js:{ email: document.getElementById("email").value }'>
                                Lấy mã
                            </button>
                        </div>
                    </div>
                    {/if}
                    <div class="mb-3">
                        <input id="password" type="password" class="form-control" placeholder="Mật khẩu đăng nhập" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <input id="confirm_password" type="password" class="form-control" placeholder="Nhập lại mật khẩu đăng nhập" autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <input id="invite_code" type="text" class="form-control"
                               placeholder="Mã mời đăng ký{if ($public_setting['reg_mode']|default:'open') === 'open'}（tùy chọn）{else}（bắt buộc）{/if}"
                               value="{$invite_code|default:''}">
                    </div>
                    <div class="mb-3" id="tos-wrap">
                        <label class="form-check">
                            <input id="tos" type="checkbox" class="form-check-input"/>
                            <span class="form-check-label">
                                    Tôi đã đọc và đồng ý <a href="/tos" tabindex="-1"> Điều khoản dịch vụ và Chính sách bảo mật </a>
                                </span>
                        </label>
                    </div>
                    <div class="mb-3">
                        <div class="input-group mb-3">
                        {if $public_setting['enable_reg_captcha']|default:false}
                            {include file='captcha/div.tpl'}
                        {/if}
                        </div>
                    </div>
                    <div class="form-footer">
                        <button id="register-btn" type="button" class="btn btn-primary w-100"
                                hx-post="/auth/register" hx-swap="none" hx-vals='js:{
                                    {if $public_setting['reg_email_verify']|default:false}
                                        emailcode: document.getElementById("emailcode").value,
                                    {/if}
                                    {if $public_setting['enable_reg_captcha']|default:false}
                                        {include file='captcha/ajax.tpl'}
                                    {/if}
                                    name: document.getElementById("name").value,
                                    email: document.getElementById("email").value,
                                    password: document.getElementById("password").value,
                                    confirm_password: document.getElementById("confirm_password").value,
                                    invite_code: document.getElementById("invite_code").value,
                                    tos: document.getElementById("tos").checked,
                                 }'>
                            Đăng ký tài khoản mới
                        </button>
                    </div>
                </div>
            {else}
                <div class="card-body">
                    <p>Chưa mở đăng ký, hãy quay lại sau vài ngày</p>
                </div>
            {/if}
        </div>
        <div class="text-center text-secondary mt-3">
            Đã có tài khoản? <a href="/auth/login" tabindex="-1">Nhấn để đăng nhập</a>
        </div>
    </div>
</div>

{if $public_setting['enable_reg_captcha']|default:false}
    {include file='captcha/js.tpl'}
{/if}

{include file='footer.tpl'}

<script>
(function () {
    const requireInvite = {if ($public_setting['reg_mode']|default:'open') === 'invite'}true{else}false{/if};
    const requireEmailCode = {if $public_setting['reg_email_verify']|default:false}true{else}false{/if};

    const alertBox = document.getElementById('register-alert');
    const fields = {
        name: document.getElementById('name'),
        email: document.getElementById('email'),
        emailcode: document.getElementById('emailcode'),
        password: document.getElementById('password'),
        confirm_password: document.getElementById('confirm_password'),
        invite_code: document.getElementById('invite_code'),
        tos: document.getElementById('tos'),
        tosWrap: document.getElementById('tos-wrap'),
    };

    function clearFieldErrors() {
        ['name', 'email', 'emailcode', 'password', 'confirm_password', 'invite_code'].forEach(function (id) {
            const el = fields[id];
            if (el) {
                el.classList.remove('is-invalid');
            }
        });
        if (fields.tosWrap) {
            fields.tosWrap.classList.remove('border', 'border-danger', 'rounded', 'p-2');
        }
    }

    function markInvalid(el) {
        if (el) {
            el.classList.add('is-invalid');
        }
    }

    function showRegisterError(msg, focusEl) {
        if (alertBox) {
            alertBox.textContent = msg;
            alertBox.classList.remove('d-none');
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        if (window.failDialog && document.getElementById('fail-message')) {
            document.getElementById('fail-message').textContent = msg;
            try { failDialog.show(); } catch (e) {}
        }
        if (focusEl && typeof focusEl.focus === 'function') {
            try { focusEl.focus(); } catch (e) {}
        }
    }

    function clearRegisterError() {
        if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
        clearFieldErrors();
    }

    function validateRegisterForm() {
        clearFieldErrors();

        const name = (fields.name && fields.name.value || '').trim();
        const email = (fields.email && fields.email.value || '').trim();
        const password = fields.password ? fields.password.value : '';
        const confirmPassword = fields.confirm_password ? fields.confirm_password.value : '';
        const inviteCode = (fields.invite_code && fields.invite_code.value || '').trim();
        const emailCode = (fields.emailcode && fields.emailcode.value || '').trim();

        if (!name) {
            markInvalid(fields.name);
            return { ok: false, msg: 'Vui lòng nhập biệt danh', focus: fields.name };
        }

        if (!email) {
            markInvalid(fields.email);
            return { ok: false, msg: 'Vui lòng nhập email', focus: fields.email };
        }

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            markInvalid(fields.email);
            return { ok: false, msg: 'Email không hợp lệ', focus: fields.email };
        }

        if (requireEmailCode && !emailCode) {
            markInvalid(fields.emailcode);
            return { ok: false, msg: 'Vui lòng nhập mã xác minh email', focus: fields.emailcode };
        }

        if (!password) {
            markInvalid(fields.password);
            return { ok: false, msg: 'Vui lòng nhập mật khẩu', focus: fields.password };
        }

        if (password.length < 8) {
            markInvalid(fields.password);
            return { ok: false, msg: 'Mật khẩu phải có ít nhất 8 ký tự', focus: fields.password };
        }

        if (!confirmPassword) {
            markInvalid(fields.confirm_password);
            return { ok: false, msg: 'Vui lòng nhập lại mật khẩu', focus: fields.confirm_password };
        }

        if (password !== confirmPassword) {
            markInvalid(fields.password);
            markInvalid(fields.confirm_password);
            return { ok: false, msg: 'Hai lần nhập mật khẩu không khớp', focus: fields.confirm_password };
        }

        if (requireInvite && !inviteCode) {
            markInvalid(fields.invite_code);
            return { ok: false, msg: 'Vui lòng nhập mã mời đăng ký', focus: fields.invite_code };
        }

        if (!fields.tos || !fields.tos.checked) {
            if (fields.tosWrap) {
                fields.tosWrap.classList.add('border', 'border-danger', 'rounded', 'p-2');
            }
            return {
                ok: false,
                msg: 'Vui lòng đồng ý với Điều khoản dịch vụ và Chính sách bảo mật',
                focus: fields.tos,
            };
        }

        return { ok: true };
    }

    ['name', 'email', 'emailcode', 'password', 'confirm_password', 'invite_code'].forEach(function (id) {
        const el = fields[id];
        if (!el) {
            return;
        }
        el.addEventListener('input', function () {
            el.classList.remove('is-invalid');
            if (alertBox && !alertBox.classList.contains('d-none')) {
                // Keep message until next submit, but clear field highlight only
            }
        });
    });

    if (fields.tos) {
        fields.tos.addEventListener('change', function () {
            if (fields.tos.checked) {
                clearRegisterError();
            }
        });
    }

    if (typeof htmx === 'undefined') {
        return;
    }

    htmx.on('htmx:beforeRequest', function (evt) {
        const el = evt.detail.elt;
        if (!el || el.id !== 'register-btn') {
            return;
        }

        const result = validateRegisterForm();
        if (!result.ok) {
            evt.preventDefault();
            showRegisterError(result.msg, result.focus);
        } else if (alertBox) {
            alertBox.classList.add('d-none');
            alertBox.textContent = '';
        }
    });
})();
</script>
