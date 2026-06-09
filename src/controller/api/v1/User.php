<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\service\UserService;

/**
 * 用户相关 API
 * @class User
 * @package plugin\fbg\controller\api\v1
 */
class User extends Auth
{
    public function info(): void
    {
        $this->success('获取成功', UserService::profile($this->user));
    }

    public function update(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式不支持');
        }

        $data = $this->_vali([
            'nickname.default' => null,
            'avatar_url.default' => null,
            'gender.default' => null,
            'birthday.default' => null,
            'region.default' => null,
            'signature.default' => null,
            'score.default' => null,
        ]);

        $update = [];

        if ($data['nickname'] !== null) {
            $nickname = trim((string) $data['nickname']);
            if ($nickname === '') {
                $this->error('昵称不能为空');
            }
            if (mb_strlen($nickname) > 30) {
                $this->error('昵称不能超过 30 个字符');
            }
            $update['nickname'] = $nickname;
        }

        if ($data['avatar_url'] !== null) {
            $url = trim((string) $data['avatar_url']);
            if ($url !== '') {
                $host = parse_url($url, PHP_URL_HOST);
                if (empty($host) || !in_array($host, Upload::allowedHosts($this->request), true)) {
                     $this->error('非法的头像地址');
                }
            }
            $update['avatar_url'] = $url;
        }

        if ($data['gender'] !== null) {
            $gender = intval($data['gender']);
            if (!in_array($gender, [0, 1, 2], true)) {
                $this->error('性别参数不合法');
            }
            $update['gender'] = $gender;
        }

        if ($data['birthday'] !== null) {
            $birthday = trim((string) $data['birthday']);
            if ($birthday !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday)) {
                $this->error('生日格式应为 YYYY-MM-DD');
            }
            $update['birthday'] = $birthday === '' ? null : $birthday;
        }

        if ($data['region'] !== null) {
            $region = trim((string) $data['region']);
            if (mb_strlen($region) > 100) {
                $this->error('地区长度超限');
            }
            $update['region'] = $region;
        }

        if ($data['signature'] !== null) {
            $signature = trim((string) $data['signature']);
            if (mb_strlen($signature) > 100) {
                $this->error('个性签名不能超过 100 字');
            }
            $update['signature'] = $signature;
        }

        if ($data['score'] !== null) {
            $update['score'] = intval($data['score']);
        }

        if (empty($update)) {
            $this->error('没有要更新的字段');
        }

        $this->user->save($update);
        $this->user->refresh();

        $this->success('保存成功', UserService::profile($this->user));
    }
}
