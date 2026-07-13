{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Thông tin tài khoản</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem lịch sử đăng nhập và sử dụng gần đây</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Email tài khoản</div>
                            </div>
                            <div class="h1 mb-3">{$user->email}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Tên người dùng</div>
                            </div>
                            <div class="h1 mb-3">{$user->user_name}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Thời gian đăng ký</div>
                            </div>
                            <div class="h1 mb-3">{$user->reg_date}</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="subheader">Tổng lưu lượng đã sử dụng</div>
                            </div>
                            <div class="h1 mb-3">{$user->totalTraffic()}</div>
                        </div>
                    </div>
                </div>
            </div>
            {if $public_setting['subscribe_log']}
            <div class="row row-deck my-3">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">10 lần đăng ký node gần nhất</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>Loại</th>
                                        <th>UA</th>
                                        <th>IP</th>
                                        <th>Vị trí IP</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $subs as $sub}
                                    <tr>
                                        <td>{$sub->type}</td>
                                        <td>{$sub->request_user_agent}</td>
                                        <td>{$sub->request_ip}</td>
                                        <td>{$sub->location}</td>
                                        <td>{$sub->request_time}</td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            {/if}
            <div class="row row-deck my-3">
                {if $public_setting['login_log']}
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">10 lần đăng nhập thành công gần nhất</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Vị trí IP</th>
                                        <th>Thời gian</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $logins as $login}
                                    <tr>
                                        <td>{$login->ip}</td>
                                        <td>{$login->location}</td>
                                        <td>{$login->datetime}</td>
                                    </tr>
                                    {/foreach}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                {/if}
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">IP đang trực tuyến</h3>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter text-nowrap card-table">
                                <thead>
                                    <tr>
                                        <th>IP</th>
                                        <th>Vị trí IP</th>
                                        <th>Tên máy chủ</th>
                                        <th>Lần trực tuyến cuối</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {foreach $ips as $ip}
                                    <tr>
                                        <td>{$ip->ip}</td>
                                        <td>{$ip->location}</td>
                                        <td>{$ip->node_name}</td>
                                        <td>{$ip->last_time}</td>
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
