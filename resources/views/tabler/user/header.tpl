<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta name="theme-color" content="#7c3aed"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <meta name="referrer" content="never">
    <title>{$config['appName']}</title>
    <link href="https://{$config['jsdelivr_url']}/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="/assets/css/tabler-icons.min.css?v=3.31.0" rel="stylesheet"/>
    <link href="/assets/css/gopass.css?v=20260715c" rel="stylesheet"/>
    <script src="/assets/js/fuck.min.js"></script>
    <script src="https://{$config['jsdelivr_url']}/npm/qrcode_js@latest/qrcode.min.js"></script>
    <script src="https://{$config['jsdelivr_url']}/npm/clipboard@latest/dist/clipboard.min.js"></script>
    <script src="https://{$config['jsdelivr_url']}/npm/htmx.org@2.0.4/dist/htmx.min.js"></script>
</head>

{if $user->is_dark_mode}
<body class="gopass-theme" data-bs-theme="dark">
{else}
<body class="gopass-theme" data-bs-theme="light">
{/if}

<div id="gopass-sidebar-overlay" class="gopass-sidebar-overlay"></div>

<div class="gopass-app">
    <aside id="gopass-sidebar" class="gopass-sidebar">
        <div class="gopass-sidebar-brand">
            <img src="/images/uim-logo-round_48x48.png" height="36" width="36" alt="{$config['appName']}">
            <span>{$config['appName']}</span>
        </div>

        <div class="gopass-sidebar-user">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm" style="background-image: url({$user->dice_bear})"></span>
                <div>
                    <div class="user-name">{$user->user_name}</div>
                    <div class="user-email">{$user->email}</div>
                </div>
            </div>
        </div>

        <nav class="gopass-nav">
            <div class="gopass-nav-section">Trang chính</div>
            <a class="gopass-nav-link" href="/user">
                <i class="ti ti-home"></i> Trang chủ
            </a>
            <a class="gopass-nav-link" href="/user/server">
                <i class="ti ti-server"></i> Máy chủ
                <span class="gopass-nav-badge">Hot</span>
            </a>

            <div class="gopass-nav-section">Tài khoản</div>
            <a class="gopass-nav-link" href="/user/profile">
                <i class="ti ti-user"></i> Thông tin
            </a>
            <a class="gopass-nav-link" href="/user/edit">
                <i class="ti ti-settings"></i> Hồ sơ
            </a>
            <a class="gopass-nav-link" href="/user/money">
                <i class="ti ti-wallet"></i> Ví tiền
            </a>
            <a class="gopass-nav-link" href="/user/invite">
                <i class="ti ti-friends"></i> Mời bạn
            </a>

            <div class="gopass-nav-section">Cửa hàng</div>
            <a class="gopass-nav-link" href="/user/product">
                <i class="ti ti-shopping-cart"></i> Sản phẩm
            </a>
            <a class="gopass-nav-link" href="/user/order">
                <i class="ti ti-file-invoice"></i> Đơn hàng
            </a>
            <a class="gopass-nav-link" href="/user/invoice">
                <i class="ti ti-receipt"></i> Hóa đơn
            </a>

            <div class="gopass-nav-section">Hỗ trợ</div>
            <a class="gopass-nav-link" href="/user/announcement">
                <i class="ti ti-speakerphone"></i> Thông báo
            </a>
            {if $public_setting['enable_ticket']|default:false}
            <a class="gopass-nav-link" href="/user/ticket">
                <i class="ti ti-ticket"></i> Phiếu hỗ trợ
            </a>
            {/if}
            {if ($public_setting['display_docs']|default:false) &&
            (! ($public_setting['display_docs_only_for_paid_user']|default:false) || $user->class !== 0)}
            <a class="gopass-nav-link" href="/user/docs">
                <i class="ti ti-book"></i> Tài liệu
            </a>
            {/if}
            <a class="gopass-nav-link" href="/user/rate">
                <i class="ti ti-chart-bar"></i> Hệ số lưu lượng
            </a>

            <div class="gopass-nav-section">Kiểm duyệt</div>
            <a class="gopass-nav-link" href="/user/detect">
                <i class="ti ti-shield-check"></i> Quy tắc
            </a>
            {if $public_setting['display_detect_log']|default:false}
            <a class="gopass-nav-link" href="/user/detect/log">
                <i class="ti ti-list"></i> Nhật ký
            </a>
            {/if}

            {if $user->is_admin}
            <div class="gopass-nav-section">Quản trị</div>
            <a class="gopass-nav-link" href="/admin">
                <i class="ti ti-dashboard"></i> Bảng quản trị
            </a>
            {/if}
        </nav>
    </aside>

    <div class="gopass-content">
        <header class="gopass-topbar">
            <div class="d-flex align-items-center gap-2 min-w-0">
                <button id="gopass-sidebar-toggle" class="gopass-sidebar-toggle" type="button" aria-label="Menu">
                    <i class="ti ti-menu-2"></i>
                </button>
                <div class="min-w-0">
                    <div class="fw-bold text-truncate d-md-none" style="font-size:0.95rem;letter-spacing:-0.02em">
                        {$config['appName']}
                    </div>
                    <span class="text-secondary d-none d-md-inline" style="font-size:0.85rem">
                        Xin chào, <strong>{$user->user_name}</strong>
                    </span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1 gap-sm-2 flex-shrink-0">
                {if $user->is_dark_mode}
                <button class="btn btn-ghost-secondary btn-sm" hx-post="/user/switch_theme_mode" hx-swap="none" title="Chế độ sáng">
                    <i class="ti ti-sun"></i>
                </button>
                {else}
                <button class="btn btn-ghost-secondary btn-sm" hx-post="/user/switch_theme_mode" hx-swap="none" title="Chế độ tối">
                    <i class="ti ti-moon"></i>
                </button>
                {/if}
                <a href="/user/logout" class="btn btn-ghost-danger btn-sm">
                    <i class="ti ti-logout"></i>
                    <span class="d-none d-sm-inline ms-1">Đăng xuất</span>
                </a>
            </div>
        </header>

        <main class="gopass-main">
