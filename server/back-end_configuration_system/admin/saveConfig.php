<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
header('Content-Type: application/json');


include_once './SafeSettingsPhpBuilder.php';
include_once './compile.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取POST数据
    $input = json_decode(file_get_contents('php://input'), true);
    //检查参数类型
    checkParameterType($input['useToken'],'boolean');
    checkParameterType($input['useCookie'],'boolean');
    checkParameterType($input['cookieTime'],'integer');
    checkParameterType($input['logIp'],'boolean');
    checkParameterType($input['useVirtualPath'],'boolean');
    checkParameterType($input['virtualPath'],'string');
    checkParameterType($input['onlyHttpsAccess'],'boolean');
    checkParameterType($input['restrictUa'],'boolean');
    checkParameterType($input['allowedUa'],'string');
    checkParameterType($input['restrictIp'],'boolean');
    checkParameterType($input['whitelistIp'],'string');
    checkParameterType($input['blacklistIp'],'string');
    checkParameterType($input['restrictUuid'],'boolean');
    checkParameterType($input['allowedUuid'],'string');

    // 生成配置文件
    $safeSettingsPhpBuilder=new SafeSettingsPhpBuilder('../');

    // 保存配置
    foreach ($input as $key => $value) {
        $safeSettingsPhpBuilder->putValue($key,$value);
    }

    //判断文件夹是否存在,不存在则创建
    if (!is_dir('../security/access_check')) {
        // 尝试创建文件夹（权限需允许）
        if (!mkdir('../security/access_check', 0755, true)) {
            echo json_encode(['success' => false, 'message' => '尝试创建文件夹失败']);
            exit();
        }
    }
    //判断文件夹是否存在,不存在则创建
    if (!is_dir('../security/log')) {
        // 尝试创建文件夹（权限需允许）
        if (!mkdir('../security/log', 0755, true)) {
            echo json_encode(['success' => false, 'message' => '尝试创建文件夹失败']);
            exit();
        }
    }

    //判断UserAgent.php是否需要重新生成
    if (in_array('restrictUa', $safeSettingsPhpBuilder->getUpdatedItem())
        || in_array('allowedUa', $safeSettingsPhpBuilder->getUpdatedItem())
    ){
        if($safeSettingsPhpBuilder->getValue('restrictUa')===false || $safeSettingsPhpBuilder->getValue('allowedUa')==''){
            Compile::compilePhpCheckFunction([],'checkUa',true,'../security/access_check/UserAgent.php');
        }else{
            Compile::compilePhpCheckFunction(
                Compile::settingsStrToArrayList($safeSettingsPhpBuilder->getValue('allowedUa')),
                'checkUa',true,'../security/access_check/UserAgent.php');
        }
    }
    //判断WhiteIp.php BlackIp.php是否需要重新生成
    if (in_array('restrictIp', $safeSettingsPhpBuilder->getUpdatedItem()) ||
        in_array('whitelistIp', $safeSettingsPhpBuilder->getUpdatedItem()) ||
        in_array('blacklistIp', $safeSettingsPhpBuilder->getUpdatedItem())

    ){
        if($safeSettingsPhpBuilder->getValue('restrictIp')===false){
            Compile::compilePhpCheckFunction(
                [],
                'checkWhiteIp',
                true,'../security/access_check/WhiteIp.php');
            Compile::compilePhpCheckFunction([],
                'checkBlackIp',
                false,
                '../security/access_check/BlackIp.php',
                true);
        }else{
            //判断WhiteIp.php是否需要重新生成
            if ($safeSettingsPhpBuilder->getValue('whitelistIp')===''){
                Compile::compilePhpCheckFunction([],
                    'checkWhiteIp',
                    true,
                    '../security/access_check/WhiteIp.php');
            }else{
                Compile::compilePhpCheckFunction(
                    Compile::settingsStrToArrayList($safeSettingsPhpBuilder->getValue('whitelistIp'))
                    ,'checkWhiteIp',true,'../security/access_check/WhiteIp.php');
            }
            //判断BlackIp.php是否需要重新生成
            if ($safeSettingsPhpBuilder->getValue('blacklistIp')===''){
                Compile::compilePhpCheckFunction([],
                    'checkBlackIp',
                    false,
                    '../security/access_check/BlackIp.php',
                    true);
            }else{
                Compile::compilePhpCheckFunction(
                    Compile::settingsStrToArrayList($safeSettingsPhpBuilder->getValue('checkBlackIp'))
                    ,'checkBlackIp',false,
                    '../security/access_check/BlackIp.php',
                    true);
            }

        }
    }
    //判断Uuid.php是否需要重新生成
    if (in_array('restrictUuid', $safeSettingsPhpBuilder->getUpdatedItem())
        || in_array('allowedUuid', $safeSettingsPhpBuilder->getUpdatedItem())
    ){
        if($safeSettingsPhpBuilder->getValue('restrictUuid')===false || $safeSettingsPhpBuilder->getValue('allowedUuid')==''){
            Compile::compilePhpCheckFunction([],'checkUuid',true,'../security/access_check/Uuid.php');
        }else{
            Compile::compilePhpCheckFunction(
                Compile::settingsStrToArrayList($safeSettingsPhpBuilder->getValue('allowedUuid'))
                ,'checkUuid',true,'../security/access_check/Uuid.php');
        }
    }
    //判断是否需要重新生成
    if($safeSettingsPhpBuilder->isUpdated()){
        $safeSettingsPhpBuilder->save();
    }
    echo json_encode(['success' => true, 'message' => '保存设置成功']);
} else {
    echo json_encode(['success' => false, 'message' => '请求方法不正确']);
}
function checkParameterType($itemName, $itemType)
{
    if(gettype($itemName)!=='NULL'){
      if (gettype($itemName)===$itemType){
          return true;
      }else{
          echo json_encode(['success' => false, 'message' => '参数类型错误']);
          exit();
      }
    }

}
//function regenerateAccessCheckFiles($config) {
//    // 生成 BlackIp.php
//    $blackIpContent = "<?php\nfunction checkBlackIp(\$ip) {\n";
//    if (!empty($config['blacklistIp'])) {
//        $blackIps = array_filter(array_map('trim', explode("\n", $config['blacklistIp'])));
//        $blackIpContent .= "    return in_array(\$ip, [\n";
//        foreach ($blackIps as $ip) {
//            $blackIpContent .= "        '" . addslashes($ip) . "',\n";
//        }
//        $blackIpContent .= "    ]);\n";
//    } else {
//        $blackIpContent .= "    return false;\n";
//    }
//    $blackIpContent .= "}\n";
//    file_put_contents('../security/access_check/BlackIp.php', $blackIpContent);
//    
//    // 生成 WhiteIp.php
//    $whiteIpContent = "<?php\nfunction checkWhiteIp(\$ip) {\n";
//    if (!empty($config['whitelistIp'])) {
//        $whiteIps = array_filter(array_map('trim', explode("\n", $config['whitelistIp'])));
//        $whiteIpContent .= "    return in_array(\$ip, [\n";
//        foreach ($whiteIps as $ip) {
//            $whiteIpContent .= "        '" . addslashes($ip) . "',\n";
//        }
//        $whiteIpContent .= "    ]);\n";
//    } else {
//        $whiteIpContent .= "    return true;\n";
//    }
/*    $whiteIpContent .= "}\n?>\n";*/
//    file_put_contents('../security/access_check/WhiteIp.php', $whiteIpContent);
//    
//    // 生成 Uuid.php
//    $uuidContent = "<?php\nfunction checkUuid(\$uuid) {\n";
//    if (!empty($config['allowedUuid']) && $config['restrictUuid']) {
//        $uuids = array_filter(array_map('trim', explode("\n", $config['allowedUuid'])));
//        $uuidContent .= "    return in_array(\$uuid, [\n";
//        foreach ($uuids as $uuid) {
//            $uuidContent .= "        '" . addslashes($uuid) . "',\n";
//        }
//        $uuidContent .= "    ]);\n";
//    } else {
//        $uuidContent .= "    return true;\n";
//    }
/*    $uuidContent .= "}\n?>\n";*/
//    file_put_contents('../security/access_check/Uuid.php', $uuidContent);
//    
//    // 生成 UserAgent.php
//    $uaContent = "<?php\nfunction checkUa(\$ua) {\n";
//    if (!empty($config['allowedUa']) && $config['restrictUa']) {
//        $uas = array_filter(array_map('trim', explode("\n", $config['allowedUa'])));
//        $uaContent .= "    return in_array(\$ua, [\n";
//        foreach ($uas as $ua) {
//            $uaContent .= "        '" . addslashes($ua) . "',\n";
//        }
//        $uaContent .= "    ]);\n";
//    } else {
//        $uaContent .= "    return true;\n";
//    }
//    $uaContent .= "}\n";
//    file_put_contents('../security/access_check/UserAgent.php', $uaContent);
//}
