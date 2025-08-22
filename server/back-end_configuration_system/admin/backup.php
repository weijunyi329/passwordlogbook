<?php

session_start();
// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

include_once "../config.php";
include_once "../DbHelper.php";

// 检查是否支持zip功能
if (!class_exists('ZipArchive')) {
    echo json_encode(['success' => false, 'message' => '系统不支持ZipArchive类，无法创建备份']);
    exit;
}

// 创建缓存目录
$cacheDir = './.cache';
if (!is_dir($cacheDir)) {
    $createdDir = mkdir($cacheDir, 0755, true);
    if (!$createdDir) {
        echo json_encode(['success' => false, 'message' => '创建缓存目录失败']);
        exit;
    }
}
// 数据库备份到
global $mysql_settings;
try {
    $dbHelper = new DbHelper(
        $mysql_settings['host'],
        $mysql_settings['port'],
        $mysql_settings['database'],
        $mysql_settings['table'],
        $mysql_settings['user'],
        $mysql_settings['password']
    );
   $result= $dbHelper->findAll();
   file_put_contents('./.cache/data.json',json_encode($result));
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => '数据库连接失败']);
    exit;
}


// 生成备份文件名
$zipFileName = 'uploads_backup_' . date('Y-m-d_H-i-s') . '.zip';
$zipFilePath = $cacheDir . '/' . $zipFileName;

// 创建zip文件
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    echo json_encode(['success' => false, 'message' => '无法创建zip文件']);
    exit;
}

// 添加uploads目录中的文件到zip
$uploadDir = '../uploads';
if (is_dir($uploadDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($uploadDir) + 1);
        
        if (is_file($filePath)) {
            $zip->addFile($filePath, 'uploads/'.basename($filePath));
        }
    }
    if (file_exists('./.cache/data.json')){
        $zip->addFile('./.cache/data.json', 'data.json');
    }
} else {
    $zip->close();
    echo json_encode(['success' => false, 'message' => '上传目录不存在']);
    exit;
}

$zip->close();

// 提供文件下载
if (file_exists($zipFilePath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    
    readfile($zipFilePath);
    
    // 删除临时文件
    unlink($zipFilePath);
    if (file_exists('./.cache/data.json')){
        unlink('./.cache/data.json');
    }
    exit;
} else {
    echo json_encode(['success' => false, 'message' => '备份文件创建失败']);
    exit;
}