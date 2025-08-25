<?php
// 判断当前运行目录
global $relativePath;
if (!isset($relativePath)){
    $relativePath = './';
    if (substr(getcwd(), -8)=='security'){
        $relativePath='../';
    }
}

include_once $relativePath.'Utils.php';
include_once $relativePath.'common.php';
class SecurityHandler {
    private $safeSettings;
    private $realPath;

    private $https;
    public function __construct() {
        global $relativePath;
        $this->safeSettings=require $relativePath.'security/safe.settings.php';
        $this->realPath=getURLRelativeInstall(true);
        $this->https=(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    }

    public function getRealUrl() {
        return $this->realPath;
    }



    /**
     * 检查API入口
     * @return void
     */
    public function checkApiEntrance() {
        global $relativePath;
        include_once $relativePath.'security/access_check/BlackIp.php';
        include_once $relativePath.'security/access_check/WhiteIp.php';
        include_once $relativePath.'security/access_check/Uuid.php';
        include_once $relativePath.'security/access_check/UserAgent.php';
        if (file_exists('./SERVER_STOP')){
            $this->writeAccessLog(false);
            sendFinalResult(500, 'SERVER_STOPPED');
            exit();
        }
        if ($this->safeSettings['restrictIp'] && checkBlackIp(getVisitorIP()??'')){
            $this->writeAccessLog(false);
            sendFinalResult(403, 'unable to access');
            exit();
        }
        if ($this->safeSettings['restrictIp'] && !checkWhiteIp(getVisitorIP()??'')){
           $this->writeAccessLog(false);
            sendFinalResult(403, 'unable to access');
            exit();
        }
        if ($this->safeSettings['restrictUa'] && !checkUa(getUserAgent()??'')){
            $this->writeAccessLog(false);
            sendFinalResult(403, 'unable to access');
            exit();
        }
        if ($this->safeSettings['restrictUuid'] && !checkUuid(getVisitorUUID()??'')){
            $this->writeAccessLog(false);
            sendFinalResult(403, 'unable to access');
            exit();
        }
        if ($this->safeSettings['onlyHttpsAccess'] && !$this->https){
            $this->writeAccessLog(false);
            sendFinalResult(403, 'unable to access');
            exit();
        }
        allowCrossDomain();

    }
    public function writeAccessLog($accept) {
        global $relativePath;
        // 如果不需要记录IP则返回
        if (!$this->safeSettings['logIp'])return;
        $ip=getVisitorIP();
       $ua=getUserAgent();
       $uuid=getVisitorUUID();
       $time=date("Y-m-d H:i:s");
       $currentMin=time()/600;
       $log=array(
           'time'=>$time,
           'access'=>$this->realPath,
           'accept'=>$accept,
           'ip'=>$ip,
           'ua'=>$ua,
           'uuid'=>$uuid
       );
        // 打开文件以追加内容
        $fileHandle = fopen($relativePath.'security/log/access_log.txt', 'a');
        $logStr= json_encode($log);
        $logStr=str_replace("\n", '\\n', $logStr);
        $logStr=str_replace("\r", '\\r', $logStr);
        $logStr.=PHP_EOL;
        if ($fileHandle) {
            fwrite($fileHandle, $logStr);
            fclose($fileHandle);
        }
    }
}
