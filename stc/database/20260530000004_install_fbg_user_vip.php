<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_user_vip_log（朋友圈背景-用户会员变更日志表）
 */
class InstallFbgUserVip extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgUserVip';
    }

    public function change(): void
    {
        // Create fbg_user_vip_log table
        $table = $this->table('fbg_user_vip_log', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '朋友圈背景-用户会员变更日志表',
        ]);
        PhinxExtend::upgrade($table, [
            ['user_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '用户ID']],
            ['source', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '会员变更来源']],
            ['days', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '变更天数']],
            ['before_vip_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '变更前会员时间戳']],
            ['after_vip_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '变更后会员时间戳']],
            ['remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注说明']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'user_id',
            'source',
            'status',
            'create_at',
        ]);
    }
}
