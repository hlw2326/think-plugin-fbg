<?php
declare(strict_types=1);

namespace plugin\fbg\controller\tools;

use plugin\fbg\model\FbgTools;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 工具列表
 * @class Index
 * @package plugin\fbg\controller\tools
 */
class Index extends Controller
{
    /**
     * 工具列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgTools::mQuery()->layTable(function () {
            $this->title = '工具列表';
        }, function (QueryHelper $query) {
            $query->like('title')->like('desc')->like('appid');
            $query->equal('status');
        });
    }

    /**
     * 添加工具
     * @auth true
     */
    public function add(): void
    {
        $this->_applyFormToken();
        FbgTools::mForm('form');
    }

    /**
     * 编辑工具
     * @auth true
     */
    public function edit(): void
    {
        $this->_applyFormToken();
        FbgTools::mForm('form');
    }

    /**
     * 修改状态或排序
     * @auth true
     */
    public function state(): void
    {
        FbgTools::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除小程序
     * @auth true
     */
    public function remove(): void
    {
        FbgTools::mDelete();
    }

    /**
     * 导出数据
     * @auth true
     */
    public function export(): void
    {
        $query = FbgTools::mQuery();
        $query->like('title')->like('desc')->like('appid')->equal('status');
        $fields = ['title', 'desc', 'logo', 'appid', 'path', 'click_count', 'sort', 'status'];
        $list = $query->db()->field($fields)->order('sort desc,id asc')->select()->toArray();
        $data = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="tools_' . date('YmdHis') . '.json"');
        echo $data;
        exit;
    }

    /**
     * 导入数据
     * @auth true
     */
    public function import(): void
    {
        if ($this->request->isGet()) {
            $this->fetch();
            return;
        }

        $jsonData = $this->request->post('json_data', '');
        if (empty($jsonData)) {
            $this->error('JSON数据不能为空！');
        }

        $data = json_decode($jsonData, true);
        if ($data === null) {
            $this->error('JSON解析失败，请检查格式是否正确！');
        }

        // 支持导入单条或多条数组
        $list = isset($data['title']) ? [$data] : $data;
        if (!is_array($list)) {
            $this->error('无效的JSON格式，必须是单个对象或数组！');
        }

        $successCount = 0;
        $failCount = 0;

        // 允许导入的字段列表
        $allowedFields = [
            'title', 'desc', 'logo', 'appid', 'path', 'click_count', 'sort', 'status'
        ];

        try {
            foreach ($list as $item) {
                if (empty($item['title'])) {
                    $failCount++;
                    continue;
                }

                $title = trim((string)$item['title']);
                $appid = isset($item['appid']) ? trim((string)$item['appid']) : '';

                $updateData = [];
                foreach ($allowedFields as $field) {
                    if (isset($item[$field])) {
                        $updateData[$field] = $item[$field];
                    }
                }

                // 匹配规则：如果有 appid 则通过 appid 查找，否则通过 title 查找
                if (!empty($appid)) {
                    $tool = FbgTools::mk()->where(['appid' => $appid])->findOrEmpty();
                } else {
                    $tool = FbgTools::mk()->where(['title' => $title])->findOrEmpty();
                }

                if ($tool->isEmpty()) {
                    // 添加新记录
                    FbgTools::mk($updateData)->save();
                } else {
                    // 更新已存在记录
                    $tool->save($updateData);
                }
                $successCount++;
            }
        } catch (\think\exception\HttpResponseException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->error('导入失败：' . $e->getMessage());
        }
        $this->success("导入成功！已成功添加/更新了 {$successCount} 条，失败 {$failCount} 条。");
    }
}


