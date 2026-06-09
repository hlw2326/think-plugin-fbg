<?php
declare(strict_types=1);

namespace plugin\fbg\service;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgUserScoreLog;
use think\facade\Db;

/**
 * 积分服务
 * @class UserScoreService
 * @package plugin\fbg\service
 */
class UserScoreService
{
    public const TYPES = [
        'register' => '新用户注册',
        'signin'   => '每日签到',
        'video'    => '激励广告',
        'download' => '下载扣除',
        'admin'    => '系统发放',
        'invite'   => '邀请好友',
        'rollback' => '积分回滚',
    ];

    /**
     * 获取所有可用的积分类型
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return self::TYPES;
    }

    /**
     * 是否正在由服务处理积分变更（用于防止模型事件重复记录日志）
     * @var bool
     */
    public static $changing = false;

    /**
     * 变更用户积分
     *
     * @param int $userId    用户ID
     * @param int $value     变化值（正数增加，负数减少）
     * @param string $source 积分来源/操作类型
     * @param string $remark 备注说明
     * @return array         返回状态 ['status' => bool, 'msg' => string, 'data' => array]
     */
    public static function change(int $userId, int $value, string $source, string $remark = ''): array
    {
        if ($value === 0) {
            return ['status' => true, 'msg' => '积分未发生变化', 'data' => []];
        }

        self::$changing = true;
        try {
            return Db::transaction(function () use ($userId, $value, $source, $remark) {
                // 1. 获取并锁定用户记录，防止并发冲突
                $user = FbgUser::mk()->where(['id' => $userId, 'deleted' => 0])->lock(true)->findOrEmpty();
                if ($user->isEmpty()) {
                    throw new \RuntimeException('用户不存在');
                }
                if (intval($user->status) !== 1) {
                    throw new \RuntimeException('用户账号已被禁用');
                }

                $before = intval($user->score);
                $after = $before + $value;

                if ($after < 0) {
                    throw new \RuntimeException('用户积分不足');
                }

                // 2. 更新用户积分
                $user->save(['score' => $after]);

                // 3. 记录积分日志
                self::log($userId, $value, $before, $after, $source, $remark);

                return [
                    'status' => true,
                    'msg'    => '积分变更成功',
                    'data'   => [
                        'user_id' => $userId,
                        'before'  => $before,
                        'after'   => $after,
                        'value'   => $value,
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
     * 写入积分记录日志
     *
     * @param int $userId    用户ID
     * @param int $value     变化值
     * @param int $before    变更前积分
     * @param int $after     变更后积分
     * @param string $source 积分来源
     * @param string $remark 备注说明
     * @return bool
     */
    public static function log(int $userId, int $value, int $before, int $after, string $source, string $remark = ''): bool
    {
        $log = FbgUserScoreLog::mk();
        return $log->save([
            'user_id' => $userId,
            'source'  => $source,
            'value'   => $value,
            'before'  => $before,
            'after'   => $after,
            'remark'  => $remark,
            'status'  => 1,
        ]);
    }

    /**
     * 获取用户积分记录列表
     *
     * @param int $userId 用户ID
     * @param int $limit  返回数量限制
     * @return array
     */
    public static function getLogs(int $userId, int $limit = 20): array
    {
        return FbgUserScoreLog::mk()
            ->where(['user_id' => $userId, 'status' => 1])
            ->order('id desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
