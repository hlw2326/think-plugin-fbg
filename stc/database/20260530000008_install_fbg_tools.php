<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_tools（插件-工具列表）
 */
class InstallFbgTools extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgTools';
    }

    public function change(): void
    {
        $table = $this->table('fbg_tools', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '插件-工具列表',
        ]);
        PhinxExtend::upgrade($table, [
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '标题']],
            ['desc', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '描述']],
            ['logo', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => 'Logo']],
            ['appid', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '跳转小程序 AppID']],
            ['path', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '跳转路径']],
            ['click_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '点击次数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'appid',
            'status',
            'sort',
            'click_count',
        ]);
    }
}
