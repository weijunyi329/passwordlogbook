<?php

session_start();
// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');


include_once '../security/safe.settings.php';

// 获取当前目录
$currentDir = getcwd();
$relativePath = './';
if (substr($currentDir, -5)=='admin'){
    $relativePath='../';
}
include_once $relativePath.'config.php';
global $app_settings;
$safeSettings=require $relativePath.'security/safe.settings.php';
$appApi=$app_settings['install_path'];
// 检查虚拟目录
if($safeSettings['useVirtualPath']){
    if (substr($appApi, -1)!='/'){
        $appApi.='/';
    }
    $appApi.=$safeSettings['virtualPath'];
}
$safeSettings['appApi']=$appApi;
// 获取服务器状态
$safeSettings['serverStatus']=!file_exists($relativePath.'SERVER_STOP');
// 直接返回配置文件中的设置
echo json_encode($safeSettings);