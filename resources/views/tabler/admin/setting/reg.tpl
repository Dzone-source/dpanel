{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Cài đặt đăng ký</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Quản lý cài đặt đăng ký của trang web</span>
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
                                    <a href="#reg" class="nav-link active" data-bs-toggle="tab">Cài đặt đăng ký</a>
                                </li>
                                <li class="nav-item">
                                    <a href="#default_value" class="nav-link" data-bs-toggle="tab">Giá trị mặc định</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="reg">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Chế độ đăng ký</label>
                                            <div class="col">
                                                <select id="reg_mode" class="col form-select"
                                                        value="{$settings['reg_mode']}">
                                                    <option value="close"
                                                            {if $settings['reg_mode'] === 'close'}selected{/if}>Tắt đăng ký
                                                    </option>
                                                    <option value="open"
                                                            {if $settings['reg_mode'] === 'open'}selected{/if}>Đăng ký công khai
                                                    </option>
                                                    <option value="invite"
                                                            {if $settings['reg_mode'] === 'invite'}selected{/if}>
                                                        Chỉ đăng ký bằng lời mời
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Xác minh email</label>
                                            <div class="col">
                                                <select id="reg_email_verify" class="col form-select"
                                                        value="{$settings['reg_email_verify']}">
                                                    <option value="0" {if ! $settings['reg_email_verify']}selected{/if}>
                                                        Tắt
                                                    </option>
                                                    <option value="1" {if $settings['reg_email_verify']}selected{/if}>
                                                        Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Nhận email báo cáo sử dụng hàng ngày mặc định</label>
                                            <div class="col">
                                                <select id="reg_daily_report" class="col form-select"
                                                        value="{$settings['reg_daily_report']}">
                                                    <option value="0"
                                                            {if ! $settings['reg_daily_report']}selected{/if}>Tắt
                                                    </option>
                                                    <option value="1"
                                                            {if $settings['reg_daily_report']}selected{/if}>Bật
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane" id="default_value">
                                    <div class="card-body">
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Nhóm được gán ngẫu nhiên khi đăng ký, phân tách nhiều nhóm bằng dấu phẩy</label>
                                            <div class="col">
                                                <input id="random_group" type="text" class="form-control"
                                                       value="{$settings['random_group']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Giá trị tối thiểu pool cổng người dùng, đặt 0
                                                thì người dùng sẽ không được gán cổng</label>
                                            <div class="col">
                                                <input id="min_port" type="text" class="form-control"
                                                       value="{$settings['min_port']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Giá trị tối đa pool cổng người dùng, đặt 0
                                                thì người dùng sẽ không được gán cổng</label>
                                            <div class="col">
                                                <input id="max_port" type="text" class="form-control"
                                                       value="{$settings['max_port']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Lưu lượng tặng khi đăng ký (GB)</label>
                                            <div class="col">
                                                <input id="reg_traffic" type="text" class="form-control"
                                                       value="{$settings['reg_traffic']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Ngày reset lưu lượng người dùng miễn phí, đặt 0
                                                thì không reset</label>
                                            <div class="col">
                                                <input id="free_user_reset_day" type="text" class="form-control"
                                                       value="{$settings['free_user_reset_day']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Lưu lượng miễn phí cần reset, đặt 0
                                                thì không reset</label>
                                            <div class="col">
                                                <input id="free_user_reset_bandwidth" type="text" class="form-control"
                                                       value="{$settings['free_user_reset_bandwidth']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Cấp độ khi đăng ký</label>
                                            <div class="col">
                                                <input id="reg_class" type="text" class="form-control"
                                                       value="{$settings['reg_class']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Thời hạn cấp độ khi đăng ký (ngày)</label>
                                            <div class="col">
                                                <input id="reg_class_time" type="text" class="form-control"
                                                       value="{$settings['reg_class_time']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Mã hóa mặc định</label>
                                            <div class="col">
                                                <input id="reg_method" type="text" class="form-control"
                                                       value="{$settings['reg_method']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Giới hạn IP kết nối</label>
                                            <div class="col">
                                                <input id="reg_ip_limit" type="text" class="form-control"
                                                       value="{$settings['reg_ip_limit']}">
                                            </div>
                                        </div>
                                        <div class="form-group mb-3 row">
                                            <label class="form-label col-3 col-form-label">Giới hạn tốc độ sử dụng</label>
                                            <div class="col">
                                                <input id="reg_speed_limit" type="text" class="form-control"
                                                       value="{$settings['reg_speed_limit']}">
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
                    url: '/admin/setting/reg',
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
