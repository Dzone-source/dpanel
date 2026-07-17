{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Đơn hàng #{$order->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chi tiết đơn hàng</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <a href="/admin/user/{$order->user_id}/edit" targer="_blank" class="btn btn-primary">
                            <i class="icon ti ti-user"></i>
                            Xem người dùng liên quan
                        </a>
                        <a href="/admin/invoice/{$invoice->id}/view" targer="_blank" class="btn btn-primary">
                            <i class="icon ti ti-file-dollar"></i>
                            Xem hóa đơn liên quan
                        </a>
                        {if $order->status === 'pending_payment'}
                            <button href="#" class="btn btn-red" data-bs-toggle="modal"
                                    data-bs-target="#cancel_order_confirm_dialog">
                                <i class="icon ti ti-x"></i>
                                Hủy đơn hàng
                            </button>
                        {/if}
                    </div>
                </div>
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
                            <div class="datagrid-title">Email người gửi</div>
                            <div class="datagrid-content">
                                <a href="/admin/user/{$order->user_id}/edit">{$owner_email}</a>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">ID sản phẩm</div>
                            <div class="datagrid-content">{$order->product_id}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Loại sản phẩm</div>
                            <div class="datagrid-content">{$order->product_type_text}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Tên sản phẩm</div>
                            <div class="datagrid-content">{$order->product_name}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Mã giảm giá đơn hàng</div>
                            <div class="datagrid-content">{$order->coupon}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Số tiền đơn hàng</div>
                            <div class="datagrid-content">{$order->price|format_vnd:0} VNĐ</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trạng thái đơn hàng</div>
                            <div class="datagrid-content">{$order->status_text}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Thời gian tạo</div>
                            <div class="datagrid-content">{$order->create_time}</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Thời gian cập nhật</div>
                            <div class="datagrid-content">{$order->update_time}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card my-3">
                <div class="card-header">
                    <h3 class="card-title">Nội dung sản phẩm</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        {if $order->product_type === 'tabp' || $order->product_type === 'time'}
                            <div class="datagrid-item">
                                <div class="datagrid-title">Thời hạn sản phẩm (ngày)</div>
                                <div class="datagrid-content">{$order->content->time}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Thời hạn cấp (ngày)</div>
                                <div class="datagrid-content">{$order->content->class_time}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Cấp</div>
                                <div class="datagrid-content">{$order->content->class}</div>
                            </div>
                        {/if}
                        {if $order->product_type === 'tabp' || $order->product_type === 'bandwidth'}
                            <div class="datagrid-item">
                                <div class="datagrid-title">Lưu lượng khả dụng (GB)</div>
                                <div class="datagrid-content">{$order->content->bandwidth}</div>
                            </div>
                        {/if}
                        {if $order->product_type === 'tabp' || $order->product_type === 'time'}
                            <div class="datagrid-item">
                                <div class="datagrid-title">Nhóm người dùng</div>
                                <div class="datagrid-content">{$order->content->node_group}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Giới hạn tốc độ (Mbps)</div>
                                <div class="datagrid-content">
                                    {if $order->content->ip_limit === '0'}
                                        Không giới hạn
                                    {else}
                                        {$order->content->speed_limit}
                                    {/if}
                                </div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Giới hạn IP kết nối đồng thời</div>
                                <div class="datagrid-content">
                                    {if $order->content->ip_limit === '0'}
                                        Không giới hạn
                                    {else}
                                        {$order->content->ip_limit}
                                    {/if}
                                </div>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="card my-3">
                <div class="card-header">
                    <h3 class="card-title">Hóa đơn liên quan</h3>
                </div>
                <div class="card-body">
                    <div class="datagrid">
                        <div class="datagrid-item">
                            <div class="datagrid-title">Nội dung hóa đơn</div>
                            <div class="datagrid-content">
                                <div class="table-responsive">
                                    <table id="invoice_content_table" class="table table-vcenter card-table">
                                        <thead>
                                        <tr>
                                            <th>Tên</th>
                                            <th>Giá</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        {foreach $invoice->content as $invoice_content}
                                            <tr>
                                                <td>{$invoice_content->name}</td>
                                                <td>{$invoice_content->price|format_vnd:0} VNĐ</td>
                                            </tr>
                                        {/foreach}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Số tiền hóa đơn</div>
                            <div class="datagrid-content">{$invoice->price|format_vnd:0} VNĐ</div>
                        </div>
                        <div class="datagrid-item">
                            <div class="datagrid-title">Trạng thái hóa đơn</div>
                            <div class="datagrid-content">{$invoice->status}</div>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="cancel_order_confirm_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hủy đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p>
                            Xác nhận hủy đơn hàng này?
                        <p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                    <button id="confirm_cancel" type="button" class="btn btn-primary" data-bs-dismiss="modal">Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $("#confirm_cancel").click(function () {
            $.ajax({
                url: "/admin/order/{$order->id}/cancel",
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
    </script>

    {include file='admin/footer.tpl'}
