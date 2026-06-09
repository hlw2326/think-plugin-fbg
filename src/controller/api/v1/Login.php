<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\service\UserService;
use WeMini\Crypt;

/**
 * 登录服务 API
 * @class Login
 * @package plugin\fbg\controller\api\v1
 */
class Login extends Base
{
    public function in(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式不支持');
        }

        $data = $this->_vali([
            'code.require' => 'code 不能为空！',
            'nickname.default' => '',
            'avatar_url.default' => '',
            'invite_uid.default' => '',
            'uid.default' => '',
        ]);

        if (empty($this->mp->appid) || empty($this->mp->appsecret)) {
            $this->error('小程序配置不完整，请在后台配置 AppID 和 AppSecret');
        }

        try {
            $session = Crypt::instance([
                'appid' => $this->mp->appid,
                'appsecret' => $this->mp->appsecret,
            ])->session($data['code']);
        } catch (\Exception $exception) {
            $this->error('微信接口调用失败：' . $exception->getMessage());
        }

        if (empty($session['openid'])) {
            $this->error('登录失败：' . ($session['errmsg'] ?? 'code 无效'));
        }

        try {
            $user = UserService::sync(
                openid: $session['openid'],
                unionid: $session['unionid'] ?? '',
                profile: ['nickname' => $data['nickname'], 'avatar_url' => $data['avatar_url']],
                device: $this->device,
                ip: $this->request->ip(),
                inviteUid: (string) ($data['invite_uid'] ?: $data['uid']),
                appid: $this->mp->appid ?? ''
            );
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());
        }

        $oldToken = (string)($user->token ?? '');
        $token = bin2hex(random_bytes(32));
        $user->save([
            'token' => $token,
            'old_token' => $oldToken,
            'old_token_time' => time(),
        ]);
        $user->refresh();

        $this->success('登录成功', [
            'token' => $token,
            'user' => UserService::profile($user),
        ]);
    }
}
