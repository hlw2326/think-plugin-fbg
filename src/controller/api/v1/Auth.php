<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\model\FbgUser;

/**
 * 登录验证 API
 * @class Auth
 * @package plugin\fbg\controller\api\v1
 */
class Auth extends Base
{
    protected function initialize(): void
    {
        parent::initialize();
        $this->checkToken();
    }
}
