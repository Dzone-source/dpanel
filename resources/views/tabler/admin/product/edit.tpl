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
                        <a id="save-product" href="#" class="btn btn-primary">
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
                                <label class="form-label col-3 col-form-label required">Tên</label>
                                <div class="col">
                                    <input id="name" type="text" class="form-control"
                                           value="{$product->name}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giá</label>
                                <div class="col">
                                    <input id="price" type="text" class="form-control"
                                           value="{$product->price}">
                                    <small class="form-hint">Nếu có tùy chọn thời hạn bên dưới, giá mặc định sẽ lấy theo tùy chọn đầu tiên.</small>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Tồn kho (nhỏ hơn 0 là không giới hạn)</label>
                                <div class="col">
                                    <input id="stock" type="text" class="form-control"
                                           value="{$product->stock}">
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
                                        <option value="tabp" {if $product->type === "tabp"}selected{/if}>Gói thời gian và lưu lượng
                                        </option>
                                        <option value="time" {if $product->type === "time"}selected{/if}>Gói thời gian</option>
                                        <option value="bandwidth" {if $product->type === "bandwidth"}selected{/if}>
                                            Gói lưu lượng
                                        </option>
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
                            <div id="time_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Thời hạn sản phẩm (ngày)</label>
                                <div class="col">
                                    <input id="time" type="text" class="form-control"
                                           value="{$content->time}">
                                </div>
                            </div>
                            <div id="class_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Cấp</label>
                                <div class="col">
                                    <input id="class" type="text" class="form-control"
                                           value="{$content->class}">
                                </div>
                            </div>
                            <div id="class_time_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Thời hạn cấp (ngày)</label>
                                <div class="col">
                                    <input id="class_time" type="text" class="form-control"
                                           value="{$content->class_time}">
                                </div>
                            </div>
                            <div id="bandwidth_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Lưu lượng khả dụng (GB)</label>
                                <div class="col">
                                    <input id="bandwidth" type="text" class="form-control"
                                           value="{$content->bandwidth}">
                                </div>
                            </div>
                            <div id="node_group_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Nhóm người dùng</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control"
                                           value="{$content->node_group}">
                                </div>
                            </div>
                            <div id="speed_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn tốc độ (Mbps)</label>
                                <div class="col">
                                    <input id="speed_limit" type="text" class="form-control"
                                           value="{$content->speed_limit}">
                                </div>
                            </div>
                            <div id="ip_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn IP kết nối đồng thời</label>
                                <div class="col">
                                    <input id="ip_limit" type="text" class="form-control"
                                           value="{$content->ip_limit}">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>Giới hạn mua hàng</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Yêu cầu cấp người dùng</label>
                                <div class="col">
                                    <input id="class_required" type="text" class="form-control"
                                           value="{$limit->class_required}">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Nhóm máy chủ của người dùng</label>
                                <div class="col">
                                    <input id="node_group_required" type="text" class="form-control"
                                           value="{$limit->node_group_required}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="row">
                                    <span class="col">Chỉ người dùng mới được mua</span>
                                    <span class="col-auto">
                                        <label class="form-check form-check-single form-switch">
                                            <input id="new_user_required" class="form-check-input" type="checkbox"
                                                   {if $limit->new_user_required === 1}checked="" {/if}>
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
                                Thêm nhiều gói thời gian (ví dụ 30 / 90 / 180 ngày) với giá riêng.
                                Khi mua, người dùng sẽ chọn một tùy chọn. Để trống nếu chỉ dùng 1 mức giá như cũ.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-vcenter" id="product-options-table">
                                    <thead>
                                    <tr>
                                        <th style="width:28%">Nhãn</th>
                                        <th style="width:22%">Số ngày</th>
                                        <th style="width:28%">Giá</th>
                                        <th style="width:22%"></th>
                                    </tr>
                                    </thead>
                                    <tbody id="product-options-body"></tbody>
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

