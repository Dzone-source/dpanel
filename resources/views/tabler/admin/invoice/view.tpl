{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title my-3">Hóa đơn #{$invoice->id}</span>
                    </h2>
                    <div class="page-pretitle">
                        <span class="home-subtitle">Chi tiết hóa đơn</span>
                    </div>
                </div>
                {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
                    <div class="col-auto">
                        <div class="btn-list">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#mark_paid_confirm_dialog">
                                <i class="icon ti ti-checklist"></i>
                                {if $invoice->status === 'partially_paid'}
                                    Duyệt đơn hàng
                                {else}
                                    Đánh dấu đã thanh toán
                                {/if}
                            </button>
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Thông tin cơ bản</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Email người sở hữu</div>
                            <div class="datagrid-content">
                                <a href="/admin/user/{$invoice->user_id}/edit">{$owner_email}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID đơn hàng liên quan</div>
                            <div class="datagrid-content">{$invoice->order_id}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Số tiền hóa đơn</div>
                            <div class="datagrid-content">{$invoice->price|format_vnd:0} VNĐ</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trạng thái hóa đơn</div>
                            <div class="datagrid-content">{$invoice->status_text}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Thời gian tạo</div>
                            <div class="datagrid-content">{$invoice->create_time}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Thời gian cập nhật</div>
                            <div class="datagrid-content">{$invoice->update_time}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Thời gian thanh toán</div>
                            <div class="datagrid-content">{$invoice->pay_time}</div>
                        </div>
                        {if $invoice->status === 'paid_gateway'}
                            <div class="datagrid-item">
                                <div class="datagrid-title">Mã đơn cổng thanh toán</div>
                                <div class="datagrid-content">{$paylist->tradeno}</div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="card my-3">
                <div class="card-header">
                    <h3 class="card-title">Chi tiết hóa đơn</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="invoice_content_table" class="table table-vcenter card-table">
                            <thead>
                            <tr>
                                <th>Tên</th>
                                <th>Giá</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $invoice_content as $invoice_content_detail}
                                <tr>
                                    <td>{$invoice_content_detail->name}</td>
                                    <td>{$invoice_content_detail->price|format_vnd:0} VNĐ</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
    <div class="modal modal-blur fade" id="mark_paid_confirm_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        {if $invoice->status === 'partially_paid'}
                            Duyệt đơn hàng
                        {else}
                            Đánh dấu đã thanh toán
                        {/if}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        {if $invoice->status === 'partially_paid'}
                            <p>
                                Hóa đơn đã thanh toán một phần bằng số dư.
                                Xác nhận duyệt phần còn lại và kích hoạt đơn hàng?
                            </p>
                        {else}
                            <p>
                                Xác nhận đánh dấu hóa đơn này là đã thanh toán?
                            </p>
                        {/if}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                    <button id="confirm_mark_paid" type="button" class="btn btn-primary" data-bs-dismiss="modal">Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $("#confirm_mark_paid").click(function () {
            $.ajax({
                url: "/admin/invoice/{$invoice->id}/mark_paid",
                type: 'POST',
                dataType: "json",
                success: function (data) {
                    if (data.ret === 1) {
                        $('#success-message').text(data.msg);
                        $('#success-dialog').modal('show');
                        window.setTimeout(function () {
                            location.reload();
                        }, 1200);
                    } else {
                        $('#fail-message').text(data.msg);
                        $('#fail-dialog').modal('show');
                    }
                }
            })
        });
    </script>
    {/if}

    {include file='admin/footer.tpl'}
