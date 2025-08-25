<?php

include_once "./DbHelper.php";
include_once "./config.php";
include_once "./common.php";
/**
 * 获取数据库连接实例
 * @return DbHelper 数据库操作实例
 */
function getDbConnection(): DbHelper {
    global $mysql_settings;
    return new DbHelper(
        $mysql_settings['host'],
        $mysql_settings['port'],
        $mysql_settings['database'],
        $mysql_settings['table'],
        $mysql_settings['user'],
        $mysql_settings['password']
    );
}


//接口功能实现方法
///*********************************************************************///

/**
 * 处理文件上传请求
 * 接收客户端上传的文件并保存到服务器
 */
function handleFileUpload() {
    checkMethod('POST');

    if (!isset($_FILES['file'])) {
        sendFinalResult(400, 'Error: No file uploaded');
        return;
    }

    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        sendFinalResult(400, "Error: No file uploaded：" . $_FILES['file']['error']);
        return;
    }

    // 检查文件大小 (小于10KB)
    if ($_FILES['file']['size'] > 20240) { // 10KB = 10240 bytes
        sendFinalResult(400, '文件大小超过10KB限制');
        return;
    }

    $uploadDir = './uploads/';

    // 创建上传目录（如果不存在）
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $tmp_name = $_FILES['file']['tmp_name'];
    $name = $_FILES['file']['name'];
    $size = $_FILES['file']['size'];
    $type = $_FILES['file']['type'];

    // 验证文件类型为PNG图片
    $finfo = isPngFile($tmp_name );
//    $mimeType = isPngFile($finfo, $tmp_name);
//    finfo_close($finfo);

    if (!$finfo) {
        sendFinalResult(400, '只允许上传PNG格式图片');
        return;
    }

    // 生成唯一文件名
    $newFileName = (str_replace('.','_',uniqid('icon', true))). ".png";
    $fileDestination = $uploadDir . $newFileName;

    // 验证图片尺寸 (小于128*128)
    $imageInfo = getimagesize($tmp_name);
    if ($imageInfo === false) {
        sendFinalResult(400, '文件不是有效的图片');
        return;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];

    if ($width > 128 || $height > 128) {
        sendFinalResult(400, '图片尺寸超过128*128限制');
        return;
    }

    // 移动上传文件到目标位置
    if (move_uploaded_file($tmp_name, $fileDestination)) {
        // 记录临时图片名，表示这个图片没有被使用
        recordTempFile($fileDestination);

        sendFinalResult(200, 'File upload successful', ['fileName' => $newFileName]);
    } else {
        sendFinalResult(500, '文件移动失败');
    }
}


/**
 * 处理资源下载请求
 * 根据文件名下载指定的资源文件
 */
function handleResourceDownload() {
    checkMethod('GET');

    // 检查文件名参数
    if (empty($_GET['name'])) {
        sendFinalResult(400, '文件名参数缺失');
        return;
    }

    $file_path = './uploads/' . $_GET['name'];

    // 检查文件是否存在
    if (!file_exists($file_path)) {
        sendFinalResult(404, '文件不存在');
        return;
    }

    // 设置下载响应头
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
    header('Content-Length: ' . filesize($file_path));

    // 输出文件内容
    readfile($file_path);
    exit;
}

/**
 * 处理数据插入请求
 * 向数据库中插入新的密码记录
 */
function handleInsert() {
    checkMethod('POST');

    $db = getDbConnection();

    // 验证至少一个必填字段
    if (isEmpty($_POST['title']) && isEmpty($_POST['pkg']) && isEmpty($_POST['url'])) {
        sendFinalResult(400, '至少填写一个title/pkg/urlStr');
        return;
    }

    // 检查重复记录
    $title = $_POST['title'] ?? '';
    $pkg = $_POST['pkg'] ?? '';
    $urlStr = $_POST['url'] ?? '';

    if ((!isEmpty($title) && $db->searchByTitle($title)) ||
        (!isEmpty($pkg) && $db->searchByPackageName($pkg)) ||
        (!isEmpty($urlStr) && $db->searchByUrl($urlStr))) {
        sendFinalResult(400, '相似的选项已经存在');
        return;
    }

    if( isset($_POST['icon']) && $_POST['icon']!=null && $_POST['icon'] != ''){
        removeTempFile("./uploads/".$_POST['icon']);
    }

    // 处理modification字段,保证为正确的时间戳
    $modification = getNumber($_POST['modification']);
    if ($modification <= 0){
        $modification = obtain13Timestamp();
    }
    // 准备插入数据
    $newData = [
        'title' => $_POST['title'] ?? '',
        'url' => $_POST['url'] ?? '',
        'packageName' => $_POST['packageName'] ?? '',
        'icon' =>$_POST['icon'] ?? '',
        'remark' => $_POST['remark'] ?? '',
        'modification' => $modification ,
        'accounts' => $_POST['accounts'] ?? ''
    ];

    // 执行插入操作
    $result = $db->insert($newData);
    if (!$result){
        sendFinalResult(400, '相似的选项已经存在');
        return;
    }
    sendFinalResult(200, '添加成功');
}

