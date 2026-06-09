<?php
declare(strict_types=1);

namespace plugin\fbg\controller\help;

use plugin\fbg\model\FbgHelp;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 帮助列表
 * @class Index
 * @package plugin\fbg\controller\help
 */
class Index extends Controller
{
    /**
     * 帮助列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgHelp::mQuery()->layTable(function () {
            $this->title = '帮助列表';
        }, function (QueryHelper $query) {
            $query->like('question')->like('answer');
            $query->where(['type' => 'faq'])->equal('status');
        });
    }

    /**
     * 添加帮助
     * @auth true
     */
    public function add(): void
    {
        $this->_applyFormToken();
        FbgHelp::mForm('form');
    }

    /**
     * 编辑帮助
     * @auth true
     */
    public function edit(): void
    {
        $this->_applyFormToken();
        FbgHelp::mForm('form');
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgHelp::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除帮助
     * @auth true
     */
    public function remove(): void
    {
        FbgHelp::mDelete();
    }

    /**
     * 导出数据
     * @auth true
     */
    public function export(): void
    {
        $query = FbgHelp::mQuery();
        $query->like('question')->like('answer')->equal('status');
        $fields = ['type', 'question', 'answer', 'sort', 'status'];
        $list = $query->db()->where(['type' => 'faq'])->field($fields)->order('sort desc,id asc')->select()->toArray();
        $data = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="help_' . date('YmdHis') . '.json"');
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
        $list = isset($data['question']) ? [$data] : $data;
        if (!is_array($list)) {
            $this->error('无效的JSON格式，必须是单个对象或数组！');
        }

        $successCount = 0;
        $failCount = 0;

        // 允许导入的字段列表
        $allowedFields = [
            'question', 'answer', 'sort', 'status'
        ];

        try {
            foreach ($list as $item) {
                if (empty($item['question'])) {
                    $failCount++;
                    continue;
                }

                $question = trim((string)$item['question']);

                $updateData = [];
                foreach ($allowedFields as $field) {
                    if (isset($item[$field])) {
                        $updateData[$field] = $item[$field];
                    }
                }
                $updateData['type'] = 'faq';

                // 匹配规则：通过 question 和 type 查找
                $help = FbgHelp::mk()->where(['type' => 'faq', 'question' => $question])->findOrEmpty();

                if ($help->isEmpty()) {
                    // 添加新记录
                    FbgHelp::mk($updateData)->save();
                } else {
                    // 更新已存在记录
                    $help->save($updateData);
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


