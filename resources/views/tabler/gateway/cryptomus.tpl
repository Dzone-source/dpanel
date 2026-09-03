<div class="card-inner">
    <h4>
        Cryptomus
    </h4>
    <p class="card-heading"></p>
    <form class="cryptomus" name="cryptomus" method="post">
        <button class="btn btn-flat waves-attach gopass-busy-submit"
                type="button"
                hx-post="/user/payment/purchase/cryptomus" hx-swap="none"
                hx-disabled-elt="this"
                data-gopass-busy-text="Đang chuyển..."
                data-gopass-keep-busy="1"
                hx-vals='js:{
                    price: {$invoice->price},
                    invoice_id: {$invoice->id},
                    type: "cryptomus",
                    redir: window.location.href
                }'>
            <span>Pay</span>
        </button>
    </form>
</div>
