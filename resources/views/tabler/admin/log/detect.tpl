{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Nhật ký vi phạm kiểm toán</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem nội dung nhật ký vi phạm kiểm toán</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="table-responsive">
                            <table id="data-table" class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    {foreach $details['field'] as $key => $value}
                                        <th>{$value}</th>
                                    {/foreach}
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {include file='datatable.tpl'}

    <script>
        tableConfig.serverSide = true;
        tableConfig.ajax = {
            url: '/admin/detect/log/ajax',
            type: 'POST',
            dataSrc: 'logs.data'
        };
        tableConfig.order = [
            [0, 'desc']
        ];
        tableConfig.columnDefs = [
            {
                orderable: false,
                targets: [3, 5]
            },
        ];

        let table = new DataTable('#data-table', tableConfig);

        function loadTable() {
            table;
        }

        loadTable();
    </script>

    {include file='admin/footer.tpl'}
