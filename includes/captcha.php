<?php
/**
 * 图片验证码（自绘，基于 GD）
 * 用于评论人机验证。验证码文本保存在会话中，验证后一次性作废。
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** 生成并保存新的验证码文本（4 位，去掉易混淆字符 0/O/1/l/I） */
function captcha_new_code()
{
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
    $code = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < 4; $i++) {
        $code .= $chars[random_int(0, $max)];
    }
    $_SESSION['captcha_code'] = $code;
    return $code;
}

/** 校验用户输入（不区分大小写），校验后立即清除，保证一次性 */
function captcha_verify($input)
{
    $code = $_SESSION['captcha_code'] ?? '';
    unset($_SESSION['captcha_code']);
    return $code !== '' && strtolower(trim((string)$input)) === strtolower($code);
}
