<?php
/**
 * 博客公共函数库
 */

require_once __DIR__ . '/db.php';

// 若已安装，加载配置常量（ADMIN_USER / ADMIN_PASS_HASH / BLOG_BASE_URL 等）
if (file_exists(__DIR__ . '/../config.php')) {
    require_once __DIR__ . '/../config.php';
}

/* ==================== URL 相关 ==================== */

/** 生成站点内 URL（自动加上安装目录前缀） */
function url($path = '')
{
    if ($path === '' || preg_match('#^(https?:)?//#i', $path)) {
        return $path;
    }
    $base = defined('BLOG_BASE_URL') ? rtrim(BLOG_BASE_URL, '/') : '';
    return $base . '/' . ltrim($path, '/');
}

/** 当前完整 URL */
function current_url()
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
}

/* ==================== 设置项 ==================== */

function get_all_settings()
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $stmt = db()->query('SELECT skey, svalue FROM settings');
            while ($row = $stmt->fetch()) {
                $cache[$row['skey']] = $row['svalue'];
            }
        } catch (Exception $e) {
            // 数据表尚未创建（如安装中）时静默返回
        }
    }
    return $cache;
}

/** 读取单个设置 */
function get_setting($key, $default = '')
{
    $all = get_all_settings();
    $val = $all[$key] ?? '';
    return ($val === '') ? $default : $val;
}

/** 写入单个设置（不存在则插入） */
function set_setting($key, $value)
{
    $stmt = db()->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)');
    $stmt->execute([$key, (string)$value]);
}

/* ==================== 输出转义 ==================== */

/** HTML 输出转义 */
function e($str)
{
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

/* ==================== 安全相关 ==================== */

/** 生成随机字符串 */
function random_str($length = 16)
{
    return bin2hex(random_bytes((int)ceil($length / 2)));
}

/**
 * HTML 白名单净化（基于 DOM）
 * 用于 Markdown 渲染结果，防止 XSS。
 */
function sanitize_html($html)
{
    if ($html === '' || $html === null) {
        return '';
    }
    $allowedTags = [
        'p','br','hr','h1','h2','h3','h4','h5','h6',
        'strong','em','b','i','del','s','u','sub','sup','mark','kbd',
        'code','pre','blockquote','a','img','ul','ol','li',
        'table','thead','tbody','tfoot','tr','th','td','caption',
        'span','div','details','summary','input',
    ];
    $allowedAttr = [
        'a'      => ['href','title','target','rel'],
        'img'    => ['src','alt','title','width','height'],
        'code'   => ['class'],
        'pre'    => ['class'],
        'input'  => ['type','checked','disabled'],
        'th'     => ['align'],
        'td'     => ['align'],
    ];

    // 预处理：转义所有原始 HTML 尖括号之外的 PHP 短标签/危险指令已被 Parsedown 处理，
    // 此处使用 DOM 逐节点清理。
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    // 包装成完整文档避免 HTML5 标签解析问题
    $doc->loadHTML('<?xml encoding="UTF-8">' . '<div id="__root__">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $clean = static function ($node) use ($allowedTags, $allowedAttr, &$clean) {
        if ($node instanceof DOMText) {
            return $node;
        }
        if (!$node instanceof DOMElement) {
            // 注释等其他节点直接删除
            return null;
        }
        $tag = strtolower($node->nodeName);
        if (!in_array($tag, $allowedTags, true)) {
            // 非白名单标签：保留其子节点（提升为父级），删除标签本身
            $parent = $node->parentNode;
            $children = iterator_to_array($node->childNodes);
            foreach ($children as $child) {
                $parent->insertBefore($node->ownerDocument->importNode($clean($child), true), $node);
            }
            $parent->removeChild($node);
            return null;
        }

        // 清理属性
        $attrs = [];
        foreach ($node->attributes as $attr) {
            $name  = strtolower($attr->nodeName);
            $value = $attr->nodeValue;

            // 拒绝事件属性与危险样式
            if (strpos($name, 'on') === 0) {
                continue;
            }
            if ($name === 'style' && preg_match('/url\s*\(/i', $value)) {
                continue;
            }
            $allowedFor = $allowedAttr[$tag] ?? [];
            if ($allowedFor && !in_array($name, $allowedFor, true)) {
                continue;
            }

            // 校验 URL 属性（先 rawurldecode 以拦截 percent-encoded 协议绕过，如 javascript%3A）
            if (in_array($name, ['href', 'src'], true)) {
                $value = trim($value);
                $decoded = rawurldecode($value);
                $scheme = strtolower((string)parse_url($decoded, PHP_URL_SCHEME));
                $isSafe = ($scheme === '')
                    || in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)
                    || ($scheme === 'data' && strpos($decoded, 'data:image/') === 0);
                if (!$isSafe) {
                    continue;
                }
            }

            // target 只能为 _blank / _self / _top，并强制 noopener
            if ($name === 'target') {
                if (!in_array($value, ['_blank', '_self', '_top'], true)) {
                    $value = '_blank';
                }
            }
            if ($name === 'rel') {
                $set = preg_split('/\s+/', $value);
                if (!in_array('noopener', $set, true)) {
                    $set[] = 'noopener';
                }
                $value = implode(' ', $set);
            }

            $attrs[$name] = $value;
        }

        // 重建属性
        while ($node->attributes->length > 0) {
            $node->removeAttribute($node->attributes->item(0)->nodeName);
        }
        foreach ($attrs as $name => $value) {
            $node->setAttribute($name, $value);
        }

        // 递归处理子节点
        $children = iterator_to_array($node->childNodes);
        foreach ($children as $child) {
            $clean($child);
        }
        return $node;
    };

    $root = $doc->getElementById('__root__');
    if (!$root) {
        return '';
    }
    $clean($root);

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    return $out;
}

