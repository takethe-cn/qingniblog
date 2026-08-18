<?php
/**
 * 图片验证码输出接口
 * 访问本文件会生成一张带干扰的验证码图片，并将答案写入会话。
 * 每次访问都会生成新验证码，配合前台 JS 刷新使用。
 *
 * 兼容性：优先用 GD 生成 PNG；若 GD 缺失/无 PNG 支持/相关函数不可用，
 * 自动降级为纯文本 SVG 验证码（无需 GD，任何环境都能出图）。
 */
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/captcha.php';

$code = captcha_new_code();

/** 用 GD 生成 PNG 验证码，返回图片二进制；失败返回 null */
function captcha_make_png($code)
{
    $need = ['imagecreatetruecolor', 'imagecolorallocate', 'imagefilledrectangle',
             'imageline', 'imagesetpixel', 'imagepng'];
    foreach ($need as $f) {
        if (!function_exists($f)) {
            return null;
        }
    }

    $w = 130;
    $h = 44;

    $img = @imagecreatetruecolor($w, $h);
    if ($img === false) {
        return null;
    }

    // 背景（浅色，与主题协调）
    $bg = imagecolorallocate($img, 246, 247, 240);
    imagefilledrectangle($img, 0, 0, $w, $h, $bg);

    // 干扰色
    $lineColors = [
        imagecolorallocate($img, 214, 224, 200), // 淡绿
        imagecolorallocate($img, 250, 225, 150), // 淡黄
    ];

    // 随机干扰线
    for ($i = 0; $i < 5; $i++) {
        imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $lineColors[random_int(0, 1)]);
    }

    // 噪点
    for ($i = 0; $i < 90; $i++) {
        imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $lineColors[random_int(0, 1)]);
    }

    // 文字：仅使用 GD 内置位图字体（不依赖任何外部字体文件，
    // 避免 open_basedir / FreeType 等环境差异导致警告或无法出图）
    if (!function_exists('imagechar') || !function_exists('imagecopyresized')) {
        return null;
    }
    $textColor = imagecolorallocate($img, 77, 124, 15); // 青柠深绿
    $len = strlen($code);

    // 每个字符先用内置字体画到临时画布，再放大 2 倍贴回，提高可读性
    for ($i = 0; $i < $len; $i++) {
        $charImg = @imagecreatetruecolor(9, 15);
        if ($charImg === false) {
            continue;
        }
        $transparent = imagecolorallocatealpha($charImg, 0, 0, 0, 127);
        imagefill($charImg, 0, 0, $transparent);
        $charColor = imagecolorallocate($charImg, 77, 124, 15);
        imagechar($charImg, 5, 0, 0, $code[$i], $charColor);
        imagecolortransparent($charImg, $transparent);

        $x = 10 + $i * 30;
        $y = random_int(4, 12);
        @imagecopyresized($img, $charImg, $x, $y, 0, 0, 18, 30, 9, 15);
        imagedestroy($charImg);
    }

    // 捕获 PNG 二进制
    ob_start();
    $ok = @imagepng($img);
    $data = ob_get_clean();
    imagedestroy($img);

    return ($ok && $data !== '' && $data !== false) ? $data : null;
}

/** 纯文本 SVG 验证码（无需 GD），任何环境都能输出 */
function captcha_make_svg($code)
{
    $w = 130;
    $h = 44;
    $len = strlen($code);
    $step = intdiv($w, $len + 1);
    $chars = [];
    $lines = [];
    for ($i = 0; $i < $len; $i++) {
        $angle = random_int(-24, 24);
        $x = $step * ($i + 1) + random_int(-4, 4);
        $y = 30 + random_int(-4, 4);
        $chars[] = sprintf(
            '<text x="%d" y="%d" font-family="Verdana, Arial, sans-serif" font-size="26" font-weight="bold" fill="#4d7c0f" transform="rotate(%d %d %d)">%s</text>',
            $x, $y, $angle, $x, $y, htmlspecialchars($code[$i], ENT_QUOTES, 'UTF-8')
        );
    }
    for ($i = 0; $i < 5; $i++) {
        $x1 = random_int(0, $w);
        $y1 = random_int(0, $h);
        $x2 = random_int(0, $w);
        $y2 = random_int(0, $h);
        $color = $i % 2 ? '#d6e0c8' : '#fae196';
        $lines[] = '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="' . $color . '" stroke-width="1"/>';
    }
    $dots = '';
    for ($i = 0; $i < 80; $i++) {
        $dots .= '<circle cx="' . random_int(0, $w) . '" cy="' . random_int(0, $h) . '" r="1" fill="' . ($i % 2 ? '#d6e0c8' : '#fae196') . '"/>';
    }
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">'
        . '<rect width="100%" height="100%" fill="#f6f7f0"/>'
        . implode('', $lines) . $dots . implode('', $chars)
        . '</svg>';
}

// 禁用缓存，保证每次刷新都是新图
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// 兜底：生成过程中的任何意外输出（如 PHP 警告）都会被捕获并丢弃，
// 保证最终输出的是纯净的图片数据，不污染 header 与二进制内容
ob_start();
$png = captcha_make_png($code);
ob_end_clean();

if ($png !== null) {
    header('Content-Type: image/png');
    echo $png;
} else {
    header('Content-Type: image/svg+xml; charset=utf-8');
    echo captcha_make_svg($code);
}
