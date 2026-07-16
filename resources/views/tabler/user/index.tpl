{include file='user/header.tpl'}

<style>
/* Animation classes for collapsible sections */
.collapsible-section {
    transition: all 0.35s ease;
    overflow: hidden;
}

.collapsible-section.collapsing {
    opacity: 0.3;
    transform: scale(0.98);
}

.collapsible-section.expanded {
    opacity: 1;
    transform: scale(1);
}

/* Client item hover effects */
.client-item:hover {
    border-color: var(--tblr-primary) !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

/* Copy button feedback */
.copy.copied {
    background-color: var(--tblr-success) !important;
    border-color: var(--tblr-success) !important;
}

.recommended-section {
    background: rgba(var(--tblr-primary-rgb), 0.1);
    border: 1px solid rgba(var(--tblr-primary-rgb), 0.2);
}

.client-item {
    transition: all 0.3s;
}

.client-item:hover {
    background: var(--tblr-bg-surface-secondary);
    transform: translateX(5px);
}

@media (max-width: 576px) {
    .client-item:hover {
        transform: none;
    }
    
    .client-item .btn-group-vertical {
        margin-top: 0.5rem;
    }
    
    .recommended-section h4 {
        font-size: 1rem;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .copy button {
        word-break: keep-all;
        white-space: nowrap;
    }
    
    /* Enhanced mobile button styles */
    .btn-group-vertical .btn {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        min-height: 44px; /* iOS recommended touch target */
    }
    
    .btn-group-vertical {
        gap: 0.5rem;
    }
    
    .client-item {
        padding: 1rem !important;
    }
    
    /* Recommended client cards on mobile */
    .recommended-section .card-body {
        padding: 1rem;
    }
    
    .recommended-section .btn-group {
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
    }
    
    .recommended-section .btn-group-vertical {
        align-items: stretch;
        width: 100%;
    }
    
    .recommended-section .btn {
        flex: 1 1 auto;
        min-width: 100px;
    }
}

/* Kiểu accordion */
.accordion-button:not(.collapsed) {
    background: var(--tblr-primary-lt);
    color: var(--tblr-primary);
}

/* Hiệu ứng làm mờ thông tin nhạy cảm */
.spoiler {
    filter: blur(5px);
    transition: filter 0.3s;
}

.spoiler:hover {
    filter: none;
}
</style>

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Trung tâm người dùng</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem thông tin tài khoản và thông báo mới nhất tại đây</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="row row-cards">
                        {foreach $info_cards as $card}
                        <div class="{if isset($card.cta) && $card.cta}col-12 col-lg-3{else}col-sm-6 col-lg-3{/if}">
                            <div class="card gopass-stat-card{if isset($card.cta) && $card.cta} gopass-stat-card--cta{/if}">
                                <div class="card-body">
                                    {if isset($card.cta) && $card.cta}
                                    <div class="gopass-stat-cta">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <div class="gopass-stat-icon {$card.gradient}">
                                                <i class="ti {$card.icon}"></i>
                                            </div>
                                            <div class="flex-fill min-w-0">
                                                <div class="gopass-stat-label mb-0">{$card.title}</div>
                                                <div class="gopass-stat-hint">{$card.value}</div>
                                            </div>
                                        </div>
                                        <a href="{$card.action_url}" class="btn btn-primary w-100 gopass-stat-cta-btn">
                                            <i class="ti ti-shopping-cart"></i>
                                            {$card.cta_label|default:'Mua hàng'}
                                        </a>
                                    </div>
                                    {else}
                                    <div class="d-flex align-items-center gap-2 gap-sm-3">
                                        <div class="gopass-stat-icon {$card.gradient}">
                                            <i class="ti {$card.icon}"></i>
                                        </div>
                                        <div class="flex-fill min-w-0">
                                            <div class="gopass-stat-label">{$card.title}</div>
                                            <div class="gopass-stat-value">{$card.value}</div>
                                        </div>
                                        {if isset($card.buy_new) && $card.buy_new}
                                        <a href="{$card.action_url}" class="btn btn-primary btn-sm gopass-stat-buy-new">
                                            <i class="ti ti-shopping-cart"></i>
                                            <span>{$card.buy_new_label|default:'Mua gói mới'}</span>
                                        </a>
                                        {elseif isset($card.action_url)}
                                        <a href="{$card.action_url}" class="btn btn-primary btn-icon btn-sm">
                                            <i class="ti ti-arrow-right"></i>
                                        </a>
                                        {/if}
                                    </div>
                                    {/if}
                                </div>
                            </div>
                        </div>
                        {/foreach}
                    </div>
                </div>
                
                <div class="col-lg-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Cấu hình nhanh</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h4 class="mb-3">
                                    <i class="ti ti-link"></i> Địa chỉ đăng ký node dành riêng cho bạn
                                </h4>
                                <div class="input-group mb-2">
                                    <input type="text" class="form-control" value="{$UniversalSub}" readonly id="universal-sub-link">
                                    <button class="btn btn-primary copy" data-clipboard-text="{$UniversalSub}">
                                        <i class="ti ti-copy"></i> Sao chép
                                    </button>
                                </div>
                                <p class="text-muted mb-0">
                                    <small>Địa chỉ đăng ký này dùng cho mọi ứng dụng khách, vui lòng bảo mật</small>
                                </p>
                            </div>

                            <div class="recommended-section p-3 bg-primary-lt rounded mb-3">
                                <h4 class="mb-3">
                                    <i class="ti ti-rocket"></i> 
                                    Ứng dụng khách <span id="detected-os" class="text-primary">Windows</span> được đề xuất cho bạn
                                </h4>
                                <div class="row g-3" id="recommended-clients">
                                </div>
                            </div>

                            <div class="text-center">
                                <button class="btn btn-ghost-primary" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#all-platforms" aria-expanded="false">
                                    <i class="ti ti-package"></i> 
                                    Xem ứng dụng khách cho nền tảng khác
                                    <i class="ti ti-chevron-down ms-1"></i>
                                </button>
                            </div>
                            
                            <div class="collapse mt-3" id="all-platforms">
                                <div class="accordion" id="platform-accordion">
                                </div>
                                
                                <div class="mt-3 p-3 bg-secondary-lt rounded">
                                    <h5 class="mb-2">Định dạng đăng ký nâng cao</h5>
                                    <div class="small text-muted mb-2">Nếu bạn cần liên kết đăng ký theo định dạng cụ thể:</div>
                                    <div class="btn-group btn-group-sm flex-wrap">
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/json">
                                            Định dạng JSON
                                        </button>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/v2rayjson">
                                            V2Ray JSON
                                        </button>
                                        {if $public_setting['enable_ss_sub']}
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/sip008">
                                            SIP008
                                        </button>
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/ss">
                                            Shadowsocks
                                        </button>
                                        {/if}
                                        {if $public_setting['enable_v2_sub']}
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/v2ray">
                                            V2Ray
                                        </button>
                                        {/if}
                                        {if $public_setting['enable_trojan_sub']}
                                        <button class="btn btn-outline-secondary copy" data-clipboard-text="{$UniversalSub}/trojan">
                                            Trojan
                                        </button>
                                        {/if}
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <button class="btn btn-ghost-secondary w-100" type="button" data-bs-toggle="collapse" 
                                            data-bs-target="#connection-info" aria-expanded="false">
                                        <i class="ti ti-info-circle"></i> 
                                        Xem thông tin kết nối
                                        <i class="ti ti-chevron-down ms-1"></i>
                                    </button>
                                    <div class="collapse mt-2" id="connection-info">
                                        <div class="p-3 bg-light rounded">
                                            <div class="table-responsive">
                                                <table class="table table-sm mb-0">
                                                    <tbody>
                                                    <tr>
                                                        <td class="text-muted" style="width: 100px;">Cổng</td>
                                                        <td><code>{$user->port}</code></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Mật khẩu kết nối</td>
                                                        <td><code class="spoiler">{$user->passwd}</code></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">UUID</td>
                                                        <td><code class="spoiler" style="font-size: 0.8em;">{$user->uuid}</code></td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted">Phương thức mã hóa</td>
                                                        <td><code>{$user->method}</code></td>
                                                    </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-sm-12">
                    <div class="vstack gap-3">
                        <div class="card gopass-traffic-card">
                            <div class="card-body">
                                <div class="gopass-traffic-head">
                                    <div>
                                        <h3 class="card-title mb-1">Sử dụng lưu lượng</h3>
                                        <div class="gopass-traffic-sub">
                                            {if $user->transfer_enable > 0}
                                            Tổng gói: <strong>{$user->enableTraffic()}</strong>
                                            {else}
                                            Chưa có gói lưu lượng
                                            {/if}
                                        </div>
                                    </div>
                                    {if $user->transfer_enable > 0}
                                    <div class="gopass-traffic-remain-badge">
                                        {$user->unusedTrafficPercent()|string_format:"%.0f"}% còn lại
                                    </div>
                                    {else}
                                    <div class="gopass-traffic-remain-badge gopass-traffic-remain-badge--empty">
                                        Chưa kích hoạt
                                    </div>
                                    {/if}
                                </div>

                                <div class="gopass-traffic-bar progress progress-separated" role="progressbar"
                                     aria-label="Tiến độ lưu lượng"
                                     aria-valuenow="{$user->unusedTrafficPercent()|string_format:'%.0f'}"
                                     aria-valuemin="0" aria-valuemax="100">
                                    {assign var=last_pct value=$user->lastUsedTrafficPercent()}
                                    {assign var=today_pct value=$user->todayUsedTrafficPercent()}
                                    {if $last_pct > 0}
                                    <div class="progress-bar gopass-traffic-bar-used"
                                         style="width: {$last_pct}%"></div>
                                    {/if}
                                    {if $today_pct > 0}
                                    <div class="progress-bar gopass-traffic-bar-today"
                                         style="width: {$today_pct}%"></div>
                                    {/if}
                                </div>

                                <div class="gopass-traffic-stats">
                                    <div class="gopass-traffic-stat gopass-traffic-stat--used">
                                        <div class="gopass-traffic-stat-label">
                                            <span class="gopass-traffic-dot"></span>
                                            Đã dùng
                                        </div>
                                        <div class="gopass-traffic-stat-value">{$user->lastUsedTraffic()}</div>
                                    </div>
                                    <div class="gopass-traffic-stat gopass-traffic-stat--today">
                                        <div class="gopass-traffic-stat-label">
                                            <span class="gopass-traffic-dot"></span>
                                            Hôm nay
                                        </div>
                                        <div class="gopass-traffic-stat-value">{$user->todayUsedTraffic()}</div>
                                    </div>
                                    <div class="gopass-traffic-stat gopass-traffic-stat--left">
                                        <div class="gopass-traffic-stat-label">
                                            <span class="gopass-traffic-dot"></span>
                                            Còn lại
                                        </div>
                                        <div class="gopass-traffic-stat-value">{$user->unusedTraffic()}</div>
                                    </div>
                                </div>

                                {if $user->class === 0}
                                <a href="/user/product" class="btn btn-primary w-100 gopass-traffic-cta">
                                    <i class="ti ti-shopping-cart"></i>
                                    Đến cửa hàng mua gói dịch vụ
                                </a>
                                {else}
                                <div class="gopass-traffic-expire">
                                    <i class="ti ti-calendar-event"></i>
                                    <span>
                                        Gói <strong>LV. {$user->class}</strong> hết hạn sau
                                        <strong>{$class_expire_days} ngày</strong>
                                        <span class="gopass-traffic-expire-date">({$user->class_expire})</span>
                                    </span>
                                </div>
                                {/if}
                            </div>
                        </div>
                        {if $public_setting['traffic_log']}
                        <div class="card mb-0">
                            <div class="card-body">
                                <h3 class="card-title">Lưu lượng theo giờ</h3>
                                <div id="traffic-log"></div>
                            </div>
                        </div>
                        {/if}
                    </div>
                </div>
                {if $public_setting['enable_checkin']}
                <div class="col-lg-6 col-sm-12">
                    <div class="card">
                        <div class="card-stamp">
                            <div class="card-stamp-icon bg-green">
                                <i class="ti ti-check"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">Điểm danh hàng ngày</h3>
                            <p>
                                Điểm danh để nhận lưu lượng trong khoảng
                                {if $public_setting['checkin_min'] !== $public_setting['checkin_max']}
                                &nbsp;
                                <code>{$public_setting['checkin_min']} MB</code>
                                đến
                                <code>{$public_setting['checkin_max']} MB</code>
                                {else}
                                <code>{$public_setting['checkin_min']} MB</code>
                                {/if}
                            </p>
                            <p>
                                Lần điểm danh gần nhất: <code id="last-checkin-time">{$user->lastCheckInTime()}</code>
                            </p>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex">
                                {if !$user->isAbleToCheckin()}
                                <button id="check-in" class="btn btn-primary ms-auto" disabled>Đã điểm danh</button>
                                {else}
                                {if $public_setting['enable_checkin_captcha']}
                                {include file='captcha/div.tpl'}
                                {/if}
                                <button id="check-in" class="btn btn-primary ms-auto"
                                    hx-post="/user/checkin" hx-swap="none" hx-vals='js:{
                                    {if $public_setting['enable_checkin_captcha']}
                                    {include file='captcha/ajax.tpl'}
                                    {/if}
                                    }'>
                                    Điểm danh
                                </button>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                {/if}
                <div class="col-lg-6 col-sm-12">
                    <div class="card">
                        <div class="ribbon ribbon-top bg-yellow">
                            <i class="ti ti-bell-ringing icon"></i>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">
                                Thông báo ghim
                                {if $ann !== null}
                                <span class="card-subtitle">{$ann->date}</span>
                                {/if}
                            </h3>
                            <p class="text-secondary">
                                {if $ann !== null}
                                {$ann->content}
                                {else}
                                Chưa có thông báo
                                {/if}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {if $public_setting['enable_checkin_captcha'] && $user->isAbleToCheckin()}
        {include file='captcha/js.tpl'}
    {/if}

    {if $public_setting['traffic_log']}
    <script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/libs/apexcharts/dist/apexcharts.min.js"></script>
    <script>
        function getTrafficChartConfig(trafficData) {
            return {
                chart: {
                    type: "line",
                    fontFamily: "inherit",
                    height: '100%',
                    parentHeightOffset: 0,
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: false
                    }
                },
                stroke: {
                    curve: "smooth"
                },
                fill: {
                    opacity: 1
                },
                series: [
                    {
                        name: "Lưu lượng sử dụng (MB)",
                        data: trafficData
                    }
                ],
                tooltip: {
                    theme: "dark"
                },
                grid: {
                    padding: {
                        top: -20,
                        right: 0,
                        left: 0,
                        bottom: 0
                    },
                    strokeDashArray: 4
                },
                xaxis: {
                    title: {
                        text: "Giờ"
                    },
                    labels: {
                        padding: 0
                    },
                    tooltip: {
                        enabled: false
                    },
                    axisBorder: {
                        show: false
                    },
                    categories: [
                        "00", "01", "02", "03", "04", "05", "06", "07", "08", "09", "10", "11",
                        "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "23"
                    ]
                },
                yaxis: {
                    title: {
                        text: "Lưu lượng sử dụng (MB)",
                        rotate: -90
                    },
                    labels: {
                        padding: 14
                    }
                },
                colors: ["#FF4500"],
                legend: {
                    show: false
                }
            };
        }
        
        function initTrafficChart() {
            const chartElement = document.getElementById('traffic-log');
            if (!chartElement || !window.ApexCharts) return;
            
            try {
                const chart = new ApexCharts(chartElement, getTrafficChartConfig({$traffic_logs}));
                chart.render();
            } catch (error) {
                console.error('Khởi tạo biểu đồ lưu lượng thất bại:', error);
            }
        }
        
        document.addEventListener("DOMContentLoaded", function () {
            initTrafficChart();
        });
    </script>
    {/if}

    <script>
    window.APP_CONFIG = {
        enableR2Download: {if $config['enable_r2_client_download']}true{else}false{/if},
        universalSubUrl: "{$UniversalSub}",
        appName: "{$config['appName']}",
        enableSsSub: {if $public_setting['enable_ss_sub']}true{else}false{/if},
        enableV2Sub: {if $public_setting['enable_v2_sub']}true{else}false{/if},
        enableTrojanSub: {if $public_setting['enable_trojan_sub']}true{else}false{/if}
    };
    
    const platformIcons = {$platformIcons};

    const clientRecommendations = {$clientData};
    
    {literal}
    function detectOS() {
        const userAgent = navigator.userAgent;
        if (userAgent.indexOf("Win") !== -1) return "Windows";
        if (userAgent.indexOf("Mac") !== -1) return "macOS";
        if (userAgent.indexOf("Android") !== -1) return "Android";
        if (userAgent.match(/iPhone|iPad|iPod/i)) return "iOS";
        if (userAgent.indexOf("Linux") !== -1) return "Linux";
        return "Windows"; // default
    }
    

    const CONFIG = {
        ANIMATION_DURATION: 350,        // Thời gian hiệu ứng (ms)
        FEEDBACK_TIMEOUT: 2000,         // Thời gian hiển thị phản hồi (ms)
        CLIPBOARD_SUCCESS_TEXT: 'Đã sao chép',
        CLIPBOARD_ERROR_TEXT: 'Sao chép thất bại, vui lòng chọn và sao chép thủ công',
        CLASSES: {
            BTN_GROUP_MOBILE: 'btn-group-vertical',
            BTN_GROUP_DESKTOP: 'btn-group btn-group-sm', 
            MOBILE_ONLY: 'd-md-none w-100',
            DESKTOP_ONLY: 'd-none d-md-flex',
            MOBILE_SM: 'd-sm-none w-100',
            DESKTOP_SM: 'd-none d-sm-flex'
        },
        BUTTONS: {
            download: { icon: 'ti-download', text: 'Tải xuống', class: 'btn-primary' },
            downloadAppStore: { icon: 'ti-brand-appstore', text: 'App Store', class: 'btn-primary' },
            copy: { icon: 'ti-copy', text: 'Sao chép đăng ký', class: 'btn-info copy' },
            import: { icon: 'ti-external-link', text: 'Mở trong app', class: 'btn-success' },
            importRecommended: { icon: 'ti-external-link', text: 'Mở trong app', class: 'btn-success' }
        }
    };

    function safeInit(fn, name) {
        try {
            fn();
        } catch (error) {
            console.error(`${name} khởi tạo thất bại:`, error);
        }
    }
    
    function createElement(tag, className, content) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (content) element.textContent = content;
        return element;
    }
    
    function createIcon(iconClass) {
        const icon = createElement('i', 'ti ' + iconClass);
        return icon;
    }
    
    function createButton(type, options = {}) {
        const { client, url, isMobile, isRecommended } = options;
        const btnConfig = CONFIG.BUTTONS[type];
        
        let config = { ...btnConfig };
        if (type === 'download' && client?.isAppStore) {
            config = CONFIG.BUTTONS.downloadAppStore;
        } else if (type === 'import' && isRecommended) {
            config = CONFIG.BUTTONS.importRecommended;
        }
        
        const btn = createElement(type === 'copy' ? 'button' : 'a', 'btn ' + config.class);
        
        if (type === 'copy') {
            btn.setAttribute('data-clipboard-text', url);
        } else {
            btn.href = url;
            if (type === 'download' && client?.isAppStore) {
                btn.target = '_blank';
            }
        }
        
        btn.appendChild(createIcon(config.icon));
        btn.appendChild(document.createTextNode(' ' + config.text));
        
        return btn;
    }
    
    function createResponsiveButtonGroups(client, urls, isRecommended = false) {
        const { downloadUrl, subUrl, importUrl } = urls;
        const buttons = [];
        
        const buttonConfigs = [
            { type: 'download', url: downloadUrl, needsClient: true },
            { type: 'copy', url: subUrl },
            { type: 'import', url: importUrl }
        ];
        
        const variants = [
            { 
                isMobile: true, 
                classes: isRecommended ? 
                    `${CONFIG.CLASSES.BTN_GROUP_MOBILE} ${CONFIG.CLASSES.MOBILE_ONLY}` :
                    `${CONFIG.CLASSES.BTN_GROUP_MOBILE} ${CONFIG.CLASSES.MOBILE_SM}`
            },
            { 
                isMobile: false, 
                classes: isRecommended ?
                    `${CONFIG.CLASSES.BTN_GROUP_DESKTOP.replace('btn-group-sm', '')} ${CONFIG.CLASSES.DESKTOP_ONLY}` :
                    `${CONFIG.CLASSES.BTN_GROUP_DESKTOP} ${CONFIG.CLASSES.DESKTOP_SM}`
            }
        ];
        
        variants.forEach(variant => {
            const group = createElement('div', variant.classes);
            
            buttonConfigs.forEach(btnConfig => {
                const options = {
                    client: btnConfig.needsClient ? client : null,
                    url: btnConfig.url,
                    isMobile: variant.isMobile,
                    isRecommended
                };
                group.appendChild(createButton(btnConfig.type, options));
            });
            
            buttons.push(group);
        });
        
        return buttons;
    }
    
    function createClientCardContent(client) {
        const content = createElement('div');
        
        const title = createElement('h4', 'mb-1', client.name);
        const desc = createElement('p', 'text-secondary mb-0', client.description);
        
        content.appendChild(title);
        content.appendChild(desc);
        
        return content;
    }
    
    function generateClientHtml(client, isRecommended) {
        const config = window.APP_CONFIG;
        
        let downloadUrl = client.downloadUrl;
        if (!client.isAppStore && downloadUrl.includes('/clients/')) {
            downloadUrl = config.enableR2Download ? '/user' + downloadUrl : downloadUrl;
        }
        
        const subUrl = config.universalSubUrl + '/' + client.format;
        const importUrl = client.importUrl;
        
        const container = createElement('div', 'col-12');
        
        if (isRecommended) {
            const card = createElement('div', 'card');
            const cardBody = createElement('div', 'card-body');
            const flexContainer = createElement('div', 'd-flex flex-column flex-md-row align-items-center justify-content-between gap-3');
            
            const contentDiv = createClientCardContent(client);
            
            const buttonsContainer = createElement('div');
            const urls = { downloadUrl, subUrl, importUrl };
            const buttonGroups = createResponsiveButtonGroups(client, urls, true);
            buttonGroups.forEach(group => buttonsContainer.appendChild(group));
            
            flexContainer.appendChild(contentDiv);
            flexContainer.appendChild(buttonsContainer);
            cardBody.appendChild(flexContainer);
            card.appendChild(cardBody);
            container.appendChild(card);
        } else {
            const item = createElement('div', 'client-item d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between p-3 border rounded gap-2');
            
            const contentDiv = createElement('div', 'flex-fill');
            const title = createElement('h5', 'mb-0', client.name);
            const desc = createElement('small', 'text-muted', client.description);
            contentDiv.appendChild(title);
            contentDiv.appendChild(desc);
            
            const urls = { downloadUrl, subUrl, importUrl };
            const buttonGroups = createResponsiveButtonGroups(client, urls, false);
            
            item.appendChild(contentDiv);
            buttonGroups.forEach(group => item.appendChild(group));
            
            container.appendChild(item);
        }
        
        return container.outerHTML;
    }
    
    function initClientSelector() {
        const os = detectOS();
        document.getElementById('detected-os').textContent = os;

        const recommendations = clientRecommendations[os] || clientRecommendations["Windows"];
        const recommendedContainer = document.getElementById('recommended-clients');
        
        if (recommendedContainer) {
            recommendations.forEach(function(client) {
                const clientHtml = generateClientHtml(client, true);
            recommendedContainer.insertAdjacentHTML('beforeend', clientHtml);
            });
        }
        
        const accordionContainer = document.getElementById('platform-accordion');
        
        if (accordionContainer) {
            Object.keys(clientRecommendations).forEach(function(platform) {
                const clients = clientRecommendations[platform];
                const platformId = 'platform-' + platform.toLowerCase();
                const icon = platformIcons[platform] || CONFIG.BUTTONS.download.icon.replace('ti-', 'ti-device-');
                
                const accordionHtml = `
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#${platformId}">
                                <i class="ti ${icon} me-2"></i> ${platform}
                            </button>
                        </h2>
                        <div id="${platformId}" class="accordion-collapse collapse" 
                             data-bs-parent="#platform-accordion">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    ${clients.map(client => generateClientHtml(client, false)).join('')}
                                </div>
                            </div>
                        </div>
                    </div>`;
                    
                accordionContainer.insertAdjacentHTML('beforeend', accordionHtml.trim());
            });
        }
    }
    
    function initClipboard() {
        if (typeof ClipboardJS === 'undefined') {
            console.warn('ClipboardJS chưa được tải');
            return;
        }
        
        const clipboard = new ClipboardJS('.copy');
        
        clipboard.on('success', function(e) {
            e.clearSelection();
            const originalText = e.trigger.innerHTML;
            const checkIcon = createIcon('ti-check');
            e.trigger.innerHTML = '';
            e.trigger.appendChild(checkIcon);
            e.trigger.appendChild(document.createTextNode(' ' + CONFIG.CLIPBOARD_SUCCESS_TEXT));
            setTimeout(function() {
                e.trigger.innerHTML = originalText;
            }, CONFIG.FEEDBACK_TIMEOUT);
        });
        
        clipboard.on('error', function(e) {
            console.error('Sao chép thất bại:', e.action);
            alert(CONFIG.CLIPBOARD_ERROR_TEXT);
        });
    }
    
    function initCollapseAnimations() {
        const allPlatforms = document.getElementById('all-platforms');
        const recommendedSection = document.querySelector('.recommended-section');
        
        if (!allPlatforms || !recommendedSection) return;
        
        recommendedSection.classList.add('collapsible-section');
        
        allPlatforms.addEventListener('show.bs.collapse', function (e) {
            if (e.target !== allPlatforms) return;
            recommendedSection.classList.add('collapsing');
        });
        
        allPlatforms.addEventListener('hide.bs.collapse', function (e) {
            if (e.target !== allPlatforms) return;
            recommendedSection.classList.remove('collapsing');
            setTimeout(function() {
                recommendedSection.classList.add('expanded');
            }, CONFIG.ANIMATION_DURATION);
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        safeInit(initClientSelector, 'Bộ chọn ứng dụng khách');
        safeInit(initClipboard, 'Chức năng clipboard');
        safeInit(initCollapseAnimations, 'Hiệu ứng thu gọn');
    });
    {/literal}
    </script>

    {include file='user/footer.tpl'}
