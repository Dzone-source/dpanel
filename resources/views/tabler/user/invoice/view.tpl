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
                <div class="col-12">
                    <div class="alert alert-info" id="gopass-invoice-wait-hint" role="status">
                        Trang sẽ tự cập nhật khi hóa đơn được xác nhận thanh toán.
                    </div>
                </div>
                {/if}
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
                                    <div class="datagrid-content">{$invoice_price_vnd|default:$invoice->price}</div>
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
                                            <td>{$invoice_content_detail->price|format_vnd:0} VNĐ</td>
                                        </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
                {$can_pay_balance = $invoice->type !== 'topup'}
                {$can_pay_gateway = count($payments) > 0}
                {$balance_enough = $user->money >= $invoice->price}
                {$default_pay = 'gateway'}
                {if $can_pay_balance}
                    {$default_pay = 'balance'}
                {/if}
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="card gopass-pay-card">
                        <div class="card-body gopass-pay-body">
                            <div class="gopass-pay-due">
                                <span class="gopass-pay-due-label">Số tiền cần thanh toán</span>
                                <strong class="gopass-pay-due-value">{$invoice_price_vnd|default:$invoice->price}</strong>
                            </div>

                            {if $can_pay_balance || $can_pay_gateway}
                                <p class="gopass-pay-choose-label">Chọn cách thanh toán</p>
                                <div class="gopass-pay-methods" role="radiogroup" aria-label="Chọn phương thức thanh toán">
                                    {if $can_pay_balance}
                                        <button type="button"
                                                class="gopass-pay-method{if $default_pay === 'balance'} is-active{/if}"
                                                data-gopass-pay="balance"
                                                aria-pressed="{if $default_pay === 'balance'}true{else}false{/if}">
                                            <span class="gopass-pay-method-radio" aria-hidden="true"></span>
                                            <span class="gopass-pay-method-icon" aria-hidden="true">
                                                <i class="ti ti-wallet"></i>
                                            </span>
                                            <span class="gopass-pay-method-copy">
                                                <span class="gopass-pay-method-title">Thanh toán bằng số dư</span>
                                                <span class="gopass-pay-method-desc">Trừ ngay từ tài khoản</span>
                                            </span>
                                        </button>
                                    {/if}
                                    {if $can_pay_gateway}
                                        <button type="button"
                                                class="gopass-pay-method{if $default_pay === 'gateway'} is-active{/if}"
                                                data-gopass-pay="gateway"
                                                aria-pressed="{if $default_pay === 'gateway'}true{else}false{/if}">
                                            <span class="gopass-pay-method-radio" aria-hidden="true"></span>
                                            <span class="gopass-pay-method-icon" aria-hidden="true">
                                                <i class="ti ti-qrcode"></i>
                                            </span>
                                            <span class="gopass-pay-method-copy">
                                                <span class="gopass-pay-method-title">Thanh toán qua cổng</span>
                                                <span class="gopass-pay-method-desc">Chuyển khoản / QR</span>
                                            </span>
                                        </button>
                                    {/if}
                                </div>

                                {if $can_pay_balance}
                                    <div class="gopass-pay-panel{if $default_pay === 'balance'} is-active{/if}" data-gopass-panel="balance">
                                        <div class="gopass-pay-balance{if $balance_enough} is-enough{elseif $user->money > 0} is-partial{else} is-empty{/if}">
                                            <span>Số dư khả dụng</span>
                                            <strong>{$user->displayMoney()} VNĐ</strong>
                                        </div>
                                        {if $balance_enough}
                                            <p class="gopass-pay-hint">Số dư đủ để thanh toán đầy đủ hóa đơn này.</p>
                                            <button class="btn btn-primary w-100 gopass-pay-submit" type="button"
                                                    hx-post="/user/invoice/pay_balance" hx-swap="none"
                                                    hx-disabled-elt="this"
                                                    hx-vals='js:{ invoice_id: {$invoice->id} }'>
                                                Thanh toán bằng số dư
                                            </button>
                                        {elseif $user->money > 0}
                                            <p class="gopass-pay-hint">Số dư chưa đủ. Bạn có thể thanh toán một phần bằng số dư, phần còn lại dùng cổng thanh toán.</p>
                                            <button class="btn btn-primary w-100 gopass-pay-submit" type="button"
                                                    hx-post="/user/invoice/pay_balance" hx-swap="none"
                                                    hx-disabled-elt="this"
                                                    hx-vals='js:{ invoice_id: {$invoice->id} }'>
                                                Thanh toán một phần bằng số dư
                                            </button>
                                        {else}
                                            <p class="gopass-pay-hint">Bạn chưa có số dư. Hãy chọn thanh toán qua cổng hoặc nạp tiền trước.</p>
                                            {if $can_pay_gateway}
                                                <button type="button" class="btn btn-outline-primary w-100" data-gopass-pay-switch="gateway">
                                                    Chọn thanh toán qua cổng
                                                </button>
                                            {/if}
                                        {/if}
                                    </div>
                                {/if}

                                {if $can_pay_gateway}
                                    <div class="gopass-pay-panel{if $default_pay === 'gateway'} is-active{/if}" data-gopass-panel="gateway">
                                        <p class="gopass-pay-hint mb-3">Làm theo hướng dẫn bên dưới để hoàn tất thanh toán.</p>
                                        {foreach from=$payments item=payment}
                                            <div class="gopass-pay-gateway-item">
                                                {$payment_name = $payment::_name()}
                                                {include file="gateway/$payment_name.tpl"}
                                            </div>
                                        {/foreach}
                                    </div>
                                {/if}
                            {else}
                                <div class="alert alert-warning mb-0">
                                    {if $invoice->type === 'topup'}
                                        Chưa có phương thức thanh toán.
                                        Vui lòng vào Admin → Cài đặt tài chính → bật
                                        <strong>Chuyển khoản QR thủ công</strong>
                                        và điền mã ngân hàng + số tài khoản VietQR.
                                    {else}
                                        Hiện chưa có phương thức thanh toán khả dụng. Vui lòng liên hệ hỗ trợ.
                                    {/if}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>
                {/if}
            </div>
        </div>
    </div>

    {if $invoice->status === 'unpaid' || $invoice->status === 'partially_paid'}
    <script>
    (function () {
        const invoiceId = {$invoice->id};
        const POLL_MS = 10000;
        let busy = false;

        async function pollInvoiceStatus() {
            if (busy || document.hidden) return;
            busy = true;
            try {
                const res = await fetch('/user/invoice/' + invoiceId + '/status', {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.ret === 1 && data.paid) {
                    window.location.reload();
                }
            } catch (e) {
                // ignore
            } finally {
                busy = false;
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) pollInvoiceStatus();
        });

        setInterval(pollInvoiceStatus, POLL_MS);
    })();
    </script>
    {/if}

    {include file='user/footer.tpl'}