/* ==================== Markdown 渲染 ==================== */

/** Markdown -> 安全 HTML */
function markdown_to_html($md)
{
    static $pd = null;
    if ($pd === null) {
        require_once __DIR__ . '/parsedown.php';
        $pd = new Parsedown();
        $pd->setMarkupEscaped(true); // 原始 HTML 一律转义
        $pd->setSafeMode(true);      // 链接/图片安全模式
    }
    $html = $pd->text((string)$md);
    return sanitize_html($html);
}

/**
 * 解析 Markdown 文件的 YAML front matter（--- 开头）
 * 返回 ['meta' => [...], 'content' => '...']
 */
function parse_front_matter($md)
{
    $md = (string)$md;
    $meta = [];
    if (preg_match('/^---\s*\R(.*?)\R---\s*\R(.*)$/s', $md, $m)) {
        foreach (preg_split('/\R/', $m[1]) as $line) {
            if (strpos($line, ':') === false) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $meta[trim($k)] = trim($v, " \t\"'");
        }
        $content = $m[2];
    } else {
        $content = $md;
    }
    return ['meta' => $meta, 'content' => $content];
}

/* ==================== 文章相关 ==================== */

/** 生成唯一 slug */
function slugify($text, $fallback = '')
{
    $text = trim((string)$text);
    if ($text === '') {
        return $fallback;
    }
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');
    if ($text === '') {
        return $fallback;
    }
    return mb_substr($text, 0, 120);
}

/** 截取摘要 */
function excerpt($html, $len = 120)
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)));
    if (mb_strlen($text) <= $len) {
        return $text;
    }
    return mb_substr($text, 0, $len) . '…';
}

/* ==================== 日期 ==================== */

function fmt_date($dt)
{
    if (!$dt) {
        return '';
    }
    return date('Y-m-d', strtotime($dt));
}

function fmt_datetime($dt)
{
    if (!$dt) {
        return '';
    }
    return date('Y-m-d H:i', strtotime($dt));
}

/* ==================== 分页 ==================== */

/**
 * 生成分页 HTML
 * $totalRows 总数, $perPage 每页条数, $current 当前页, $baseUrl 基础链接(含占位符 {page})
 */
function paginate($totalRows, $perPage, $current, $baseUrl)
{
    $pages = (int)ceil($totalRows / $perPage);
    if ($pages <= 1) {
        return '';
    }
    $current = max(1, min((int)$current, $pages));
    $html = '<nav class="pagination" role="navigation" aria-label="分页">';
    $html .= '<span class="page-info">第 ' . $current . ' / ' . $pages . ' 页</span>';

    if ($current > 1) {
        $html .= '<a href="' . e(str_replace('{page}', 1, $baseUrl)) . '">首页</a>';
        $html .= '<a href="' . e(str_replace('{page}', $current - 1, $baseUrl)) . '">上一页</a>';
    }
    $start = max(1, $current - 2);
    $end   = min($pages, $current + 2);
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $current) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . e(str_replace('{page}', $i, $baseUrl)) . '">' . $i . '</a>';
        }
    }
    if ($current < $pages) {
        $html .= '<a href="' . e(str_replace('{page}', $current + 1, $baseUrl)) . '">下一页</a>';
        $html .= '<a href="' . e(str_replace('{page}', $pages, $baseUrl)) . '">末页</a>';
    }
    $html .= '</nav>';
    return $html;
}

