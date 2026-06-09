<?php
declare(strict_types=1);

namespace plugin\fbg\service;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgUserVipLog;
use think\facade\Db;

/**
 * 会员服务
 * @class UserVipService
 * @package plugin\fbg\service
 */
class UserVipService
{
    public const TYPES = [
        'admin'    => '系统发放',
        'consume'  => '积分兑换',
        'register' => '新用户注册',
        'invite'   => '邀请奖励',
        'change'   => '数据调整',
        'rollback' => '会员回滚',
    ];

    /**
     * 获取所有可用的会员变更类型
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * 是否正在由服务处理会员变更（用于防止模型事件重复记录日志）
     * @var bool
     */
    public static $changing = false;

    /**
     * 变更用户会员天数
     *
     * @param int $userId    用户ID
     * @param int $days      变更天数（正数增加，负数减少）
     * @param string $source 会员变更来源/操作类型
     * @param string $remark 备注说明
     * @return array         返回状态 ['status' => bool, 'msg' => string, 'data' => array]
     */
    public static function change(int $userId, int $days, string $source, string $remark = ''): array
    {
        if ($days === 0) {
            return ['status' => true, 'msg' => '天数未发生变化', 'data' => []];
        }

        self::$changing = true;
        try {
            return Db::transaction(function () use ($userId, $days, $source, $remark) {
                // 1. 获取并锁定用户记录，防止并发冲突
                $user = FbgUser::mk()->where(['id' => $userId, 'deleted' => 0])->lock(true)->findOrEmpty();
                if ($user->isEmpty()) {
                    throw new \RuntimeException('用户不存在');
                }
                if (intval($user->status) !== 1) {
                    throw new \RuntimeException('用户账号已被禁用');
                }

                $before = intval($user->vip_time);
                $now = time();

                if ($days > 0) {
                    $baseTime = $before > $now ? $before : $now;
                    $after = $baseTime + ($days * 86400);
                } else {
                    $subDays = abs($days);
                    if ($before <= $now) {
                        $after = 0;
                    } else {
                        $after = $before - ($subDays * 86400);
                        if ($after < $now) {
                            $after = 0;
                        }
                    }
                }

                // 2. 更新用户会员到期时间
                $user->save(['vip_time' => $after]);

                // 3. 记录变更日志
                self::log($userId, $days, $before, $after, $source, $remark);

                return [
                    'status' => true,
                    'msg'    => '会员时间变更成功',
                    'data'   => [
                        'user_id' => $userId,
                        'before'  => $before,
                        'after'   => $after,
                        'days'    => $days,
                    ]
                ];
            });
        } catch (\Exception $e) {
            return [
                'status' => false,
                'msg'    => $e->getMessage(),
                'data'   => []
            ];
        } finally {
            self::$changing = false;
        }
    }

    /**
     * 写入会员记录日志
     *
     * @param int $userId    用户ID
     * @param int $days      变更天数
     * @param int $before    变更前会员到期时间戳
     * @param int $after     变更后会员到期时间戳
     * @param string $source 会员变更来源
     * @param string $remark 备注说明
     * @return bool
     */
    public static function log(int $userId, int $days, int $before, int $after, string $source, string $remark = ''): bool
    {
        $log = FbgUserVipLog::mk();
        return $log->save([
            'user_id'         => $userId,
            'source'          => $source,
            'days'            => $days,
            'before_vip_time' => $before,
            'after_vip_time'  => $after,
            'remark'          => $remark,
            'status'          => 1,
        ]);
    }

    /**
     * 获取用户会员变更记录列表
     *
     * @param int $userId 用户ID
     * @param int $limit  返回数量限制
     * @return array
     */
    public static function getLogs(int $userId, int $limit = 20): array
    {
        return FbgUserVipLog::mk()
            ->where(['user_id' => $userId, 'status' => 1])
            ->order('id desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
