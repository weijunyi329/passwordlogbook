<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');

try {
    if (!file_exists('../SERVER_STOP')){
        file_put_contents('../SERVER_STOP','stop');
        echo json_encode(['success'=>true,'message'=>'Server stopped successfully.','status'=>'stopped']);
    }else{
        unlink('../SERVER_STOP');
        echo json_encode(['success'=>true,'message'=>'Server started successfully.','status'=>'running']);
    }
}catch (Exception $e){
    echo json_encode(['success'=>false,'message'=>'Failed to stop server.'.$e->getMessage(),'status'=>'error']);
}
