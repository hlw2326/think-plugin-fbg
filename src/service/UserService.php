<?php
declare(strict_types=1);

namespace plugin\fbg\service;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgResCollect;
use plugin\fbg\model\FbgResLike;

/**
 * 用户服务
 */
class UserService
{
    public static function profile(FbgUser $user): array
    {
        return [
            'id' => intval($user->id),
            'nickname' => (string) $user->nickname,
            'avatar_url' => (string) $user->avatar_url,
            'phone' => (string) $user->phone,
            'vip_time' => intval($user->vip_time),
            'gender' => intval($user->gender),
            'birthday' => (string) ($user->birthday ?? ''),
            'region' => (string) ($user->region ?? ''),
            'signature' => (string) ($user->signature ?? ''),
            'score' => intval($user->score ?? 100),
            'down_total' => intval($user->down_total ?? 0),
            'vip_no_ad' => intval($user->vip_no_ad ?? 0),
            'collect_count' => intval(FbgResCollect::mk()->where('user_id', intval($user->id))->count()),
            'like_count' => intval(FbgResLike::mk()->where('user_id', intval($user->id))->count()),
        ];
    }

    public static function sync(
        string $openid,
        string $unionid,
        array $profile,
        array $device,
        string $ip,
        string $inviteUid = '',
        string $appid = ''
    ): FbgUser {
        $user = FbgUser::mk()->where('openid', $openid)->findOrEmpty();

        if ($user->isEmpty()) {
            return static::register($openid, $unionid, $profile, $device, $ip, $inviteUid, $appid);
        }

        return static::refresh($user, $unionid, $device, $ip);
    }

    private static function register(
        string $openid,
        string $unionid,
        array $profile,
        array $device,
        string $ip,
        string $inviteUid,
        string $appid
    ): FbgUser {
        $pid = 0;
        $inviteUserId = intval($inviteUid);
        if ($inviteUserId > 0) {
            $inviter = FbgUser::mk()->where(['id' => $inviteUserId, 'deleted' => 0, 'status' => 1])->findOrEmpty();
            if ($inviter->isExists()) {
                $pid = intval($inviter->id);
            }
        }

        $user = FbgUser::mk();
        $user->save([
            'openid' => $openid,
            'appid' => $appid,
            'pid' => $pid,
            'unionid' => $unionid,
            'nickname' => $profile['nickname'] ?? '',
            'avatar_url' => $profile['avatar_url'] ?? '',
            'device_model' => $device['device_model'] ?? '',
            'device_system' => $device['device_system'] ?? '',
            'screen_width' => intval($device['screen_width'] ?? 0),
            'screen_height' => intval($device['screen_height'] ?? 0),
            'sdk_version' => $device['sdk_version'] ?? '',
            'version' => $device['version'] ?? '',
            'channel' => $device['channel'] ?? '',
            'login_ip' => $ip,
            'login_at' => date('Y-m-d H:i:s'),
            'status' => 1,
        ]);

        return $user;
    }

    private static function refresh(FbgUser $user, string $unionid, array $device, string $ip): FbgUser
    {
        if (intval($user->status) !== 1) {
            throw new \RuntimeException('账号已被禁用');
        }

        $update = [
            'last_login_ip' => $user->login_ip,
            'last_login_at' => $user->login_at,
            'login_ip' => $ip,
            'login_at' => date('Y-m-d H:i:s'),
            'device_model' => $device['device_model'] ?? '',
            'device_system' => $device['device_system'] ?? '',
            'screen_width' => intval($device['screen_width'] ?? 0),
            'screen_height' => intval($device['screen_height'] ?? 0),
            'sdk_version' => $device['sdk_version'] ?? '',
            'version' => $device['version'] ?? '',
            'channel' => $device['channel'] ?? '',
        ];

        if ($unionid !== '' && empty($user->unionid)) {
            $update['unionid'] = $unionid;
        }

        $user->save($update);
        return $user;
    }
}
