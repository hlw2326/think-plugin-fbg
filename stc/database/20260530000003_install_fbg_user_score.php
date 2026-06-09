<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_user_score_log（朋友圈背景-用户积分变更日志表）并增加用户表相关字段
 */
class InstallFbgUserScore extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgUserScore';
    }

    public function change(): void
    {
        // 1. Ensure columns exist on fbg_user table for compatibility
        $userTable = $this->table('fbg_user');
        if ($userTable->exists()) {
            $userUpdated = false;
            if (!$userTable->hasColumn('score')) {
                $userTable->addColumn('score', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '当前可用积分']);
                $userUpdated = true;
            }
            if (!$userTable->hasColumn('down_total')) {
                $userTable->addColumn('down_total', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '累计下载数']);
                $userUpdated = true;
            }
            if (!$userTable->hasColumn('vip_no_ad')) {
                $userTable->addColumn('vip_no_ad', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否免广告']);
                $userUpdated = true;
            }
            if ($userUpdated) {
                $userTable->update();
            }
        }

        // 2. Create fbg_user_score_log table
        $tableLog = $this->table('fbg_user_score_log', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '朋友圈背景-用户积分变更日志表',
        ]);
        PhinxExtend::upgrade($tableLog, [
            ['user_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '用户ID']],
            ['source', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '积分来源']],
            ['value', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '积分变化值']],
            ['before', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '变更前积分']],
            ['after', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '变更后积分']],
            ['remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'user_id', 'source', 'status', 'create_at',
        ]);
    }
}
