<?php

declare(strict_types=1);

namespace plugin\fbg\exception;

use think\exception\Handle;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;
use Throwable;

/**
 * API 全局异常处理器。
 */
class ApiExceptionHandle extends Handle
{
    public function render(Request $request, Throwable $e): Response
    {
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        }

        if (str_contains($request->pathinfo(), 'api.')) {
            $this->report($e);
            return json([
                'code' => 0,
                'info' => $e->getMessage() ?: '服务器内部错误',
                'data' => (object) [],
            ], 200);
        }

        return parent::render($request, $e);
    }
}

