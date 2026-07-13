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
                <div class="mb-3">
                    <label class="form-label">Email đăng ký</label>
                    <input id="email" type="email" class="form-control">
                </div>
                <div class="mb-3">
                    <div class="input-group mb-3">
                    {if $public_setting['enable_reset_password_captcha']}
                        {include file='captcha/div.tpl'}
                    {/if}
                    </div>
                </div>
                <div class="form-footer">
                    <button id="send" class="btn btn-primary w-100"
                        hx-post="/password/reset" hx-swap="none" hx-vals='js:{
                            {if $public_setting['enable_reset_password_captcha']}
                                {include file='captcha/ajax.tpl'}
                            {/if}
                            email: document.getElementById("email").value,
                         }'>
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

{if $public_setting['enable_reset_password_captcha']}
    {include file='captcha/js.tpl'}
{/if}
{include file='footer.tpl'}
