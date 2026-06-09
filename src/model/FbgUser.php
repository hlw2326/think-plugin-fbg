<?php
declare(strict_types=1);

namespace plugin\fbg\model;

use plugin\fbg\service\UserScoreService;
use plugin\fbg\service\UserVipService;
use think\admin\Model;

/**
 * 用户 - 模型
 * @class FbgUser
 * @package plugin\fbg\model
 */
class FbgUser extends Model
{
    /**
     * 模型更新前事件钩子：自动记录积分和会员变更日志
     *
     * @param FbgUser $user
     * @return void
     */
    public static function onBeforeUpdate($user)
    {
        if (!UserScoreService::$changing) {
            $before = intval($user->getOrigin('score'));
            $after = intval($user->score);
            if ($before !== $after) {
                $source = isset($user->score_source) ? (string)$user->score_source : 'change';
                $remark = isset($user->score_remark) ? (string)$user->score_remark : '数据调整同步记录';
                $value = isset($user->score_change_value) ? intval($user->score_change_value) : ($after - $before);

                UserScoreService::log(
                    intval($user->id),
                    $value,
                    $before,
                    $after,
                    $source,
                    $remark
                );
            }
        }

        if (!UserVipService::$changing) {
            $beforeVip = intval($user->getOrigin('vip_time'));
            $afterVip = intval($user->vip_time);
            if ($beforeVip !== $afterVip) {
                $source = isset($user->vip_source) ? (string)$user->vip_source : 'change';
                $remark = isset($user->vip_remark) ? (string)$user->vip_remark : '数据调整同步记录';
                if (isset($user->vip_change_days)) {
                    $days = intval($user->vip_change_days);
                } else {
                    $diff = $afterVip - $beforeVip;
                    $days = intval(round($diff / 86400));
                }

                UserVipService::log(
                    intval($user->id),
                    $days,
                    $beforeVip,
                    $afterVip,
                    $source,
                    $remark
                );
            }
        }
    }
}


