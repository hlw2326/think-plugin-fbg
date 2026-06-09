<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 朋友圈背景-资源点赞表
 */
class InstallFbgResLike extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgResLike';
    }

    public function change(): void
    {
        $tableLike = $this->table('fbg_res_like', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '朋友圈背景-资源点赞表',
        ]);
        PhinxExtend::upgrade($tableLike, [
            ['user_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '用户ID']],
            ['res_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '资源ID']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'user_id', 'res_id',
        ]);
    }
}
