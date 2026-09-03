{include file='user/header.tpl'}

<!-- Quy tắc kiểm duyệt dùng để ngăn DMCA và spam email, không phải để chặn người dùng -->
<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Quy tắc kiểm duyệt</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Các quy tắc kiểm duyệt đang dùng trên trang web</span>
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
                            <table class="table table-vcenter card-table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên</th>
                                    <th>Mô tả</th>
                                    <th>Biểu thức chính quy</th>
                                    <th>Loại</th>
                                </tr>
                                </thead>
                                <tbody>
                                {foreach $rules as $rule}
                                    <tr>
                                        <td>#{$rule->id}</td>
                                        <td>{$rule->name}</td>
                                        <td>{$rule->text}</td>
                                        <td>{$rule->regex}</td>
                                        {if $rule->type === 1}
                                            <td>Khớp văn bản gói dữ liệu</td>
                                        {/if}
                                        {if $rule->type === 2}
                                            <td>Khớp hex gói dữ liệu</td>
                                        {/if}
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
