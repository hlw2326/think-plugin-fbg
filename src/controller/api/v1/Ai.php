<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\service\AiService;
use Throwable;

/**
 * AI 服务 API
 * @class Ai
 * @package plugin\fbg\controller\api\v1
 */
class Ai extends Auth
{
    /**
     * AI 对话调用接口
     */
    public function chat(): void
    {
        if (!$this->request->isPost()) {
            $this->error('请求方式不支持');
        }

        $config = AiService::config();
        if (empty($config['enabled'])) {
            $this->error('AI 服务暂未启用');
        }

        $prompt = trim((string) $this->request->post('prompt', ''));
        $messages = $this->request->post('messages', []);

        if ($prompt === '' && empty($messages)) {
            $this->error('内容不能为空');
        }

        try {
            $reply = AiService::chat($prompt, $messages);
            $this->success('请求成功', [
                'reply' => $reply,
            ]);
        } catch (Throwable $exception) {
            $this->error('AI 响应失败: ' . $exception->getMessage());
        }
    }
}
