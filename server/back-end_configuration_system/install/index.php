<?php
/**
 * PasswordBook 安装程序
 * 提供Web界面安装向导，配置数据库连接和系统设置
 */



function getCurrentUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST']; // 域名（含端口，如 :8080）
    $uri = $_SERVER['REQUEST_URI']; // 路径和查询参数（如 /path?id=123）
    return $protocol . $host . $uri;
}
/**
 * 获取当前安装路径,以/结尾
 *
 */
function getCurrentInstallPath() {
    $currentUrl = $_SERVER['REQUEST_URI'];
    $wstartp=strpos($currentUrl, '?');
    if ($wstartp !== false){
        $currentUrl = substr($currentUrl, 0, $wstartp);
    }
    $start = strpos($currentUrl, '/install/index.php');
    if ($start !== false || ($start = strpos($currentUrl, '/index.php'))!== false){
        $currentUrl=  substr($currentUrl, 0, $start);
    }
    if (substr($currentUrl, strlen($currentUrl)-1,1)!=="/"){
        $currentUrl = $currentUrl . "/";
    }
    return $currentUrl;
}

/**
 * 获取当前URL，不包含查询参数
 * @return false|string
 */
function getCurrentUrlWithoutQuery()
{
    $currentUrl=getCurrentUrl();
    $start = strpos($currentUrl, '/install/index.php');
    if ($start !== false){
        return  substr($currentUrl, 0, $start);
    }else if(substr($currentUrl, strlen($currentUrl)-1,1)==="/"){
        return  substr($currentUrl, 0,strlen($currentUrl)-1);
    }else{
        return $currentUrl;
    }
}

/**
 * 获取网站URL
 * @return string
 */
function getWebsiteUrl(): string
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host;
}

/**
 * 生成指定长度的随机字符串
 * @param $length
 * @return string
 */