/* ==================== 文件上传 ==================== */

/**
 * 处理图片上传
 * $field 表单字段名; 返回 ['ok'=>bool, 'url'=>相对URL, 'msg'=>错误信息]
 */
function handle_image_upload($field)
{
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                'gif' => 'image/gif', 'webp' => 'image/webp', 'bmp' => 'image/bmp'];
    $maxSize = 8 * 1024 * 1024; // 8MB

    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'url' => '', 'msg' => '未选择文件'];
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'url' => '', 'msg' => '文件上传失败（错误码 ' . $file['error'] . '）'];
    }
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'url' => '', 'msg' => '文件大小不能超过 8MB'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'url' => '', 'msg' => '仅支持图片格式：jpg/jpeg/png/gif/webp/bmp'];
    }

    // 校验真实 MIME（防止伪装扩展名）
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, array_values($allowed), true)) {
            return ['ok' => false, 'url' => '', 'msg' => '文件内容不是有效的图片'];
        }
    }

    $dir   = dirname(__DIR__) . '/uploads/' . date('Ym');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = date('YmdHis') . '_' . random_str(6) . '.' . $ext;
    $dest = $dir . '/' . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'url' => '', 'msg' => '文件保存失败，请检查 uploads 目录权限'];
    }
    return [
        'ok'  => true,
        'url' => url('uploads/' . date('Ym') . '/' . $name),
        'msg' => '上传成功',
    ];
}

/* ==================== 浏览计数 ==================== */

/** 增加文章浏览量 */
function bump_views($id)
{
    try {
        db()->prepare('UPDATE posts SET views = views + 1 WHERE id = ?')->execute([$id]);
    } catch (Exception $e) {
        // 忽略计数失败
    }
}

/* ==================== 预加载 ==================== */

function preload_enabled()
{
    return get_setting('preload_enabled', '1') === '1';
}

/* ==================== 上传目录浏览 ==================== */

/** 列出 uploads 下的图片（用于后台图片选择器） */
function list_uploads()
{
    $dir = dirname(__DIR__) . '/uploads';
    $out = [];
    if (!is_dir($dir)) {
        return $out;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if (!$f->isFile()) {
            continue;
        }
        $name = $f->getFilename();
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true)) {
            continue;
        }
        $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(dirname(__DIR__)) + 1));
        $out[] = [
            'url' => url($rel),
            'name' => $name,
            'time' => $f->getMTime(),
            'size' => $f->getSize(),
        ];
    }
    usort($out, fn($a, $b) => $b['time'] <=> $a['time']);
    return $out;
}

/* ==================== 评论系统 ==================== */

/** 确保评论数据表存在（兼容旧版本升级） */
function ensure_comments_table()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS comments (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                post_id INT UNSIGNED NOT NULL,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(190) NOT NULL,
                website VARCHAR(255) NOT NULL DEFAULT \'\',
                content TEXT NOT NULL,
                avatar VARCHAR(500) NOT NULL DEFAULT \'\',
                status TINYINT(1) NOT NULL DEFAULT 0,
                ip VARCHAR(45) NOT NULL DEFAULT \'\',
                created_at DATETIME DEFAULT NULL,
                KEY idx_post (post_id, status),
                KEY idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    } catch (Exception $e) {
        // 忽略：安装过程中调用会失败
    }
}

/**
 * 根据邮箱返回头像 URL
 * - QQ 邮箱：调用 QQ 头像接口 http://q1.qlogo.cn/g?b=qq&nk=QQ号&s=100
 * - 其他邮箱：返回空字符串（由模板使用默认头像）
 */
