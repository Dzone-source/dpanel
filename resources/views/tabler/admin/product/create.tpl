{include file="admin/header.tpl"}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Tạo sản phẩm</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Tạo các loại sản phẩm</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a id="create-product" href="#" class="btn btn-primary">
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
                                    <input id="name" type="text" class="form-control" value="" required>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giá</label>
                                <div class="col">
                                    <input id="price" type="text" class="form-control" value="" required>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Tồn kho (-1 là không giới hạn)</label>
                                <div class="col">
                                    <input id="stock" type="text" class="form-control" value="" required>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Trạng thái bán hàng</label>
                                <div class="col">
                                    <select id="status" class="col form-select">
                                        <option value="1">Bình thường</option>
                                        <option value="0">Ngừng bán</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Loại</label>
                                <div class="col">
                                    <select id="type" class="col form-select">
                                        <option value="tabp">Gói thời gian và lưu lượng</option>
                                        <option value="bandwidth">Gói lưu lượng</option>
                                        <option value="time">Gói thời gian</option>
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
                                    <input id="time" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="class_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Cấp</label>
                                <div class="col">
                                    <input id="class" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="class_time_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Thời hạn cấp (ngày)</label>
                                <div class="col">
                                    <input id="class_time" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="bandwidth_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Lưu lượng khả dụng (GB)</label>
                                <div class="col">
                                    <input id="bandwidth" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="node_group_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Nhóm người dùng</label>
                                <div class="col">
                                    <input id="node_group" type="text" class="form-control" value="">
                                </div>
                            </div>
                            <div id="speed_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn tốc độ (Mbps)</label>
                                <div class="col">
                                    <input id="speed_limit" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div id="ip_limit_option" class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label required">Giới hạn IP kết nối đồng thời</label>
                                <div class="col">
                                    <input id="ip_limit" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="hr-text">
                                <span>Giới hạn mua hàng</span>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Yêu cầu cấp người dùng</label>
                                <div class="col">
                                    <input id="class_required" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Nhóm máy chủ của người dùng</label>
                                <div class="col">
                                    <input id="node_group_required" type="text" class="form-control"
                                           value="">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="row">
                                    <span class="col">Chỉ người dùng mới được mua</span>
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
            </div>
        </div>
    </div>
</div>

<script>
    $(function () {
        $("#type").change();
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
            $("#fail-message").text("Vui lòng điền đầy đủ các trường bắt buộc");
            $("#fail-dialog").modal("show");
        } else {
            $.ajax({
                url: "/admin/product",
                type: "POST",
                dataType: "json",
                data: {
                    {foreach $update_field as $key}
                    {$key}: $("#{$key}").val(),
                    {/foreach}
                    new_user_required: $("#new_user_required").is(":checked"),
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
