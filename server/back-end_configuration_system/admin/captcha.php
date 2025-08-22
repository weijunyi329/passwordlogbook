<?php
session_start();

// 生成验证码图片
function generateCaptchaImage($text) {
    // 创建图像
    $width = 120;
    $height = 40;
    $image = imagecreate($width, $height);

    // 设置颜色
    $bgColor = imagecolorallocate($image, 255, 255, 255); // 白色背景
    $textColor = imagecolorallocate($image, 0, 0, 0);     // 黑色文字
    $noiseColor = imagecolorallocate($image, 150, 150, 150); // 灰色干扰点

    // 填充背景
    imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

    // 添加干扰点
    for ($i = 0; $i < 50; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $noiseColor);
    }

    // 添加干扰线
    for ($i = 0; $i < 5; $i++) {
        imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $noiseColor);
    }

    // 添加文字
    $font = 5; // 内置字体
    $x = ($width - imagefontwidth($font) * strlen($text)) / 2;
    $y = ($height - imagefontheight($font)) / 2;
    imagestring($image, $font, $x, $y, $text, $textColor);

    // 输出图像
    header('Content-Type: image/png');
    imagepng($image);
    imagedestroy($image);
}

// 生成随机验证码
function generateCaptchaText() {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $captcha = '';
    for ($i = 0; $i < 4; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $captcha;
}

// 处理刷新请求
if (isset($_GET['refresh'])) {
    $_SESSION['captcha'] = generateCaptchaText();
    exit();
}

// 生成并显示验证码图片
if (!isset($_SESSION['captcha'])) {
    $_SESSION['captcha'] = generateCaptchaText();
}

generateCaptchaImage($_SESSION['captcha']);