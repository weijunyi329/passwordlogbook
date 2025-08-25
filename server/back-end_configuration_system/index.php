<?php
include './vendor/autoload.php';
use Phroute\Phroute\RouteCollector;
use Phroute\Phroute\Dispatcher;

include_once "./security/security.php";
include_once "./handler.php";
$collector = new RouteCollector();
$securityHandler=new SecurityHandler();
$securityHandler->checkApiEntrance();

$collector->get('/', function(){
    return  'Hello World!';
});
$collector->get('/index.php', function(){
    return  'Hello World!';
});

$collector->post('/upload', function(){
    handleFileUpload();
    return  null;
});
$collector->get('/getResource', function(){
    handleResourceDownload();
    return  null;
});

$collector->get('/getAll', function(){
    handleGetAll();
    return null;
});
$collector->get('/forget', function(){
    forgetPassword();
    return null;
});
$collector->get('/delete', function(){
    handleDelete();
    return  null;
});
$collector->get('/check', function(){
    return  json_encode(['message'=>'OK','code'=>200,'success'=>true,'data'=>[]]);
});
$collector->post('/resetpwd', function(){
    resetPassword();
    return null;
});
$collector->post('/addNew', function(){
    handleInsert();
    return  null;
});
$collector->post('/update', function(){
    handleUpdate();
    return  null;
});
$dispatcher =  new Dispatcher($collector->getData());
try {
    echo $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $securityHandler->getRealUrl());
    $securityHandler->writeAccessLog(true);
} catch (Exception $e) {
    http_response_code(500);
    echo '<h1> Internal Server Error 500</h1>';
    echo '<h2>url:'.$_SERVER['REQUEST_URI'].'</h2>';
    $securityHandler->writeAccessLog(false);
}


