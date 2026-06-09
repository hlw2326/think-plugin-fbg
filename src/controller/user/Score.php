<?php
declare(strict_types=1);

namespace plugin\fbg\controller\user;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgUserScoreLog;
use plugin\fbg\service\UserScoreService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 积分记录管理
 * @class Score
 * @package plugin\fbg\controller\user
 */
class Score extends Controller
{
    /**
     * 积分记录
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        $this->types = UserScoreService::TYPES;
        FbgUserScoreLog::mQuery()->layTable(function () {
            $this->title = '积分记录';
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
     * 回滚积分记录
     * @auth true
     */
    public function rollback(): void
    {
        $id = intval($this->request->post('id', 0));
        $log = FbgUserScoreLog::mk()->where(['id' => $id])->field('user_id, value, create_at, NOW() as db_now')->findOrEmpty();
        if ($log->isEmpty()) {
            $this->error('记录不存在！');
        }

        $diff = strtotime((string)$log->getAttr('db_now')) - strtotime((string)$log->create_at);
        if ($diff > 300) {
            $this->error('该记录已超过 5 分钟，不允许回滚！');
        }

        $userId = intval($log->user_id);
        $rollbackValue = -intval($log->value);

        if ($rollbackValue === 0) {
            $this->error('该记录变化值为 0，无需回滚！');
        }

        $user = FbgUser::mk()->where(['id' => $userId, 'deleted' => 0])->findOrEmpty();
        if ($user->isEmpty()) {
            $this->error('用户不存在！');
        }

        $newScore = intval($user->score) + $rollbackValue;
        if ($newScore < 0) {
            $this->error('回滚后积分不足，无法回滚！');
        }

        $user->score_source = 'rollback';
        $user->score_remark = "回滚记录 #{$id}";
        $user->score_change_value = $rollbackValue;

        if ($user->save(['score' => $newScore])) {
            $this->success('回滚成功！');
        } else {
            $this->error('回滚失败！');
        }
    }
}
