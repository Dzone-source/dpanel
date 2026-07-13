{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Trạng thái hệ thống</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem trạng thái hoạt động của hệ thống</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-12 col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <table class="table table-transparent table-responsive">
                                <tr>
                                    <td>Phiên bản DPanel</td>
                                    <td class="text-end" id="version"><a href="#" id="version_check">{$version} </a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Phiên bản cơ sở dữ liệu</td>
                                    <td class="text-end">{$db_version}</td>
                                </tr>
                                <tr>
                                    <td>Thời gian thực thi tác vụ hàng ngày gần nhất</td>
                                    <td class="text-end">{$last_daily_job_time}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $('#version_check').click(function () {
            $.ajax({
                url: '/admin/system/check_update',
                type: 'POST',
                dataType: "json",
                success: function (data) {
                    if (data.is_upto_date) {
                        $('.badge').remove();
                        $('#version').append('<span class="badge bg-green text-green-fg">Đã là phiên bản mới nhất</span>');
                    } else {
                        $('.badge').remove();
                        $('#version').append('<span class="badge bg-red text-red-fg">Có phiên bản mới ' + data.latest_version + ' khả dụng</span>');
                    }
                }
            })
        });
    </script>

    {include file='admin/footer.tpl'}
