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
                <div class="mb-3">
                    <label class="form-label">Mật khẩu mới</label>
                    <input id="password" type="password" class="form-control" placeholder="Nhập mật khẩu mới">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nhập lại mật khẩu mới</label>
                    <input id="confirm_password" type="password" class="form-control" placeholder="Nhập lại mật khẩu mới">
                </div>
                <div class="form-footer">
                    <button class="btn btn-primary w-100"
                            hx-post="{ location.pathname }" hx-swap="none"
                            hx-vals='js:{
                            password: document.getElementById("password").value,
                            confirm_password: document.getElementById("confirm_password").value, }'>
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
