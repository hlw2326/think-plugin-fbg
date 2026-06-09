<?php

declare(strict_types=1);

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

/**
 * 创建表：fbg_user（插件-用户列表）
 */
class InstallFbgUser extends Migrator
{
    public function getName(): string
    {
        return 'InstallFbgUser';
    }

    public function change(): void
    {
        $table = $this->table('fbg_user', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => '插件-用户列表',
        ]);
        PhinxExtend::upgrade($table, [
            ['token', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '登录Token']],
            ['old_token', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '临时Token']],
            ['old_token_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '临时Token时间']],
            ['openid', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '微信OpenID']],
            ['appid', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '小程序AppID']],
            ['pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '推荐人用户表ID']],
            ['unionid', 'string', ['limit' => 64, 'default' => '', 'null' => true, 'comment' => '微信UnionID']],
            ['nickname', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '用户昵称']],
            ['avatar_url', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '用户头像']],
            ['gender', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '性别']],
            ['birthday', 'date', ['null' => true, 'default' => null, 'comment' => '生日']],
            ['region', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '地区']],
            ['signature', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '个性签名']],
            ['phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '手机号']],
            ['vip_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '会员过期时间戳']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '状态']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态']],
            ['login_ip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '本次登录IP']],
            ['last_login_ip', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '上次登录IP']],
            ['login_at', 'datetime', ['null' => true, 'comment' => '本次登录时间']],
            ['last_login_at', 'datetime', ['null' => true, 'comment' => '上次登录时间']],
            ['remark', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '备注']],
            ['device_model', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '手机型号']],
            ['device_system', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '系统版本']],
            ['screen_width', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '屏幕宽度']],
            ['screen_height', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '屏幕高度']],
            ['sdk_version', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '微信基础库版本']],
            ['version', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '小程序版本号']],
            ['channel', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '小程序来源渠道']],
            ['create_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
            ['update_at', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '更新时间']],
        ], [
            'token', 'openid', 'appid', 'pid', 'unionid', 'phone', 'status', 'deleted', 'create_at',
        ], true);

        $this->execute('ALTER TABLE `fbg_user` AUTO_INCREMENT = 10000');
    }
}
