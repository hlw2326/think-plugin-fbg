<?php
declare(strict_types=1);

namespace plugin\fbg\controller\res;

use plugin\fbg\model\FbgResDown;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 下载记录列表
 * @class Down
 * @package plugin\fbg\controller\res
 */
class Down extends Controller
{
    /**
     * 下载列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgResDown::mQuery()->layTable(function () {
            $this->title = '下载列表';
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
        FbgResDown::mDelete();
    }
}
