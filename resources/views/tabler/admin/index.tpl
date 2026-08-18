{include file='admin/header.tpl'}

{assign var=never_checkin value=$total_user-$checkin_user}
{assign var=past_checkin value=$checkin_user-$today_checkin_user}
{assign var=offline_node value=$total_node-$alive_node}
{if $never_checkin < 0}{assign var=never_checkin value=0}{/if}
{if $past_checkin < 0}{assign var=past_checkin value=0}{/if}
{if $offline_node < 0}{assign var=offline_node value=0}{/if}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Tổng quan hệ thống</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Tổng quan trạng thái vận hành</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="row row-cards">
                        <div class="col-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-info text-white avatar">
                                                <i class="ti ti-calendar-event icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {$today_income|format_vnd:0} VNĐ
                                            </div>
                                            <div class="text-secondary">
                                                Doanh thu hôm nay
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-blue text-white avatar">
                                                <i class="ti ti-calendar-minus icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {$yesterday_income|format_vnd:0} VNĐ
                                            </div>
                                            <div class="text-secondary">
                                                Doanh thu hôm qua
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-warning text-white avatar">
                                                <i class="ti ti-calendar-stats icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {$this_month_income|format_vnd:0} VNĐ
                                            </div>
                                            <div class="text-secondary">
                                                Doanh thu tháng này
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-lg-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-danger text-white avatar">
                                                <i class="ti ti-calendar-plus icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                {$total_income|format_vnd:0} VNĐ
                                            </div>
                                            <div class="text-secondary">
                                                Doanh thu tích lũy
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Điểm danh · {$total_user} người dùng</h3>
                        </div>
                        <div class="card-body">
                            <div id="check-in"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Máy chủ · {$total_node} node</h3>
                        </div>
                        <div class="card-body">
                            <div id="node-online"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Trạng thái tài khoản</h3>
                        </div>
                        <div class="card-body">
                            <div id="user-inactive"></div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Lưu lượng sử dụng</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-2 text-secondary small">
                                Còn lại: <strong>{$unused_traffic}</strong>
                                · Tổng đã dùng hôm nay: <strong>{$today_traffic}</strong>
                                · Trước đó: <strong>{$last_traffic}</strong>
                            </div>
                            <div id="traffic-usage"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const donutBase = {
                chart: {
                    type: "donut",
                    fontFamily: 'inherit',
                    height: 300,
                    animations: { enabled: true },
                },
                fill: { opacity: 1 },
                stroke: { width: 0 },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return Math.round(val) + "%";
                    },
                },
                legend: {
                    show: true,
                    position: 'bottom',
                    offsetY: 8,
                    markers: { width: 10, height: 10, radius: 100 },
                    itemMargin: { horizontal: 8, vertical: 8 },
                },
                tooltip: {
                    fillSeriesColor: false,
                    y: {
                        formatter: function (val) {
                            return val;
                        },
                    },
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Tổng',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    },
                                },
                            },
                        },
                    },
                },
            };

            window.ApexCharts && (new ApexCharts(document.getElementById('check-in'), {
                ...donutBase,
                series: [{$never_checkin}, {$past_checkin}, {$today_checkin_user}],
                labels: [
                    "Chưa điểm danh ({$never_checkin})",
                    "Đã từng điểm danh ({$past_checkin})",
                    "Điểm danh hôm nay ({$today_checkin_user})"
                ],
                colors: ["#94a3b8", "#3b82f6", "#16a34a"],
            })).render();

            window.ApexCharts && (new ApexCharts(document.getElementById('node-online'), {
                ...donutBase,
                series: [{$alive_node}, {$offline_node}],
                labels: [
                    "Trực tuyến ({$alive_node})",
                    "Ngoại tuyến ({$offline_node})"
                ],
                colors: ["#16a34a", "#ef4444"],
            })).render();

            window.ApexCharts && (new ApexCharts(document.getElementById('user-inactive'), {
                ...donutBase,
                series: [{$active_user}, {$inactive_user}],
                labels: [
                    "Đang hoạt động ({$active_user})",
                    "Không hoạt động ({$inactive_user})"
                ],
                colors: ["#0ea5e9", "#f59e0b"],
            })).render();

            // Bar chart: unused TB dwarfs used GB on a donut — bars stay readable.
            window.ApexCharts && (new ApexCharts(document.getElementById('traffic-usage'), {
                chart: {
                    type: "bar",
                    fontFamily: 'inherit',
                    height: 280,
                    toolbar: { show: false },
                    animations: { enabled: true },
                },
                plotOptions: {
                    bar: {
                        horizontal: true,
                        borderRadius: 4,
                        barHeight: '55%',
                        distributed: true,
                    },
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (_val, opts) {
                        const labels = [
                            "{$today_traffic}",
                            "{$last_traffic}",
                            "{$unused_traffic}"
                        ];
                        return labels[opts.dataPointIndex] || '';
                    },
                    style: { fontSize: '12px' },
                },
                series: [{
                    name: 'Lưu lượng (GB)',
                    data: [
                        {$raw_today_traffic|default:0},
                        {$raw_last_traffic|default:0},
                        {$raw_unused_traffic|default:0}
                    ],
                }],
                xaxis: {
                    categories: ['Hôm nay', 'Trước đó', 'Còn lại'],
                    labels: {
                        formatter: function (val) {
                            const n = Number(val);
                            if (!isFinite(n)) return val;
                            if (n >= 1024) return (n / 1024).toFixed(1) + ' TB';
                            return n.toFixed(1) + ' GB';
                        },
                    },
                },
                yaxis: {
                    labels: { style: { fontSize: '12px' } },
                },
                colors: ["#16a34a", "#0ea5e9", "#94a3b8"],
                legend: { show: false },
                tooltip: {
                    y: {
                        formatter: function (val) {
                            const n = Number(val);
                            if (n >= 1024) return (n / 1024).toFixed(2) + ' TB';
                            return n.toFixed(2) + ' GB';
                        },
                    },
                },
                grid: { strokeDashArray: 3 },
            })).render();
        });
    </script>

    <script src="//{$config['jsdelivr_url']}/npm/@tabler/core@latest/dist/libs/apexcharts/dist/apexcharts.min.js"></script>

    {include file='admin/footer.tpl'}
