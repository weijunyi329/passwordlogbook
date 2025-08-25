<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');


$logPath=  '../security/log/access_log.txt';
if (file_exists($logPath)){
    unlink($logPath);
}
echo json_encode(['success'=>true,'message'=>'Log file deleted successfully.']);