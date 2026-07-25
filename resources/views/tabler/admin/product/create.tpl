{include file="admin/header.tpl"}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">创建商品</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">创建各类商品</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a id="create-product" href="#" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            保存
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
                            <h3 class="card-title">基础信息</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">名称</label>
                                <div class="col">
                                    <input id="name" type="text" class="form-control" value="" required>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">价格</label>
                                <div class="col">
                                    <input id="price" type="text" class="form-control" value="" required>
                                    <small class="form-hint">Nếu có tùy chọn thời hạn bên dưới, giá mặc định sẽ lấy theo tùy chọn đầu tiên.</small>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">库存（-1为不限制）</label>
                                <div class="col">
                                    <input id="stock" type="text" class="form-control" value="" required>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">销售状态</label>
                                <div class="col">
                                    <select id="status" class="col form-select">
                                        <option value="1">正常</option>
                                        <option value="0">下架</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">类型</label>
                                <div class="col">
                                    <select id="type" class="col form-select">
                                        <option value="tabp">时间流量包</option>
                                        <option value="bandwidth">流量包</option>
                                        <option value="time">时间包</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header card-header-light">
                            <h3 class="card-title">商品内容</h3>
                        </div>
                        <div class="card-body">
                            <div id="time_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">商品时长 (天)</label>
                                <div class="col">
                                    <input id="time" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="class_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">等级</label>
                                <div class="col">
                                    <input id="class" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="class_time_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">等级时长 (天)</label>
                                <div class="col">
                                    <input id="class_time" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="bandwidth_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">可用流量 (GB)</label>
                                <div class="col">
                                    <input id="bandwidth" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="node_group_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">用户分组</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="speed_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">速率限制 (Mbps)</label>
                                <div class="col">
                                    <input id="speed_limit" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div id="ip_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">同时连接IP限制</label>
                                <div class="col">
                                    <input id="ip_limit" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>购买限制</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">用户等级要求</label>
                                <div class="col">
                                    <input id="class_required" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">用户所在的节点组</label>
                                <div class="col">
                                    <input id="node_group_required" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="row">
                                    <span class="col">仅限新用户购买</span>
                                    <span class="col-auto">
                                        <label class="form-check form-check-single form-switch">
                                            <input id="new_user_required" class="form-check-input" type="checkbox">
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
                                <table class="table table-vcenter">
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

    $("#create-product").click(function () {
        let emptyFields = $('input[required]').filter(function () {
            return $(this).val() === '';
        });

        if (emptyFields.length > 0) {
            $("#fail-message").text("请填写所有必要栏位");
            $("#fail-dialog").modal("show");
        } else {
            var options = collectProductOptions();
            if (options.length > 0) {
                $('#time').val(options[0].days);
                $('#class_time').val(options[0].days);
                $('#price').val(options[0].price);
            }
            $.ajax({
                url: "/admin/product",
                type: "POST",
                dataType: "json",
                data: {
                    {foreach $update_field as $key}
                    {$key}: $("#{$key}").val(),
                    {/foreach}
                    new_user_required: $("#new_user_required").is(":checked"),
                    options_json: JSON.stringify(options),
                },
                success: function (data) {
                    if (data.ret === 1) {
                        $("#success-message").text(data.msg);
                        $("#success-dialog").modal("show");
                        window.setTimeout("location.href=top.document.referrer", {$config["jump_delay"]});
                    } else {
                        $("#fail-message").text(data.msg);
                        $("#fail-dialog").modal("show");
                    }
                }
            })
        }
    });
</script>

{include file="admin/footer.tpl"}
