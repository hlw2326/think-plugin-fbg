<?php
declare(strict_types=1);

namespace plugin\fbg\controller\main;

use plugin\fbg\model\FbgUser;
use plugin\fbg\model\FbgTools;
use plugin\fbg\model\FbgHelp;
use plugin\fbg\model\FbgMpReply;
use plugin\fbg\model\FbgRes;
use plugin\fbg\model\FbgResCate;
use think\admin\Controller;

/**
 * 系统统计
 * @class Index
 * @package plugin\fbg\controller\main
 */
class Index extends Controller
{
    /**
     * 系统统计
     * @menu true
     * @auth true
     */
    public function index(): void
    {
        $this->title = '系统统计';
        
        // 核心统计指标
        $this->user_count = FbgUser::mk()->cache(true, 10)->count();
        $this->day_user_count = FbgUser::mk()->whereDay('create_at')->cache(true, 10)->count();
        $this->vip_count = FbgUser::mk()->where('vip_time', '>', time())->cache(true, 10)->count();
        
        $this->res_count = FbgRes::mk()->cache(true, 10)->count();
        $this->down_count = FbgRes::mk()->cache(true, 10)->sum('down_count');
        $this->view_count = FbgRes::mk()->cache(true, 10)->sum('view_count');
        $this->like_count = FbgRes::mk()->cache(true, 10)->sum('like_count');
        $this->collect_count = FbgRes::mk()->cache(true, 10)->sum('collect_count');

        $this->tools_count = FbgTools::mk()->cache(true, 10)->count();
        $this->tools_click_count = FbgTools::mk()->cache(true, 10)->sum('click_count');
        
        $this->help_count = FbgHelp::mk()->cache(true, 10)->count();
        $this->reply_count = FbgMpReply::mk()->cache(true, 10)->count();

        // 近半月新增用户趋势
        $this->days = $this->app->cache->get('base_portals_days', []);
        if (empty($this->days)) {
            for ($i = 15; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i}days"));
                $this->days[] = [
                    '当天日期' => date('m-d', strtotime("-{$i}days")),
                    '新增用户' => FbgUser::mk()->whereLike('create_at', "{$date}%")->count(),
                ];
            }
            $this->app->cache->set('base_portals_days', $this->days, 60);
        }

        // 工具点击排行统计数据
        $this->tools_list = FbgTools::mk()->order('click_count desc')->limit(10)->select()->toArray();
        
        // 资源分类分布统计
        $cateTable = FbgResCate::mk()->getTable();
        $this->cate_stats = FbgRes::mk()
            ->alias('r')
            ->join("{$cateTable} c", 'r.cate_id = c.id')
            ->group('c.name')
            ->field('c.name as name, count(r.id) as value')
            ->select()
            ->toArray();
        
        $this->fetch();
    }
}
