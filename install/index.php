<?php
/**
 * 博客安装向导
 * 使用步骤：1 环境检查 → 2 数据库配置 → 3 站点与管理员 → 4 完成
 * 安全：安装完成后请务必删除本 install 目录。
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 会话：跨步骤保存数据库连接信息
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root = dirname(__DIR__);
$configFile = $root . '/config.php';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$ok = [];

$installed = file_exists($configFile) && defined('BLOG_INSTALLED');

// 处理“删除配置并重新安装”
if (isset($_GET['reset']) && $_GET['reset'] === '1') {
    if (file_exists($configFile)) {
        @unlink($configFile);
    }
    header('Location: index.php?step=1');
    exit;
}
if ($installed) {
    $pageTitle = '系统已安装';
    require __DIR__ . '/tpl/header.php';
    ?>
    <div class="box warn">
        <h2>系统已安装</h2>
        <p>检测到 <code>config.php</code> 已存在，说明系统已完成安装。</p>
        <p>出于安全考虑，安装向导已被锁定，且 <b>安装目录建议立即删除</b>。</p>
        <div class="actions">
            <a class="btn btn-ghost" href="<?= htmlspecialchars(dirname($_SERVER['SCRIPT_NAME']) === '/install' || dirname($_SERVER['SCRIPT_NAME']) === '/install/' ? '../index.php' : '../index.php') ?>">返回网站首页</a>
            <a class="btn btn-danger" href="index.php?reset=1"
               onclick="return confirm('确定要删除 config.php 并重新安装吗？重新安装不会清空已发布的文章数据，但会重置系统设置。');">我了解风险，删除配置并重新安装</a>
        </div>
    </div>
    <?php
    require __DIR__ . '/tpl/footer.php';
    exit;
}

/* ============ 环境检查（Step 1） ============ */
$requirements = [
    ['PHP 版本 >= 7.4', version_compare(PHP_VERSION, '7.4.0', '>='), PHP_VERSION],
    ['PDO 扩展', extension_loaded('pdo'), ''],
    ['pdo_mysql 扩展', extension_loaded('pdo_mysql'), ''],
    ['mbstring 扩展', extension_loaded('mbstring'), ''],
    ['json 扩展', extension_loaded('json'), ''],
    ['GD 扩展（可选，用于图片处理）', extension_loaded('gd'), ''],
    ['finfo 扩展（用于上传校验）', function_exists('finfo_open'), ''],
    ['uploads 目录可写', is_writable($root . '/uploads'), ''],
];

$allPass = true;
foreach ($requirements as $r) {
    if (!$r[1]) {
        $allPass = false;
    }
}

