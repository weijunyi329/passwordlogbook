<?php
session_start();
include_once '../config.php';
include_once '../security/safe.settings.php';

// 检查用户是否已登录
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo json_encode(['loggedIn' => true]);
} else {
    echo json_encode(['loggedIn' => false]);
}