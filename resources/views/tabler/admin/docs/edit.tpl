{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Chỉnh sửa tài liệu #{$doc->id}</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Chỉnh sửa tài liệu hệ thống</span>
                    </div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <button id="save" href="#" class="btn btn-primary">
                            <i class="icon ti ti-device-floppy"></i>
                            Lưu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-md-9 col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label col-3 col-form-label">Tiêu đề tài liệu</label>
                                <div class="col">
                                    <input id="title" type="text" class="form-control" value="{$doc->title}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <form method="post">
                                    <textarea id="tinymce">{$doc->content}</textarea>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h3 class="card-title">Tùy chọn</h3>
                            <div class="form-group mb-3 row">
                                <label class="form-label col-3 col-form-label">Trạng thái</label>
                                <div class="col">
                                    <select id="status" class="col form-select" value="{$doc->status}">
                                        <option value="0" {if $doc->status == 0} selected {/if}>Chưa xuất bản</option>
                                        <option value="1" {if $doc->status == 1} selected {/if}>Đã xuất bản</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group mb-3 row">
                                <label class="form-label">Thứ tự</label>
                                <div class="col">
                                    <input id="sort" type="text" class="form-control" value="{$doc->sort}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{include file='tinymce.tpl'}

<script>
    $("#save").click(function () {
        $.ajax({
            url: '/admin/docs/' + {$doc->id},
            type: 'PUT',
            dataType: "json",
            data: {
                {foreach $update_field as $key}
                {$key}: $('#{$key}').val(),
                {/foreach}
                content: tinyMCE.activeEditor.getContent(),
            },
            success: function (data) {
                if (data.ret === 1) {
                    $('#success-message').text(data.msg);
                    $('#success-dialog').modal('show');
                    window.setTimeout("location.href=top.document.referrer", {$config['jump_delay']});
                } else {
                    $('#fail-message').text(data.msg);
                    $('#fail-dialog').modal('show');
                }
            }
        })
    });
</script>

{include file='admin/footer.tpl'}