function comment_avatar_url($email)
{
    $email = strtolower(trim((string)$email));
    if (preg_match('/^([a-z0-9._-]+)@qq\.com$/i', $email, $m)) {
        $qq = $m[1];
        // QQ 号通常为纯数字
        if (preg_match('/^\d{4,11}$/', $qq)) {
            return 'https://q1.qlogo.cn/g?b=qq&nk=' . $qq . '&s=100';
        }
        // 非纯数字的 qq.com 邮箱（如 abc@qq.com）也尝试拼接，接口对非法号会返回默认头像
        return 'https://q1.qlogo.cn/g?b=qq&nk=' . rawurlencode($qq) . '&s=100';
    }
    return '';
}

/** 获取某文章已通过的评论 */
function get_comments($postId)
{
    ensure_comments_table();
    $stmt = db()->prepare(
        'SELECT id, name, email, website, content, avatar, created_at
         FROM comments WHERE post_id = ? AND status = 1
         ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute([(int)$postId]);
    return $stmt->fetchAll();
}

/** 某文章的评论数（已通过） */
function count_comments($postId)
{
    ensure_comments_table();
    try {
        $stmt = db()->prepare('SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 1');
        $stmt->execute([(int)$postId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/** 批量获取评论数：返回 [post_id => count] */
function comments_count_map(array $postIds)
{
    $postIds = array_values(array_filter(array_map('intval', $postIds)));
    if (!$postIds) {
        return [];
    }
    ensure_comments_table();
    $place = implode(',', array_fill(0, count($postIds), '?'));
    try {
        $stmt = db()->prepare('SELECT post_id, COUNT(*) c FROM comments WHERE post_id IN (' . $place . ') AND status = 1 GROUP BY post_id');
        $stmt->execute($postIds);
        $out = [];
        foreach ($stmt->fetchAll() as $row) {
            $out[(int)$row['post_id']] = (int)$row['c'];
        }
        return $out;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * 保存新评论（默认待审核）
 * 返回 ['ok'=>bool, 'msg'=>string]
 */
function save_comment($postId, $name, $email, $website, $content)
{
    ensure_comments_table();

    $name = trim(strip_tags((string)$name));
    $email = trim((string)$email);
    $website = trim((string)$website);
    $content = trim((string)$content);

    if ($name === '' || mb_strlen($name) > 100) {
        return ['ok' => false, 'msg' => '请填写昵称（100 字以内）'];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        return ['ok' => false, 'msg' => '请填写有效的邮箱地址'];
    }
    if ($website !== '') {
        $u = strtolower($website);
        if (!preg_match('#^https?://#i', $u) || !filter_var($website, FILTER_VALIDATE_URL)) {
            return ['ok' => false, 'msg' => '网站地址格式不正确（需以 http:// 或 https:// 开头）'];
        }
        if (strlen($website) > 255) {
            return ['ok' => false, 'msg' => '网站地址过长'];
        }
    }
    if ($content === '' || mb_strlen($content) > 2000) {
        return ['ok' => false, 'msg' => '请填写评论内容（2000 字以内）'];
    }

    $avatar = comment_avatar_url($email);
    $ip = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

    $stmt = db()->prepare(
        'INSERT INTO comments (post_id, name, email, website, content, avatar, status, ip, created_at)
         VALUES (?,?,?,?,?,?,0,?,?)'
    );
    $stmt->execute([(int)$postId, $name, $email, $website, $content, $avatar, $ip, date('Y-m-d H:i:s')]);
    return ['ok' => true, 'msg' => '评论已提交，审核通过后会显示在这里。'];
}

/** 后台：分页获取全部评论 */
function get_all_comments($page = 1, $perPage = 20)
{
    ensure_comments_table();
    $page = max(1, (int)$page);
    $stmt = db()->prepare('SELECT COUNT(*) FROM comments');
    $stmt->execute();
    $total = (int)$stmt->fetchColumn();

    $stmt = db()->prepare(
        'SELECT c.*, p.title AS post_title, p.slug AS post_slug
         FROM comments c LEFT JOIN posts p ON p.id = c.post_id
         ORDER BY c.status ASC, c.id DESC LIMIT ? OFFSET ?'
    );
    $stmt->bindValue(1, $perPage, PDO::PARAM_INT);
    $stmt->bindValue(2, ($page - 1) * $perPage, PDO::PARAM_INT);
    $stmt->execute();
    return ['total' => $total, 'items' => $stmt->fetchAll()];
}

