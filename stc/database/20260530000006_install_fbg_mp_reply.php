<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_mp_reply（插件-回复规则）
 */
class InstallFbgMpReply extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgMpReply';
    }

    public function change(): void
    {
        $table = $this->table('fbg_mp_reply', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '插件-回复规则',
        ]);
        PhinxExtend::upgrade($table, [
            ['appid', 'string', ['limit' => 50, 'default' => '', 'null' => false, 'comment' => '小程序AppID']],
            ['msg_type', 'string', ['limit' => 20, 'default' => 'text', 'null' => false, 'comment' => '消息类型(text,event,all)']],
            ['match_type', 'string', ['limit' => 20, 'default' => 'exact', 'null' => false, 'comment' => '匹配方式(exact,contains,default)']],
            ['keyword', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '匹配关键词']],
            ['reply_type', 'string', ['limit' => 20, 'default' => 'text', 'null' => false, 'comment' => '回复类型(text,image,link,miniprogrampage)']],
            ['content', 'text', ['default' => null, 'null' => true, 'comment' => '回复内容']],
            ['image_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '回复图片']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['reply_count', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '回复次数']],
            ['title', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '标题']],
            ['pagepath', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '小程序路径']],
            ['url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '跳转链接']],
            ['text_content', 'text', ['default' => null, 'null' => true, 'comment' => '文本内容']],
            ['image_image_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '图片链接']],
            ['link_title', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '图文标题']],
            ['link_content', 'text', ['default' => null, 'null' => true, 'comment' => '图文描述']],
            ['link_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '图文链接']],
            ['link_image_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '图文封面']],
            ['page_title', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '卡片标题']],
            ['page_pagepath', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '卡片路径']],
            ['page_image_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '卡片封面']],
            ['page_appid', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '卡片AppID']],
            ['voice_voice_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '语音链接']],
            ['video_title', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '视频标题']],
            ['video_content', 'text', ['default' => null, 'null' => true, 'comment' => '视频描述']],
            ['video_video_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '视频链接']],
            ['music_title', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '音乐标题']],
            ['music_content', 'text', ['default' => null, 'null' => true, 'comment' => '音乐描述']],
            ['music_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '音乐链接']],
            ['music_hqurl', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '高质链接']],
            ['music_image_url', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '音乐封面']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'appid',
            'msg_type',
            'match_type',
            'status',
        ]);
    }
}
