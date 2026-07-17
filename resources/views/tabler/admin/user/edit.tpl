{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Người dùng #{$edit_user->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chỉnh sửa người dùng</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <button type="button" id="save_changes" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            Lưu thay đổi
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Thông tin tài khoản</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Email</label>
                                <div class="col">
                                    <input id="email" type="email" class="form-control" value="{$edit_user->email}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Tên người dùng</label>
                                <div class="col">
                                    <input id="user_name" type="text" class="form-control"
                                           value="{$edit_user->user_name}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Mật khẩu tài khoản</label>
                                <div class="col">
                                    <input id="pass" type="text" class="form-control" value=""
                                           autocomplete="new-password"
                                           placeholder="Nhập mật khẩu mới rồi bấm Lưu (để trống = không đổi)">
                                    <small class="form-hint text-muted">Không hiện mật khẩu hiện tại. Chỉ điền khi muốn đặt lại.</small>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Số dư tài khoản</label>
                                <div class="col">
                                    <input id="money" type="number" step="1" class="form-control"
                                           value="{$edit_user->money}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Người mời</label>
                                <div class="col">
                                    <input id="ref_by" type="text" class="form-control" value="{$edit_user->ref_by}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Cổng SS</label>
                                <div class="col">
                                    <input id="port" type="text" class="form-control" value="{$edit_user->port}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Phương thức mã hóa SS</label>
                                <div class="col">
                                    <select id="method" class="col form-select" value="{$edit_user->method}">
                                        {foreach $ss_methods as $method}
                                            <option value="{$method}" {if $edit_user->method === $method}selected{/if}>
                                                {$method}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">IP đăng ký</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->reg_ip}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Ngày đăng ký</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->reg_date}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Thời gian sử dụng gần nhất</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_use_time}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Thời gian điểm danh gần nhất</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_check_in_time}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Thời gian đăng nhập gần nhất</label>
                                <div class="col">
                                    <input type="text" class="form-control" value="{$edit_user->last_login_time}" disabled/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Giới hạn sử dụng</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Giới hạn lưu lượng</label>
                                <div class="col">
                                    <input id="transfer_enable" type="text" class="form-control"
                                           value="{$edit_user->enableTraffic()}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Lưu lượng kỳ hiện tại</label>
                                <div class="col">
                                    <input type="text" class="form-control"
                                           value="{$edit_user->usedTraffic()}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Lưu lượng tích lũy</label>
                                <div class="col">
                                    <input type="text" class="form-control"
                                           value="{$edit_user->totalTraffic()}" disabled/>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Nhóm máy chủ</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control"
                                           value="{$edit_user->node_group}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Cấp tài khoản</label>
                                <div class="col">
                                    <input id="class" type="text" class="form-control"
                                           value="{$edit_user->class}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Thời gian hết hạn cấp</label>
                                <div class="col">
                                    <input id="class_expire" type="text" class="form-control"
                                           value="{$edit_user->class_expire}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Ngày đặt lại lưu lượng miễn phí</label>
                                <div class="col">
                                    <input id="auto_reset_day" type="text" class="form-control"
                                           value="{$edit_user->auto_reset_day}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Lưu lượng miễn phí đặt lại (GB)</label>
                                <div class="col">
                                    <input id="auto_reset_bandwidth" type="text" class="form-control"
                                           value="{$edit_user->auto_reset_bandwidth}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Giới hạn tốc độ (Mbps)</label>
                                <div class="col">
                                    <input id="node_speedlimit" type="text" class="form-control"
                                           value="{$edit_user->node_speedlimit}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-4 col-form-label">Giới hạn IP kết nối đồng thời</label>
                                <div class="col">
                                    <input id="node_iplimit" type="text" class="form-control"
                                           value="{$edit_user->node_iplimit}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Cài đặt khác</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <span class="col">Quản trị viên</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="is_admin" class="form-check-input" type="checkbox"
                                               {if $edit_user->is_admin}checked="" {/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">Xác thực hai bước (MFA)</span>
                                <span class="col-auto">
                                    {if $mfa_enabled}
                                    <span class="badge bg-green-lt">Đã bật</span>
                                    {else}
                                    <span class="badge bg-secondary-lt">Chưa bật</span>
                                    {/if}
                                </span>
                            </div>
                            {if $mfa_enabled}
                            <div class="form-group mb-3 row">
                                <span class="col">Xóa thiết bị MFA của user</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="clear_mfa" class="form-check-input" type="checkbox">
                                    </label>
                                </span>
                            </div>
                            {/if}
                            <div class="form-group mb-3 row">
                                <span class="col">Trạng thái bất thường tài khoản (Shadow Banned)</span>
                                <span class="col-auto form-check-single form-switch">
                                    <input id="is_shadow_banned" class="form-check-input" type="checkbox"
                                           {if $edit_user->is_shadow_banned}checked=""{/if}>
                                </span>
                            </div>
                            <div class="form-group mb-3 row">
                                <span class="col">Khóa người dùng</span>
                                <span class="col-auto">
                                    <label class="form-check form-check-single form-switch">
                                        <input id="is_banned" class="form-check-input" type="checkbox"
                                               {if $edit_user->is_banned}checked=""{/if}>
                                    </label>
                                </span>
                            </div>
                            <div class="form-group mb-3 col-12">
                                <span class="form-label col-12 col-form-label">Lý do khóa thủ công</span>
                                <span class="col-auto">
                                    <textarea id="banned_reason" class="form-control">{$edit_user->banned_reason|escape}</textarea>
                                </span>
                            </div>
                            <div class="form-group mb-3 col-12">
                                <label class="form-label col-12 col-form-label">Ghi chú tài khoản</label>
                                <div class="col">
                                    <textarea id="remark" class="form-control"
                                              placeholder="Chỉ quản trị viên mới thấy">{$edit_user->remark|escape}</textarea>
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
    (function () {
        const saveBtn = document.getElementById('save_changes');
        const editUserId = {$edit_user->id};
        const jumpDelay = Number({$config['jump_delay']|default:800}) || 800;
        let saving = false;

        function setSaving(isSaving) {
            saving = isSaving;
            if (!saveBtn) return;
            saveBtn.disabled = isSaving;
            if (isSaving) {
                saveBtn.dataset.originalHtml = saveBtn.innerHTML;
                saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Đang lưu...';
            } else if (saveBtn.dataset.originalHtml) {
                saveBtn.innerHTML = saveBtn.dataset.originalHtml;
            }
        }

        function showFail(msg) {
            const el = document.getElementById('fail-message');
            if (el) el.textContent = msg || 'Cập nhật thất bại';
            if (typeof failDialog !== 'undefined' && failDialog) {
                failDialog.show();
            } else {
                alert(msg || 'Cập nhật thất bại');
            }
        }

        function showSuccess(msg) {
            const el = document.getElementById('success-message');
            if (el) el.textContent = msg || 'Cập nhật thành công';
            try {
                if (typeof successDialog !== 'undefined' && successDialog) {
                    successDialog.show();
                }
            } catch (e) {}
        }

        function saveUserChanges() {
            if (saving) return;
            setSaving(true);

            const payload = {
                {foreach $update_field as $key}
                {$key}: $('#{$key}').val(),
                {/foreach}
                is_admin: $("#is_admin").is(":checked"),
                clear_mfa: $("#clear_mfa").is(":checked"),
                is_shadow_banned: $("#is_shadow_banned").is(":checked"),
                is_banned: $("#is_banned").is(":checked"),
            };

            $.ajax({
                url: '/admin/user/' + editUserId,
                type: 'POST',
                dataType: 'json',
                data: payload,
                success: function (data) {
                    if (data && data.ret === 1) {
                        showSuccess(data.msg);
                        window.setTimeout(function () {
                            // Reload edit page so badges/status (MFA, etc.) reflect saved data.
                            window.location.href = '/admin/user/' + editUserId + '/edit?saved=1';
                        }, jumpDelay);
                        return;
                    }
                    setSaving(false);
                    showFail(data && data.msg ? data.msg : 'Cập nhật thất bại');
                },
                error: function (xhr) {
                    setSaving(false);
                    let msg = 'Không gửi được yêu cầu lưu (HTTP ' + xhr.status + ')';
                    try {
                        const body = JSON.parse(xhr.responseText);
                        if (body && body.msg) msg = body.msg;
                    } catch (e) {}
                    showFail(msg);
                }
            });
        }

        if (saveBtn) {
            saveBtn.addEventListener('click', function (e) {
                e.preventDefault();
                saveUserChanges();
            });
        }

        if (new URLSearchParams(window.location.search).get('saved') === '1') {
            showSuccess('Cập nhật thành công');
            if (window.history && window.history.replaceState) {
                window.history.replaceState({}, '', '/admin/user/' + editUserId + '/edit');
            }
        }
    })();
</script>

{include file='admin/footer.tpl'}
