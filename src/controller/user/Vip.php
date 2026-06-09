<?php
declare(strict_types=1);

namespace plugin\fbg\controller\user;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgUserVipLog;
use plugin\fbg\service\UserVipService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 会员记录管理
 * @class Vip
 * @package plugin\fbg\controller\user
 */
class Vip extends Controller
{
    /**
     * 会员记录
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        $this->types = UserVipService::TYPES;
        FbgUserVipLog::mQuery()->layTable(function () {
            $this->title = '会员记录';
        }, function (QueryHelper $query) {
            $query->equal('s.user_id#user_id,s.status#status');
            $query->like('s.source#source,s.remark#remark');
            $query->dateBetween('s.create_at#create_at');

            $db = $query->db();
            $db->alias('s')
               ->join('fbg_user u', 's.user_id = u.id')
               ->field('s.*, u.nickname, u.avatar_url');
        });
    }

    /**
     * 回滚会员记录
     * @auth true
     */
    public function rollback(): void
    {
        $id = intval($this->request->post('id', 0));
        $log = FbgUserVipLog::mk()->where(['id' => $id])->field('user_id, days, before_vip_time, after_vip_time, create_at, NOW() as db_now')->findOrEmpty();
        if ($log->isEmpty()) {
            $this->error('记录不存在！');
        }

        $diff = strtotime((string)$log->getAttr('db_now')) - strtotime((string)$log->create_at);
        if ($diff > 300) {
            $this->error('该记录已超过 5 分钟，不允许回滚！');
        }

        $userId = intval($log->user_id);
        $rollbackDays = -intval($log->days);

        if ($rollbackDays === 0) {
            $this->error('该记录变化天数为 0，无需回滚！');
        }

        $user = FbgUser::mk()->where(['id' => $userId, 'deleted' => 0])->findOrEmpty();
        if ($user->isEmpty()) {
            $this->error('用户不存在！');
        }

        $currentVipTime = intval($user->vip_time);
        $now = time();

        if ($rollbackDays > 0) {
            // 原记录是扣除，回滚是增加
            $baseTime = $currentVipTime > $now ? $currentVipTime : $now;
            $newVipTime = $baseTime + ($rollbackDays * 86400);
        } else {
            // 原记录是增加，回滚是扣除
            $subDays = abs($rollbackDays);
            if ($currentVipTime <= $now) {
                $newVipTime = 0;
            } else {
                $newVipTime = $currentVipTime - ($subDays * 86400);
                if ($newVipTime < $now) {
                    $newVipTime = 0;
                }
            }
        }

        $user->vip_source = 'rollback';
        $user->vip_remark = "回滚记录 #{$id}";
        $user->vip_change_days = $rollbackDays;

        if ($user->save(['vip_time' => $newVipTime])) {
            $this->success('回滚成功！');
        } else {
            $this->error('回滚失败！');
        }
    }
}
