<?php
// 获取php运行相对目录
global $relativePath;
if (!isset($relativePath)){
    $relativePath = './';
    if (substr(getcwd(), -8)=='security' || substr(getcwd(), -5)=='admin'){
        $relativePath='../';
    }
}

function AccessStaticResource($url){
    global $relativePath;
    $suffix=substr($url,-3);
    if ($suffix==='png' || $suffix==='jpg' || $suffix==='gif' || $suffix==='ico'){
        header('Content-Type: image/'.$suffix);
    }else if ($suffix==='css'){
        header('Content-Type: text/css');
    }else if ($suffix==='js'){
        header('Content-Type: application/javascript');
    }else if(($suffix=substr($url,-4))==='html'){
        header('Content-Type: text/html');
    }else{
        header('Content-Type: text/html');
        http_send_status(404);
        echo '<h1>404 Not Found</h1>';
    }
    if (substr($url,0,1)==='/'){
        $url.=$relativePath.substr($url,1);
    }else if (substr($url,0,3)==='../'){
        $url.=$relativePath.substr($url,3);
    }else if (substr($url,0,2)==='./'){
        $url.=$relativePath.substr($url,2);
    }
    if (!file_exists($url)){
        http_send_status(404);
        echo '<h1>404 Not Found</h1>';
    }else{
        readfile($url);
    }
}