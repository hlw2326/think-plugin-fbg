<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\model\FbgUser;
use plugin\fbg\service\AdService;

/**
 * 广告相关 API
 * @class Ad
 * @package plugin\fbg\controller\api\v1
 */
class Ad extends Base
{
    public function config(): void
    {
        $this->success('获取成功', AdService::mpConfig($this->mp));
    }

    public function reward(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式不支持');
        }

        $user = $this->currentUser();
        $result = AdService::grant(intval($user->id));
        if (!$result['state']) {
            $this->error($result['msg'] ?: '发放失败');
        }
        $this->success($result['msg'] ?: '领取成功', $result['data'] ?? []);
    }

    private function currentUser(): FbgUser
    {
        $token = $this->request->header('X-Token', '');
        if ($token === '') {
            $this->error('请先登录', [], 401);
        }

        $user = FbgUser::mk()->where(['token' => $token, 'deleted' => 0])->findOrEmpty();
        if ($user->isEmpty()) {
            $this->error('登录已过期，请重新登录', [], 401);
        }
        if (intval($user->status) !== 1) {
            $this->error('账号已被禁用', [], 403);
        }
        return $user;
    }
}
