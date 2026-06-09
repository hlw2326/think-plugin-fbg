<?php
declare(strict_types=1);

namespace plugin\fbg\controller\mp;

use plugin\fbg\model\FbgMp;
use plugin\fbg\model\FbgMpReply;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\SystemService;

/**
 * 回复规则
 * @class Reply
 * @package plugin\fbg\controller\mp
 */
class Reply extends Controller
{
    /**
     * 回复类型
     * @var array<string,string>
     */
    public array $types = [
        'text'             => '文字',
        'image'            => '图片',
        'link'             => '图文链接',
        'miniprogrampage'  => '小程序卡片',
        'transfer'         => '转人工客服',
        'voice'            => '语音',
        'video'            => '视频',
        'music'            => '音乐',
    ];

    /**
     * 匹配方式
     * @var array<string,string>
     */
    public array $matchTypes = [
        'default'  => '默认回复',
        'exact'    => '完全匹配',
        'contains' => '包含匹配',
        'enter'    => '进入会话',
    ];

    /**
     * 回复规则
     * @auth true
     * @menu true
     */
    public function index(): void
    {
        $this->appid = (string)($this->get['appid'] ?? '');
        FbgMpReply::mQuery()->layTable(function () {
            $this->title = '回复规则';
            $this->mps = $this->mps();
        }, function (QueryHelper $query) {
            $query->like('keyword|content#keys')->equal('reply_type#mtype,match_type,status')->dateBetween('create_at');
            $query->where(['appid' => $this->appid]);
        });
    }

    /**
     * 列表数据处理
     * @param array $data
     */
    protected function _index_page_filter(array &$data): void
    {
        foreach ($data as &$vo) {
            $vo['type'] = $this->types[$vo['reply_type']] ?? $vo['reply_type'];
            $vo['match_name'] = $this->matchTypes[$vo['match_type']] ?? $vo['match_type'];
            $vo['keys'] = $vo['match_type'] === 'default' ? '默认回复' : ($vo['match_type'] === 'enter' ? '进入会话回复' : ($vo['keyword'] ?: '-'));
            $vo['appid'] = $vo['appid'] ?: '通用回复';
        }
        unset($vo);
    }

    /**
     * 保存排序
     * @auth true
     */
    public function sort(): void
    {
        FbgMpReply::mSave($this->_vali([
            'sort.require' => '排序值不能为空！',
            'sort.number'  => '排序值格式异常！',
        ]));
    }

    /**
     * 默认回复
     * @auth true
     */
    public function defaults(): void
    {
        $this->_applyFormToken();
        $appid = (string)($this->get['appid'] ?? '');
        $data = ['appid' => $appid, 'match_type' => 'default', 'msg_type' => 'all', 'status' => 1, 'sort' => 0];
        $vo = FbgMpReply::mk()->where(['appid' => $appid, 'match_type' => 'default'])->findOrEmpty()->toArray();
        if (!empty($vo)) {
            $data = array_merge($data, $vo);
        }
        FbgMpReply::mForm('form', 'id', [], $data);
    }

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
     * @return array
     */
    protected function mps(): array
    {
        return FbgMp::mk()->where(['status' => 1])->order('sort desc,id asc')->select()->toArray();
    }

    /**
     * 表单视图变量
     */
    protected function assignFormVars(): void
    {
        $this->mps = $this->mps();
        $this->defaultImage = SystemService::uri('/static/theme/img/image.png', '__FULL__');
        $this->customerUrl = $this->customerUrl();
    }



    /**
     * 添加规则
     * @auth true
     */
    public function add(): void
    {
        $this->_applyFormToken();
        FbgMpReply::mForm('form');
    }

