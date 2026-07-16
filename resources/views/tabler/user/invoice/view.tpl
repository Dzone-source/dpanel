{include file='user/header.tpl'}

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
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
                <div class="col-sm-12 col-md-6 col-lg-9">
                {else}
                <div class="col-md-12">
                {/if}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin cơ bản</h3>
                        </div>
                        <div class="card-body">
                            <div class="datagrid">
                                <div class="datagrid-item">
                                    <div class="datagrid-title">ID đơn hàng</div>
                                    <div class="datagrid-content">{$invoice->order_id}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Số tiền đơn hàng</div>
                                    <div class="datagrid-content">{$invoice->price}</div>
                                </div>
                                <div class="datagrid-item">
                                    <div class="datagrid-title">Trạng thái đơn hàng</div>
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
                                    <div class="datagrid-title">Mã giao dịch cổng thanh toán</div>
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
                                            <td>{$invoice_content_detail->price}</td>
                                        </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="card">
                        <ul class="nav nav-tabs nav-fill" data-bs-toggle="tabs">
                            {if $invoice->type !== 'topup'}
                            <li class="nav-item">
                                <a href="#balance" class="nav-link active" data-bs-toggle="tab">
                                    <i class="ti ti-coins icon"></i>
                                    &nbsp;Thanh toán bằng số dư
                                </a>
                            </li>
                            {/if}
                            {if count($payments) > 0}
                            <li class="nav-item">
                                <a href="#gateway" class="nav-link{if $invoice->type === 'topup'} active{/if}" data-bs-toggle="tab">
                                    <i class="ti ti-coin icon"></i>
                                    &nbsp;Thanh toán qua cổng
                                </a>
                            </li>
                            {/if}
                        </ul>
                        <div class="card-body">
                            <div class="tab-content">
                                {if $invoice->type !== 'topup'}
                                <div class="tab-pane active show" id="balance">
                                    <div class="mb-3">
                                        Số dư khả dụng: <code>{$user->money}</code> VND
                                    </div>
                                    <div class="d-flex">
                                        <button class="btn btn-primary" type="button"
                                                hx-post="/user/invoice/pay_balance" hx-swap="none"
                                                hx-vals='js:{
                                                    invoice_id: {$invoice->id},
                                                }'>
                                            Thanh toán
                                        </button>
                                    </div>
                                </div>
                                {/if}
                                {if count($payments) > 0}
                                <div class="tab-pane{if $invoice->type === 'topup'} active{/if} show" id="gateway">
                                    {foreach from=$payments item=payment}
                                    <div class="mb-3">
                                        {$payment_name = $payment::_name()}
                                        {include file="../../gateway/$payment_name.tpl"}
                                    </div>
                                    {/foreach}
                                </div>
                                {/if}
                                {if $invoice->type === 'topup' && count($payments) === 0}
                                <div class="alert alert-warning mb-0">
                                    Chưa có phương thức thanh toán.
                                    Vui lòng vào Admin → Cài đặt tài chính → bật
                                    <strong>Chuyển khoản QR thủ công</strong>
                                    và điền mã ngân hàng + số tài khoản VietQR.
                                </div>
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
                {/if}
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
