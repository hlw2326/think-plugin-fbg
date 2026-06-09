# ThinkAdmin v6 朋友圈背景及小程序通用管理插件

`hlw2326/think-plugin-fbg` 是一个专为 **ThinkAdmin v6** 框架定制开发的高性能、多功能小程序后台管理与接口插件。该插件最初设计用于“朋友圈背景/壁纸”小程序，但其架构已全面演进为通用的多租户小程序管理系统，涵盖了用户体系、积分变现、VIP 会员制、智能 AI 对话、工具箱跳转及帮助客服等核心功能模块。

---

## 🚀 核心功能特性

### 1. 用户体系与统计 (`fbg_user`)
- **微信一键登录**：支持小程序无感登录（Code 换 Token），支持自动关联推荐人（PID 裂变推广）。
- **多维度设备记录**：自动记录用户登录 IP、手机型号、操作系统版本、微信 SDK 版本、小程序版本号以及来源渠道等。
- **系统统计面板**：后台提供直观的数据概览与统计分析。

### 2. 积分变现与 VIP 会员制
- **积分体系 (`fbg_user_score`)**：
  - 下载资源可消耗积分（支持全局点数配置或单个资源独立设置积分消耗）。
  - 支持微信激励视频广告（AD）对接，用户看完广告后自动发放奖励积分。
- **VIP 会员制 (`fbg_user_vip`)**：
  - 会员有效期管理，支持后台数据调整。
  - **尊享特权**：VIP 会员在有效期内可无限制免费下载所有资源，免除积分扣除。

### 3. 资源中心管理
- **多级分类 (`fbg_res_cate`)**：支持分类排序、禁用/启用状态管理。
- **资源管理 (`fbg_res`)**：支持上传壁纸、朋友圈背景等资源，设置标题、分辨率、大小、独立积分。
- **互动中心**：自动追踪并关联点赞 (`fbg_res_like`)、收藏 (`fbg_res_collect`) 与下载历史 (`fbg_res_down`)。

### 4. 智能 AI 对话助手 (`Ai.php`)
- **主流模型支持**：内置对接千问（Qwen）与 OpenAI 兼容格式 API。
- **灵活的配置面板**：后台可配置 API Key、Base URL、Model、Temperature、System Prompt、Max Tokens 等。
- **一键联调测试**：支持在后台进行连接测试，实时获取 API 响应。

### 5. 多小程序跳转工具箱 (`fbg_tools`)
- **互推工具列表**：管理合作的小程序跳转（AppID、Path）。
- **点击追踪**：后台自动统计每个工具的点击数，方便流量互换与效果监控。

### 6. 客服与帮助系统
- **常见问题分类 (`fbg_help`)**：支持常见使用指南展示，减少人工客服压力。
- **客服自动回复 (`fbg_mp_reply`)**：支持微信小程序客服消息关键词自动回复，可灵活配置文字、网页卡片或小程序卡片。

---

## 🛠️ 开发与规范

### 1. 基础规范
- **命名空间**：`plugin\fbg` (映射目录 `src/`)
- **表名前缀**：`fbg_` (例如：`fbg_user`, `fbg_res`, `fbg_tools` 等)
- **后端管理模块**：`plugin-fbg`

### 2. 路由与接口前缀
- **API 接口前缀**：`/plugin-fbg/api.v1.{controller}/{action}` (需在 Header 中携带 `X-Token` 进行用户鉴权)
- **后台管理地址**：`/admin.html#/plugin-fbg/{controller}/index`

---

## 📂 目录结构说明

```text
think-plugin-fbg/
├── src/                    # 插件核心源码
│   ├── controller/         # 控制器层
│   │   ├── api/v1/         # 小程序 API 接口控制器 (Auth, Res, Download, AI 等)
│   │   ├── config/         # 基础/分享/客服/AI 等参数配置控制器
│   │   ├── main/           # 系统统计看板控制器
│   │   └── ...             # 各模块后台管理控制器 (User, Res, Tools 等)
│   ├── exception/          # 统一异常处理
│   ├── model/              # 数据模型层 (与 fbg_* 数据表关联)
│   ├── service/            # 业务逻辑服务层 (AiService, UserService 等)
│   ├── view/               # 插件后台模板文件 (.html)
│   ├── helper.php          # 辅助函数库
│   └── Service.php         # 插件注册与引导服务类
├── stc/
│   └── database/           # 数据库 Phinx 迁移脚本 (包含建表与初始配置更新)
├── composer.json           # 依赖与自动加载配置
└── README.md               # 项目说明文档
```

---

## 📦 安装与升级

1. **引入插件包**：
   在 ThinkAdmin 项目根目录的 `composer.json` 中配置或直接通过命令行引入：
   ```bash
   composer require hlw2326/think-plugin-fbg
   ```

2. **数据库迁移**：
   Composer 安装完成后，插件将自动将 `stc/database` 中的 Phinx 迁移文件复制到系统的 `database/migrations` 目录。
   运行迁移命令以创建相应的数据表：
   ```bash
   php think migrate:run
   ```

3. **菜单与权限初始化**：
   登录 ThinkAdmin 后台，在权限/菜单管理中刷新或通过命令行自动注册插件定义的菜单项。

