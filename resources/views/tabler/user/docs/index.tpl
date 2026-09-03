{include file='user/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title">Trung tâm tài liệu</span>
                    </h2>
                    <div class="page-pretitle my-3">
                        <span class="home-subtitle">Xem hướng dẫn cài đặt và sử dụng tại đây</span>
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
                        <div class="card-header">
                            <h3 class="card-title">Danh sách tài liệu</h3>
                        </div>
                        <div class="list-group list-group-flush list-group-hoverable">
                            {foreach $docs as $doc}
                                <div class="list-group-item">
                                    <div class="row align-items-center">
                                        <div class="col text-truncate">
                                            <div class="text-reset d-block">{$doc->title}</div>
                                            <div class="d-block text-secondary text-truncate mt-n1">
                                                {$doc->date}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <a class="btn btn-primary" href="/user/docs/{$doc->id}/view">
                                                Xem
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{include file='user/footer.tpl'}
