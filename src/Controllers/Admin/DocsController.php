<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\Docs;
use App\Services\LLM;
use App\Utils\Tools;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;
use function time;

final class DocsController extends BaseController
{
    private static array $details =
        [
            'field' => [
                'op' => 'Thao tác',
                'id' => 'ID',
                'status' => 'Trạng thái',
                'sort' => 'Sắp xếp',
                'date' => 'Ngày',
                'title' => 'Tiêu đề',
            ],
        ];

    private static array $update_field = [
        'status',
        'sort',
        'title',
    ];

    /**
     * 后台文档页面
     *
     * @throws Exception
     */
    public function index(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('details', self::$details)
                ->fetch('admin/docs/index.tpl')
        );
    }

    /**
     * 后台文档创建页面
     *
     * @throws Exception
     */
    public function create(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        return $response->write(
            $this->view()
                ->assign('update_field', self::$update_field)
                ->fetch('admin/docs/create.tpl')
        );
    }

    /**
     * 后台添加文档
     */
    public function add(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $status = (int) $request->getParam('status');
        $sort = (int) $request->getParam('sort');
        $title = $request->getParam('title');
        $content = $request->getParam('content');

        if ($title === '' || $content === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tiêu đề và nội dung tài liệu không được để trống',
            ]);
        }

        $doc = new Docs();
        $doc->status = in_array($status, [0, 1]) ? $status : 1;
        $doc->sort = $sort > 999 || $sort < 0 ? 0 : $sort;
        $doc->date = Tools::toDateTime(time());
        $doc->title = $title;
        $doc->content = $content;

        if (! $doc->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Thêm tài liệu thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Thêm tài liệu thành công',
        ]);
    }

    /**
     * 使用LLM生成文档
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param array $args
     *
     * @return ResponseInterface
     */
    public function generate(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $content = LLM::genTextResponse($request->getParam('question'));

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Tạo tài liệu thành công',
            'content' => $content,
        ]);
    }

    /**
     * 文档Chỉnh sửa页面
     *
     * @throws Exception
     */
    public function edit(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $doc = (new Docs())->find($args['id']);

        return $response->write(
            $this->view()
                ->assign('doc', $doc)
                ->assign('update_field', self::$update_field)
                ->fetch('admin/docs/edit.tpl')
        );
    }

    /**
     * 后台Chỉnh sửa文档提交
     */
    public function update(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $status = (int) $request->getParam('status');
        $sort = (int) $request->getParam('sort');
        $title = $request->getParam('title');
        $content = $request->getParam('content');

        if ($title === '' || $content === '') {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tiêu đề và nội dung tài liệu không được để trống',
            ]);
        }

        $doc = (new Docs())->find($args['id']);

        if ($doc === null) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Tài liệu không tồn tại',
            ]);
        }

        $doc->status = in_array($status, [0, 1]) ? $status : 1;
        $doc->sort = $sort > 999 || $sort < 0 ? 0 : $sort;
        $doc->title = $request->getParam('title');
        $doc->content = $request->getParam('content');
        $doc->date = Tools::toDateTime(time());

        if (! $doc->save()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Cập nhật tài liệu thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Cập nhật tài liệu thành công',
        ]);
    }

    /**
     * 后台Xóa文档
     */
    public function delete(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $doc = (new Docs())->find($args['id']);

        if (! $doc->delete()) {
            return $response->withJson([
                'ret' => 0,
                'msg' => 'Xóa thất bại',
            ]);
        }

        return $response->withJson([
            'ret' => 1,
            'msg' => 'Xóa thành công',
        ]);
    }

    /**
     * 后台文档页面 AJAX
     */
    public function ajax(ServerRequest $request, Response $response, array $args): ResponseInterface
    {
        $docs = (new Docs())->orderBy('id')->get();

        foreach ($docs as $doc) {
            $doc->op = '<button class="btn btn-red" id="delete-doc-' . $doc->id . '" 
            onclick="deleteDoc(' . $doc->id . ')">Xóa</button>
            <a class="btn btn-primary" href="/admin/docs/' . $doc->id . '/edit">Chỉnh sửa</a>';
            $doc->status = $doc->status();
        }

        return $response->withJson([
            'docs' => $docs,
        ]);
    }
}
