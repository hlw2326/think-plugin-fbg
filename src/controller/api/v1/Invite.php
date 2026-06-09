<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\service\InviteService;

/**
 * 邀请/分享相关 API（登录态）
 * @class Invite
 * @package plugin\fbg\controller\api\v1
 */
class Invite extends Auth
{
    /**
     * 生成/获取当前用户的专属邀请小程序码
     *
     * 扫码启动后，options.query.scene = 邀请人用户 id
     * @return void
     */
    public function qrcode(): void
    {
        try {
            $url = InviteService::qrcode(
                (string) ($this->mp->appid ?? ''),
                (string) ($this->mp->appsecret ?? ''),
                (string) $this->user->id,
                'pages/index/index'
            );
        } catch (\Throwable $e) {
            $this->error('生成二维码失败：' . ($e->getMessage() ?: get_class($e)));
            return;
        }

        $this->success('获取成功', [
            'qrcode_url' => $url,
            'page'       => 'pages/index/index',
        ]);
    }
}