/**
 * 处理获取所有数据请求
 * 从数据库中获取所有密码记录
 */
function handleGetAll() {
    checkMethod('GET');

    $db = getDbConnection();
    $result = $db->findAll();
    if ($result===false){
        sendFinalResult(400, '没有数据');
    }else {
        echo json_encode($result);
    }
}

/**
 * 处理删除数据请求
 * 根据ID删除指定的密码记录
 */
function handleDelete() {
    checkMethod('GET');
    // 验证ID参数
    if (empty($_GET['id']) || !isNum($_GET['id'])) {
        sendFinalResult(400, '无效的ID参数');
        return;
    }

    $db = getDbConnection();
    $id = intval($_GET['id']);
    $result = $db->delete($id);

    // 检查删除结果
    if (!$result) {
        sendFinalResult(400, '删除失败，ID可能不存在');
        return;
    }
    sendFinalResult(200, '删除成功');
}

/**
 * 处理更新数据请求
 * 根据ID更新指定的密码记录
 */
function handleUpdate() {
    checkMethod('POST');

    $db = getDbConnection();

    // 验证至少一个必填字段
    if (isEmpty($_POST['title']) && isEmpty($_POST['pkg']) && isEmpty($_POST['urlStr'])) {
        sendFinalResult(400, '至少填写一个title/pkg/urlStr');
        return;
    }

    // 检查重复记录
    $title = $_POST['title'] ?? '';
    $pkg = $_POST['pkg'] ?? '';
    $urlStr = $_POST['urlStr'] ?? '';


    // 验证ID参数
    if (!isNum($_POST['id'])) {
        sendFinalResult(400, 'id错误');
        return;
    }

    // 处理modification字段,保证为正确的时间戳
    $modification = getNumber($_POST['modification']);
    if ($modification <= 0){
        $modification = obtain13Timestamp();
    }

    if(isset($_POST['icon']) &&  $_POST['icon'] && $_POST['icon'] != 'null'){
        removeTempFile("./uploads/".$_POST['icon']);
    }
    // 准备更新数据
    $newData = [
        'title' => $_POST['title'] ?? '',
        'url' => $_POST['url'] ?? '',
        'packageName' => $_POST['packageName'] ?? '',
        'icon' => $_POST['icon'] ?? '',
        'remark' => $_POST['remark'] ?? '',
        'modification' => $modification ,
        'accounts' => $_POST['accounts'] ?? ''
    ];

    // 执行更新操作
    $result = $db->update(intval($_POST['id']), $newData);
    if(!$result ){
        sendFinalResult(400, '未知错误!!!');
        return;
    }
    sendFinalResult(200, '更新成功');
}

/**
 * 处理忘记密码请求
 * @return void
 * @throws Exception
 */
function forgetPassword ()
{
    checkMethod('GET');

    $db = getDbConnection();
    //将数据库Accounts清空
    $result = $db->updateAccountsEmpty();
    if(!$result ){
        sendFinalResult(400, '未知错误!!!');
        return;
    }
    sendFinalResult(200, '重置成功');
}
/**
 * 处理重置密码请求
 * @return void
 * @throws Exception
 */
function resetPassword()
{
    checkMethod('POST');

    $db = getDbConnection();
    // 获取原始 POST 数据
    $json = file_get_contents('php://input');


    // 将 JSON 字符串解码为 PHP 数组或对象
    $data = json_decode($json, true); // 第二个参数设置为 true 以获得关联数组

    // 检查是否有错误发生（例如，无效的 JSON）
    if (json_last_error() != JSON_ERROR_NONE || !is_array($data)) {
        sendFinalResult(400, 'json错误!!!');
        return;
    }
    for ($i = 0; $i < count($data); $i++) {
        $value=$data[$i];
        if (isNum($value['id']) && isset($value['accounts'])){
            $res= $db->updateAccounts($value['id'],$value['accounts']);
            if(!$res){
                sendFinalResult(400, '未知错误!!!');
                return;
            }
        }else{
            sendFinalResult(400, '参数错误!!!');
            return;
        }
    }

    sendFinalResult(200, '重置密码成功');
}
