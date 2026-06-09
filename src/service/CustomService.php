<?php
declare(strict_types=1);

namespace plugin\fbg\service;

use plugin\fbg\model\FbgMp;
use plugin\fbg\model\FbgMpReply;
use think\admin\Storage;
use WeChat\Contracts\Tools;
use WeMini\Custom;
use WeMini\Media;

/**
 * 小程序客服消息服务
 * @class CustomService
 * @package plugin\fbg\service
 */
class CustomService
{
    /**
     * 匹配客服回复规则
     * @param FbgMp $mp
     * @param array $message
     * @return null|FbgMpReply
     */
    public static function match(FbgMp $mp, array $message): ?FbgMpReply
    {
        $msgType = strtolower((string)($message['MsgType'] ?? $message['msgtype'] ?? ''));
        $content = trim((string)($message['Content'] ?? $message['content'] ?? ''));
        $event = trim((string)($message['Event'] ?? $message['event'] ?? ''));
        $target = $msgType === 'event' ? $event : $content;
        foreach ([(string)$mp->appid, ''] as $appid) {
            $rule = self::matchByAppid($appid, $msgType, $target);
            if ($rule) {
                return $rule;
            }
        }
        return null;
    }

    /**
     * 按 AppID 匹配客服回复规则，空 AppID 表示通用回复
     * @param string $appid
     * @param string $msgType
     * @param string $target
     * @return null|FbgMpReply
     */
    private static function matchByAppid(string $appid, string $msgType, string $target): ?FbgMpReply
    {
        $default = null;
        foreach (FbgMpReply::mk()->where(['appid' => $appid, 'status' => 1])->order('sort desc,id asc')->cursor() as $rule) {
            $ruleMsgType = strtolower((string)$rule->msg_type);
            if ($ruleMsgType !== 'all' && $ruleMsgType !== $msgType) {
                continue;
            }

            $matchType = strtolower((string)$rule->match_type);
            $keyword = trim((string)$rule->keyword);
            if ($matchType === 'default') {
                if ($msgType !== 'event' || $target !== 'user_enter_tempsession') {
                    $default ??= $rule;
                }
                continue;
            }
            if ($matchType === 'enter') {
                if ($msgType === 'event' && $target === 'user_enter_tempsession') {
                    return $rule;
                }
                continue;
            }
            if ($target === '' || $keyword === '') {
                continue;
            }
            if ($matchType === 'exact' && $target === $keyword) {
                return $rule;
            }
            if ($matchType === 'contains' && stripos($target, $keyword) !== false) {
                return $rule;
            }
        }
        return $default;
    }

    /**
     * 发送文本客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $content
     * @return array
     */
    public static function sendText(FbgMp $mp, string $openid, string $content): array
    {
        if ($openid === '' || trim($content) === '') {
            return ['errcode' => 0, 'errmsg' => 'empty message'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'text',
            'text' => ['content' => $content],
        ]);
    }

    /**
     * 发送图片客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $imageUrl
     * @return array
     */
    public static function sendImage(FbgMp $mp, string $openid, string $imageUrl): array
    {
        if ($openid === '' || trim($imageUrl) === '') {
            return ['errcode' => 0, 'errmsg' => 'empty image'];
        }

        $upload = Media::instance(self::config($mp))->upload(self::localFile($imageUrl));
        if (empty($upload['media_id'])) {
            return ['errcode' => -1, 'errmsg' => $upload['errmsg'] ?? 'upload image failed'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'image',
            'image' => ['media_id' => $upload['media_id']],
        ]);
    }

    /**
     * 发送图文链接客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $thumbUrl
     * @return array
     */
    public static function sendLink(FbgMp $mp, string $openid, string $title, string $description, string $url, string $thumbUrl): array
    {
        if ($openid === '' || trim($title) === '' || trim($url) === '') {
            return ['errcode' => -1, 'errmsg' => 'missing link parameters'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'link',
            'link' => [
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'thumb_url' => $thumbUrl,
            ],
        ]);
    }

    /**
     * 发送小程序卡片客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $title
     * @param string $pagepath
     * @param string $imageUrl
     * @param string $targetAppid
     * @return array
     */
    public static function sendMiniprogrampage(FbgMp $mp, string $openid, string $title, string $pagepath, string $imageUrl, string $targetAppid = ''): array
    {
        if ($openid === '' || trim($title) === '' || trim($pagepath) === '' || trim($imageUrl) === '') {
            return ['errcode' => -1, 'errmsg' => 'missing miniprogrampage parameters'];
        }

        $upload = Media::instance(self::config($mp))->upload(self::localFile($imageUrl));
        if (empty($upload['media_id'])) {
            return ['errcode' => -1, 'errmsg' => $upload['errmsg'] ?? 'upload image failed'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'miniprogrampage',
            'miniprogrampage' => [
                'title' => $title,
                'appid' => $targetAppid ?: $mp->appid,
                'pagepath' => $pagepath,
                'thumb_media_id' => $upload['media_id'],
            ],
        ]);
    }

