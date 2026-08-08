{include file='user/header.tpl'}

{function name=product_price price=0 show_from=false}
    <div class="gopass-product-price">
        {if $show_from}
            <span class="gopass-product-from">Từ</span>
        {/if}
        <span class="gopass-product-amount">{$price|format_vnd:0}</span>
        <span class="gopass-product-currency">VNĐ</span>
    </div>
{/function}

{function name=product_feature icon='' value='' label=''}
    <li class="gopass-product-feature">
        <span class="gopass-product-feature-icon" aria-hidden="true">
            <i class="ti {$icon}"></i>
        </span>
        <span class="gopass-product-feature-label">{$label}</span>
        <span class="gopass-product-feature-value">{$value}</span>
    </li>
{/function}

{function name=product_buy_btn id=0 stock=0}
    {if $stock == -1 || $stock > 0}
        <a href="/user/order/create?product_id={$id}" class="btn btn-primary w-100 gopass-product-buy">
            <i class="ti ti-shopping-cart"></i>
            Mua ngay
        </a>
    {else}
        <button type="button" class="btn btn-secondary w-100 gopass-product-buy" disabled>
            Hết hàng
        </button>
    {/if}
{/function}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Cửa hàng</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chọn gói phù hợp và kích hoạt ngay</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="gopass-shop">
                <ul class="nav gopass-shop-tabs" data-bs-toggle="tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a href="#tabp" class="gopass-shop-tab active" data-bs-toggle="tab" role="tab" aria-selected="true">
                            <i class="ti ti-package"></i>
                            <span>Thời gian + lưu lượng</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#bandwidth" class="gopass-shop-tab" data-bs-toggle="tab" role="tab" aria-selected="false">
                            <i class="ti ti-database"></i>
                            <span>Lưu lượng</span>
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a href="#time" class="gopass-shop-tab" data-bs-toggle="tab" role="tab" aria-selected="false">
                            <i class="ti ti-clock"></i>
                            <span>Thời gian</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active show" id="tabp" role="tabpanel">
                        {if $tabps|@count > 0}
                            <div class="row g-3 g-lg-4">
                                {foreach $tabps as $tabp}
                                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                                        <article class="gopass-product-card">
                                            <div class="gopass-product-card-body">
                                                <header class="gopass-product-header">
                                                    <div class="gopass-product-name">{$tabp->name}</div>
                                                    {product_price price=$tabp->price_min show_from=$tabp->has_options}
                                                    {if $tabp->has_options}
                                                        <p class="gopass-product-note">Nhiều thời hạn — chọn khi mua</p>
                                                    {/if}
                                                </header>
                                                <ul class="gopass-product-features">
                                                    {product_feature icon='ti-crown' value="Lv. `$tabp->content->class`" label='Cấp độ'}
                                                    {if $tabp->has_options}
                                                        {product_feature icon='ti-calendar' value='Tùy chọn khi mua' label='Thời hạn'}
                                                    {else}
                                                        {product_feature icon='ti-calendar' value="`$tabp->content->class_time` ngày" label='Thời hạn'}
                                                    {/if}
                                                    {product_feature icon='ti-database' value="`$tabp->content->bandwidth` GB" label='Lưu lượng'}
                                                    {if $tabp->content->speed_limit == '0'}
                                                        {product_feature icon='ti-bolt' value='Không giới hạn' label='Tốc độ'}
                                                    {else}
                                                        {product_feature icon='ti-bolt' value="`$tabp->content->speed_limit` Mbps" label='Tốc độ'}
                                                    {/if}
                                                    {if $tabp->content->ip_limit == '0'}
                                                        {product_feature icon='ti-devices' value='Không giới hạn' label='Thiết bị'}
                                                    {else}
                                                        {product_feature icon='ti-devices' value="`$tabp->content->ip_limit` thiết bị" label='Thiết bị'}
                                                    {/if}
                                                </ul>
                                                {product_buy_btn id=$tabp->id stock=$tabp->stock}
                                            </div>
                                        </article>
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            <div class="gopass-shop-empty">
                                <i class="ti ti-package-off"></i>
                                <p>Chưa có gói trong danh mục này</p>
                            </div>
                        {/if}
                    </div>

                    <div class="tab-pane" id="bandwidth" role="tabpanel">
                        {if $bandwidths|@count > 0}
                            <div class="row g-3 g-lg-4">
                                {foreach $bandwidths as $bandwidth}
                                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                                        <article class="gopass-product-card">
                                            <div class="gopass-product-card-body">
                                                <header class="gopass-product-header">
                                                    <div class="gopass-product-name">{$bandwidth->name}</div>
                                                    {product_price price=$bandwidth->price}
                                                </header>
                                                <ul class="gopass-product-features">
                                                    {product_feature icon='ti-database' value="`$bandwidth->content->bandwidth` GB" label='Lưu lượng'}
                                                </ul>
                                                {product_buy_btn id=$bandwidth->id stock=$bandwidth->stock}
                                            </div>
                                        </article>
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            <div class="gopass-shop-empty">
                                <i class="ti ti-database-off"></i>
                                <p>Chưa có gói trong danh mục này</p>
                            </div>
                        {/if}
                    </div>

                    <div class="tab-pane" id="time" role="tabpanel">
                        {if $times|@count > 0}
                            <div class="row g-3 g-lg-4">
                                {foreach $times as $time}
                                    <div class="col-12 col-sm-6 col-xl-4 col-xxl-3">
                                        <article class="gopass-product-card">
                                            <div class="gopass-product-card-body">
                                                <header class="gopass-product-header">
                                                    <div class="gopass-product-name">{$time->name}</div>
                                                    {product_price price=$time->price_min show_from=$time->has_options}
                                                    {if $time->has_options}
                                                        <p class="gopass-product-note">Nhiều thời hạn — chọn khi mua</p>
                                                    {/if}
                                                </header>
                                                <ul class="gopass-product-features">
                                                    {product_feature icon='ti-crown' value="Lv. `$time->content->class`" label='Cấp độ'}
                                                    {if $time->has_options}
                                                        {product_feature icon='ti-calendar' value='Tùy chọn khi mua' label='Thời hạn'}
                                                    {else}
                                                        {product_feature icon='ti-calendar' value="`$time->content->class_time` ngày" label='Thời hạn'}
                                                    {/if}
                                                    {if $time->content->speed_limit == '0'}
                                                        {product_feature icon='ti-bolt' value='Không giới hạn' label='Tốc độ'}
                                                    {else}
                                                        {product_feature icon='ti-bolt' value="`$time->content->speed_limit` Mbps" label='Tốc độ'}
                                                    {/if}
                                                    {if $time->content->ip_limit == '0'}
                                                        {product_feature icon='ti-devices' value='Không giới hạn' label='Thiết bị'}
                                                    {else}
                                                        {product_feature icon='ti-devices' value="`$time->content->ip_limit` thiết bị" label='Thiết bị'}
                                                    {/if}
                                                </ul>
                                                {product_buy_btn id=$time->id stock=$time->stock}
                                            </div>
                                        </article>
                                    </div>
                                {/foreach}
                            </div>
                        {else}
                            <div class="gopass-shop-empty">
                                <i class="ti ti-clock-off"></i>
                                <p>Chưa có gói trong danh mục này</p>
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
