const {dialog,app,BrowserWindow}=require('electron');
const PwdEncryption = require('./PwdEncryption.js');
const fs = require('fs');
const path = require('path');
const os = require('os');
const URLHostParsing = require('./url_host_parsing.js').URLHostParsing;
const readme = require('./readme.js');
const show_alert_reload= async (event, message) => {
    console.log('message:', message);
   await dialog.showMessageBox(BrowserWindow.fromWebContents(event.sender),{
        type: 'info', // 或 'none', 'error', 'question'
        title: '提示',
        message: message,
        buttons: ['确定'] ,// 可以自定义按钮

    }).then(({ response }) => {
        // 通过 webContents.send 方法，向渲染进程发送一个 'refresh-page' 事件
        event.sender.send('refresh-page');
    });
};
const show_alert=  async (event, message) => {
    // dialog.showMessageBox 是异步的，不会阻塞
    await dialog.showMessageBox({
        type: 'info', // 或 'none', 'error', 'question'
        title: '提示',
        message: message,
        buttons: ['确定'] // 可以自定义按钮
    });
};
const decrypt=  (event,str ,password)=>{
    let decrypted = '';
    try {
        decrypted=  PwdEncryption.decrypt(str, password);
        if(decrypted===null)decrypted='';
    }catch (e) {
        console.log(e);
    }
    event.returnValue = decrypted;
};
const encrypt= (event,str ,password)=>{
    event.returnValue = PwdEncryption.encrypt(str, password);
};
const finish= (event)=>{
    app.quit();
};
const getConfig=(event,configure)=> {
    try {
        // 1. 获取App主目录
        const appDir = path.join(
            os.homedir(),
            'AppData',
            'Local',
            'PwdLogbookClient',
            'User'
        );

        // 2. 构建配置文件路径
        const configFile = path.join(appDir, 'configure.json');

        // 3. 检查并创建目录
        if (!fs.existsSync(appDir)) {
            fs.mkdirSync(appDir, { recursive: true });
        }

        // 4. 检查文件是否存在
        if (!fs.existsSync(configFile)) {
            console.log('配置文件不存在');
            event.returnValue = '';
            return;
        }

        // 5. 读取文件内容
        const jsonContent = fs.readFileSync(configFile, 'utf-8');

        try {
            // 6. 解析 JSON
            const config = JSON.parse(jsonContent);
            console.log('jsonContent:', jsonContent);

            // 检查请求的配置项是否存在
            if (config.hasOwnProperty(configure)) {
                console.log('获取配置成功：', config[configure]);
                event.returnValue = config[configure] || '';
                return;
            }
        } catch (e) {
            console.log('json 错误：', e.message);
        }

        event.returnValue = '';
    } catch (err) {
        console.error('获取配置时出错:', err);
    }
    event.returnValue = '';
}
const setConfig=(event, configure, value)=>{
    try {
        // 1. 获取App主目录
        const appDir = path.join(
            os.homedir(),
            'AppData',
            'Local',
            'PwdLogbookClient',
            'User'
        );

        // 2. 构建配置文件路径
        const configFile = path.join(appDir, 'configure.json');

        // 3. 检查并创建目录
        if (!fs.existsSync(appDir)) {
            fs.mkdirSync(appDir, { recursive: true });
        }

        let configData = {};

        // 4. 检查文件是否存在
        if (fs.existsSync(configFile)) {
            try {
                // 读取现有配置文件
                const fileContent = fs.readFileSync(configFile, 'utf-8');
                configData = JSON.parse(fileContent);
            } catch (e) {
                console.error('读取配置文件时出错:', e.message);
                // 如果文件存在但读取失败，继续使用空对象
            }
        } else {
            console.log('配置文件不存在，将创建新文件');
        }

        // 更新配置值
        configData[configure] = value;

        try {
            // 写入配置文件
            fs.writeFileSync(configFile, JSON.stringify(configData, null, 2));
            event.returnValue = value;
        } catch (e) {
            console.error('写入配置文件时出错:', e.message);
            event.returnValue = '';
        }

    } catch (err) {
        console.error('设置配置时出错:', err);
        event.returnValue = '';
    }
}
const download_and_convert_icon=async (event, parseOriginUrl, parseUrlhost, uploadAddr) => {
    try {
        const result = await URLHostParsing.downConvertPng(parseOriginUrl, parseUrlhost, uploadAddr);
        return result;
    } catch (error) {
        return {error: error.message, title: '',};
    }
}
const clientInfo = (event) => {
    event.returnValue = JSON.stringify(readme.clientInfo());
}
module.exports = {
    show_alert_reload,
    show_alert,
    finish,
    decrypt,
    encrypt,
    getConfig,
    setConfig,
    download_and_convert_icon,
    clientInfo,
}
