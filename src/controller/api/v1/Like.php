<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\model\FbgRes;
use plugin\fbg\model\FbgResLike;

/**
 * 点赞互动 API
 * @class Like
 * @package plugin\fbg\controller\api\v1
 */
class Like extends Auth
{
    /**
     * 点赞/取消点赞
     */
    public function click(): void
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            $this->error('ID 不能为空');
        }

        $res = FbgRes::mk()->where(['id' => $id, 'status' => 1])->findOrEmpty();
        if ($res->isEmpty()) {
            $this->error('资源不存在或已下架');
        }

        $userId = intval($this->userId);
        $like = FbgResLike::mk()->where(['user_id' => $userId, 'res_id' => $id])->findOrEmpty();

        if ($like->isEmpty()) {
            // 点赞
            FbgResLike::mk()->save([
                'user_id' => $userId,
                'res_id' => $id,
            ]);
            FbgRes::mk()->where('id', $id)->inc('like_count')->update();
            $liked = true;
        } else {
            // 取消点赞
            $like->delete();
            FbgRes::mk()->where('id', $id)->dec('like_count')->update();
            $liked = false;
        }

        $res->refresh();
        $this->success($liked ? '点赞成功' : '已取消点赞', [
            'liked' => $liked,
            'like_count' => intval($res->like_count),
        ]);
    }
}
