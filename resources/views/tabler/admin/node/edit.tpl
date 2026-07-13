{include file='admin/header.tpl'}

<script src="//{$config['jsdelivr_url']}/npm/jsoneditor@latest/dist/jsoneditor.min.js"></script>
<link href="//{$config['jsdelivr_url']}/npm/jsoneditor@latest/dist/jsoneditor.min.css" rel="stylesheet" type="text/css">

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Máy chủ #{$node->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chỉnh sửa thông tin máy chủ</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a id="save-node" href="#" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            Lưu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Thông tin cơ bản</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Tên</label>
                                <div class="col">
                                    <input id="name" type="text" class="form-control" value="{$node->name}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Địa chỉ kết nối</label>
                                <div class="col">
                                    <input id="server" type="text" class="form-control" value="{$node->server}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Địa chỉ IPv4</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$node->ipv4}" disabled>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Địa chỉ IPv6</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$node->ipv6}" disabled>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Hệ số lưu lượng</label>
                                <div class="col">
                                    <input id="traffic_rate" type="text" class="form-control"
                                           value="{$node->traffic_rate}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Loại kết nối</label>
                                <div class="col">
                                    <select id="sort" class="col form-select" value="{$node->sort}">
                                        <option value="14" {if $node->sort === 14}selected{/if}>Trojan</option>
                                        <option value="11" {if $node->sort === 11}selected{/if}>Vmess</option>
                                        <option value="2" {if $node->sort === 2}selected{/if}>TUIC</option>
                                        <option value="1" {if $node->sort === 1}selected{/if}>Shadowsocks2022</option>
                                        <option value="0" {if $node->sort === 0}selected{/if}>Shadowsocks</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Cấu hình tùy chỉnh</label>
                                <div id="custom_config"></div>
                                <label class="form-label col-form-label">
                                    Vui lòng tham khảo
                                    <a href="https://docs.sspanel.io/docs/configuration/nodes" target="_blank">
                                        tài liệu cấu hình tùy chỉnh máy chủ
                                    </a>
                                    để chỉnh sửa cấu hình tùy chỉnh
                                </label>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">Hiển thị máy chủ này</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="type" class="form-check-input" type="checkbox" {if $node->type}checked="" {/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="hr-text">
                                <span>Hệ số động</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">Bật hệ số lưu lượng động</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="is_dynamic_rate" class="form-check-input" type="checkbox" {if $node->is_dynamic_rate}checked="" {/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Cách tính hệ số lưu lượng động</label>
                                <div class="col">
                                    <select id="dynamic_rate_type" class="col form-select" value="{$node->dynamic_rate_type}">
                                        <option value="0" {if $node->dynamic_rate_type === 0}selected{/if}>Logistic</option>
                                        <option value="1" {if $node->dynamic_rate_type === 1}selected{/if}>Linear</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Hệ số tối đa</label>
                                <div class="col">
                                    <input id="max_rate" type="text" class="form-control" value="{$node->max_rate}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Thời gian hệ số tối đa (giờ)</label>
                                <div class="col">
                                    <input id="max_rate_time" type="text" class="form-control" value="{$node->max_rate_time}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Hệ số tối thiểu</label>
                                <div class="col">
                                    <input id="min_rate" type="text" class="form-control" value="{$node->min_rate}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Thời gian hệ số tối thiểu (giờ)</label>
                                <div class="col">
                                    <input id="min_rate_time" type="text" class="form-control" value="{$node->min_rate_time}">
                                </div>
                                <label class="form-label col-form-label">
                                    Thời gian hệ số tối đa phải lớn hơn thời gian hệ số tối thiểu, nếu không sẽ không có hiệu lực
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Thông tin khác</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Cấp</label>
                                <div class="col">
                                    <input id="node_class" type="text" class="form-control" value="{$node->node_class}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Nhóm</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control" value="{$node->node_group}">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>Cài đặt lưu lượng</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Lưu lượng đã dùng</label>
                                <div class="col">
                                    <input id="node_bandwidth" type="text" class="form-control"
                                           value="{$node->node_bandwidth}" disabled="">
                                </div>
                                <div class="col-auto">
                                    <button id="reset-bandwidth" class="btn btn-red">Đặt lại</button>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Lưu lượng khả dụng (GB)</label>
                                <div class="col">
                                    <input id="node_bandwidth_limit" type="text" class="form-control"
                                           value="{$node->node_bandwidth_limit}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Ngày đặt lại lưu lượng</label>
                                <div class="col">
                                    <input id="bandwidthlimit_resetday" type="text" class="form-control"
                                           value="{$node->bandwidthlimit_resetday}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Giới hạn tốc độ (Mbps)</label>
                                <div class="col">
                                    <input id="node_speedlimit" type="text" class="form-control"
                                           value="{$node->node_speedlimit}">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>Tùy chọn nâng cao</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Khóa giao tiếp máy chủ</label>
                                <input type="text" class="form-control" id="password" value="{$node->password}"
                                       disabled="">
                                <div class="row my-3">
                                    <div class="col">
                                        <button id="reset-password" class="btn btn-red">Đặt lại</button>
                                        <button id="copy-password" class="copy btn btn-primary"
                                                data-clipboard-text="{$node->password}">
                                            Sao chép
                                        </button>
                                    </div>
                                </div>
                                <label class="form-label col-form-label">
                                    Khóa giao tiếp dùng để xác thực NodeAPI, nhấn Đặt lại nếu cần thay đổi
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let clipboard = new ClipboardJS('.copy');
    clipboard.on('success', function (e) {
        $('#success-message').text('Đã sao chép vào bộ nhớ tạm');
        $('#success-dialog').modal('show');
    });

    const container = document.getElementById('custom_config');
    let options = {
        modes: ['code', 'tree'],
    };
    const editor = new JSONEditor(container, options);
    editor.set({$node->custom_config})

    $("#reset-bandwidth").click(function () {
        $.ajax({
            url: '/admin/node/{$node->id}/reset_bandwidth',
            type: 'POST',
            dataType: "json",
            success: function (data) {
                if (data.ret === 1) {
                    $('#success-message').text(data.msg);
                    $('#success-dialog').modal('show');
                } else {
                    $('#fail-message').text(data.msg);
                    $('#fail-dialog').modal('show');
                }
            }
        })
    });

    $("#reset-password").click(function () {
        $.ajax({
            url: '/admin/node/{$node->id}/reset_password',
            type: 'POST',
            dataType: "json",
            success: function (data) {
                if (data.ret === 1) {
                    $('#success-message').text(data.msg);
                    $('#success-dialog').modal('show');
                } else {
                    $('#fail-message').text(data.msg);
                    $('#fail-dialog').modal('show');
                }
            }
        })
    });

    $("#save-node").click(function () {
        $.ajax({
            url: '/admin/node/{$node->id}',
            type: 'PUT',
            dataType: "json",
            data: {
                {foreach $update_field as $key}
                {$key}: $('#{$key}').val(),
                {/foreach}
                type: $("#type").is(":checked"),
                is_dynamic_rate: $("#is_dynamic_rate").is(":checked"),
                custom_config: JSON.stringify(editor.get()),
            },
            success: function (data) {
                if (data.ret === 1) {
                    $('#success-message').text(data.msg);
                    $('#success-dialog').modal('show');
                    window.setTimeout("location.href=top.document.referrer", {$config['jump_delay']});
                } else {
                    $('#fail-message').text(data.msg);
                    $('#fail-dialog').modal('show');
                }
            }
        })
    });
</script>

{include file='admin/footer.tpl'}
