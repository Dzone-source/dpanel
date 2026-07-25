{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">创建订单</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">创建商品订单</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-12 col-md-6 col-lg-9">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">订单内容</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr hidden>
                                    <td>商品ID</td>
                                    <td id="product-id" class="text-end">{$product->id}</td>
                                </tr>
                                <tr>
                                    <td>商品名称</td>
                                    <td class="text-end">{$product->name}</td>
                                </tr>
                                <tr>
                                    <td>商品类型</td>
                                    <td class="text-end">{$product->type_text}</td>
                                </tr>
                                {if $product->has_options}
                                    <tr>
                                        <td>Chọn thời hạn</td>
                                        <td class="text-end" style="min-width:220px">
                                            <select id="option-index" class="form-select">
                                                {foreach from=$product_options item=opt}
                                                    <option value="{$opt.index}"
                                                            data-days="{$opt.days}"
                                                            data-price="{$opt.price}"
                                                            data-label="{$opt.label|escape:'html'}">
                                                        {$opt.label|escape:'html'} — {$opt.price}
                                                    </option>
                                                {/foreach}
                                            </select>
                                        </td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>商品时长</td>
                                        <td class="text-end"><span id="display-time">{$product->content->time}</span> 天</td>
                                    </tr>
                                    <tr>
                                        <td>等级时长</td>
                                        <td class="text-end"><span id="display-class-time">{$product->content->class_time}</span> 天</td>
                                    </tr>
                                    <tr>
                                        <td>等级</td>
                                        <td class="text-end">Lv. {$product->content->class}</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'bandwidth'}
                                    <tr>
                                        <td>可用流量</td>
                                        <td class="text-end">{$product->content->bandwidth} GB</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>速率限制</td>
                                        {if $product->content->speed_limit === '0' || $product->content->speed_limit === 0}
                                            <td class="text-end">不限制</td>
                                        {else}
                                            <td class="text-end">{$product->content->speed_limit} Mbps</td>
                                        {/if}
                                    </tr>
                                    <tr>
                                        <td>同时连接 IP 限制</td>
                                        {if $product->content->ip_limit === '0' || $product->content->ip_limit === 0}
                                            <td class="text-end">不限制</td>
                                        {else}
                                            <td class="text-end">{$product->content->ip_limit}</td>
                                        {/if}
                                    </tr>
                                {/if}
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-sm-12 col-md-6 col-lg-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">价格明细（元）</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr>
                                    <td>商品价格</td>
                                    <td class="text-end" id="product-base-price">{$product->price}</td>
                                </tr>
                                <tr>
                                    <td>优惠码</td>
                                    <td class="text-end" id="coupon-code"></td>
                                </tr>
                                <tr>
                                    <td>优惠金额</td>
                                    <td class="text-end" id="product-buy-discount"></td>
                                </tr>
                                <tr>
                                    <td>实际支付</td>
                                    <td class="text-end" id="product-buy-total">{$product->price}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="card my-3">
                        <div class="card-header">
                            <h3 class="card-title">优惠码</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="input-group mb-2">
                                    <input id="coupon" type="text" class="form-control"
                                           placeholder="填写优惠码，没有请留空">
                                    <button class="btn" type="button" id="apply-coupon-btn"
                                            hx-post="/user/coupon" hx-swap="none"
                                            hx-vals='js:{
                                                coupon: document.getElementById("coupon").value,
                                                product_id: {$product->id},
                                                option_index: (document.getElementById("option-index") ? document.getElementById("option-index").value : "")
                                            }'>
                                        应用
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card my-3">
                        <div class="card-body">
                            <button class="btn btn-primary w-100 my-3"
                                    hx-post="/user/order/create" hx-swap="none"
                                    hx-vals='js:{
                                        type: "product",
                                        coupon: document.getElementById("coupon").value,
                                        product_id: {$product->id},
                                        option_index: (document.getElementById("option-index") ? document.getElementById("option-index").value : "")
                                    }'>
                                创建订单
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
{literal}
(function () {
    var select = document.getElementById('option-index');
    if (!select) return;

    function syncOption() {
        var opt = select.options[select.selectedIndex];
        if (!opt) return;
        var days = opt.getAttribute('data-days');
        var price = opt.getAttribute('data-price');
        var timeEl = document.getElementById('display-time');
        var classTimeEl = document.getElementById('display-class-time');
        var basePriceEl = document.getElementById('product-base-price');
        var totalEl = document.getElementById('product-buy-total');
        if (timeEl) timeEl.textContent = days;
        if (classTimeEl) classTimeEl.textContent = days;
        if (basePriceEl) basePriceEl.textContent = price;
        if (totalEl) totalEl.textContent = price;
        var discountEl = document.getElementById('product-buy-discount');
        var couponCodeEl = document.getElementById('coupon-code');
        if (discountEl) discountEl.textContent = '';
        if (couponCodeEl) couponCodeEl.textContent = '';
    }

    select.addEventListener('change', syncOption);
    syncOption();
})();
{/literal}
</script>

    {include file='user/footer.tpl'}
