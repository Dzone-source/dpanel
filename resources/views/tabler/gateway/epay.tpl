<div class="card-inner">
    <h4>
        EPay
    </h4>
    <p class="card-heading"></p>
    <form class="epay" name="epay" method="post">
        {if $public_setting['epay_alipay']}
        <button class="btn btn-flat waves-attach gopass-busy-submit"
                type="button"
                hx-post="/user/payment/purchase/epay" hx-swap="none"
                hx-disabled-elt="this"
                data-gopass-busy-text="Đang chuyển..."
                data-gopass-keep-busy="1"
                hx-vals='js:{
                    invoice_id: {$invoice->id},
                    type: "alipay",
                    redir: window.location.href
                }'>
            <img src="/images/alipay.svg" height="50px"/>
        </button>
        {/if}
        {if $public_setting['epay_wechat']}
        <button class="btn btn-flat waves-attach gopass-busy-submit"
                type="button"
                hx-post="/user/payment/purchase/epay" hx-swap="none"
                hx-disabled-elt="this"
                data-gopass-busy-text="Đang chuyển..."
                data-gopass-keep-busy="1"
                hx-vals='js:{
                    invoice_id: {$invoice->id},
                    type: "wxpay",
                    redir: window.location.href
                }'>
            <img src="/images/wechat.svg" height="50px"/>
        </button>
        {/if}
        {if $public_setting['epay_qq']}
        <button class="btn btn-flat waves-attach gopass-busy-submit"
                type="button"
                hx-post="/user/payment/purchase/epay" hx-swap="none"
                hx-disabled-elt="this"
                data-gopass-busy-text="Đang chuyển..."
                data-gopass-keep-busy="1"
                hx-vals='js:{
                    invoice_id: {$invoice->id},
                    type: "qqpay",
                    redir: window.location.href
                }'>
            <img src="/images/qq.svg" height="50px"/>
        </button>
        {/if}
        {if $public_setting['epay_usdt']}
        <button class="btn btn-flat waves-attach gopass-busy-submit"
                type="button"
                hx-post="/user/payment/purchase/epay" hx-swap="none"
                hx-disabled-elt="this"
                data-gopass-busy-text="Đang chuyển..."
                data-gopass-keep-busy="1"
                hx-vals='js:{
                    invoice_id: {$invoice->id},
                    type: "usdt",
                    redir: window.location.href
                }'>
            <img src="/images/tether.svg" height="50px"/>
        </button>
        {/if}
    </form>
</div>
