<?php

session_start();
// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');



include_once '../config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $newToken = $input['newToken'] ?? '';

    if (empty($newToken)) {
        echo json_encode(['success' => false, 'message' => '新口令不能为空']);
        exit;
    }

    // 读取config.php文件
    $configContent = file_get_contents('../config.php');
    
    // 生成新的密码哈希
    $hashedToken = password_hash($newToken, PASSWORD_DEFAULT);
    $hashedToken=trim($hashedToken);

    // 替换token值
    $newConfigContent = preg_replace(
        "/('token'\s*=>(\s)*')[^']*(')/",
        "'token' => '".preg_quote($hashedToken)."'",
        $configContent
    );
    
    // 写入文件
    if (file_put_contents('../config.php', $newConfigContent)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '修改口令失败']);
    }
} else {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
}