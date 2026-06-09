<?php
declare(strict_types=1);

namespace plugin\fbg\controller\mp;

use plugin\fbg\model\FbgMp;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 小程序管理
 * @class Index
 * @package plugin\fbg\controller\mp
 */
class Index extends Controller
{
    /**
     * 客服回调地址
     * @return string
     */
    protected function customerUrl(): string
    {
        return sprintf('%s/plugin-fbg/api.v1.custom/index?appid=小程序AppID', $this->request->domain());
    }

    /**
     * 小程序列表
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        FbgMp::mQuery()->layTable(function () {
            $this->title = '小程序列表';
        }, function (QueryHelper $query) {
            $query->like('name')->like('appid');
            $query->equal('status');
        });
    }

    /**
     * 添加小程序
     * @auth true
     */
    public function add(): void
    {
        $this->_applyFormToken();
        FbgMp::mForm('form');
    }

    /**
     * 编辑小程序
     * @auth true
     */
    public function edit(): void
    {
        $this->_applyFormToken();
        FbgMp::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     */
    protected function _form_filter(array &$data): void
    {
        if ($this->request->isGet()) {
            $this->customerUrl = $this->customerUrl();
            if (!isset($data['custom_reply_enabled'])) {
                $data['custom_reply_enabled'] = 1;
            }
        }
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgMp::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 修改客服消息状态
     * @auth true
     */
    public function custom(): void
    {
        FbgMp::mSave($this->_vali([
            'custom_reply_enabled.in:0,1' => '客服消息状态范围异常！',
            'custom_reply_enabled.require' => '客服消息状态不能为空！',
        ]));
    }

    /**
     * 删除小程序
     * @auth true
     */
    public function remove(): void
    {
        FbgMp::mDelete();
    }

    /**
     * 配置 pages.json
     * @auth true
     */
    public function pages(): void
    {
        $this->_applyFormToken();
        FbgMp::mForm();
    }

    /**
     * 广告配置
     * @auth true
     */
    public function ad(): void
    {
        $this->_applyFormToken();
        FbgMp::mForm();
    }

    /**
     * 导入小程序
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
        $list = isset($data['appid']) ? [$data] : $data;
        if (!is_array($list)) {
            $this->error('无效的JSON格式，必须是单个对象或数组！');
        }

        $successCount = 0;
        $failCount = 0;

        // 允许导入的字段列表
        $allowedFields = [
            'name', 'appsecret', 'pages_config', 'token', 'encodingaeskey', 
            'custom_reply_enabled', 'logo', 'remark', 'banner_unit_id', 'grid_unit_id', 
            'custom_unit_id', 'video_unit_id', 'reward_unit_id', 'popup_unit_id', 
            'ad_global_enabled', 'ad_enabled_banner', 'ad_enabled_grid', 
            'ad_enabled_custom', 'ad_enabled_video', 'ad_enabled_reward', 
            'ad_enabled_popup', 'vip_no_ad', 'sort', 'status'
        ];

        try {
            foreach ($list as $item) {
                if (empty($item['appid'])) {
                    $failCount++;
                    continue;
                }

                $appid = trim((string)$item['appid']);
                $updateData = [];
                foreach ($allowedFields as $field) {
                    if (isset($item[$field])) {
                        $updateData[$field] = $item[$field];
                    }
                }

                $mp = FbgMp::mk()->where(['appid' => $appid])->findOrEmpty();
                if ($mp->isEmpty()) {
                    // 添加新记录
                    $updateData['appid'] = $appid;
                    FbgMp::mk($updateData)->save();
                } else {
                    // 更新已存在记录
                    $mp->save($updateData);
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

    /**
     * 导出数据
     * @auth true
     */
    public function export(): void
    {
        $query = FbgMp::mQuery();
        $query->like('name')->like('appid')->equal('status');
        
        $fields = [
            'name', 'appid', 'appsecret', 'pages_config', 'token', 'encodingaeskey', 
            'custom_reply_enabled', 'logo', 'remark', 'banner_unit_id', 'grid_unit_id', 
            'custom_unit_id', 'video_unit_id', 'reward_unit_id', 'popup_unit_id', 
            'ad_global_enabled', 'ad_enabled_banner', 'ad_enabled_grid', 
            'ad_enabled_custom', 'ad_enabled_video', 'ad_enabled_reward', 
            'ad_enabled_popup', 'vip_no_ad', 'sort', 'status'
        ];
        $list = $query->db()->field($fields)->order('sort desc,id asc')->select()->toArray();
        $data = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="mp_' . date('YmdHis') . '.json"');
        echo $data;
        exit;
    }
}

