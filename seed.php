<?php
/* 临时脚本：重建数据表 + 默认设置 + 示例数据（自测用，用后删除） */
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

$pdo->exec('CREATE TABLE IF NOT EXISTS settings (
    skey VARCHAR(64) NOT NULL PRIMARY KEY, svalue TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$pdo->exec('CREATE TABLE IF NOT EXISTS posts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL,
    content_md MEDIUMTEXT, content_html MEDIUMTEXT, summary VARCHAR(600),
    cover VARCHAR(500), category VARCHAR(64), tags VARCHAR(255),
    is_pinned TINYINT(1) NOT NULL DEFAULT 0, status TINYINT(1) NOT NULL DEFAULT 1,
    views INT UNSIGNED NOT NULL DEFAULT 0, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uk_slug (slug), KEY idx_status_created (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$pdo->exec('CREATE TABLE IF NOT EXISTS friends (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL, url VARCHAR(255) NOT NULL, avatar VARCHAR(500),
    description VARCHAR(500), sort INT NOT NULL DEFAULT 0,
    status TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$pdo->exec('CREATE TABLE IF NOT EXISTS comments (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    post_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, email VARCHAR(190) NOT NULL,
    website VARCHAR(255) NOT NULL DEFAULT \'\', content TEXT NOT NULL,
    avatar VARCHAR(500) NOT NULL DEFAULT \'\', status TINYINT(1) NOT NULL DEFAULT 0,
    ip VARCHAR(45) NOT NULL DEFAULT \'\', created_at DATETIME DEFAULT NULL,
    KEY idx_post (post_id, status), KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$defaults = [
    'site_name' => '青柠博客', 'site_slogan' => '记录，让时间有迹可循',
    'site_subtitle' => '用代码和文字，各留一份', 'site_description' => '用代码和文字，各留一份',
    'about_title' => '关于我', 'about_name' => '青柠',
    'about_content' => '你好，我是青柠。这里是我的个人博客，记录技术、生活与思考。',
    'about_avatar' => '',
    'announcement' => '', 'announcement_enabled' => '0',
    'preload_enabled' => '1', 'preload_text' => '正在加载',
    'hero_enabled' => '1', 'about_enabled' => '1', 'recent_enabled' => '1', 'recent_count' => '5',
    'blog_page_size' => '8', 'blog_title' => '最新文章', 'blog_subtitle' => '用文字记录走过的路',
    'footer_line' => '页面由 青柠博客 独立设计 & 开发',
    'copyright_text' => 'Copyright © 2026 青柠博客. All Rights Reserved.',
    'icp_text' => '', 'icp_url' => 'https://beian.miit.gov.cn/', 'icp_enabled' => '1',
    'friend_desc' => '交换友链，一起成长。', 'friend_enabled' => '1', 'custom_css' => '',
];
$stmt = $pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
foreach ($defaults as $k => $v) { $stmt->execute([$k, $v]); }

$posts = [
    ['你好，青柠博客', 'hello-lime-blog', '随笔', '开篇,博客',
     '这是我的第一篇博客，欢迎来到青柠博客。', 1,
     "# 你好，青柠博客\n\n欢迎来到我的个人博客。这里主要记录**技术**、**生活**与**思考**。\n\n## 为什么叫「青柠」？\n\n因为主页用的是青柠绿和柠檬黄的配色，清新又明亮，就像一杯夏日青柠气泡水。\n\n> 用文字记录走过的路，让时间有迹可循。\n\n```php\n<?php echo \"Hello, Lime Blog!\"; ?>\n"],
    ['如何优雅地使用 Markdown 写作', 'how-to-write-markdown', '技术', 'Markdown,写作',
     '分享我日常使用的 Markdown 写作技巧，让你的博客文章更有条理。', 0,
     "# 如何优雅地使用 Markdown 写作\n\nMarkdown 是一种轻量级标记语言，写作体验非常流畅。\n\n## 常用语法\n\n| 语法 | 效果 |\n| --- | --- |\n| `**加粗**` | **加粗** |\n| `*斜体*` | *斜体* |\n\n> 好的排版，是给读者最好的礼物。"],
];
$ps = $pdo->prepare('INSERT INTO posts (title, slug, category, tags, summary, content_md, content_html, is_pinned, status, views, created_at, updated_at) VALUES (?,?,?,?,?,?,?,?,1,0,?,?)');
foreach ($posts as $i => $p) {
    $now = date('Y-m-d H:i:s', time() - ($i * 86400));
    $html = markdown_to_html($p[6]);
    $ps->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[6], $html, $p[5], $now, $now]);
    echo "post: {$p[0]}\n";
}

$fs = $pdo->prepare('INSERT INTO friends (name, url, avatar, description, sort, status, created_at) VALUES (?,?,?,?,?,1,?)');
foreach ([['示例站点 A', 'https://example.com', '', '一个示例友情链接', 1],
          ['示例站点 B', 'https://example.org', '', '交换友链，一起成长', 2]] as $f) {
    $fs->execute([$f[0], $f[1], $f[2], $f[3], $f[4], date('Y-m-d H:i:s')]);
    echo "friend: {$f[0]}\n";
}
echo "DONE\n";