/* ============ Step 2 处理 ============ */
if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim($_POST['db_host'] ?? '127.0.0.1');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $dbPrefix = ''; // 预留：表前缀
    $tablesPrefix = $dbPrefix;

    if ($dbName === '' || $dbUser === '') {
        $errors[] = '数据库名、数据库用户为必填项。';
    }
    if (preg_match('/[^A-Za-z0-9_]/', $dbName)) {
        $errors[] = '数据库名只能包含字母、数字和下划线。';
    }

    if (!$errors) {
        try {
            $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            // 创建数据库（如不存在）
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $dbName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `" . $dbName . "`");
            $pdo->exec("SET NAMES utf8mb4");

            // 建表
            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$tablesPrefix}settings` (
                `skey` VARCHAR(64) NOT NULL PRIMARY KEY,
                `svalue` TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$tablesPrefix}posts` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NOT NULL,
                `content_md` MEDIUMTEXT,
                `content_html` MEDIUMTEXT,
                `summary` VARCHAR(600),
                `cover` VARCHAR(500),
                `category` VARCHAR(64),
                `tags` VARCHAR(255),
                `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `views` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` DATETIME DEFAULT NULL,
                `updated_at` DATETIME DEFAULT NULL,
                UNIQUE KEY `uk_slug` (`slug`),
                KEY `idx_status_created` (`status`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$tablesPrefix}friends` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(100) NOT NULL,
                `url` VARCHAR(255) NOT NULL,
                `avatar` VARCHAR(500),
                `description` VARCHAR(500),
                `sort` INT NOT NULL DEFAULT 0,
                `status` TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` DATETIME DEFAULT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `{$tablesPrefix}comments` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `post_id` INT UNSIGNED NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `email` VARCHAR(190) NOT NULL,
                `website` VARCHAR(255) NOT NULL DEFAULT '',
                `content` TEXT NOT NULL,
                `avatar` VARCHAR(500) NOT NULL DEFAULT '',
                `status` TINYINT(1) NOT NULL DEFAULT 0,
                `ip` VARCHAR(45) NOT NULL DEFAULT '',
                `created_at` DATETIME DEFAULT NULL,
                KEY `idx_post` (`post_id`, `status`),
                KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // 表结构变更校验：若 posts 表旧版本缺少列则补充
            $cols = $pdo->query("SHOW COLUMNS FROM `{$tablesPrefix}posts`")->fetchAll(PDO::FETCH_COLUMN);
            $needCols = ['is_pinned' => "TINYINT(1) NOT NULL DEFAULT 0", 'summary' => 'VARCHAR(600)', 'cover' => 'VARCHAR(500)', 'category' => 'VARCHAR(64)', 'tags' => 'VARCHAR(255)'];
            foreach ($needCols as $c => $def) {
                if (!in_array($c, $cols, true)) {
                    $pdo->exec("ALTER TABLE `{$tablesPrefix}posts` ADD COLUMN `$c` $def");
                }
            }

            // 把连接信息暂存会话，进入下一步
            $_SESSION['inst_dsn_host'] = $dbHost;
            $_SESSION['inst_dsn_port'] = $dbPort;
            $_SESSION['inst_dsn']  = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
            $_SESSION['inst_db']   = $dbName;
            $_SESSION['inst_user'] = $dbUser;
            $_SESSION['inst_pass'] = $dbPass;
            $_SESSION['inst_prefix'] = $tablesPrefix;
            header('Location: index.php?step=3');
            exit;
        } catch (PDOException $ex) {
            $errors[] = '数据库连接/初始化失败：' . htmlspecialchars($ex->getMessage());
        }
    }
}

