<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_mp（插件-小程序）
 */
class InstallFbgMp extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgMp';
    }

    public function change(): void
    {
        $table = $this->table('fbg_mp', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '插件-小程序',
        ]);
        PhinxExtend::upgrade($table, [
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '小程序名称']],
            ['appid', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '小程序AppID']],
            ['appsecret', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '小程序密钥']],
            ['pages_config', 'text', ['default' => null, 'null' => true, 'comment' => 'pages.json配置']],
            ['token', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '消息校验Token']],
            ['encodingaeskey', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '消息加密密钥']],
            ['custom_reply_enabled', 'integer', ['limit' => 1, 'default' => 1, 'null' => false, 'comment' => '客服消息开关']],
            ['logo', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '小程序 Logo']],
            ['remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注信息']],
            ['banner_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '横幅广告单元ID']],
            ['grid_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '格子广告单元ID']],
            ['custom_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '原生模板广告单元ID']],
            ['video_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '视频贴片广告单元ID']],
            ['reward_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '激励广告单元ID']],
            ['popup_unit_id', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '插屏广告单元ID']],
            ['ad_global_enabled', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '广告总开关']],
            ['ad_enabled_banner', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '横幅广告开关']],
            ['ad_enabled_grid', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '格子广告开关']],
            ['ad_enabled_custom', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '原生模板广告开关']],
            ['ad_enabled_video', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '视频贴片广告开关']],
            ['ad_enabled_reward', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '激励广告开关']],
            ['ad_enabled_popup', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => '插屏广告开关']],
            ['vip_no_ad', 'integer', ['limit' => 1, 'default' => 0, 'null' => false, 'comment' => 'VIP 屏蔽展示型广告']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'appid',
            'status',
        ]);

        if (!$this->fetchRow("SELECT id FROM fbg_mp LIMIT 1")) {
            $this->table('fbg_mp')->insert([
                [
                    'name' => '测试',
                    'appid' => 'wx0c1972421f064dde',
                    'appsecret' => '',
                    'pages_config' => null,
                    'token' => '',
                    'encodingaeskey' => '',
                    'logo' => '',
                    'remark' => '',
                    'banner_unit_id' => '',
                    'grid_unit_id' => '',
                    'custom_unit_id' => '',
                    'video_unit_id' => '',
                    'reward_unit_id' => '',
                    'popup_unit_id' => '',
                    'vip_no_ad' => 0,
                    'sort' => 0,
                    'status' => 1,
                ]
            ])->saveData();
        }
    }
}
