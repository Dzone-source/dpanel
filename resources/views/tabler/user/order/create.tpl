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
                    <div class="card">
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
                                {if $product->type === 'tabp' || $product->type === 'time'}
                                    <tr>
                                        <td>Thời hạn sản phẩm</td>
                                        <td class="text-end">{$product->content->time} ngày</td>
                                    </tr>
                                    <tr>
                                        <td>Thời hạn cấp độ</td>
                                        <td class="text-end">{$product->content->class_time} ngày</td>
                                    </tr>
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
                                        {if $product->content->speed_limit === '0'}
                                            <td class="text-end">Không giới hạn</td>
                                        {else}
                                            <td class="text-end">{$product->content->speed_limit} Mbps</td>
                                        {/if}
                                    </tr>
                                    <tr>
                                        <td>Giới hạn IP kết nối đồng thời</td>
                                        {if $product->content->ip_limit === '0'}
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
                                    <td class="text-end">{$product->price|format_vnd:0} VNĐ</td>
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
                                    <button class="btn" type="button"
                                            hx-post="/user/coupon" hx-swap="none"
                                            hx-vals='js:{
                                                coupon: document.getElementById("coupon").value,
                                                product_id: {$product->id},
                                            }'>
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
                                    hx-vals='js:{
                                        type: "product",
                                        coupon: document.getElementById("coupon").value,
                                        product_id: {$product->id},
                                    }'>
                                Tạo đơn hàng
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
