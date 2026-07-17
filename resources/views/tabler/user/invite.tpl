{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Mời bạn đăng ký</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem liên kết mời và lịch sử hoàn tiền</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="row row-deck row-cards">
                        <div class="col-sm-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title">Quy tắc mời</h3>
                                    <ul>
                                        <li>Khi người được mời xác nhận hóa đơn, bạn nhận <code>{$invite_reward_rate}%</code>
                                            hoàn tiền từ số tiền hóa đơn
                                        </li>
                                        <li>Một số sản phẩm có tỷ lệ hoàn tiền khác</li>
                                    </ul>
                                    <p>Tổng hoàn tiền từ mời bạn bè hiện tại: <code>{$paybacks_sum}</code> VNĐ</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-lg-6">
                            <div class="card">
                                <div class="card-body">
                                    <h3 class="card-title">Liên kết mời</h3>
                                    <input class="form-control" id="invite-url" value="{$invite_url}" disabled>
                                </div>
                                <div class="card-footer">
                                    <div class="d-flex">
                                        <button class="btn text-red btn-link"
                                                hx-post="/user/invite/reset" hx-swap="none">
                                            Đặt lại
                                        </button>
                                        <button data-clipboard-text="{$invite_url}"
                                           class="copy btn btn-primary ms-auto">Sao chép</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 my-3">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Lịch sử hoàn tiền</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    <th>ID bản ghi</th>
                                    <th>ID người được mời</th>
                                    <th>Biệt danh người được mời</th>
                                    <th>Số tiền hoàn</th>
                                    <th>Thời gian hoàn tiền</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $paybacks as $payback}
                                    <tr>
                                        <td>{$payback->id}</td>
                                        <td>{$payback->userid}</td>
                                        <td>{$payback->user_name}</td>
                                        <td>{$payback->ref_get|format_vnd:0} VNĐ</td>
                                        <td>{$payback->datetime}</td>
                                    </tr>
                                {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='user/footer.tpl'}
