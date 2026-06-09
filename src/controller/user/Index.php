<?php
declare(strict_types=1);

namespace plugin\fbg\controller\user;

use plugin\fbg\model\FbgUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 用户列表
 * @class Index
 * @package plugin\fbg\controller\user
 */
class Index extends Controller
{
    /**
     * 用户列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgUser::mQuery()->layTable(function () {
            $this->title = '用户列表';
        }, function (QueryHelper $query) {
            $query->equal('id');
            $query->like('nickname')->like('phone')->like('openid')->like('device_model');
            $query->equal('status,appid');
            $query->dateBetween('create_at');
            $query->where(['deleted' => 0]);
        });
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgUser::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'vip_no_ad.in:0,1' => '免广告状态异常！',
        ]));
    }

    /**
     * 退出登录
     * @auth true
     */
    public function logout(): void
    {
        $id = intval($this->request->post('id', $this->request->get('id', 0)));
        if ($id <= 0) {
            $this->error('用户 ID 不能为空！');
        }

        FbgUser::mk()->where(['id' => $id, 'deleted' => 0])->update(['token' => '']);
        $this->success('已退出登录！');
    }

    /**
     * 用户详细信息
     * @auth true
     */
    public function info(): void
    {
        $id = intval($this->request->get('id', 0));
        $this->vo = FbgUser::mk()->where(['id' => $id, 'deleted' => 0])->findOrEmpty()->toArray();
        if (empty($this->vo)) {
            $this->error('用户不存在！');
        }
        $this->fetch();
    }

    /**
     * 调整积分
     * @auth true
     */
    public function score(): void
    {
        $id = intval($this->request->get('id', $this->request->post('id', 0)));
        $user = FbgUser::mk()->where(['id' => $id, 'deleted' => 0])->findOrEmpty();
        if ($user->isEmpty()) {
            $this->error('用户不存在！');
        }

        if ($this->request->isGet()) {
            $this->vo = $user->toArray();
            $this->fetch();
            return;
        }

        $value = intval($this->request->post('value', 0));
        $action = $this->request->post('action', 'add');
        $remark = trim($this->request->post('remark', ''));

        if ($value <= 0) {
            $this->error('积分数值必须大于 0！');
        }

        $changeValue = ($action === 'sub') ? -$value : $value;
        $newScore = intval($user->score) + $changeValue;

        if ($newScore < 0) {
            $this->error('用户积分不足！');
        }

        $user->score_source = 'admin';
        $user->score_remark = $remark ?: '管理员后台手动调整';
        $user->score_change_value = $changeValue;

        if ($user->save(['score' => $newScore])) {
            $this->success('积分调整成功！');
        } else {
            $this->error('积分调整失败！');
        }
    }

    /**
     * 调整会员时间
     * @auth true
     */
    public function vip(): void
    {
        $id = intval($this->request->get('id', $this->request->post('id', 0)));
        $user = FbgUser::mk()->where(['id' => $id, 'deleted' => 0])->findOrEmpty();
        if ($user->isEmpty()) {
            $this->error('用户不存在！');
        }

        if ($this->request->isGet()) {
            $this->vo = $user->toArray();
            $this->fetch();
            return;
        }

        $days = intval($this->request->post('days', 0));
        $action = $this->request->post('action', 'add');
        $remark = trim($this->request->post('remark', ''));

        if ($days <= 0) {
            $this->error('调整天数必须大于 0！');
        }

        $currentVipTime = intval($user->vip_time);
        $now = time();

        if ($currentVipTime > $now) {
            $baseTime = $currentVipTime;
        } else {
            $baseTime = $now;
        }

        if ($action === 'add') {
            $newVipTime = $baseTime + ($days * 86400);
            $changeDays = $days;
        } else {
            $changeDays = -$days;
            if ($currentVipTime <= $now) {
                $newVipTime = 0;
            } else {
                $newVipTime = $currentVipTime - ($days * 86400);
                if ($newVipTime < $now) {
                    $newVipTime = 0;
                }
            }
        }

        $user->vip_source = 'admin';
        $user->vip_remark = $remark ?: '管理员后台手动调整';
        $user->vip_change_days = $changeDays;

        if ($user->save(['vip_time' => $newVipTime])) {
            $this->success('会员时间调整成功！');
        } else {
            $this->error('会员时间调整失败！');
        }
    }
}

