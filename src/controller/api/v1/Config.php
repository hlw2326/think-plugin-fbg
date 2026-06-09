<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\service\AdService;

/**
 * 系统配置 API
 * @class Config
 * @package plugin\fbg\controller\api\v1
 */
class Config extends Base
{
    public function index(): void
    {
        $this->success('获取成功', [
            'base' => [
                'ad_reward_score' => intval(sysconf('fbg.ad_reward_score') ?: 10),
                'download_score' => intval(sysconf('fbg.download_score') ?: 10),
            ],
            'share' => [
                'title' => (string) sysconf('fbg.share_title'),
                'path' => (string) (sysconf('fbg.share_path') ?: '/pages/index/index'),
                'image_url' => (string) sysconf('fbg.share_image'),
            ],
            'contact' => [
                'send_message_title' => (string) sysconf('fbg.contact_send_message_title'),
                'send_message_path' => (string) sysconf('fbg.contact_send_message_path'),
                'send_message_img' => (string) sysconf('fbg.contact_send_message_img'),
                'show_message_card' => (int) (sysconf('fbg.contact_show_message_card') ?: 0) === 1,
                'official_qrcode' => (string) sysconf('fbg.contact_official_qrcode'),
            ],
            'ad' => AdService::mpConfig($this->mp),
        ]);
    }
}
