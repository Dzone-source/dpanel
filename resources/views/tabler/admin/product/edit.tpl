{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Sản phẩm #{$product->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chỉnh sửa thông tin sản phẩm</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <button type="button" id="save-product" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            Lưu
                        </button>
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
                                <label class="form-label col-3 col-form-label required">Tên</label>
                                <div class="col">
                                    <input id="name" type="text" class="form-control" value="{$product->name|escape:'html'}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row" id="price_option">
                                <label class="form-label col-3 col-form-label required">Giá</label>
                                <div class="col">
                                    <input id="price" type="text" class="form-control" value="{$product->price}">
                                </div>
                            </div>
                            <input type="hidden" id="time" value="{$content->time}">
                            <input type="hidden" id="class_time" value="{$content->class_time}">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Tồn kho (nhỏ hơn 0 là không giới hạn)</label>
                                <div class="col">
                                    <input id="stock" type="text" class="form-control" value="{$product->stock}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Trạng thái bán hàng</label>
                                <div class="col">
                                    <select id="status" class="col form-select">
                                        <option value="1" {if $product->status === 1}selected{/if}>Bình thường</option>
                                        <option value="0" {if $product->status === 0}selected{/if}>Ngừng bán</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Loại</label>
                                <div class="col">
                                    <select id="type" class="col form-select">
                                        <option value="tabp" {if $product->type === "tabp"}selected{/if}>Gói thời gian và lưu lượng</option>
                                        <option value="time" {if $product->type === "time"}selected{/if}>Gói thời gian</option>
                                        <option value="bandwidth" {if $product->type === "bandwidth"}selected{/if}>Gói lưu lượng</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Nội dung sản phẩm</h3>
                        </div>
                        <div class="card-body">
                            <div id="class_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Cấp</label>
                                <div class="col">
                                    <input id="product_class" type="text" class="form-control" value="{$content->class}">
                                </div>
                            </div>
                            <div id="bandwidth_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Lưu lượng khả dụng (GB)</label>
                                <div class="col">
                                    <input id="bandwidth" type="text" class="form-control" value="{$content->bandwidth}">
                                </div>
                            </div>
                            <div id="node_group_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Nhóm người dùng</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control" value="{$content->node_group}">
                                </div>
                            </div>
                            <div id="speed_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn tốc độ (Mbps)</label>
                                <div class="col">
                                    <input id="speed_limit" type="text" class="form-control" value="{$content->speed_limit}">
                                </div>
                            </div>
                            <div id="ip_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn IP kết nối đồng thời</label>
                                <div class="col">
                                    <input id="ip_limit" type="text" class="form-control" value="{$content->ip_limit}">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>Giới hạn mua hàng</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Yêu cầu cấp người dùng</label>
                                <div class="col">
                                    <input id="class_required" type="text" class="form-control" value="{$limit->class_required|escape:'html'}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Nhóm máy chủ của người dùng</label>
                                <div class="col">
                                    <input id="node_group_required" type="text" class="form-control" value="{$limit->node_group_required|escape:'html'}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="row">
                                    <span class="col">Chỉ người dùng mới được mua</span>
                                    <span class="col-auto">
                                        <label class="form-check form-check-single form-switch">
                                            <input id="new_user_required" class="form-check-input" type="checkbox"
                                                   {if $limit->new_user_required === 1}checked{/if}>
                                        </label>
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 mt-3" id="product_options_card">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">Tùy chọn thời hạn &amp; giá</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary mb-3">
                                Thêm các gói thời gian (ví dụ 30 / 90 / 180 ngày) kèm giá.
                                Khách sẽ chọn một tùy chọn khi mua. Gói thời gian bắt buộc có ít nhất một tùy chọn.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-vcenter">
                                    <thead>
                                    <tr>
                                        <th style="width:28%">Nhãn</th>
                                        <th style="width:22%">Số ngày</th>
                                        <th style="width:28%">Giá</th>
                                        <th style="width:22%"></th>
                                    </tr>
                                    </thead>
                                    <tbody id="product-options-body">
                                    {foreach from=$product_options item=opt}
                                        <tr class="product-option-row">
                                            <td><input type="text" class="form-control option-label" value="{$opt.label|escape:'html'}" placeholder="VD: 30 ngày"></td>
                                            <td><input type="number" min="1" class="form-control option-days" value="{$opt.days}" placeholder="30"></td>
                                            <td><input type="number" min="0" step="0.01" class="form-control option-price" value="{$opt.price}" placeholder="30000"></td>
                                            <td><button type="button" class="btn btn-outline-danger remove-product-option">Xóa</button></td>
                                        </tr>
                                    {/foreach}
                                    </tbody>
                                </table>
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="add-product-option">
                                <i class="icon ti ti-plus"></i> Thêm tùy chọn
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{include file='admin/footer.tpl'}

<script>
{literal}
(function () {
    function optionRowHtml() {
        return '<tr class="product-option-row">' +
            '<td><input type="text" class="form-control option-label" placeholder="VD: 30 ngày"></td>' +
            '<td><input type="number" min="1" class="form-control option-days" placeholder="30"></td>' +
            '<td><input type="number" min="0" step="0.01" class="form-control option-price" placeholder="30000"></td>' +
            '<td><button type="button" class="btn btn-outline-danger remove-product-option">Xóa</button></td>' +
            '</tr>';
    }

    function fieldVal(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }

    function syncType(value) {
        var map = {
            price_option: value === 'bandwidth',
            class_option: value !== 'bandwidth',
            bandwidth_option: value === 'bandwidth' || value === 'tabp',
            node_group_option: value !== 'bandwidth',
            speed_limit_option: value !== 'bandwidth',
            ip_limit_option: value !== 'bandwidth',
            product_options_card: value !== 'bandwidth'
        };
        Object.keys(map).forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.style.display = map[id] ? '' : 'none';
        });
    }

    function collectOptions() {
        var days = [];
        var prices = [];
        var labels = [];
        document.querySelectorAll('#product-options-body .product-option-row').forEach(function (row) {
            var d = parseInt((row.querySelector('.option-days') || {}).value, 10);
            var p = parseFloat((row.querySelector('.option-price') || {}).value);
            var l = ((row.querySelector('.option-label') || {}).value || '').trim();
            if (!d || d <= 0 || isNaN(p) || p < 0) return;
            if (!l) l = d + ' ngày';
            days.push(d);
            prices.push(p);
            labels.push(l);
        });
        return { days: days, prices: prices, labels: labels };
    }

    function notify(ok, msg) {
        var msgEl = document.getElementById(ok ? 'success-message' : 'fail-message');
        if (msgEl) msgEl.textContent = msg || (ok ? 'Thành công' : 'Thất bại');
        if (ok && typeof successDialog !== 'undefined') {
            successDialog.show();
            return;
        }
        if (!ok && typeof failDialog !== 'undefined') {
            failDialog.show();
            return;
        }
        window.alert(msg || (ok ? 'Thành công' : 'Thất bại'));
    }

    document.getElementById('type').addEventListener('change', function () {
        syncType(this.value);
    });
    syncType(document.getElementById('type').value);

    document.getElementById('add-product-option').addEventListener('click', function () {
        document.getElementById('product-options-body').insertAdjacentHTML('beforeend', optionRowHtml());
    });

    document.getElementById('product-options-body').addEventListener('click', function (e) {
        var btn = e.target.closest('.remove-product-option');
        if (!btn) return;
        var row = btn.closest('tr');
        if (row) row.remove();
    });

    document.getElementById('save-product').addEventListener('click', function () {
        var type = fieldVal('type');
        var opts = collectOptions();
        if (type === 'tabp' || type === 'time') {
            if (opts.days.length === 0) {
                notify(false, 'Vui lòng thêm ít nhất một tùy chọn thời hạn & giá');
                return;
            }
            document.getElementById('time').value = opts.days[0];
            document.getElementById('class_time').value = opts.days[0];
            document.getElementById('price').value = opts.prices[0];
        }

        if (!fieldVal('name')) {
            notify(false, 'Vui lòng nhập tên sản phẩm');
            return;
        }
        if (type === 'bandwidth' && (fieldVal('price') === '' || isNaN(parseFloat(fieldVal('price'))))) {
            notify(false, 'Vui lòng nhập giá hợp lệ');
            return;
        }

        var data = {
            type: type,
            name: fieldVal('name'),
            price: fieldVal('price'),
            status: fieldVal('status'),
            stock: fieldVal('stock'),
            time: fieldVal('time'),
            bandwidth: fieldVal('bandwidth'),
            class: fieldVal('product_class'),
            class_time: fieldVal('class_time'),
            node_group: fieldVal('node_group'),
            speed_limit: fieldVal('speed_limit'),
            ip_limit: fieldVal('ip_limit'),
            class_required: fieldVal('class_required'),
            node_group_required: fieldVal('node_group_required'),
            new_user_required: document.getElementById('new_user_required').checked ? 'true' : 'false',
            option_days: opts.days,
            option_prices: opts.prices,
            option_labels: opts.labels
        };

        $.ajax({
            url: '/admin/product/' + {/literal}{$product->id}{literal},
            type: 'PUT',
            dataType: 'json',
            data: data,
            success: function (res) {
                if (res && res.ret === 1) {
                    notify(true, res.msg || 'Cập nhật thành công');
                    window.setTimeout(function () {
                        location.href = '/admin/product';
                    }, {/literal}{$config['jump_delay']|default:1500}{literal});
                } else {
                    notify(false, (res && res.msg) ? res.msg : 'Cập nhật thất bại');
                }
            },
            error: function (xhr) {
                var msg = 'Không gửi được yêu cầu lưu (HTTP ' + xhr.status + ')';
                try {
                    var res = JSON.parse(xhr.responseText);
                    if (res && res.msg) msg = res.msg;
                } catch (e) {}
                notify(false, msg);
            }
        });
    });
})();
{/literal}
</script>
