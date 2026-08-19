# 青柠博客 · QingNing Blog

[English](./README-en.md)

一款轻量级个人博客系统。纯 **PHP + MySQL** 编写，无 Composer、无 Node 依赖，上传即可用，自带安装向导。

> 灵感与视觉风格参考自 ttawa.cn。

---

## ✨ 特性

- **关于install**：额那个TT忘记写判定了，把文件丢进去后记得打开https://你的域名/intsll/ 自行安装。
- **全屏翻页首页**：Hero 大标题 → 关于 → 最新文章 → 页脚，每个区块占一屏，滑动切换
- **关于我页面**：头像 / 姓名 / 文案，后台「首页设置」即可修改
- **评论系统**：昵称、邮箱必填，网站选填；QQ 邮箱自动显示 QQ 头像；内置图片验证码（GD / SVG 自适应，无 GD 也能出图）
- **Markdown 写作**：后台发布 / 编辑 / 删除文章
- **一站式后台**：文章、评论（审核 / 删除）、首页、站点设置、友情链接
- **响应式布局**：内容不足一屏时页脚自动贴底
- **单文件安装向导**：自动建库建表，开箱即用

## 📸 截图

> 可在此处补充：首页 / 文章页 / 评论区 / 后台的截图。

## 🚀 快速开始

1. 上传源码到站点根目录（或子目录，如 `htdocs/blog`）
2. 确保 `uploads/` 目录可写：`chmod -R 755 uploads`
3. 浏览器访问 `install/index.php`，按向导 4 步完成安装
4. 安装完成后**删除 `install/` 目录**

本地快速体验：

```bash
cd blog
php -S 127.0.0.1:8080
# 访问 http://127.0.0.1:8080
```

详细步骤见 [安装教程 INSTALL.md](./INSTALL.md)。

## 🧰 环境要求

| 组件 | 要求 |
| --- | --- |
| PHP | ≥ 7.4（推荐 8.0+） |
| 数据库 | MySQL 5.7+ 或 MariaDB 10.3+ |
| PHP 扩展 | `pdo`、`pdo_mysql`、`mbstring`、`json`、`finfo`（必需）；`gd`（可选，验证码用） |

## 📁 目录结构

```
blog/
├── index.php          首页（全屏翻页：Hero → 关于 → 最新文章 → 页脚）
├── about.php          关于我
├── blog.php           博客列表
├── post.php           文章详情（含评论系统 + 图片验证码）
├── friends.php        友情链接
├── captcha.php        评论验证码图片接口
├── admin/             后台管理
├── assets/            样式 / 脚本 / 图片
├── includes/          公共函数、配置、CSRF、验证码等
├── install/           安装向导（装完请删除）
├── uploads/           上传图片（需可写）
├── LICENSE            开源协议
└── README.md          本文档
```

## 🛡️ 安全说明

- `config.php` 包含**数据库密码**，切勿提交到公开仓库（本仓库已在 `.gitignore` 中排除）
- 安装完成后请删除 `install/` 目录，防止他人重装 / 篡改配置

## 📄 开源协议

本项目基于 [MIT](./LICENSE) + 附加署名条款：

- ✅ 可商用
- ✅ 可修改、可再分发
- ✅ 必须保留原作者署名（详见 LICENSE 中的「附加署名条款」）

## 👤 开发者

- 作者：[takethe-cn](https://github.com/takethe-cn)
- 由 Trae AI（traeAI）辅助开发

## ⭐ 支持

如果这个项目对你有帮助，欢迎 **Star** / **Fork** / **提交 Issue**。
