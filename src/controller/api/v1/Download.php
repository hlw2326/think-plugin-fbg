<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\model\FbgRes;
use plugin\fbg\model\FbgResDown;

/**
 * 下载管理 API
 * @class Download
 * @package plugin\fbg\controller\api\v1
 */
class Download extends Auth
{
    /**
     * 删除单条下载记录
     */
    public function remove(): void
    {
        $id = intval($this->request->post('id', 0));
        if ($id <= 0) {
            $this->error('ID 不能为空');
        }

        $userId = intval($this->userId);
        FbgResDown::mk()->where(['user_id' => $userId, 'res_id' => $id])->delete();
        $this->success('删除成功');
    }

    /**
     * 清空所有下载记录
     */
    public function clear(): void
    {
        $userId = intval($this->userId);
        FbgResDown::mk()->where(['user_id' => $userId])->delete();
        $this->success('清空成功');
    }

    /**
     * 获取我下载的资源列表 (list)
     */
    public function list(): void
    {
        $page = max(1, intval($this->request->get('page', 1)));
        $limit = max(1, min(50, intval($this->request->get('limit', 10))));
        $userId = intval($this->userId);

        $query = FbgRes::mk()
            ->alias('r')
            ->join("fbg_res_down i", 'r.id = i.res_id')
            ->where('i.user_id', $userId)
            ->where('r.status', 1);

        $count = $query->count();
        $rows = $query->page($page, $limit)->order('i.id desc')->field('r.*')->select()->toArray();

        $list = array_map(static function (array $row): array {
            return [
                'id' => intval($row['id']),
                'cate_id' => intval($row['cate_id']),
                'title' => (string) $row['title'],
                'url' => (string) $row['url'],
                'resolution' => (string) $row['resolution'],
                'size' => (string) $row['size'],
                'tags' => array_values(array_filter(explode(',', (string) $row['tags']))),
                'like_count' => intval($row['like_count']),
                'collect_count' => intval($row['collect_count']),
                'down_count' => format_number(intval($row['down_count'])),
                'view_count' => format_number(intval($row['view_count'])),
                'is_indep_score' => intval($row['is_indep_score'] ?? 0),
                'indep_score' => intval($row['indep_score'] ?? 0),
            ];
        }, $rows);

        $this->success('获取成功', [
            'list' => $list,
            'total' => $count,
        ]);
    }
}