function generateRandomString($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

session_start();
// 检查是否已经安装
if (file_exists('../config.php') &&  $_SESSION['step']!='result') {
    // 检查是否已经配置完成
    include '../config.php';
    if (isset($mysql_settings) && isset($app_settings)) {
        die('系统已经安装完成，如需重新安装请删除 config.php 文件');
    }
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // error_log("POST DATA: " . print_r($_POST, true));

    if (isset($_POST['step'])) {
        $_SESSION['step'] = $_POST['step'];
    }else{
        $_SESSION['step'] = 'welcome';
        header('Location: index.php');
        exit();
    }



    // 处理数据库配置和系统设置
    if ($_SESSION['step'] == 'finish') {
        // 添加调试信息
        // error_log("开始处理数据库配置和系统设置");

        // 获取数据库配置
        $host = $_POST['host'];
        $port = $_POST['port'];
        $user = $_POST['user'];
        $password = $_POST['password'];
        $database = $_POST['database'];
        $table = $_POST['table'];



        // 获取应用配置
        $userToken = isset($_POST['userToken']) ? true : false;
        $websiteUrl = ((!empty($_POST['websiteUrl'])) ? $_POST['websiteUrl'] : getWebsiteUrl());
        // 去除结尾的/
        if (substr($websiteUrl, -1)==='/')$websiteUrl=substr($websiteUrl, 0, -1);
        //
        $token = $_POST['token'];
        $install_path= $_POST['install_path'];


        // 检查安装路径不为空
        if (!(isset($_POST['install_path']) && $_POST['install_path']!=='')){
            $_SESSION['error'] = '安装路径不能为空';
            header('Location: index.php');
            exit();
        }
        if (substr($install_path, -1)!=='/'){
            $_SESSION['error'] = '安装路径必须以以/结尾';
            header('Location: index.php');
            exit();
        }

        // 检查token是否正确
        if (!(isset($_POST['token']) && $_POST['token']!=='')){
            $_SESSION['error'] = 'token不能为空';
            header('Location: index.php');
            exit();
        }

        // 测试数据库连接
        try {
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $_SESSION['error'] = '数据库连接失败: ' . $e->getMessage();
            header('Location: index.php');
            exit();
        }

        // 重新连接到指定数据库
        try {
            $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (PDOException $e) {
            $_SESSION['error'] = '连接到数据库失败: ' . $e->getMessage();
            header('Location: index.php');
            exit();
        }

        // 检查表是否存在
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $tableExists = $stmt->fetch();

            if ($tableExists) {
                $_SESSION['error'] = '数据表已存在，请删除现有表或更改表名';
                header('Location: index.php');
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = '检查数据表失败: ' . $e->getMessage();
            header('Location: index.php');
            exit();
        }

        // 创建数据表
        try {
            $sql = "
    CREATE TABLE `$table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(128) NOT NULL DEFAULT '',
  `url` VARCHAR(128) NOT NULL DEFAULT '',
  `packageName` VARCHAR(128) NOT NULL DEFAULT '',
  `icon` VARCHAR(128) NOT NULL DEFAULT '',
  `remark` VARCHAR(1000) NOT NULL DEFAULT '',
  `modification` bigint(20) DEFAULT 0,
  `accounts` TEXT, 
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `uk_title`(`title` ASC) USING BTREE 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

            $pdo->exec($sql);
        } catch (PDOException $e) {
            $_SESSION['error'] = '创建数据表失败: ' . $e->getMessage();
            header('Location: index.php');
            exit();
        }

        // 保存配置到config.php
        $template = file_get_contents('./config.php.template');

        // 替换数据库配置
        $template = str_replace('$$$host$$$', $host, $template);
        $template = str_replace('$$$port$$$', $port, $template);
        $template = str_replace('$$$user$$$', $user, $template);
        $template = str_replace('$$$password$$$', $password, $template);
        $template = str_replace('$$$database$$$', $database, $template);
        $template = str_replace('$$$table$$$', $table, $template);

        // 替换应用配置
        $template = str_replace('$$$websiteUrl$$$', $websiteUrl, $template);
        $template = str_replace('$$$token$$$', password_hash($token, PASSWORD_DEFAULT), $template);
        // 随机生成管理员入口
        $template = str_replace('$$$install_path$$$', $install_path, $template);


        // 替换htaccess模板变量
        $htaccessTemplate = file_get_contents('./.htaccess.template');
        $htaccessTemplate= str_replace('$$$install_path$$$', $install_path,  $htaccessTemplate);

        // 写入配置文件
        $wres1=file_put_contents('../config.php', $template);
        $wres2=file_put_contents('../.htaccess',  $htaccessTemplate);
        $wres3=file_put_contents('../index.php', file_get_contents('./index.php.template'));
        $wres4=file_put_contents('../security/safe.settings.php', file_get_contents('./safe.settings.php.template'));

        if (!$wres1 || !$wres2 || !$wres3 || !$wres4 || $template==='') {
            $_SESSION['error'] = '写入配置文件失败';
            header('Location: index.php');
            exit();
        }

        //删除文件
        unlink('./index.php.template');
        unlink('./.htaccess.template');
        unlink('./config.php.template');

        // 安装完成，跳转到完成页面
        $_SESSION['step'] = 'result';
        $_SESSION['install_path'] = $install_path;
        header('Location: index.php');
        exit();
    }
}

// 获取当前步骤
$step = isset($_SESSION['step']) ? $_SESSION['step'] : 'welcome';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$install_path = isset($_SESSION['install_path']) ? $_SESSION['install_path'] : '';
if ($_SESSION['step']=='finish' && $_SESSION['error'])
    unset($_SESSION['step']);
unset($_SESSION['error']); // 清除错误信息，防止重复显示
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PasswordBook 安装程序</title>
    <!-- 引入 Prism.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-core.min.js"></script>
    <style>
        body {
            font-family: "Microsoft YaHei", sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 850px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="password"], input[type="number"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
            width: auto;
        }
        .btn {
            background-color: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background-color: #005a87;
        }
        .btn-secondary {
            background-color: #ddd;
            color: #333;
        }
        .btn-secondary:hover {
            background-color: #ccc;
        }
        .step {
            display: none;
        }
        .active {
            display: block;
        }
        .license {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            height: 300px;
            overflow-y: scroll;
            margin-bottom: 20px;
        }
        .form-row {
            display: flex;
            gap: 15px;
        }
        .form-col {
            flex: 1;
        }
        .text-center {
            text-align: center;
        }
        .error {
            color: red;
            background-color: #ffe6e6;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            background-color: #e6ffe6;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>PasswordBook 安装程序</h1>

    <!-- 步骤指示器 -->
    <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
        <div style="text-align: center; flex: 1; <?=($step=='welcome'?'font-weight: bold; color: #007cba':'')?>">1. 欢迎</div>
        <div style="text-align: center; flex: 1; <?=($step=='settings'?'font-weight: bold; color: #007cba':'')?>">2. 配置</div>
        <div style="text-align: center; flex: 1; <?=($step=='finish'?'font-weight: bold; color: #007cba':'')?>">3. 完成</div>
    </div>

    <?php if ($error!==''): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- 步骤1: 欢迎页面 -->
    <div class="step <?=($step=='welcome'?'active':'')?>" id="step-welcome">
        <h2>欢迎使用 PasswordBook</h2>
        <p>感谢您选择 PasswordBook 密码管理系统。本安装向导将帮助您完成系统的安装和配置。</p>

        <div class="license">
            <h3>服务协议</h3>
            <p><strong>使用许可</strong></p>
            <p>PasswordBook 是一个开源的密码管理系统，您可以自由使用、修改和分发本软件。</p>

            <p><strong>免责声明</strong></p>
            <p>1. 本软件按"现状"提供，不提供任何形式的担保。</p>
            <p>2. 使用本软件产生的任何风险由使用者自行承担。</p>
            <p>3. 作者不对因使用本软件而导致的任何损失负责。</p>

            <p><strong>数据安全</strong></p>
            <p>1. 用户应妥善保管自己的数据和密码。</p>
            <p>2. 建议定期备份重要数据。</p>
            <p>3. 作者不会收集或存储用户的任何数据。</p>

            <p><strong>使用限制</strong></p>
            <p>1. 禁止将本软件用于任何非法用途。</p>
            <p>2. 禁止在未经授权的情况下修改软件的核心功能。</p>
            <p>3. 禁止去除软件的版权声明和许可信息。</p>

            <p><strong>其他条款</strong></p>
            <p>1. 本协议的解释权归作者所有。</p>
            <p>2. 如有更新，恕不另行通知，请定期查看最新版本。</p>
        </div>

        <form method="post">
            <input type="hidden" name="step" value="settings">
            <div class="form-group">
                <div class="checkbox-group">
                    <input type="checkbox" id="agree" name="agree" required>
                    <label for="agree">我已阅读并同意上述服务协议</label>
                </div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn">开始安装</button>
            </div>
        </form>
    </div>

    <!-- 步骤2: 配置页面 -->
    <div class="step <?=($step=='settings'?'active':'')?>" id="step-settings">
        <h2>系统配置</h2>
        <form method="post">
            <input type="hidden" name="step" value="finish">
            <h3>数据库配置</h3>
            <div class="form-row">
                <div class="form-group form-col">
                    <label for="host">数据库主机:</label>
                    <input type="text" id="host" name="host" value="localhost" required>
                </div>
                <div class="form-group form-col">
                    <label for="port">端口:</label>
                    <input type="number" id="port" name="port" value="3306" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-col">
                    <label for="user">用户名:</label>
                    <input type="text" id="user" name="user" value="root" required>
                </div>
                <div class="form-group form-col">
                    <label for="password">密码:</label>
                    <input type="password" id="password" name="password" value="root">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group form-col">
                    <label for="database">数据库名:</label>
                    <input type="text" id="database" name="database" value="app" required>
                </div>
                <div class="form-group form-col">
                    <label for="table">表名:</label>
                    <div style="display: flex;">
                        <input type="text" id="table" name="table" value="mycbtable" required style="flex: 1;">
                        <button type="button" class="btn btn-secondary" onclick="generateRandomValue('table')" style="margin-left: 10px; white-space: nowrap;">随机</button>
                    </div>
                </div>
            </div>

            <h3>系统设置</h3>
            <div class="form-group">
                <div class="checkbox-group">
                    <label for="websiteUrl">网站地址</label>
                    <input type="text" id="websiteUrl"  value="<?= getWebsiteUrl()  ?>" name="websiteUrl">
                </div>
            </div>
            <div class="form-group">
                <div class="checkbox-group">
                    <label for="install_path">确认安装位置</label>
                    <input type="text" id="install_path"  value="<?= getCurrentInstallPath()  ?>" name="install_path">
                </div>
            </div>


            <div class="form-group">
                <label for="token">访问口令:</label>
                <div style="display: flex;">
                    <input type="text" id="token" name="token" value="weijunyi" style="flex: 1;">
                    <button type="button" class="btn btn-secondary" onclick="generateRandomValue('token')" style="margin-left: 10px; white-space: nowrap;">随机</button>
                </div>
            </div>


            <div class="text-center">
                <button type="submit" class="btn">完成安装</button>
            </div>
        </form>
    </div>

    <!-- 步骤3: 完成页面 -->
    <div class="step <?=($step=='result' && !$error)?'active':''?>" id="step-finish">
        <h2>安装完成</h2>
        <p>恭喜您，PasswordBook 已成功安装！</p>
        <h3>可以使用API:<?= $install_path  ?></h3>
        <h3>管理员入口：<?= ($install_path.'admin') ?></h3>
        <h3>需要设置伪静态才可以正常使用：</h3>
        <p>参考以下的设置：</p>
        <h4>Apache:</h4>
        <!-- 使用 -->
        <div class="code-block">
              <pre><code class="language-javascript">
                &lt;IfModule mod_rewrite.c&gt;
                    RewriteEngine On
                    RewriteBase <?= $install_path ?>
                    RewriteCond %{REQUEST_FILENAME} !-f
                    RewriteCond %{REQUEST_FILENAME} !-d
                    RewriteRule ^(.*)$ index.php?$1 [L,QSA]
                &lt;/IfModule&gt;
              </code></pre>
        </div>
        <h4>Nginx:</h4>
        <!-- 使用 -->
        <div class="code-block">
              <pre><code class="language-javascript">
            location <?= $install_path ?> {
             try_files $uri $uri/ <?= $install_path ?>index.php?$query_string;
            }
              </code></pre>
        </div>
        <h3>为了安全起见，请删除 install 目录。</h3>
        <div class="text-center">
            <a href="../index.php" class="btn">访问系统</a>
        </div>
    </div>

    <!-- 步骤-1: 安装错误页面 -->
    <div class="step <?=($error)?'active':''?>" id="step-error">
        <h2>安装错误！！！！</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="text-center">
            <button class="btn" onclick="location.reload()">重新尝试</button>
        </div>
    </div>
</div>

<script>
    // 根据复选框状态启用/禁用相关输入框
    document.addEventListener('DOMContentLoaded', function() {
        // 口令验证相关
        const userTokenCheckbox = document.getElementById('userToken');
        const tokenInput = document.getElementById('token');

        userTokenCheckbox.addEventListener('change', function() {
            tokenInput.disabled = !this.checked;
        });

        // 初始化状态
        tokenInput.disabled = !userTokenCheckbox.checked;

        // URL路径相关
        const useUrlPartCheckbox = document.getElementById('useUrlPart');
        const urlPartInput = document.getElementById('urlPart');

        useUrlPartCheckbox.addEventListener('change', function() {
            urlPartInput.disabled = !this.checked;
        });

        // 初始化状态
        urlPartInput.disabled = !useUrlPartCheckbox.checked;
    });

    // 生成随机值并填充到指定输入框
    function generateRandomValue(fieldId) {
        let randomValue = '';

        // 根据字段类型生成合适的随机值
        switch(fieldId) {
            case 'table':
                // 表名: 8位随机字母数字组合
                randomValue = 'table_' + generateRandomString(8);
                break;
            case 'token':
                // 访问口令: 16位随机字符串
                randomValue = generateRandomString(16);
                break;
            case 'urlPart':
                // URL路径标识: 12位随机字符串
                randomValue = generateRandomString(12);
                break;
            default:
                randomValue = generateRandomString(8);
        }

        // 填充到对应输入框
        document.getElementById(fieldId).value = randomValue;
    }

    // 生成指定长度的随机字符串
    function generateRandomString(length) {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        let result = '';
        for (let i = 0; i < length; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }
</script>
</body>
</html>