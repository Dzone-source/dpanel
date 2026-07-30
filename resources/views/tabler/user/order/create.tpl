{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Tạo đơn hàng</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Tạo đơn hàng sản phẩm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-sm-12 col-md-6 col-lg-9">
                    <div class="card gopass-order-main-card">
                        <div class="card-header">
                            <h3 class="card-title">Nội dung đơn hàng</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr hidden>
                                    <td>ID sản phẩm</td>
                                    <td id="product-id" class="text-end">{$product->id}</td>
                                </tr>
                                <tr>
                                    <td>Tên sản phẩm</td>
                                    <td class="text-end">{$product->name}</td>
                                </tr>
                                <tr>
                                    <td>Loại sản phẩm</td>
                                    <td class="text-end">{$product->type_text}</td>
                                </tr>
                                {if $product->has_options}
                                    <tr>
                                        <td colspan="2" class="gopass-order-options-cell">
                                            <div class="gopass-order-options-label">Chọn thời hạn</div>
                                            <input type="hidden" id="option-index" value="0">
                                            <div class="gopass-order-options" role="radiogroup" aria-label="Chọn thời hạn">
                                                {foreach from=$product_options item=opt name=prod_opts}
                                                    <button type="button"
                                                            class="gopass-order-option{if $smarty.foreach.prod_opts.first} is-selected{/if}"
                                                            role="radio"
                                                            aria-checked="{if $smarty.foreach.prod_opts.first}true{else}false{/if}"
                                                            data-index="{$opt.index}"
                                                            data-days="{$opt.days}"
                                                            data-price="{$opt.price}"
                                                            data-label="{$opt.label|escape:'html'}">
                                                        <span class="gopass-order-option-left">
                                                            {if $opt.label != '' && $opt.label != $opt.days && $opt.label != ($opt.days|cat:' ngày')}
                                                                <span class="gopass-order-option-label">{$opt.label|escape:'html'}</span>
                                                                <span class="gopass-order-option-days">{$opt.days} ngày</span>
                                                            {else}
                                                                <span class="gopass-order-option-label">{$opt.days} ngày</span>
                                                            {/if}
                                                        </span>
                                                        <span class="gopass-order-option-price">{$opt.price|format_vnd:0} VNĐ</span>
                                                    </button>
                                                {/foreach}
                                            </div>
                                        </td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    {if $product->has_options}
                                        <span id="display-time" hidden>{$product->content->time}</span>
                                        <span id="display-class-time" hidden>{$product->content->class_time}</span>
                                    {else}
                                        <tr>
                                            <td>Thời hạn sản phẩm</td>
                                            <td class="text-end"><span id="display-time">{$product->content->time}</span> ngày</td>
                                        </tr>
                                        <tr>
                                            <td>Thời hạn cấp độ</td>
                                            <td class="text-end"><span id="display-class-time">{$product->content->class_time}</span> ngày</td>
                                        </tr>
                                    {/if}
                                    <tr>
                                        <td>Cấp độ</td>
                                        <td class="text-end">Lv. {$product->content->class}</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'bandwidth'}
                                    <tr>
                                        <td>Lưu lượng khả dụng</td>
                                        <td class="text-end">{$product->content->bandwidth} GB</td>
                                    </tr>
                                {/if}
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>Giới hạn tốc độ</td>
                                        {if $product->content->speed_limit === '0' || $product->content->speed_limit === 0}
                                            <td class="text-end">Không giới hạn</td>
                                        {else}
                                            <td class="text-end">{$product->content->speed_limit} Mbps</td>
                                        {/if}
                                    </tr>
                                    <tr>
                                        <td>Giới hạn IP kết nối đồng thời</td>
                                        {if $product->content->ip_limit === '0' || $product->content->ip_limit === 0}
                                            <td class="text-end">Không giới hạn</td>
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
                            <h3 class="card-title">Chi tiết giá (VND)</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr>
                                    <td>Giá sản phẩm</td>
                                    <td class="text-end" id="product-base-price">{$product->price|format_vnd:0} VNĐ</td>
                                </tr>
                                <tr>
                                    <td>Mã giảm giá</td>
                                    <td class="text-end" id="coupon-code"></td>
                                </tr>
                                <tr>
                                    <td>Số tiền giảm</td>
                                    <td class="text-end" id="product-buy-discount"></td>
                                </tr>
                                <tr>
                                    <td>Thanh toán thực tế</td>
                                    <td class="text-end" id="product-buy-total">{$product->price|format_vnd:0} VNĐ</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="card my-3">
                        <div class="card-header">
                            <h3 class="card-title">Mã giảm giá</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="input-group mb-2">
                                    <input id="coupon" type="text" class="form-control"
                                           placeholder="Nhập mã giảm giá, để trống nếu không có">
                                    <button class="btn" type="button" id="apply-coupon-btn"
                                            hx-post="/user/coupon" hx-swap="none"
                                            hx-vals='js:{"coupon": document.getElementById("coupon").value, "product_id": {$product->id}, "option_index": (document.getElementById("option-index") ? document.getElementById("option-index").value : ""), "option_days": (document.getElementById("option-index") && document.querySelector(".gopass-order-option.is-selected") ? document.querySelector(".gopass-order-option.is-selected").getAttribute("data-days") : "")}'>
                                        Áp dụng
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card my-3">
                        <div class="card-body">
                            <button class="btn btn-primary w-100 my-3"
                                    hx-post="/user/order/create" hx-swap="none"
                                    hx-vals='js:{"type": "product", "coupon": document.getElementById("coupon").value, "product_id": {$product->id}, "option_index": (document.getElementById("option-index") ? document.getElementById("option-index").value : ""), "option_days": (document.querySelector(".gopass-order-option.is-selected") ? document.querySelector(".gopass-order-option.is-selected").getAttribute("data-days") : "")}'>
                                Tạo đơn hàng
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
    var input = document.getElementById('option-index');
    var buttons = document.querySelectorAll('.gopass-order-option');
    if (!input || !buttons.length) return;

    function formatVnd(n) {
        var num = Number(n);
        if (isNaN(num)) return String(n);
        return num.toLocaleString('vi-VN') + ' VNĐ';
    }

    function applyOption(btn) {
        if (!btn) return;
        var days = btn.getAttribute('data-days');
        var price = btn.getAttribute('data-price');
        var index = btn.getAttribute('data-index');
        input.value = index;

        buttons.forEach(function (el) {
            var on = el === btn;
            el.classList.toggle('is-selected', on);
            el.setAttribute('aria-checked', on ? 'true' : 'false');
        });

        var timeEl = document.getElementById('display-time');
        var classTimeEl = document.getElementById('display-class-time');
        var basePriceEl = document.getElementById('product-base-price');
        var totalEl = document.getElementById('product-buy-total');
        if (timeEl) timeEl.textContent = days;
        if (classTimeEl) classTimeEl.textContent = days;
        if (basePriceEl) basePriceEl.textContent = formatVnd(price);
        if (totalEl) totalEl.textContent = formatVnd(price);
        var discountEl = document.getElementById('product-buy-discount');
        var couponCodeEl = document.getElementById('coupon-code');
        if (discountEl) discountEl.textContent = '';
        if (couponCodeEl) couponCodeEl.textContent = '';
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            applyOption(btn);
        });
    });

    var selected = document.querySelector('.gopass-order-option.is-selected') || buttons[0];
    applyOption(selected);
})();
{/literal}
</script>

    {include file='user/footer.tpl'}
