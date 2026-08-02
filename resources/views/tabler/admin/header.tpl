<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" name="viewport"/>
    <meta name="format-detection" content="telephone=no"/>
    <title>{$config['appName']}</title>
    <!-- CSS files -->
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
    <link href="//{$config['jsdelivr_url']}/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="/assets/css/tabler-icons.min.css?v=3.31.0" rel="stylesheet"/>
    <!-- JS files -->
    <script src="//{$config['jsdelivr_url']}/npm/qrcode_js@latest/qrcode.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/clipboard@latest/dist/clipboard.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/jquery/dist/jquery.min.js"></script>
    <script src="//{$config['jsdelivr_url']}/npm/htmx.org@2.0.4/dist/htmx.min.js"></script>
    <style>
        :root {
            --tblr-font-sans-serif: "Be Vietnam Pro", "Noto Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
            --tblr-body-font-family: "Be Vietnam Pro", "Noto Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
            --tblr-primary: #ef4056;
            --tblr-primary-rgb: 239, 64, 86;
            --tblr-primary-fg: #fff;
            --gopass-primary: #ef4056;
            --gopass-primary-dark: #c91f3a;
            --gopass-primary-light: #f48291;
            --gopass-accent: #ff6b81;
        }

        body {
            font-family: "Be Vietnam Pro", "Noto Sans", system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .btn-primary,
        .bg-primary,
        .badge.bg-primary,
        .nav-pills .nav-link.active,
        .page-item.active .page-link,
        .form-check-input:checked {
            background-color: #ef4056 !important;
            border-color: #ef4056 !important;
        }

        .text-primary {
            color: #ef4056 !important;
        }

        .page-header,
        .navbar-overlap:after {
            background: linear-gradient(125deg, #83232f 0%, #ef4056 52%, #ff5c7a 100%) !important;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #c91f3a !important;
            border-color: #c91f3a !important;
        }

        .home-subtitle {
            font-size: 14px;
        }

        .home-title {
            font-size: 36px;
        }

        /* Admin: avoid Tabler/sakura leftover scroll height under footer */
        html, body {
            height: auto;
            min-height: 0;
        }

        .page {
            min-height: 0;
        }

        #gopass-sakura,
        .gopass-sakura-canvas {
            display: none !important;
            position: fixed !important;
            inset: 0 !important;
            width: 0 !important;
            height: 0 !important;
            pointer-events: none !important;
        }
    </style>
</head>

{if $user->is_dark_mode == 1}
<body data-bs-theme="dark">
{elseif $user->is_dark_mode == 2}
<body data-bs-theme="auto">
<script>
(function () {
    function apply() {
        document.body.setAttribute(
            'data-bs-theme',
            window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
        );
    }
    apply();
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', apply);
})();
</script>
{else}
<body data-bs-theme="light">
{/if}
<div class="page">
    <header class="navbar navbar-expand-md navbar-overlap d-print-none" data-bs-theme="dark">
        <div class="container-xl" style="background-image: none;">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                <span class="navbar-toggler-icon"></span>
            </button>
            <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                <img src="/images/uim-logo-round_48x48.png" height="32" alt="DPanel Logo"
                     class="navbar-brand-image" style="filter: none;">
            </h1>
            <div class="navbar-nav flex-row order-md-last">
                <div class="nav-item d-none d-md-flex me-2">
                    <a href="/admin/ticket" class="nav-link px-2 position-relative" id="gopass-live-bell"
                       title="Cập nhật trực tiếp" aria-label="Thông báo trực tiếp">
                        <i class="ti ti-bell" style="font-size:1.25rem;"></i>
                        <span id="gopass-live-badge" class="badge bg-red text-red-fg badge-notification badge-pill"
                              style="display:none;">0</span>
                    </a>
                </div>
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                       aria-label="Open user menu">
                            <span class="avatar avatar-sm"
                                  style="background-image: url({$user->dice_bear})"></span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{$user->email}</div>
                            <div class="mt-1 small text-secondary">{$user->user_name}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        {if $user->is_dark_mode == 1}
                            <a class="dropdown-item" hx-post="/user/switch_theme_mode" hx-swap="none"
                               hx-vals='js:{ prefers_dark: "1" }'>
                                Chế độ sáng
                            </a>
                        {else}
                            <a class="dropdown-item" hx-post="/user/switch_theme_mode" hx-swap="none"
                               hx-vals='js:{ prefers_dark: window.matchMedia("(prefers-color-scheme: dark)").matches ? "1" : "0" }'>
                                Chế độ tối
                            </a>
                        {/if}
                        <a href="/user/logout" class="dropdown-item">Đăng xuất</a>
                    </div>
                </div>
            </div>
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="/admin">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Tổng quan
                                    </span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-base" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Quản lý
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <div class="dropend">
                                            <a class="dropdown-item dropdown-toggle" href="#" data-bs-toggle="dropdown"
                                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                                <i class="ti ti-settings"></i>&nbsp;
                                                Cài đặt
                                            </a>
                                            <div class="dropdown-menu">
                                                <a href="/admin/setting/billing" class="dropdown-item">
                                                    Tài chính
                                                </a>
                                                <a href="/admin/setting/email" class="dropdown-item">
                                                    Email
                                                </a>
                                                <a href="/admin/setting/support" class="dropdown-item">
                                                    Hỗ trợ khách hàng
                                                </a>
                                                <a href="/admin/setting/captcha" class="dropdown-item">
                                                    Xác minh
                                                </a>
                                                <a href="/admin/setting/reg" class="dropdown-item">
                                                    Đăng ký
                                                </a>
                                                <a href="/admin/setting/ref" class="dropdown-item">
                                                    Mời bạn
                                                </a>
                                                <a href="/admin/setting/im" class="dropdown-item">
                                                    IM
                                                </a>
                                                <a href="/admin/setting/sub" class="dropdown-item">
                                                    Đăng ký node
                                                </a>
                                                <a href="/admin/setting/cron" class="dropdown-item">
                                                    Tác vụ định kỳ
                                                </a>
                                                <a href="/admin/setting/feature" class="dropdown-item">
                                                    Cài đặt khác
                                                </a>
                                            </div>
                                        </div>
                                        <a class="dropdown-item" href="/admin/user">
                                            <i class="ti ti-users"></i>&nbsp;
                                            Người dùng
                                        </a>
                                        <a class="dropdown-item" href="/admin/node">
                                            <i class="ti ti-server-2"></i>&nbsp;
                                            Máy chủ
                                        </a>
                                        <a class="dropdown-item" href="/admin/system">
                                            <i class="ti ti-tool"></i>&nbsp;
                                            Hệ thống
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-brand-hipchat icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Vận hành
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/admin/announcement">
                                    <i class="ti ti-speakerphone"></i>&nbsp;
                                    Thông báo
                                </a>
                                <a class="dropdown-item" href="/admin/ticket">
                                    <i class="ti ti-messages"></i>&nbsp;
                                    Phiếu hỗ trợ
                                    <span id="gopass-live-ticket-badge" class="badge bg-red ms-1" style="display:none;">0</span>
                                </a>
                                <a class="dropdown-item" href="/admin/docs">
                                    <i class="ti ti-notes"></i>&nbsp;
                                    Tài liệu
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-address-book icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Nhật ký
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/admin/login">
                                    <i class="ti ti-login"></i>&nbsp;
                                    Đăng nhập
                                </a>
                                <a class="dropdown-item" href="/admin/subscribe">
                                    <i class="ti ti-rss"></i>&nbsp;
                                    Đăng ký
                                </a>
                                <a class="dropdown-item" href="/admin/payback">
                                    <i class="ti ti-friends"></i>&nbsp;
                                    Hoa hồng
                                </a>
                                <a class="dropdown-item" href="/admin/money">
                                    <i class="ti ti-coin"></i>&nbsp;
                                    Số dư
                                </a>
                                <a class="dropdown-item" href="/admin/gateway">
                                    <i class="ti ti-torii"></i>&nbsp;
                                    Cổng thanh toán
                                </a>
                                <a class="dropdown-item" href="/admin/online">
                                    <i class="ti ti-router"></i>&nbsp;
                                    IP trực tuyến
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-extra" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-shield-check icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Kiểm toán
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="/admin/detect">
                                    <i class="ti ti-barrier-block"></i>&nbsp;
                                    Quy tắc
                                </a>
                                <a class="dropdown-item" href="/admin/detect/log">
                                    <i class="ti ti-notes"></i>&nbsp;
                                    Nhật ký vi phạm
                                </a>
                                <a class="dropdown-item" href="/admin/detect/ban">
                                    <i class="ti ti-notes"></i>&nbsp;
                                    Nhật ký khóa
                                </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#navbar-layout" data-bs-toggle="dropdown"
                               data-bs-auto-close="outside" role="button" aria-expanded="false">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-coin icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Tài chính
                                    </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="dropdown-menu-columns">
                                    <div class="dropdown-menu-column">
                                        <a class="dropdown-item" href="/admin/product">
                                            <i class="ti ti-list-details"></i>&nbsp;
                                            Sản phẩm
                                        </a>
                                        <a class="dropdown-item" href="/admin/order">
                                            <i class="ti ti-receipt"></i>&nbsp;
                                            Đơn hàng
                                        </a>
                                        <a class="dropdown-item" href="/admin/invoice">
                                            <i class="ti ti-file-dollar"></i>&nbsp;
                                            Hóa đơn
                                        </a>
                                        <a class="dropdown-item" href="/admin/coupon">
                                            <i class="ti ti-ticket"></i>&nbsp;
                                            Mã giảm giá
                                        </a>
                                        <a class="dropdown-item" href="/admin/giftcard">
                                            <i class="ti ti-gift"></i>&nbsp;
                                            Thẻ quà tặng
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/user">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-arrow-back-up icon"></i>
                                    </span>
                                <span class="nav-link-title">
                                        Về trang người dùng
                                    </span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
