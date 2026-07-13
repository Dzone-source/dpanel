{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="modal modal-blur fade" id="search-gateway" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tìm đơn hàng</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3 row">
                        <label class="form-label col-3 col-form-label">Mã đơn hàng cổng thanh toán</label>
                        <div class="col">
                            <input id="gateway_order_id" type="text" class="form-control"
                                   placeholder="">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                    <button id="create-button" 
                        type="button" 
                        class="btn btn-primary" 
                        hx-post="/admin/order/search" 
                        hx-swap="none"
                        hx-vals='js:{
                            gateway_order_id: document.getElementById("gateway_order_id").value
                        }'>Tìm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Danh sách đơn hàng</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Quản lý đơn hàng khách hàng</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        <a href="#" class="btn btn-primary" data-bs-toggle="modal"
                           data-bs-target="#search-gateway">
                            <i class="icon ti ti-search"></i>
                            Tìm
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table id="data-table" class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    {foreach $details['field'] as $key => $value}
                                        <th>{$value}</th>
                                    {/foreach}
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='datatable.tpl'}

    <script>
        tableConfig.ajax = {
            url: '/admin/order/ajax',
            type: 'POST',
            dataSrc: 'orders'
        };
        tableConfig.order = [
            [1, 'desc']
        ];
        tableConfig.columnDefs = [
            {
                targets: [0],
                orderable: false
            }
        ];

        let table = new DataTable('#data-table', tableConfig);

        function loadTable() {
            table;
        }

        function deleteOrder(order_id) {
            $('#notice-message').text('Bạn có chắc muốn xóa đơn hàng này?');
            $('#notice-dialog').modal('show');
            $('#notice-confirm').off('click').on('click', function () {
                $.ajax({
                    url: "/admin/order/" + order_id,
                    type: 'DELETE',
                    dataType: "json",
                    success: function (data) {
                        if (data.ret === 1) {
                            $('#success-message').text(data.msg);
                            $('#success-dialog').modal('show');
                            reloadTableAjax();
                        } else {
                            $('#fail-message').text(data.msg);
                            $('#fail-dialog').modal('show');
                        }
                    }
                })
            });
        }

        function cancelOrder(order_id) {
            $('#notice-message').text('Bạn có chắc muốn hủy đơn hàng này? Nếu hóa đơn liên quan đã thanh toán, số tiền sẽ được hoàn vào số dư người dùng.');
            $('#notice-dialog').modal('show');
            $('#notice-confirm').off('click').on('click', function () {
                $.ajax({
                    url: "/admin/order/" + order_id + "/cancel",
                    type: 'POST',
                    dataType: "json",
                    success: function (data) {
                        if (data.ret === 1) {
                            $('#success-message').text(data.msg);
                            $('#success-dialog').modal('show');
                            reloadTableAjax();
                        } else {
                            $('#fail-message').text(data.msg);
                            $('#fail-dialog').modal('show');
                        }
                    }
                })
            });
        }

        function reloadTableAjax() {
            table.ajax.reload(null, false);
        }

        loadTable();
    </script>

    {include file='admin/footer.tpl'}
