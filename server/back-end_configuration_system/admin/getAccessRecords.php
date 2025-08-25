<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');


$logPath=  '../security/log/access_log.txt'; // 指定文件名模式，这里是所有以.log.txt结尾的文件
if (file_exists($logPath)){
    $logStr=file_get_contents($logPath);
    $backData=array();
    $logItems=preg_split('/(\\n|\\r)+/',$logStr);
    for ($i = 0; $i < count($logItems); $i++) {
        $item=trim($logItems[$i]);
        if ($item==''){
            continue;
        }else{
            try {
                $backData[]=json_decode($logItems[$i],true);
            }catch (Exception $e)
            {}
        }
    }
    echo json_encode($backData);
}else{
    echo '[]';
}
