<?php
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST']; // 域名（含端口，如 :8080）
    $uri = $_SERVER['REQUEST_URI']; // 路径和查询参数（如 /path?id=123）
    return $protocol . $host . $uri;
}
function getCurrentUrlWithoutQuery()
{
    $currentUrl=getCurrentUrl();
    $start = strpos($currentUrl, 'index.php');
    if ($start !== false){
        return  substr($currentUrl, 0, $start)."install/index.php";
    }else if(substr($currentUrl, strlen($currentUrl)-1,1)==="/"){
        return  $currentUrl."install/index.php";
    }else{
        return $currentUrl."/install/index.php";
    }
}
//$currentUrl = getCurrentUrl();
//echo $currentUrl; // 输出类似：https://example.com/path/page.php?id=123
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hello World</title>
</head>
<body>
<a href="<?=  getCurrentUrlWithoutQuery()
?>"><h3>点击安装</h3></a>
</body>
</html>