    /**
     * 发送语音客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $voiceUrl
     * @return array
     */
    public static function sendVoice(FbgMp $mp, string $openid, string $voiceUrl): array
    {
        if ($openid === '' || trim($voiceUrl) === '') {
            return ['errcode' => 0, 'errmsg' => 'empty voice'];
        }

        $upload = Media::instance(self::config($mp))->upload(self::localFile($voiceUrl), 'voice');
        if (empty($upload['media_id'])) {
            return ['errcode' => -1, 'errmsg' => $upload['errmsg'] ?? 'upload voice failed'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'voice',
            'voice' => ['media_id' => $upload['media_id']],
        ]);
    }

    /**
     * 发送视频客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $title
     * @param string $description
     * @param string $videoUrl
     * @return array
     */
    public static function sendVideo(FbgMp $mp, string $openid, string $title, string $description, string $videoUrl): array
    {
        if ($openid === '' || trim($videoUrl) === '') {
            return ['errcode' => 0, 'errmsg' => 'empty video'];
        }

        $upload = Media::instance(self::config($mp))->upload(self::localFile($videoUrl), 'video');
        if (empty($upload['media_id'])) {
            return ['errcode' => -1, 'errmsg' => $upload['errmsg'] ?? 'upload video failed'];
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'video',
            'video' => [
                'media_id' => $upload['media_id'],
                'title' => $title,
                'description' => $description,
            ],
        ]);
    }

    /**
     * 发送音乐客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param string $title
     * @param string $description
     * @param string $musicUrl
     * @param string $hqMusicUrl
     * @param string $imageUrl
     * @return array
     */
    public static function sendMusic(FbgMp $mp, string $openid, string $title, string $description, string $musicUrl, string $hqMusicUrl, string $imageUrl): array
    {
        if ($openid === '' || trim($musicUrl) === '') {
            return ['errcode' => 0, 'errmsg' => 'empty music url'];
        }

        $mediaId = '';
        if ($imageUrl !== '') {
            $upload = Media::instance(self::config($mp))->upload(self::localFile($imageUrl));
            $mediaId = $upload['media_id'] ?? '';
        }

        return self::send($mp, [
            'touser' => $openid,
            'msgtype' => 'music',
            'music' => [
                'title' => $title,
                'description' => $description,
                'musicurl' => $musicUrl,
                'hqmusicurl' => $hqMusicUrl,
                'thumb_media_id' => $mediaId,
            ],
        ]);
    }

    /**
     * 按规则发送客服消息
     * @param FbgMp $mp
     * @param string $openid
     * @param FbgMpReply $rule
     * @return array
     */
    public static function sendRule(FbgMp $mp, string $openid, FbgMpReply $rule): array
    {
        $res = match (strtolower((string)$rule->reply_type)) {
            'image'            => self::sendImage($mp, $openid, (string)($rule->image_image_url ?: $rule->image_url)),
            'link'             => self::sendLink($mp, $openid, (string)($rule->link_title ?: $rule->title), (string)($rule->link_content ?: $rule->content), (string)($rule->link_url ?: $rule->url), (string)($rule->link_image_url ?: $rule->image_url)),
            'miniprogrampage'  => self::sendMiniprogrampage($mp, $openid, (string)($rule->page_title ?: $rule->title), (string)($rule->page_pagepath ?: $rule->pagepath), (string)($rule->page_image_url ?: $rule->image_url), (string)($rule->page_appid ?: $rule->url)),
            'voice'            => self::sendVoice($mp, $openid, (string)($rule->voice_voice_url ?: $rule->image_url)),
            'video'            => self::sendVideo($mp, $openid, (string)($rule->video_title ?: $rule->title), (string)($rule->video_content ?: $rule->content), (string)($rule->video_video_url ?: $rule->image_url)),
            'music'            => self::sendMusic($mp, $openid, (string)($rule->music_title ?: $rule->title), (string)($rule->music_content ?: $rule->content), (string)($rule->music_url ?: $rule->url), (string)($rule->music_hqurl ?: $rule->pagepath), (string)($rule->music_image_url ?: $rule->image_url)),
            default            => self::sendText($mp, $openid, (string)($rule->text_content ?: $rule->content)),
        };
        if (isset($res['errcode']) && $res['errcode'] === 0) {
            $rule->inc('reply_count')->save();
        }
        return $res;
    }

    /**
     * 发送小程序客服消息
     * @param FbgMp $mp
     * @param array $payload
     * @return array
     */
    private static function send(FbgMp $mp, array $payload): array
    {
        $accessToken = Custom::instance(self::config($mp))->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token={$accessToken}";
        return Tools::json2arr(Tools::post($url, Tools::arr2json($payload), ['headers' => ['Content-Type: application/json']]));
    }

    /**
     * 获取图片本地文件
     * @param string $imageUrl
     * @return string
     */
    private static function localFile(string $imageUrl): string
    {
        if (is_file($imageUrl)) {
            return $imageUrl;
        }
        $path = parse_url($imageUrl, PHP_URL_PATH) ?: $imageUrl;
        $publicFile = syspath('public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR));
        if (is_file($publicFile)) {
            return $publicFile;
        }
        return Storage::down($imageUrl)['file'];
    }

    /**
     * 小程序微信库配置
     * @param FbgMp $mp
     * @return array
     */
    public static function config(FbgMp $mp): array
    {
        return [
            'appid' => (string)$mp->appid,
            'appsecret' => (string)$mp->appsecret,
            'token' => (string)$mp->token,
            'encodingaeskey' => (string)$mp->encodingaeskey,
            'cache_path' => syspath('runtime/wechat'),
        ];
    }
}

