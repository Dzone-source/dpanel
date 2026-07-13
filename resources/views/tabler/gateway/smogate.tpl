<div class="card-inner">
    <h4>
        Thanh toán Alipay trực tiếp
    </h4>
    <p class="card-heading"></p>
    <input hidden id="amount-smogate" name="amount-smogate" value="{$invoice->price}">
    <input hidden id="invoice_id" name="invoice_id" value="{$invoice->id}">
    <div id="smogate-qrcode"></div>
    <button class="btn btn-flat waves-attach" id="smogate-button" type="button" onclick="smogate();">
        Nạp tiền
    </button>
</div>

<script>
    let pid = 0;
    let flag = false;
    let paymentButton = $('#smogate-button');

    function smogate() {
        paymentButton.attr('disabled', true);
        $.ajax({
            type: "POST",
            url: "/user/payment/purchase/smogate",
            dataType: "json",
            data: {
                amount: $('#amount-smogate').val(),
                invoice_id: $('#invoice_id').val(),
            },
            success: (data) => {
                paymentButton.attr('disabled', false);
                if (data.ret === 1) {
                    pid = data.pid;
                    paymentButton.remove();
                    paymentButton.append('<div class="text-center"><p>Quét Alipay để thanh toán</p></div>');
                    new QRCode("smogate-qrcode", {
                        render: "canvas",
                        width: 200,
                        height: 200,
                        text: encodeURI(data.qrcode)
                    });
                    
                    paymentButton.append('<div class="text-center my-3"><p>Sau khi thanh toán thành công, vui lòng làm mới trang thủ công</p></div>');
                    paymentButton.attr('href', data.qrcode);
                } else {
                    $('#fail-message').text(data.msg);
                    $('#fail-dialog').modal('show');
                }
            },
            error: () => {
                paymentButton.attr('disabled', false);
            }
        })
    }
</script>