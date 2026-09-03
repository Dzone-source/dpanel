<link href="//cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.css" rel="stylesheet"/>
<script src="//cdn.datatables.net/v/bs5/dt-2.0.8/datatables.min.js"></script>

<script>
    let tableConfig = {
        autoWidth: false,
        iDisplayLength: 10,
        scrollX: true,
        columns: [
            {foreach $details['field'] as $key => $value}
            {
                data: '{$key}'
            },
            {/foreach}
        ],
        initComplete: function () {
            $('div.dt-length').parent().parent().removeClass('mt-2').addClass('row px-3 py-3')
            $('div.dt-scroll').parent().parent().removeClass('mt-2')
            $('div.dt-info').parent().parent().removeClass('mt-2').addClass('row card-footer')
            // col-12 below sm so the length/search controls stack instead of
            // squeezing into unusable slivers on phones.
            $('div.dt-length').parent().removeClass('col-md-auto me-auto').addClass('col-12 col-sm-auto')
            $('div.dt-search').parent().removeClass('col-md-auto me-auto ms-auto').addClass('col-12 col-sm-auto ms-sm-auto')
            $('div.dt-info').parent().removeClass('col-md-auto me-auto').addClass('col-12 col-sm')
            $('div.dt-paging').parent().removeClass('col-md-auto me-auto ms-auto').addClass('col-12 col-sm-auto')
            $('div.dt-scroll-body').css('border-bottom-style', 'none')
        },
        language: {
            "sProcessing": "Đang xử lý...",
            "sLengthMenu": "Hiển thị _MENU_ mục",
            "sZeroRecords": "Không tìm thấy kết quả phù hợp",
            "sInfo": "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ mục",
            "sInfoEmpty": "Hiển thị 0 đến 0 trong tổng số 0 mục",
            "sInfoFiltered": "(lọc từ _MAX_ mục)",
            "sInfoPostFix": "",
            "sSearch": "<i class=\"ti ti-search\"></i> ",
            "sUrl": "",
            "sEmptyTable": "Không có dữ liệu trong bảng",
            "sLoadingRecords": "Đang tải...",
            "sInfoThousands": ",",
            "oPaginate": {
                "sFirst": "Đầu",
                "sPrevious": "<i class=\"ti ti-arrow-left\"></i>",
                "sNext": "<i class=\"ti ti-arrow-right\"></i>",
                "sLast": "Cuối"
            },
            "oAria": {
                "sSortAscending": ": sắp xếp cột theo thứ tự tăng dần",
                "sSortDescending": ": sắp xếp cột theo thứ tự giảm dần"
            }
        }
    };
</script>