    /**
     * 编辑规则
     * @auth true
     */
    public function edit(): void
    {
        $this->_applyFormToken();
        FbgMpReply::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     */
    protected function _form_filter(array &$data): void
    {
        if ($this->request->isGet()) {
            $this->assignFormVars();
            if (empty($data['appid']) && !empty($this->get['appid'])) {
                $data['appid'] = (string)$this->get['appid'];
            }
            // 填充表单特定字段值 (带向后兼容 fallback)
            $data['text_content'] = ($data['text_content'] ?? '') ?: (($data['reply_type'] ?? '') === 'text' ? ($data['content'] ?? '') : '');
            $data['image_image_url'] = ($data['image_image_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'image' ? ($data['image_url'] ?? '') : '');
            
            $data['link_title'] = ($data['link_title'] ?? '') ?: (($data['reply_type'] ?? '') === 'link' ? ($data['title'] ?? '') : '');
            $data['link_content'] = ($data['link_content'] ?? '') ?: (($data['reply_type'] ?? '') === 'link' ? ($data['content'] ?? '') : '');
            $data['link_url'] = ($data['link_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'link' ? ($data['url'] ?? '') : '');
            $data['link_image_url'] = ($data['link_image_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'link' ? ($data['image_url'] ?? '') : '');
            
            $data['page_title'] = ($data['page_title'] ?? '') ?: (($data['reply_type'] ?? '') === 'miniprogrampage' ? ($data['title'] ?? '') : '');
            $data['page_pagepath'] = ($data['page_pagepath'] ?? '') ?: (($data['reply_type'] ?? '') === 'miniprogrampage' ? ($data['pagepath'] ?? '') : '');
            $data['page_image_url'] = ($data['page_image_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'miniprogrampage' ? ($data['image_url'] ?? '') : '');
            $data['page_appid'] = ($data['page_appid'] ?? '') ?: (($data['reply_type'] ?? '') === 'miniprogrampage' ? ($data['url'] ?? '') : '');

            $data['voice_voice_url'] = ($data['voice_voice_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'voice' ? ($data['image_url'] ?? '') : '');

            $data['video_title'] = ($data['video_title'] ?? '') ?: (($data['reply_type'] ?? '') === 'video' ? ($data['title'] ?? '') : '');
            $data['video_content'] = ($data['video_content'] ?? '') ?: (($data['reply_type'] ?? '') === 'video' ? ($data['content'] ?? '') : '');
            $data['video_video_url'] = ($data['video_video_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'video' ? ($data['image_url'] ?? '') : '');

            $data['music_title'] = ($data['music_title'] ?? '') ?: (($data['reply_type'] ?? '') === 'music' ? ($data['title'] ?? '') : '');
            $data['music_content'] = ($data['music_content'] ?? '') ?: (($data['reply_type'] ?? '') === 'music' ? ($data['content'] ?? '') : '');
            $data['music_url'] = ($data['music_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'music' ? ($data['url'] ?? '') : '');
            $data['music_hqurl'] = ($data['music_hqurl'] ?? '') ?: (($data['reply_type'] ?? '') === 'music' ? ($data['pagepath'] ?? '') : '');
            $data['music_image_url'] = ($data['music_image_url'] ?? '') ?: (($data['reply_type'] ?? '') === 'music' ? ($data['image_url'] ?? '') : '');
            return;
        }
        
        $data['appid'] = trim((string)($data['appid'] ?? ''));
        $data['msg_type'] = 'all';
        $data['reply_type'] = trim((string)($data['reply_type'] ?? 'text'));
        
        if (in_array($data['match_type'] ?? '', ['default', 'enter'])) {
            $data['keyword'] = '';
            $query = FbgMpReply::mk()->where(['appid' => $data['appid'] ?? '', 'match_type' => $data['match_type']]);
            if (!empty($data['id'])) {
                $query->where('id', '<>', $data['id']);
            }
            if ($query->count() > 0) {
                $label = $data['match_type'] === 'default' ? '默认回复' : '进入会话回复';
                $this->error("该小程序已存在{$label}");
            }
        } elseif (trim((string)($data['keyword'] ?? '')) === '') {
            $this->error('请输入匹配关键词');
        }

        // 整理所有独立的字段
        $data['text_content'] = trim((string)($data['text_content'] ?? ''));
        $data['image_image_url'] = trim((string)($data['image_image_url'] ?? ''));
        $data['link_title'] = trim((string)($data['link_title'] ?? ''));
        $data['link_content'] = trim((string)($data['link_content'] ?? ''));
        $data['link_url'] = trim((string)($data['link_url'] ?? ''));
        $data['link_image_url'] = trim((string)($data['link_image_url'] ?? ''));
        $data['page_title'] = trim((string)($data['page_title'] ?? ''));
        $data['page_pagepath'] = trim((string)($data['page_pagepath'] ?? ''));
        $data['page_image_url'] = trim((string)($data['page_image_url'] ?? ''));
        $data['page_appid'] = trim((string)($data['page_appid'] ?? ''));
        $data['voice_voice_url'] = trim((string)($data['voice_voice_url'] ?? ''));
        $data['video_title'] = trim((string)($data['video_title'] ?? ''));
        $data['video_content'] = trim((string)($data['video_content'] ?? ''));
        $data['video_video_url'] = trim((string)($data['video_video_url'] ?? ''));
        $data['music_title'] = trim((string)($data['music_title'] ?? ''));
        $data['music_content'] = trim((string)($data['music_content'] ?? ''));
        $data['music_url'] = trim((string)($data['music_url'] ?? ''));
        $data['music_hqurl'] = trim((string)($data['music_hqurl'] ?? ''));
        $data['music_image_url'] = trim((string)($data['music_image_url'] ?? ''));

        // 仅对当前激活的类型进行非空验证
        if ($data['reply_type'] === 'text') {
            if ($data['text_content'] === '') {
                $this->error('请输入文本回复内容');
            }
        } elseif ($data['reply_type'] === 'image') {
            if ($data['image_image_url'] === '') {
                $this->error('请上传回复图片');
            }
        } elseif ($data['reply_type'] === 'link') {
            if ($data['link_title'] === '') {
                $this->error('请输入图文标题');
            }
            if ($data['link_url'] === '') {
                $this->error('请输入图文链接');
            }
        } elseif ($data['reply_type'] === 'miniprogrampage') {
            if ($data['page_title'] === '') {
                $this->error('请输入小程序卡片标题');
            }
            if ($data['page_pagepath'] === '') {
                $this->error('请输入小程序卡片页面路径');
            }
            if ($data['page_image_url'] === '') {
                $this->error('请上传卡片封面图片');
            }
        } elseif ($data['reply_type'] === 'voice') {
            if ($data['voice_voice_url'] === '') {
                $this->error('请上传语音文件');
            }
        } elseif ($data['reply_type'] === 'video') {
            if ($data['video_title'] === '') {
                $this->error('请输入视频标题');
            }
            if ($data['video_video_url'] === '') {
                $this->error('请上传视频文件');
            }
        } elseif ($data['reply_type'] === 'music') {
            if ($data['music_title'] === '') {
                $this->error('请输入音乐标题');
            }
            if ($data['music_url'] === '') {
                $this->error('请输入音乐链接');
            }
        }

        // 同步旧的基础字段，保证向前兼容
        if ($data['reply_type'] === 'text') {
            $data['content'] = $data['text_content'];
            $data['image_url'] = '';
            $data['title'] = '';
            $data['pagepath'] = '';
            $data['url'] = '';
        } elseif ($data['reply_type'] === 'image') {
            $data['content'] = '';
            $data['image_url'] = $data['image_image_url'];
            $data['title'] = '';
            $data['pagepath'] = '';
            $data['url'] = '';
        } elseif ($data['reply_type'] === 'link') {
            $data['content'] = $data['link_content'];
            $data['image_url'] = $data['link_image_url'];
            $data['title'] = $data['link_title'];
            $data['pagepath'] = '';
            $data['url'] = $data['link_url'];
        } elseif ($data['reply_type'] === 'miniprogrampage') {
            $data['content'] = '';
            $data['image_url'] = $data['page_image_url'];
            $data['title'] = $data['page_title'];
            $data['pagepath'] = $data['page_pagepath'];
            $data['url'] = $data['page_appid'];
        } elseif ($data['reply_type'] === 'voice') {
            $data['content'] = '';
            $data['image_url'] = $data['voice_voice_url'];
            $data['title'] = '';
            $data['pagepath'] = '';
            $data['url'] = '';
        } elseif ($data['reply_type'] === 'video') {
            $data['content'] = $data['video_content'];
            $data['image_url'] = $data['video_video_url'];
            $data['title'] = $data['video_title'];
            $data['pagepath'] = '';
            $data['url'] = '';
        } elseif ($data['reply_type'] === 'music') {
            $data['content'] = $data['music_content'];
            $data['image_url'] = $data['music_image_url'];
            $data['title'] = $data['music_title'];
            $data['pagepath'] = $data['music_hqurl'];
            $data['url'] = $data['music_url'];
        } elseif ($data['reply_type'] === 'transfer') {
            $data['content'] = '';
            $data['image_url'] = '';
            $data['title'] = '';
            $data['pagepath'] = '';
            $data['url'] = '';
        }
    }

    /**
     * 修改状态
     * @auth true
     */
    public function state(): void
    {
        FbgMpReply::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除规则
     * @auth true
     */
    public function remove(): void
    {
        FbgMpReply::mDelete();
    }

    /**
     * 导出数据
     * @auth true
     */
    public function export(): void
    {
        $appid = (string)($this->request->get('appid', ''));
        $query = FbgMpReply::mQuery();
        $query->like('keyword|content#keys')->equal('reply_type#mtype,match_type,status')->dateBetween('create_at');
        
        $fields = [
            'appid', 'msg_type', 'match_type', 'keyword', 'reply_type', 'sort', 'status',
            'content', 'image_url', 'title', 'pagepath', 'url',
            'text_content', 'image_image_url', 'link_title', 'link_content', 'link_url', 'link_image_url',
            'page_title', 'page_pagepath', 'page_image_url', 'page_appid', 'voice_voice_url',
            'video_title', 'video_content', 'video_video_url', 'music_title', 'music_content',
            'music_url', 'music_hqurl', 'music_image_url'
        ];
        $list = $query->db()->where(['appid' => $appid])->order('sort desc,id asc')->field($fields)->select()->toArray();
        $data = json_encode($list, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        $filename = 'reply_' . ($appid ? $appid : 'common') . '_' . date('YmdHis') . '.json';
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $data;
        exit;
    }

    /**
     * 导入数据
     * @auth true
     */
    public function import(): void
    {
        $appid = (string)($this->request->get('appid', ''));
        if ($this->request->isGet()) {
            $this->appid = $appid;
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
        $list = (isset($data['match_type']) || isset($data['reply_type'])) ? [$data] : $data;
        if (!is_array($list)) {
            $this->error('无效的JSON格式，必须是单个对象 or 数组！');
        }

        $successCount = 0;
        $failCount = 0;

        // 允许导入的字段列表
        $allowedFields = [
            'appid', 'msg_type', 'match_type', 'keyword', 'reply_type', 'sort', 'status',
            'content', 'image_url', 'title', 'pagepath', 'url',
            'text_content', 'image_image_url', 'link_title', 'link_content', 'link_url', 'link_image_url',
            'page_title', 'page_pagepath', 'page_image_url', 'page_appid', 'voice_voice_url',
            'video_title', 'video_content', 'video_video_url', 'music_title', 'music_content',
            'music_url', 'music_hqurl', 'music_image_url'
        ];

        try {
            foreach ($list as $item) {
                $matchType = trim((string)($item['match_type'] ?? 'exact'));
                $keyword = trim((string)($item['keyword'] ?? ''));

                $updateData = [];
                foreach ($allowedFields as $field) {
                    if (isset($item[$field])) {
                        $updateData[$field] = $item[$field];
                    }
                }
                $updateData['appid'] = $appid;

                // 匹配规则：
                // 如果是默认回复，只按 appid 和 match_type = 'default' 匹配
                // 如果是其他回复，按 appid、match_type 和 keyword 匹配
                if ($matchType === 'default') {
                    $reply = FbgMpReply::mk()->where(['appid' => $appid, 'match_type' => 'default'])->findOrEmpty();
                } else {
                    if (empty($keyword)) {
                        $failCount++;
                        continue;
                    }
                    $reply = FbgMpReply::mk()->where(['appid' => $appid, 'match_type' => $matchType, 'keyword' => $keyword])->findOrEmpty();
                }

                if ($reply->isEmpty()) {
                    // 添加新记录
                    FbgMpReply::mk($updateData)->save();
                } else {
                    // 更新已存在记录
                    $reply->save($updateData);
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


