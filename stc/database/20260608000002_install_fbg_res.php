<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 朋友圈背景-资源表
 */
class InstallFbgRes extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgRes';
    }

    public function change(): void
    {
        $tableRes = $this->table('fbg_res', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '朋友圈背景-资源表',
        ]);
        PhinxExtend::upgrade($tableRes, [
            ['cate_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => false, 'comment' => '分类ID']],
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => false, 'comment' => '标题']],
            ['url', 'string', ['limit' => 500, 'default' => '', 'null' => false, 'comment' => '资源链接']],
            ['ext', 'string', ['limit' => 20, 'default' => 'image', 'null' => false, 'comment' => '资源类型(image图片/gif, video视频)']],
            ['resolution', 'string', ['limit' => 50, 'default' => '1080x1920', 'null' => true, 'comment' => '分辨率']],
            ['size', 'string', ['limit' => 50, 'default' => '0.0MB', 'null' => true, 'comment' => '大小']],
            ['tags', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '标签(逗号分隔)']],
            ['like_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '点赞数']],
            ['collect_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '收藏数']],
            ['down_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '下载数']],
            ['view_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '浏览数']],
            ['is_indep_score', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '是否独立扣积分(0否/1是)']],
            ['indep_score', 'integer', ['limit' => 11, 'default' => 0, 'null' => true, 'comment' => '独立扣除积分数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'cate_id', 'status', 'sort',
        ]);

        // 插入初始化壁纸资源数据
        if (!$this->fetchRow("SELECT id FROM fbg_res LIMIT 1")) {
            $baseWallpapers = [
                ['cate_id' => 1, 'url' => 'https://media.giphy.com/media/3o7aD2saalEvTehEXm/giphy.gif', 'tags' => '唯美风景,大自然,治愈系', 'title' => '精选风景动图', 'size' => '2.4MB', 'resolution' => '1080x1920'],
                ['cate_id' => 3, 'url' => 'https://media.giphy.com/media/l41YcWbE2O3mG19vO/giphy.gif', 'tags' => '科技视觉,科幻未来,赛博朋克', 'title' => '科技感环形光晕', 'size' => '3.1MB', 'resolution' => '1080x1920'],
                ['cate_id' => 2, 'url' => 'https://media.giphy.com/media/11KzOet1ElBDz2/giphy.gif', 'tags' => '二次元,动漫女生,唯美动漫', 'title' => '星空下的少女', 'size' => '1.8MB', 'resolution' => '1080x1920'],
                ['cate_id' => 1, 'url' => 'https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/giphy.gif', 'tags' => '星空夜景,唯美风景,极光山脉', 'title' => '极光闪耀夜空', 'size' => '2.9MB', 'resolution' => '1080x1920'],
                ['cate_id' => 2, 'url' => 'https://media.giphy.com/media/l0HlNaQ6gWfllcjDO/giphy.gif', 'tags' => '二次元,动漫风景,治愈二次元', 'title' => '黄昏电车', 'size' => '2.2MB', 'resolution' => '1080x1920'],
                ['cate_id' => 3, 'url' => 'https://media.giphy.com/media/26ufdipQqU2lhNA4g/giphy.gif', 'tags' => '科技视觉,抽象空间,概念设计', 'title' => '抽象空间流动', 'size' => '4.0MB', 'resolution' => '1080x1920'],
                ['cate_id' => 1, 'url' => 'https://media.giphy.com/media/26n6WywFabvM5A3rW/giphy.gif', 'tags' => '海浪沙滩,唯美风景,海景壁纸', 'title' => '日落海浪拍打', 'size' => '2.6MB', 'resolution' => '1080x1920'],
                ['cate_id' => 2, 'url' => 'https://media.giphy.com/media/3o7TKMt1VVNkHV2PaE/giphy.gif', 'tags' => '二次元,治愈动漫,卡通少女', 'title' => '林间小路漫步', 'size' => '1.9MB', 'resolution' => '1080x1920'],
                ['cate_id' => 4, 'url' => 'https://media.giphy.com/media/xT9Igrn32LQwwA7Cxy/giphy.gif', 'tags' => '治愈手绘,可爱卡通,趣味小品', 'title' => '治愈简笔小熊', 'size' => '2.1MB', 'resolution' => '1080x1920'],
                ['cate_id' => 5, 'url' => 'https://media.giphy.com/media/xT9IgzoKnwFNmISR8I/giphy.gif', 'tags' => '极静微光,极简静谧,艺术氛围', 'title' => '静谧星轨沙漏', 'size' => '1.4MB', 'resolution' => '1080x1920'],
                ['cate_id' => 6, 'url' => 'https://media.giphy.com/media/3oEjHAUOqG3l14RVhq/giphy.gif', 'tags' => '幽默搞怪,趣味动态,仓鼠表情', 'title' => '沙雕戏精仓鼠', 'size' => '1.9MB', 'resolution' => '1080x1920']
            ];

            $wallpapers = [];
            for ($i = 0; $i < 44; $i++) {
                $base = $baseWallpapers[$i % count($baseWallpapers)];
                $urlLower = strtolower($base['url']);
                $resType = (strpos($urlLower, '.mp4') !== false || strpos($urlLower, '.m3u8') !== false || strpos($urlLower, '.webm') !== false || strpos($urlLower, '.mov') !== false) ? 'video' : 'image';
                $wallpapers[] = [
                    'cate_id' => $base['cate_id'],
                    'url' => $base['url'],
                    'ext' => $resType,
                    'tags' => $base['tags'],
                    'title' => $base['title'] . ' #' . ($i + 1),
                    'size' => $base['size'],
                    'resolution' => $base['resolution'],
                    'like_count' => rand(50, 500),
                    'collect_count' => rand(20, 200),
                    'down_count' => rand(10, 100),
                    'view_count' => rand(500, 5000),
                    'sort' => 1000 - $i,
                    'status' => 1
                ];
            }
            $this->table('fbg_res')->insert($wallpapers)->saveData();
        }
    }
}
