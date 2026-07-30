<div class="card-inner">
    <h4>Chuyển khoản QR thủ công</h4>
    <p class="text-secondary">
        Quét mã QR bên dưới hoặc chuyển khoản thủ công với nội dung bắt buộc.
    </p>

    <div class="text-center mb-3 gopass-manual-qr-wrap">
        {$transfer_note = "INV{$invoice->id}"}
        {$manual_qr_bank_bin = $public_setting['manual_qr_bank_bin']|default:''}
        {$manual_qr_account_number = $public_setting['manual_qr_account_number']|default:''}
        {$manual_qr_account_name = $public_setting['manual_qr_account_name']|default:''}
        {$manual_qr_image_url = $public_setting['manual_qr_image_url']|default:''}
        {if isset($invoice_price_vnd)}
            {$amount_vnd = $invoice_price_vnd}
        {else}
            {$amount_vnd = "{$invoice->price|format_vnd:0} VNĐ"}
        {/if}
        {if isset($invoice_price_qr)}
            {$amount_qr = $invoice_price_qr}
        {else}
            {$amount_qr = $invoice->price|string_format:"%d"}
        {/if}
        {if $manual_qr_bank_bin !== '' && $manual_qr_account_number !== ''}
        <img src="https://img.vietqr.io/image/{$manual_qr_bank_bin}-{$manual_qr_account_number}-compact2.png?amount={$amount_qr}&addInfo={$transfer_note|escape:'url'}&accountName={$manual_qr_account_name|escape:'url'}"
             alt="Mã QR VietQR thanh toán" class="img-fluid rounded gopass-manual-qr-image">
        {elseif $manual_qr_image_url !== ''}
        <img src="{$manual_qr_image_url}" alt="Mã QR thanh toán" class="img-fluid rounded gopass-manual-qr-image">
        {else}
        <div class="alert alert-warning mb-0">
            Chưa cấu hình VietQR hoặc ảnh QR. Vào Admin → Cài đặt tài chính → Manual QR để điền mã ngân hàng + số tài khoản.
        </div>
        {/if}
    </div>

    <div class="gopass-manual-qr-meta">
        <div><strong>Ngân hàng:</strong> {$public_setting['manual_qr_bank_name']|default:'-'}</div>
        <div><strong>Số tài khoản:</strong> {$manual_qr_account_number|default:'-'}</div>
        <div><strong>Chủ tài khoản:</strong> {$manual_qr_account_name|default:'-'}</div>
        <div><strong>Số tiền:</strong> {$amount_vnd}</div>
        <div><strong>Mã đơn hàng:</strong> #{$invoice->id}</div>
        <div><strong>Nội dung chuyển khoản:</strong> {$transfer_note}</div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        Sau khi chuyển khoản, hóa đơn sẽ được quản trị viên xác nhận thủ công.
    </div>

    <button class="btn btn-primary w-100 mt-3 gopass-busy-submit"
            type="button"
            hx-post="/user/payment/purchase/manualqr" hx-swap="none"
            hx-disabled-elt="this"
            data-gopass-busy-text="Đang gửi xác nhận..."
            data-gopass-keep-busy="1"
            hx-confirm="Xác nhận bạn đã chuyển khoản đúng số tiền và nội dung INV{$invoice->id}?"
            hx-vals='js:{
                invoice_id: {$invoice->id},
                confirm_paid: "1"
            }'>
        Tôi đã chuyển khoản
    </button>
</div>
