<?php
declare(strict_types=1);

namespace plugin\fbg\controller\api\v1;

use plugin\fbg\model\FbgResCate;
use plugin\fbg\model\FbgRes;
use plugin\fbg\model\FbgResLike;
use plugin\fbg\model\FbgResCollect;
use plugin\fbg\model\FbgResDown;
use plugin\fbg\model\FbgUser;
use plugin\fbg\service\UserService;

/**
 * 资源中心 API
 * @class Res
 * @package plugin\fbg\controller\api\v1
 */
class Res extends Base
{
    /**
     * 获取所有分类列表
     */
    public function cates(): void
    {
        $rows = FbgResCate::mk()
            ->field('id,name,code,sort,status')
            ->where('status', 1)
            ->order('sort desc, id asc')
            ->select()
            ->toArray();

        $cates = array_map(static fn(array $row): array => [
            'id' => intval($row['id']),
            'name' => (string) $row['name'],
            'code' => (string) $row['code'],
        ], $rows);

        $this->success('获取成功', $cates);
    }

    /**
     * 获取资源列表（支持分页、分类筛选、搜索）
     */
    public function list(): void
    {
        $cateId = intval($this->request->get('cate_id', 0));
        $keyword = trim((string) $this->request->get('keyword', ''));
        $page = max(1, intval($this->request->get('page', 1)));
        $limit = max(1, min(50, intval($this->request->get('limit', 10))));

        $query = FbgRes::mk()->where('status', 1);
        
        if ($cateId > 0) {
            $query->where('cate_id', $cateId);
        }
        
        if ($keyword !== '') {
            $query->whereLike('title|tags', "%{$keyword}%");
        }

        $count = $query->count();
        $rows = $query->page($page, $limit)->order('sort desc, id desc')->select()->toArray();

        $list = array_map(static function(array $row): array {
            return [
                'id' => intval($row['id']),
                'cate_id' => intval($row['cate_id']),
                'title' => (string) $row['title'],
                'url' => (string) $row['url'],
                'ext' => (string) ($row['ext'] ?? 'image'),
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

    /**
     * 获取资源详情
     */
    public function detail(): void
    {
        $id = intval($this->request->get('id', 0));
        if ($id <= 0) {
            $this->error('ID 不能为空');
        }

        $res = FbgRes::mk()->where(['id' => $id, 'status' => 1])->findOrEmpty();
        if ($res->isEmpty()) {
            $this->error('资源不存在或已下架');
        }

        // 增加浏览量
        FbgRes::mk()->where('id', $id)->inc('view_count')->update();

        $isLiked = false;
        $isCollected = false;

        // 检测当前登录状态，获取互动状态
        $token = $this->request->header('X-Token', '');
        if ($token !== '') {
            $user = FbgUser::mk()->where(['token' => $token, 'deleted' => 0, 'status' => 1])->findOrEmpty();
            if ($user->isEmpty()) {
                $user = FbgUser::mk()->where(['old_token' => $token, 'deleted' => 0, 'status' => 1])->findOrEmpty();
            }
            if (!$user->isEmpty()) {
                $isLiked = FbgResLike::mk()->where(['user_id' => $user->id, 'res_id' => $id])->count() > 0;
                $isCollected = FbgResCollect::mk()->where(['user_id' => $user->id, 'res_id' => $id])->count() > 0;
            }
        }

        $data = [
            'id' => intval($res->id),
            'cate_id' => intval($res->cate_id),
            'title' => (string) $res->title,
            'url' => (string) $res->url,
            'ext' => (string) ($res->ext ?? 'image'),
            'resolution' => (string) $res->resolution,
            'size' => (string) $res->size,
            'tags' => array_values(array_filter(explode(',', (string) $res->tags))),
            'like_count' => intval($res->like_count),
            'collect_count' => intval($res->collect_count),
            'down_count' => format_number(intval($res->down_count)),
            'view_count' => format_number(intval($res->view_count) + 1),
            'is_liked' => $isLiked,
            'is_collected' => $isCollected,
            'is_indep_score' => intval($res->is_indep_score ?? 0),
            'indep_score' => intval($res->indep_score ?? 0),
        ];

        $this->success('获取成功', $data);
    }

    /**
     * 下载资源：扣除积分并记录下载历史
     * @token true
     */
    public function download(): void
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
        $deducted = false;

        // 1. 检查是否为会员 (会员免积分)
        $isVip = intval($this->user->vip_time) > time();

        if (!$isVip) {
            // 2. 检查积分是否足够
            $downloadScore = intval(sysconf('fbg.download_score') ?: 10);
            if (isset($res->is_indep_score) && intval($res->is_indep_score) === 1) {
                $downloadScore = intval($res->indep_score);
            }

            $score = intval($this->user->score);
            if ($score < $downloadScore) {
                $this->error('积分余额不足，请观看广告获取积分');
            }

            // 扣除积分，增加累计下载/解析数
            $this->user->score_source = 'download';
            $this->user->score_remark = sprintf("下载资源并扣除积分[资源名称: %s]", $res->title ?? '');
            $this->user->score_change_value = -$downloadScore;
            $this->user->save([
                'score' => $score - $downloadScore,
                'down_total' => intval($this->user->down_total) + 1,
            ]);
            $deducted = true;
        }

        // 3. 写入下载记录 (如果已存在，则更新时间，避免在下载列表中出现重复数据)
        $down = FbgResDown::mk()->where(['user_id' => $userId, 'res_id' => $id])->findOrEmpty();
        if ($down->isEmpty()) {
            FbgResDown::mk()->save([
                'user_id' => $userId,
                'res_id' => $id,
            ]);
        } else {
            $down->save([
                'update_at' => date('Y-m-d H:i:s')
            ]);
        }

        // 4. 增加资源的下载数
        FbgRes::mk()->where('id', $id)->inc('down_count')->update();

        $this->user->refresh();
        $this->success('开始下载', [
            'user' => UserService::profile($this->user),
            'deducted' => $deducted,
        ]);
    }
}