<script type="application/json" id="product-options-json">{if isset($product_options_json)}{$product_options_json}{else}[]{/if}</script>
<script>
{literal}
    function optionRowHtml(opt) {
        opt = opt || {};
        var label = opt.label || '';
        var days = opt.days || '';
        var price = (opt.price !== undefined && opt.price !== null) ? opt.price : '';
        var esc = function (s) {
            return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        };
        return '' +
            '<tr class="product-option-row">' +
            '<td><input type="text" class="form-control option-label" placeholder="VD: 30 ngày" value="' + esc(label) + '"></td>' +
            '<td><input type="number" min="1" class="form-control option-days" placeholder="30" value="' + esc(days) + '"></td>' +
            '<td><input type="number" min="0" step="0.01" class="form-control option-price" placeholder="30000" value="' + esc(price) + '"></td>' +
            '<td><button type="button" class="btn btn-outline-danger remove-product-option">Xóa</button></td>' +
            '</tr>';
    }

    function initProductOptions(list) {
        var $body = $('#product-options-body');
        $body.empty();
        if (!list || !list.length) {
            return;
        }
        list.forEach(function (opt) {
            $body.append(optionRowHtml(opt));
        });
    }

    function collectProductOptions() {
        var options = [];
        $('#product-options-body .product-option-row').each(function () {
            var days = parseInt($(this).find('.option-days').val(), 10);
            var price = parseFloat($(this).find('.option-price').val());
            var label = $.trim($(this).find('.option-label').val() || '');
            if (!days || days <= 0 || isNaN(price) || price < 0) {
                return;
            }
            if (!label) {
                label = days + ' ngày';
            }
            options.push({ days: days, price: price, label: label });
        });
        return options;
    }
{/literal}

    $(function () {
        $("#type").change();
        var raw = document.getElementById('product-options-json');
        var list = [];
        try {
            list = raw ? JSON.parse(raw.textContent || '[]') : [];
        } catch (e) {
            list = [];
        }
        initProductOptions(list);
    });

    $('#add-product-option').on('click', function () {
        $('#product-options-body').append(optionRowHtml());
    });

    $(document).on('click', '.remove-product-option', function () {
        $(this).closest('tr').remove();
    });

    $("#type").on("change", function () {
        if (this.value === "bandwidth") {
            $("#time_option").hide();
            $("#class_option").hide();
            $("#class_time_option").hide();
            $("#bandwidth_option").show();
            $("#node_group_option").hide();
            $("#speed_limit_option").hide();
            $("#ip_limit_option").hide();
            $("#product_options_card").hide();
            $("#time").prop("required", false);
            $("#class").prop("required", false);
            $("#class_time").prop("required", false);
            $("#bandwidth").prop("required", true);
            $("#node_group").prop("required", false);
            $("#speed_limit").prop("required", false);
            $("#ip_limit").prop("required", false);
        } else if (this.value === "time") {
            $("#time_option").show();
            $("#class_option").show();
            $("#class_time_option").show();
            $("#bandwidth_option").hide();
            $("#node_group_option").show();
            $("#speed_limit_option").show();
            $("#ip_limit_option").show();
            $("#product_options_card").show();
            $("#time").prop("required", true);
            $("#class").prop("required", true);
            $("#class_time").prop("required", true);
            $("#bandwidth").prop("required", false);
            $("#node_group").prop("required", true);
            $("#speed_limit").prop("required", true);
            $("#ip_limit").prop("required", true);
        } else {
            $("#time_option").show();
            $("#class_option").show();
            $("#class_time_option").show();
            $("#bandwidth_option").show();
            $("#node_group_option").show();
            $("#speed_limit_option").show();
            $("#ip_limit_option").show();
            $("#product_options_card").show();
            $("#time").prop("required", true);
            $("#class").prop("required", true);
            $("#class_time").prop("required", true);
            $("#bandwidth").prop("required", true);
            $("#node_group").prop("required", true);
            $("#speed_limit").prop("required", true);
            $("#ip_limit").prop("required", true);
        }
    });

    function showFail(msg) {
        $("#fail-message").text(msg || "Thất bại");
        if (typeof failDialog !== "undefined") {
            failDialog.show();
        } else {
            alert(msg || "Thất bại");
        }
    }

    function showSuccess(msg) {
        $("#success-message").text(msg || "Thành công");
        if (typeof successDialog !== "undefined") {
            successDialog.show();
        }
    }

    $("#save-product").on("click", function (e) {
        e.preventDefault();

        var options = collectProductOptions();
        if (options.length > 0) {
            $("#time").val(options[0].days);
            $("#class_time").val(options[0].days);
            $("#price").val(options[0].price);
        }

        var emptyFields = $("input[required]").filter(function () {
            return $.trim($(this).val()) === "";
        });
        if (emptyFields.length > 0) {
            showFail("Vui lòng điền đầy đủ các trường bắt buộc");
            return;
        }
        if ($.trim($("#name").val()) === "") {
            showFail("Vui lòng nhập tên sản phẩm");
            return;
        }
        if ($.trim($("#price").val()) === "" || isNaN(parseFloat($("#price").val()))) {
            showFail("Vui lòng nhập giá hợp lệ");
            return;
        }

        $.ajax({
            url: "/admin/product/{$product->id}",
            type: "POST",
            dataType: "json",
            data: {
                {foreach $update_field as $key}
                {$key}: $("#{$key}").val(),
                {/foreach}
                new_user_required: $("#new_user_required").is(":checked"),
                options_json: JSON.stringify(options)
            },
            success: function (data) {
                if (data && data.ret === 1) {
                    showSuccess(data.msg);
                    window.setTimeout(function () {
                        location.href = "/admin/product";
                    }, {$config['jump_delay']|default:1500});
                } else {
                    showFail((data && data.msg) ? data.msg : "Cập nhật thất bại");
                }
            },
            error: function (xhr) {
                var msg = "Không gửi được yêu cầu lưu";
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data && data.msg) msg = data.msg;
                } catch (err) {}
                showFail(msg);
            }
        });
    });
</script>

{include file='admin/footer.tpl'}
