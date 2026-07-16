<div class="card-inner">
    <h4>Chuyển khoản QR thủ công</h4>
    <p class="text-secondary">
        Quét mã QR bên dưới hoặc chuyển khoản thủ công với nội dung bắt buộc.
    </p>

    <div class="text-center mb-3">
        {$transfer_note = "INV{$invoice->id}"}
        {if $public_setting['manual_qr_bank_bin'] !== '' && $public_setting['manual_qr_account_number'] !== ''}
        <img src="https://img.vietqr.io/image/{$public_setting['manual_qr_bank_bin']}-{$public_setting['manual_qr_account_number']}-compact2.png?amount={$invoice->price}&addInfo={$transfer_note|escape:'url'}&accountName={$public_setting['manual_qr_account_name']|escape:'url'}"
             alt="Mã QR VietQR thanh toán" class="img-fluid rounded" style="max-width: 240px;">
        {elseif $public_setting['manual_qr_image_url'] !== ''}
        <img src="{$public_setting['manual_qr_image_url']}" alt="Mã QR thanh toán" class="img-fluid rounded" style="max-width: 240px;">
        {else}
        <div class="alert alert-warning mb-0">
            Chưa cấu hình VietQR hoặc ảnh QR. Vui lòng liên hệ quản trị viên.
        </div>
        {/if}
    </div>

    <div class="small">
        <div><strong>Ngân hàng:</strong> {$public_setting['manual_qr_bank_name']|default:'-'}</div>
        <div><strong>Số tài khoản:</strong> {$public_setting['manual_qr_account_number']|default:'-'}</div>
        <div><strong>Chủ tài khoản:</strong> {$public_setting['manual_qr_account_name']|default:'-'}</div>
        <div><strong>Số tiền:</strong> {$invoice->price} VND</div>
        <div><strong>Mã đơn hàng:</strong> #{$invoice->id}</div>
        <div><strong>Nội dung chuyển khoản:</strong> {$transfer_note}</div>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        Sau khi chuyển khoản, hóa đơn sẽ được quản trị viên xác nhận thủ công.
    </div>

    <button class="btn btn-primary w-100 mt-3"
            hx-post="/user/payment/purchase/manualqr" hx-swap="none"
            hx-confirm="Xác nhận bạn đã chuyển khoản đúng số tiền và nội dung INV{$invoice->id}?"
            hx-vals='js:{
                invoice_id: {$invoice->id},
                confirm_paid: "1"
            }'>
        Tôi đã chuyển khoản
    </button>
</div>
