<?php


include_once './Utils.php';
include_once './config.php';
/**
 * 获取路径的各个部分
 * @return string[]
 */
function getPathParts(): array
{

    include_once "config.php";
    global $app_settings;
    $realUrl = $_SERVER['REQUEST_URI'];
    if ($pos = strpos($realUrl, '?')) {
        $realUrl = substr($realUrl, 0, $pos);
    }
    //如果是'/'不处理
    if ($realUrl == '/') {
        return ['/'];
    }
    if($realUrl==substr($app_settings['install_path'],0,-1) || $realUrl==$app_settings['install_path']){
        return ['/'];
    }
    // 如果路径中没有'/'
    if (strpos($realUrl, '/')=== false){
        die(400);
    }
    //判断是否包含安装路径
    $install_path_length=strlen($app_settings['install_path']);
    if (strlen($realUrl)<$install_path_length || substr($realUrl, 0, $install_path_length)!==$app_settings['install_path'] ){
        exit(400);
    }
    //取除了安装路径
    $realUrl=substr($realUrl, $install_path_length);

    //去除开头的'/'
    if (str_startWith($realUrl, '/')){
        $realUrl=substr($realUrl, 1);
    }
    //去除结尾的'/'
    if (str_endWith($realUrl, '/')){
        $realUrl=substr($realUrl, 0, -1);
    }
    //判断是否包含'/',没有包含则返回一个数组，包含一个元素，该元素为realUrl
    if (strpos($realUrl, '/')=== false){
        return [$realUrl];
    }

    //分隔路径
    $raw_parts = explode('/',$realUrl);

    $processed_parts = array_map(function($value) {
        // 判断 $value 是否为空字符串
        return $value;
    }, $raw_parts);

    return $processed_parts;
}


/**
 * 获取真实路径（相对于安装位置）
 * @param $checkVirtualPart 是否检查包含虚拟目录
 * @return false|mixed|string|null
 */
function getURLRelativeInstall($checkVirtualPart=false)
{

    global $app_settings;
    $safe_settings = require './security/safe.settings.php';
    $realUrl = $_SERVER['REQUEST_URI'];
    if ($pos = strpos($realUrl, '?')) {
        $realUrl = substr($realUrl, 0, $pos);
    }

    if ($app_settings['install_path'] == '/' ){
       if($realUrl=='/' || $realUrl==''){
           $realUrl='/';
       }
    }else{
        $install_path=$app_settings['install_path'];
        $install_path_=$install_path;
        if (!str_endWith($install_path,"/")){
            $install_path_=$install_path."/";
        }else{
            $install_path=substr($install_path,0,-1);
        }
        $install_pathLen=strlen($install_path);
        $install_pathLen_=strlen($install_path_);

        if(str_startWith($realUrl,$install_path) && strlen($realUrl)==strlen($install_path)){
            $realUrl='/';
        }else if (($install_pathLen!=$install_pathLen_ )
            &&(str_startWith($realUrl,$install_path_))){
            $realUrl = '/' . substr($realUrl, $install_pathLen_);
        }else{
            sendFinalResult(400, 'URL ERROR!!!');
        }

    }
    // 检查虚拟路径
    if ($checkVirtualPart && $safe_settings['useVirtualPath']){
        if ($realUrl!='')
            if (str_startWith($realUrl,'/'.$safe_settings['virtualPath'])){
                if (
                    (strlen($realUrl)==strlen('/'.$safe_settings['virtualPath']))
                    ||
                    (str_startWith($realUrl,'/'.$safe_settings['virtualPath'].'/'))
                ){
                    $realUrl= substr($realUrl,strlen('/'.$safe_settings['virtualPath']));
                    if (strlen($realUrl)==0){
                        $realUrl= '/';
                    }
                    return $realUrl;
                }
            }
        return null;
    }
    return $realUrl;
}

/**
 * 允许跨域访问
 * @return void
 */
function allowCrossDomain()
{
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS'){
        setCrossDomain();
        http_response_code(200);
        exit();
    }else{
        setCrossDomain();
    }
}

/**
 * 获取当前访问的UA
 * @return mixed
 */
function getUserAgent()
{
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    return $userAgent ;
}

/**
 * 获取当前访问的IP
 * @return mixed
 */
function getVisitorIP()
{
   return $_SERVER['REMOTE_ADDR'];
}

/**
 * 获取当前访问的UUID
 * @return void
 */
function getVisitorUUID()
{
    // 检查X-UUID是否存在
    if (isset($_SERVER['HTTP_X_UUID'])) {
        $uuid = $_SERVER['HTTP_X_UUID'];
        return $uuid;
    } else if (isset($_SERVER['HTTP_UUID'])){
        $uuid = $_SERVER['HTTP_UUID'];
        return $uuid;
    }else{
        return null;
    }
}
/**
 * 检查请求方法
 * @param $method
 * @return void
 */
function checkMethod($method) {
    if (!isset($_SERVER['REQUEST_METHOD']))exit();
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')return;
    if ($_SERVER['REQUEST_METHOD'] == null)exit();
    if ($_SERVER['REQUEST_METHOD'] != $method)exit();
}
/**
 * 设置跨域请求头
 * @return void
 */
function setCrossDomain()
{
    // 设置跨域请求头
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization, uuid");
}

/**
 * 发送最终结果
 * @param int $responseCode HTTP响应码
 * @param string $message 响应消息
 * @param array|null $data 响应数据
 */
function sendFinalResult($responseCode, $message, $data = null)
{
    $responseData = [
        'message' => $message,
        'success' => $responseCode==200
    ];
    if ($data== null){
        http_response_code($responseCode);
    }else{
        http_response_code($responseCode);
        foreach ($data as $key => $value) {
            $responseData [$key]=$value;
        }
    }
    echo json_encode($responseData);
}

/**
 * 记录临时文件
 * @param $newtempfile
 * @return void
 */
function  recordTempFile($newtempfile=null)
{
    $res=file_get_contents("./tempfiles");
    if ($res!=null && $res!="" && file_exists($res)){
        unlink($res);
    }
    if ($newtempfile!=null){
        file_put_contents("./tempfiles",$newtempfile);
    }else{
        file_put_contents("./tempfiles","");
    }
}
/**
 * 删除临时文件
 * @param $newtempfile
 * @return void
 */
function  removeTempFile($newtempfile=null)
{
    $res=file_get_contents("./tempfiles");
    if ($res!=null && $res==$newtempfile){
        file_put_contents("./tempfiles","");
    }

}