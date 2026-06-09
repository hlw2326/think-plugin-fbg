<?php
declare(strict_types=1);

namespace plugin\fbg\service;

use WeMini\Qrcode;

/**
 * 邀请/分享服务
 * @class InviteService
 * @package plugin\fbg\service
 */
class InviteService
{
    /**
     * 生成微信小程序邀请二维码
     *
     * @param string $appid
     * @param string $appsecret
     * @param string|array $scene  二维码场景参数（微信官方限制最大 32 个字符）
     * @param string $page         跳转页面路径
     * @return string              Base64 编码的图片 URI
     * @throws \RuntimeException
     */
    public static function qrcode(string $appid, string $appsecret, $scene, string $page = 'pages/index/index'): string
    {
        if (empty($appid) || empty($appsecret)) {
            throw new \RuntimeException('小程序配置不完整');
        }

        // 自动转换数组为 JSON 字符串
        if (is_array($scene)) {
            $sceneStr = json_encode($scene, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $sceneStr = (string) $scene;
        }

        // 校验微信接口规定的 32 字符限制
        if (strlen($sceneStr) > 32) {
            throw new \RuntimeException("微信小程序二维码 scene 参数长度不能超过 32 字符，当前内容为: {$sceneStr}，长度为: " . strlen($sceneStr));
        }

        // 调用微信接口生成二维码（PNG 二进制流）
        $qr = new Qrcode([
            'appid'     => $appid,
            'appsecret' => $appsecret,
        ]);

        $binary = $qr->createMiniScene($sceneStr, $page, 430, false, null, true);

        // 异常处理：微信接口可能返回 JSON 数组格式 of 错误信息而非二进制流
        if (is_array($binary)) {
            throw new \RuntimeException('微信接口返回错误：' . json_encode($binary, JSON_UNESCAPED_UNICODE));
        }
        if (!is_string($binary) || $binary === '') {
            throw new \RuntimeException('微信接口返回空二维码数据');
        }

        return 'data:image/png;base64,' . base64_encode($binary);
    }
}
