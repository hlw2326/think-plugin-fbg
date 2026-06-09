<?php
declare(strict_types=1);

namespace plugin\fbg\controller\res;

use plugin\fbg\model\FbgResCate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 资源分类管理
 * @class Cate
 * @package plugin\fbg\controller\res
 */
class Cate extends Controller
{
    /**
     * 分类列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgResCate::mQuery()->layTable(function () {
            $this->title = '分类列表';
        }, function (QueryHelper $query) {
            $query->like('name')->like('code');
            $query->equal('status');
        });
    }

    /**
     * 添加分类
     * @auth true
     */
    public function add(): void
    {
        $this->_applyFormToken();
        FbgResCate::mForm('form');
    }

    /**
     * 编辑分类
     * @auth true
     */
    public function edit(): void
    {
        $this->_applyFormToken();
        FbgResCate::mForm('form');
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgResCate::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除分类
     * @auth true
     */
    public function remove(): void
    {
        FbgResCate::mDelete();
    }
}
