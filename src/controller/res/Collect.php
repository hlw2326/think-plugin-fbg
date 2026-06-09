<?php
declare(strict_types=1);

namespace plugin\fbg\controller\res;

use plugin\fbg\model\FbgResCollect;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 收藏记录列表
 * @class Collect
 * @package plugin\fbg\controller\res
 */
class Collect extends Controller
{
    /**
     * 收藏列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgResCollect::mQuery()->layTable(function () {
            $this->title = '收藏列表';
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
        FbgResCollect::mDelete();
    }
}
