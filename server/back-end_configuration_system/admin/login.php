<?php

session_start();
header('Content-Type: application/json');

include_once '../config.php';
include_once '../security/safe.settings.php';
global $app_settings;

// 登录尝试限制逻辑
function checkLoginAttempts(): array
{
    $attemptsFile = sys_get_temp_dir() . '/login_attempts.json';
    $attemptsData = [];
    
    // 如果文件存在，读取尝试数据
    if (file_exists($attemptsFile)) {
        $attemptsData = json_decode(file_get_contents($attemptsFile), true);
    }
    
    $currentTime = time();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // 初始化当前IP的数据
    if (!isset($attemptsData[$ip])) {
        $attemptsData[$ip] = [
            'attempts' => 0,
            'locked_until' => 0,
            'level' => 0, // 0: 正常, 1: 5分钟冷却, 2: 1小时冷却, 3: 24小时冷却
            'need_captcha' => false
        ];
    }
    
    $ipData = &$attemptsData[$ip];
    
    // 检查是否在冷却期
    if ($ipData['locked_until'] > $currentTime) {
        $remainingTime = $ipData['locked_until'] - $currentTime;
        $message = '';
        
        switch ($ipData['level']) {
            case 1:
                $message = '登录错误次数过多，请在' . ceil($remainingTime / 60) . '分钟后再试';
                break;
            case 2:
                $message = '登录错误次数过多，请在' . ceil($remainingTime / 3600) . '小时后再试';
                break;
            case 3:
                $message = '登录错误次数过多，请在' . ceil($remainingTime / 86400) . '天后再试';
                break;
        }
        
        return ['locked' => true, 'message' => $message];
    }
    
    // 如果冷却期已过，重置状态
    if ($ipData['locked_until'] <= $currentTime && $ipData['locked_until'] != 0) {
        $ipData['attempts'] = 0;
        $ipData['locked_until'] = 0;
        $ipData['need_captcha'] = false;
    }
    
    return ['locked' => false, 'attemptsData' => $attemptsData, 'ipData' => $ipData];
}

// 保存尝试数据
function saveLoginAttempts($attemptsData) {
    $attemptsFile = sys_get_temp_dir() . '/login_attempts.json';
    file_put_contents($attemptsFile, json_encode($attemptsData));
}

// 更新尝试次数
function updateLoginAttempts($success) {
    $attemptsFile = sys_get_temp_dir() . '/login_attempts.json';
    $attemptsData = [];
    
    // 如果文件存在，读取尝试数据
    if (file_exists($attemptsFile)) {
        $attemptsData = json_decode(file_get_contents($attemptsFile), true);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!isset($attemptsData[$ip])) {
        $attemptsData[$ip] = [
            'attempts' => 0,
            'locked_until' => 0,
            'level' => 0,
            'captcha' => ''
        ];
    }
    
    $ipData = &$attemptsData[$ip];
    $currentTime = time();
    
    if ($success) {
        // 登录成功，清除记录
        unset($attemptsData[$ip]);
    } else {
        // 登录失败，增加尝试次数
        $ipData['attempts']++;
        
        // 根据尝试次数和级别设置冷却时间
        if ($ipData['attempts'] >= 4) {
            switch ($ipData['level']) {
                case 0: // 第一次达到4次错误，冷却5分钟
                    $ipData['locked_until'] = $currentTime + 5 * 60;
                    $ipData['level'] = 1;
                    break;
                case 1: // 第二次达到4次错误，冷却1小时
                    $ipData['locked_until'] = $currentTime + 60 * 60;
                    $ipData['level'] = 2;
                    break;
                case 2: // 第三次达到4次错误，冷却24小时
                    $ipData['locked_until'] = $currentTime + 24 * 60 * 60;
                    $ipData['level'] = 3;
                    break;
                case 3: // 已经是最高级别冷却，保持24小时冷却
                    $ipData['locked_until'] = $currentTime + 24 * 60 * 60;
                    break;
            }
            // 重置尝试次数
            $ipData['attempts'] = 0;
        }
        
        // 如果超过2次错误需要验证码
        if ($ipData['attempts'] >= 2) {
            $ipData['need_captcha'] = true;
        }
    }
    
    saveLoginAttempts($attemptsData);
    return $ipData;
}

// 验证验证码
function verifyCaptcha($inputCaptcha) {
    if (!isset($_SESSION['captcha'])) {
        return false;
    }
    return !empty($inputCaptcha) && strtolower($inputCaptcha) === strtolower($_SESSION['captcha']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查登录尝试限制
    $checkResult = checkLoginAttempts();
    
    if ($checkResult['locked']) {
        echo json_encode(['success' => false, 'message' => $checkResult['message']]);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $token = $input['token'] ?? '';
    $captcha = $input['captcha'] ?? '';

    // 获取当前IP的尝试数据
    $attemptsFile = sys_get_temp_dir() . '/login_attempts.json';
    $attemptsData = [];
    if (file_exists($attemptsFile)) {
        $attemptsData = json_decode(file_get_contents($attemptsFile), true);
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ipData = $attemptsData[$ip] ?? ['attempts' => 0, 'need_captcha' => false];
    
    // 如果需要验证码，验证它
    if ($ipData['need_captcha']) {
        if (empty($captcha)) {
            echo json_encode(['success' => false, 'message' => '请输入验证码', 'needCaptcha' => true]);
            exit();
        }
        
        if (!verifyCaptcha($captcha)) {
            // 验证码错误，更新尝试次数
            $ipData = updateLoginAttempts(false);
            $message = '验证码错误';
            
            // 添加尝试次数信息
            if ($ipData['attempts'] > 0) {
                $remainingAttempts = 4 - $ipData['attempts'];
                if ($remainingAttempts > 0) {
                    $message .= "，您还可以尝试 {$remainingAttempts} 次";
                }
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $message, 
                'needCaptcha' => true
            ]);
            exit();
        }
    }

    // 验证口令
    if (password_verify($token, $app_settings['token'])) {
        $_SESSION['admin_logged_in'] = true;
        // 登录成功，更新尝试次数
        updateLoginAttempts(true);
        // 清除验证码
        unset($_SESSION['captcha']);
        echo json_encode(['success' => true]);
    } else {
        // 登录失败，更新尝试次数
        $ipData = updateLoginAttempts(false);
        $message = '口令错误';
        
        // 添加尝试次数信息
        if ($ipData['attempts'] > 0) {
            $remainingAttempts = 4 - $ipData['attempts'];
            if ($remainingAttempts > 0) {
                $message .= "，您还可以尝试 {$remainingAttempts} 次";
            }
        }
        
        // 如果尝试次数超过2次，需要验证码
        $response = ['success' => false, 'message' => $message];
        if ($ipData['need_captcha']) {
            $response['needCaptcha'] = true;
        }
        
        echo json_encode($response);
    }
} else {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
}