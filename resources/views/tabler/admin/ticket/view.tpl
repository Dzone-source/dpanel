{include file='admin/header.tpl'}

<div class="page-wrapper">
    <div class="container-xl">
        <div class="page-header d-print-none text-white">
            <div class="row align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <span class="home-title my-3">Trả lời phiếu hỗ trợ</span>
                    </h2>
                    <div class="page-pretitle">
                        <span class="home-subtitle">Bạn có thể xem lịch sử tin nhắn và thêm trả lời tại đây</span>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="btn-list">
                        {if $ticket->status !== 'closed'}
                        <button href="#" class="btn btn-red" data-bs-toggle="modal"
                                data-bs-target="#close_ticket_confirm_dialog">
                            <i class="icon ti ti-x"></i>
                            Đóng
                        </button>
                        {/if}
                        <button href="#" class="btn btn-primary" hx-post="/admin/ticket/{$ticket->id}/llm_reply"
                                hx-swap="none">
                            <i class="icon ti ti-robot"></i>
                            Trả lời bằng LLM
                        </button>
                        <button href="#" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#add-reply">
                            <i class="icon ti ti-plus"></i>
                            Trả lời
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="h1 my-2 mb-3">#{$ticket->id} {$ticket->title}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center my-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="divide-y">
                                {foreach $comments as $comment}
                                <div>
                                    <div class="row">
                                        <div class="col">
                                            <div>
                                                {$comment->comment}
                                            </div>
                                            <div class="text-secondary my-1">                                                {$comment->commenter_name}
                                                trả lời lúc {$comment->datetime}
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div>
                                                # {$comment->comment_id + 1}
                                            </div>
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
    </div>

    <div class="modal modal-blur fade" id="add-reply" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Thêm trả lời</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <textarea id="reply-comment" class="form-control" rows="12"
                                  placeholder="Nhập nội dung trả lời"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                    <button class="btn btn-primary" data-bs-dismiss="modal"
                        hx-post="/admin/ticket/{$ticket->id}" hx-swap="none"
                        hx-vals='js:{
                            comment: document.getElementById("reply-comment").value,
                        }'>
                        Trả lời
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modal-blur fade" id="close_ticket_confirm_dialog" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Đóng phiếu hỗ trợ</h5>
                    <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            hx-post="/admin/ticket/{$ticket->id}/close" hx-swap="none"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <p>
                            Xác nhận đóng phiếu hỗ trợ?
                        <p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Hủy</button>
                    <button id="confirm_close" type="button" class="btn btn-primary" data-bs-dismiss="modal">Xác nhận
                    </button>
                </div>
            </div>
        </div>
    </div>

{include file='admin/footer.tpl'}
