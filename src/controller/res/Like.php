<?php
declare(strict_types=1);

namespace plugin\fbg\controller\res;

use plugin\fbg\model\FbgResLike;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 点赞记录列表
 * @class Like
 * @package plugin\fbg\controller\res
 */
class Like extends Controller
{
    /**
     * 点赞列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgResLike::mQuery()->layTable(function () {
            $this->title = '点赞列表';
        }, function (QueryHelper $query) {
            $query->equal('user_id')->equal('res_id');
            $query->dateBetween('create_at');
        });
    }

    /**
     * 删除记录
     * @auth true
     */
    public function remove(): void
    {
        FbgResLike::mDelete();
    }
}