/* ============ Step 3 处理 ============ */
if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $siteName   = trim($_POST['site_name'] ?? '');
    $siteSlogan = trim($_POST['site_slogan'] ?? '');
    $subtitle   = trim($_POST['site_subtitle'] ?? '');
    $adminUser  = trim($_POST['admin_user'] ?? '');
    $adminPass  = (string)($_POST['admin_pass'] ?? '');
    $adminPass2 = (string)($_POST['admin_pass2'] ?? '');
    $baseUrl    = trim($_POST['base_url'] ?? '');

    if ($siteName === '') {
        $errors[] = '请填写站点名称。';
    }
    if (mb_strlen($adminUser) < 2 || mb_strlen($adminUser) > 32 || preg_match('/[^A-Za-z0-9_\-]/', $adminUser)) {
        $errors[] = '管理员用户名需为 2-32 位字母、数字、下划线或中划线。';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = '管理员密码长度不能少于 8 位。';
    }
    if ($adminPass !== $adminPass2) {
        $errors[] = '两次输入的密码不一致。';
    }
    $baseUrl = preg_replace('#/$#', '', $baseUrl);
    if ($baseUrl !== '' && !preg_match('#^/[A-Za-z0-9_\-/]*$#', $baseUrl)) {
        $errors[] = '安装目录格式不正确（例如 /blog，根目录安装请留空）。';
    }

    if (!$errors) {
        $passHash = password_hash($adminPass, PASSWORD_DEFAULT);
        // 生成 config.php
        $config = "<?php\n"
            . "/**\n"
            . " * 博客配置文件 —— 由安装向导自动生成\n"
            . " * 请妥善保管，勿对外公开本文件。\n"
            . " */\n\n"
            . "define('DB_HOST', " . var_export($_SESSION['inst_dsn_host'] ?? '127.0.0.1', true) . ");\n"
            . "define('DB_PORT', " . var_export($_SESSION['inst_dsn_port'] ?? '3306', true) . ");\n"
            . "define('DB_NAME', " . var_export($_SESSION['inst_db'] ?? '', true) . ");\n"
            . "define('DB_USER', " . var_export($_SESSION['inst_user'] ?? '', true) . ");\n"
            . "define('DB_PASS', " . var_export($_SESSION['inst_pass'] ?? '', true) . ");\n"
            . "define('DB_CHARSET', 'utf8mb4');\n\n"
            . "define('BLOG_BASE_URL', " . var_export($baseUrl, true) . ");\n\n"
            . "define('BLOG_INSTALLED', true);\n\n"
            . "define('ADMIN_USER', " . var_export($adminUser, true) . ");\n"
            . "define('ADMIN_PASS_HASH', " . var_export($passHash, true) . ");\n\n"
            . "date_default_timezone_set('Asia/Shanghai');\n";
        if (file_put_contents($configFile, $config) === false) {
            $errors[] = '无法写入 config.php，请检查站点根目录是否可写。';
        }
        if (!$errors) {
            // 写入默认设置
            try {
                $pdo = new PDO($_SESSION['inst_dsn'], $_SESSION['inst_user'], $_SESSION['inst_pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $pdo->exec("SET NAMES utf8mb4");
                $prefix = $_SESSION['inst_prefix'] ?? '';
                $defaults = [
                    'site_name'          => $siteName,
                    'site_slogan'        => $siteSlogan !== '' ? $siteSlogan : '记录生活与代码',
                    'site_subtitle'      => $subtitle,
                    'site_description'   => $subtitle,
                    'about_title'        => '关于我',
                    'about_content'      => '你好，我是本站站长。这里是我的个人博客，记录技术、生活与思考。欢迎常来看看。',
                    'announcement'       => '',
                    'announcement_enabled' => '0',
                    'preload_enabled'    => '1',
                    'preload_text'       => '正在加载',
                    'hero_enabled'       => '1',
                    'about_enabled'      => '1',
                    'recent_enabled'     => '1',
                    'recent_count'       => '5',
                    'blog_page_size'     => '8',
                    'blog_title'         => '最新文章',
                    'blog_subtitle'      => '用文字记录走过的路',
                    'footer_line'        => '页面由 ' . $siteName . ' 独立设计 & 开发',
                    'copyright_text'     => 'Copyright © ' . date('Y') . ' ' . $siteName . '. All Rights Reserved.',
                    'icp_text'           => '',
                    'icp_url'            => 'https://beian.miit.gov.cn/',
                    'icp_enabled'        => '1',
                    'friend_desc'        => '交换友链，一起成长。',
                    'friend_enabled'     => '1',
                    'custom_css'         => '',
                ];
                $stmt = $pdo->prepare("INSERT INTO `{$prefix}settings` (skey, svalue) VALUES (?, ?) ON DUPLICATE KEY UPDATE svalue = VALUES(svalue)");
                foreach ($defaults as $k => $v) {
                    $stmt->execute([$k, $v]);
                }
                $done = true;
            } catch (Exception $ex) {
                $errors[] = '写入默认设置失败：' . htmlspecialchars($ex->getMessage());
                $done = false;
            }
            if (!empty($done)) {
                // 清理会话临时信息
                unset($_SESSION['inst_dsn'], $_SESSION['inst_db'], $_SESSION['inst_user'], $_SESSION['inst_pass'], $_SESSION['inst_prefix']);
                header('Location: index.php?step=4');
                exit;
            }
        }
    }
}

/* ============ 渲染 ============ */
$pageTitle = '安装向导';

// 计算默认安装目录（子目录部署自动识别）
$computedBase = '';
if (isset($_SERVER['SCRIPT_NAME'])) {
    $dir = dirname(dirname(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'])));
    $computedBase = ($dir === '/' || $dir === '.' || $dir === '') ? '' : $dir;
}

require __DIR__ . '/tpl/header.php';
?>

<div class="steps">
    <div class="step <?= $step >= 1 ? 'on' : '' ?>"><span>1</span>环境检查</div>
    <div class="step <?= $step >= 2 ? 'on' : '' ?>"><span>2</span>数据库</div>
    <div class="step <?= $step >= 3 ? 'on' : '' ?>"><span>3</span>站点与管理</div>
    <div class="step <?= $step >= 4 ? 'on' : '' ?>"><span>4</span>完成</div>
</div>

<?php if ($errors): ?>
    <div class="box error">
        <b>以下问题需要处理：</b>
        <ul><?php foreach ($errors as $er): ?><li><?= $er ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<?php if ($step === 1): ?>
    <div class="box">
        <h2>第一步：环境检查</h2>
        <p>请确认以下项目均已就绪，否则无法正常安装与运行。</p>
        <table class="req">
            <?php foreach ($requirements as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r[0]) ?></td>
                    <td class="st">
                        <?php if ($r[1]): ?>
                            <span class="pass">通过</span>
                        <?php else: ?>
                            <span class="fail">未通过<?= $r[2] !== '' ? '（当前 ' . htmlspecialchars($r[2]) . '）' : '' ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php if ($allPass): ?>
            <div class="actions">
                <a class="btn" href="index.php?step=2">下一步：配置数据库</a>
            </div>
        <?php else: ?>
            <div class="box warn">请先解决上方未通过的项目，再继续安装。</div>
        <?php endif; ?>
    </div>

<?php elseif ($step === 2): ?>
    <div class="box">
        <h2>第二步：数据库配置</h2>
        <p>请填写 MySQL / MariaDB 连接信息。数据库不存在时会自动创建。</p>
        <form method="post" action="index.php?step=2">
            <div class="field">
                <label>数据库主机</label>
                <input type="text" name="db_host" value="127.0.0.1" required>
            </div>
            <div class="field">
                <label>数据库端口</label>
                <input type="text" name="db_port" value="3306" required>
            </div>
            <div class="field">
                <label>数据库名</label>
                <input type="text" name="db_name" placeholder="例如 blog" required>
            </div>
            <div class="field">
                <label>数据库用户</label>
                <input type="text" name="db_user" required>
            </div>
            <div class="field">
                <label>数据库密码</label>
                <input type="password" name="db_pass" autocomplete="off">
            </div>
            <div class="actions">
                <a class="btn btn-ghost" href="index.php?step=1">上一步</a>
                <button type="submit" class="btn">测试连接并初始化</button>
            </div>
        </form>
    </div>

<?php elseif ($step === 3): ?>
    <div class="box">
        <h2>第三步：站点与管理员</h2>
        <form method="post" action="index.php?step=3">
            <div class="field">
                <label>站点名称</label>
                <input type="text" name="site_name" required>
            </div>
            <div class="field">
                <label>站点标语（首页大标题）</label>
                <input type="text" name="site_slogan" placeholder="例如：世界很美">
            </div>
            <div class="field">
                <label>站点副标题</label>
                <input type="text" name="site_subtitle" placeholder="例如：用代码和文字，各留一份">
            </div>
            <div class="field">
                <label>安装目录</label>
                <input type="text" name="base_url" value="<?= htmlspecialchars($computedBase) ?>" placeholder="根目录安装留空；子目录安装填 /blog">
                <p class="hint">系统已自动识别，请勿随意修改。</p>
            </div>
            <hr>
            <div class="field">
                <label>管理员用户名</label>
                <input type="text" name="admin_user" value="admin" required>
            </div>
            <div class="field">
                <label>管理员密码（至少 8 位）</label>
                <input type="password" name="admin_pass" autocomplete="new-password" required>
            </div>
            <div class="field">
                <label>确认密码</label>
                <input type="password" name="admin_pass2" autocomplete="new-password" required>
            </div>
            <div class="actions">
                <a class="btn btn-ghost" href="index.php?step=2">上一步</a>
                <button type="submit" class="btn">开始安装</button>
            </div>
        </form>
    </div>

<?php elseif ($step === 4): ?>
    <div class="box success">
        <h2>安装完成！</h2>
        <p>恭喜，博客已成功安装。为了安全，请立即完成以下操作：</p>
        <ul>
            <li><b>删除 <code>install</code> 目录</b>，防止被他人重新安装。</li>
            <li>妥善保管管理员账号与密码。</li>
            <li>如需修改 ICP 备案号、版权信息、预加载开关等，可进入后台「设置」中调整。</li>
        </ul>
        <div class="actions">
            <a class="btn" href="../index.php">前往网站首页</a>
            <a class="btn btn-ghost" href="../admin/login.php">进入后台</a>
        </div>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/tpl/footer.php'; ?>
