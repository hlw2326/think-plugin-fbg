<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 朋友圈背景-资源分类表
 */
class InstallFbgResCate extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgResCate';
    }

    public function change(): void
    {
        $tableCate = $this->table('fbg_res_cate', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '朋友圈背景-资源分类表',
        ]);
        PhinxExtend::upgrade($tableCate, [
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => false, 'comment' => '分类名称']],
            ['code', 'string', ['limit' => 50, 'default' => '', 'null' => false, 'comment' => '分类编码']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'code', 'status', 'sort',
        ]);

        // 插入初始化分类数据
        if (!$this->fetchRow("SELECT id FROM fbg_res_cate LIMIT 1")) {
            $this->table('fbg_res_cate')->insert([
                ['id' => 1, 'name' => '动态风景', 'code' => 'scenery', 'sort' => 100, 'status' => 1],
                ['id' => 2, 'name' => '二次元', 'code' => 'anime', 'sort' => 90, 'status' => 1],
                ['id' => 3, 'name' => '科技视觉', 'code' => 'tech', 'sort' => 80, 'status' => 1],
                ['id' => 4, 'name' => '治愈手绘', 'code' => 'art', 'sort' => 70, 'status' => 1],
                ['id' => 5, 'name' => '极简静谧', 'code' => 'minimal', 'sort' => 60, 'status' => 1],
                ['id' => 6, 'name' => '幽默趣味', 'code' => 'funny', 'sort' => 50, 'status' => 1],
            ])->saveData();
        }
    }
}
