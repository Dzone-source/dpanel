{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Cài đặt khác</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Cấu hình các tùy chọn khác của trang web</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a id="save-setting" href="#" class="btn btn-primary">
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
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs">
                                <li class="nav-item">
                                    <a href="#display" class="nav-link active" data-bs-toggle="tab">Hiển thị tính năng</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#log" class="nav-link" data-bs-toggle="tab">Nhật ký người dùng</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#checkin" class="nav-link" data-bs-toggle="tab">Điểm danh</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="display">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Hiển thị nhật ký kiểm tra người dùng</label>
                                            <div class="col">
                                                <select id="display_detect_log" class="col form-select"
                                                        value="{$settings['display_detect_log']}">
                                                    <option value="0"
                                                            {if ! $settings['display_detect_log']}selected{/if}>Tắt
                                                    </option>
                                                    <option value="1" {if $settings['display_detect_log']}selected{/if}>
                                                        Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Hiển thị tài liệu</label>
                                            <div class="col">
                                                <select id="display_docs" class="col form-select"
                                                        value="{$settings['display_docs']}">
                                                    <option value="0" {if ! $settings['display_docs']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['display_docs']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Tài liệu chỉ hiển thị cho người dùng trả phí</label>
                                            <div class="col">
                                                <select id="display_docs_only_for_paid_user" class="col form-select"
                                                        value="{$settings['display_docs_only_for_paid_user']}">
                                                    <option value="0"
                                                            {if ! $settings['display_docs_only_for_paid_user']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['display_docs_only_for_paid_user']}selected{/if}>
                                                        Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="log">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Bật nhật ký lưu lượng theo giờ</label>
                                            <div class="col">
                                                <select id="traffic_log" class="col form-select"
                                                        value="{$settings['traffic_log']}">
                                                    <option value="0" {if ! $settings['traffic_log']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['traffic_log']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Số ngày lưu nhật ký lưu lượng</label>
                                            <div class="col">
                                                <input id="traffic_log_retention_days" type="text" class="form-control"
                                                       value="{$settings['traffic_log_retention_days']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Bật nhật ký đăng ký</label>
                                            <div class="col">
                                                <select id="subscribe_log" class="col form-select"
                                                        value="{$settings['subscribe_log']}">
                                                    <option value="0" {if ! $settings['subscribe_log']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['subscribe_log']}selected{/if}>
                                                        Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Số ngày lưu nhật ký đăng ký</label>
                                            <div class="col">
                                                <input id="subscribe_log_retention_days" type="text"
                                                       class="form-control"
                                                       value="{$settings['subscribe_log_retention_days']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Thông báo đăng ký từ IP mới</label>
                                            <div class="col">
                                                <select id="notify_new_subscribe" class="col form-select"
                                                        value="{$settings['notify_new_subscribe']}">
                                                    <option value="0"
                                                            {if ! $settings['notify_new_subscribe']}selected{/if}>Tắt
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['notify_new_subscribe']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Bật nhật ký đăng nhập</label>
                                            <div class="col">
                                                <select id="login_log" class="col form-select"
                                                        value="{$settings['login_log']}">
                                                    <option value="0" {if ! $settings['login_log']}selected{/if}>Tắt
                                                    </option>
                                                    <option value="1" {if $settings['login_log']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Thông báo đăng nhập từ IP mới</label>
                                            <div class="col">
                                                <select id="notify_new_login" class="col form-select"
                                                        value="{$settings['notify_new_login']}">
                                                    <option value="0" {if ! $settings['notify_new_login']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['notify_new_login']}selected{/if}>
                                                        Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="checkin">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Bật điểm danh</label>
                                            <div class="col">
                                                <select id="enable_checkin" class="col form-select"
                                                        value="{$settings['enable_checkin']}">
                                                    <option value="0" {if ! $settings['enable_checkin']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['enable_checkin']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Lưu lượng tối thiểu khi điểm danh (MB)</label>
                                            <div class="col">
                                                <input id="checkin_min" type="text" class="form-control"
                                                       value="{$settings['checkin_min']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Lưu lượng tối đa khi điểm danh (MB)</label>
                                            <div class="col">
                                                <input id="checkin_max" type="text"
                                                       class="form-control"
                                                       value="{$settings['checkin_max']}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            $("#save-setting").click(function () {
                $.ajax({
                    url: '/admin/setting/feature',
                    type: 'POST',
                    dataType: "json",
                    data: {
                        {foreach $update_field as $key}
                        {$key}: $('#{$key}').val(),
                        {/foreach}
                    },
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
        </script>

        {include file='admin/footer.tpl'}
