<?php
declare(strict_types=1);

namespace plugin\fbg\controller\res;

use plugin\fbg\model\FbgRes;
use plugin\fbg\model\FbgResCate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 资源列表管理
 * @class Index
 * @package plugin\fbg\controller\res
 */
class Index extends Controller
{
    /**
     * 资源列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        $this->cates = FbgResCate::mk()->order('sort desc, id asc')->select()->toArray();
        FbgRes::mQuery()->layTable(function () {
            $this->title = '资源列表';
        }, function (QueryHelper $query) {
            $query->like('title')->equal('cate_id')->equal('status')->equal('ext');
        });
    }

    /**
     * 添加资源
     * @auth true
     */
    public function add(): void
    {
        $this->cates = FbgResCate::mk()->where(['status' => 1])->order('sort desc, id asc')->select()->toArray();
        $this->_applyFormToken();
        FbgRes::mForm('form');
    }

    /**
     * 编辑资源
     * @auth true
     */
    public function edit(): void
    {
        $this->cates = FbgResCate::mk()->where(['status' => 1])->order('sort desc, id asc')->select()->toArray();
        $this->_applyFormToken();
        FbgRes::mForm('form');
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgRes::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除资源
     * @auth true
     */
    public function remove(): void
    {
        FbgRes::mDelete();
    }
}
