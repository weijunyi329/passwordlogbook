
const isElectronEnvironment = typeof window.electronAPI !== 'undefined';
const isAndroidEnvironment = typeof Android !== 'undefined';
const isWindowsWebViewEnvironment =typeof chrome!=='undefined' && typeof chrome.webview!== 'undefined' && typeof chrome.webview.hostObjects !== 'undefined';
import {VERSION} from "@/version.js";
/**
 * 客户端功能封装，前端调用本地APP的API接口
 * @type {{reformatPkgName: client_function.reformatPkgName, checkEncryptSupport: ((function(): boolean)|*), readConfig: (function(*): string), logout(): void, downloadIconHostUpload: client_function.downloadIconHostUpload, pwdErrorHandle: client_function.pwdErrorHandle, encrypt: (function(*, *): string), writeConfig: client_function.writeConfig, decrypt: (function(*, *): string)}}
 */

export const  client_function= {
    /**
     * 下载图标并上传到服务器
     * @param userHost 用户主机地址，例如：http://127.0.0.1:8080/api/v1/
     * @param parseOriginUrl 解析后的原始URL，例如：https://www.example.com/favicon.ico?v=2
     * @param parsedUrlhost 解析后的原始URL的host，例如：https://www.example.com
     * @param succCallback 成功回调函数
     * @param failCallback 失败回调函数
     */
    downloadIconHostUpload:(userHost,parseOriginUrl,parsedUrlhost,succCallback,failCallback)=>{
        if (isElectronEnvironment){
            window.electronAPI.downloadAndConvertIcon(parseOriginUrl,parsedUrlhost,userHost+"/upload")
                .then(result => {
                    if (result.error) {
                        console.error('处理失败:', result.error);
                        failCallback({title:result.title});
                    } else {
                        console.log('处理成功:', result.data);
                        console.log('网站标题:', result.title);
                        succCallback({data: result.data, title: result.title});
                        // 更新UI
                    }
                })
                .catch(error => {
                    console.error('调用失败:', error);
                });
        }else if (isAndroidEnvironment){
            AndroidCallback.succCallback = succCallback;
            AndroidCallback.failCallback = failCallback;
            Android.downConvertPng(parseOriginUrl,parsedUrlhost,userHost+"/upload");
        }else if(isWindowsWebViewEnvironment){
            chrome.webview.hostObjects.AppInterface.downConvertPng(parseOriginUrl,parsedUrlhost,userHost+"/upload").then(value =>  {
                if (value) {
                    console.log(value);
                    succCallback(value);
                }
            }).catch(reason => {
                failCallback({fileName:"",title:""});
            });
        }else {
             failCallback({fileName:"",title:""});
        }

       //  NodejsFun.Test();
    },
    /**
     * 包名格式化，适配Android回调函数格式化包名，适配WindowsWebView回调函数格式化包名
     * @param userHost 用户API主机地址，例如：http://127.0.0.1:8080
     * @param result 函数回调
     */
    reformatPkgName:(userHost,result)=>{
        if (typeof Android !== 'undefined'){
            AndroidCallback.resultCallback = result;
            Android.reformatPkgName(userHost+"/upload");
        }else {
            console.log("Android is not defined");
            result({fileName:"",title:"",pkgName:""});
        }
    },
    /**
     * 判断客户端是否支持加密解密
     * @returns {boolean}
     */
    checkEncryptSupport:()=>{
        if (isAndroidEnvironment || isWindowsWebViewEnvironment || isElectronEnvironment){
            return true;
        }
        return false;
    },
    encrypt:(str, pwd) => {
        let encryptedStr = '';
        if (isElectronEnvironment) {
            //先解密accounts字段
            encryptedStr= window.electronAPI.encrypt(str, pwd);
        }else if (isAndroidEnvironment){
            //先解密accounts字段
            encryptedStr= Android.encrypt(str, pwd);
        }else if (isWindowsWebViewEnvironment) {
            //先解密accounts字段
            //尝试同步调用
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            encryptedStr=AppInterface.encrypt(str, pwd);
        }
        return encryptedStr;
    },
    decrypt:(str, pwd) => {
        let decryptedStr = '';
        if (isElectronEnvironment) {
            //先解密accounts字段
            decryptedStr=  window.electronAPI.decrypt(str, pwd);
        }else if (isAndroidEnvironment){
            //先解密accounts字段
            decryptedStr= Android.decrypt(str, pwd);
        }else if (isWindowsWebViewEnvironment) {
            //先解密accounts字段
            //尝试同步调用
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            decryptedStr=AppInterface.decrypt(str, pwd);
        }
        return decryptedStr;
    },
    /**
     * 客户端密码错误处理
     */
    pwdErrorHandle:()=>{
        if (isElectronEnvironment){
            console.log("密码错误！");
            window.electronAPI.onRefreshPage(() => {
                console.log('收到刷新指令，正在刷新页面...');
                // 使用 location.reload() 刷新当前页面
                location.reload();
            });
            window.electronAPI.alertReload('密码错误！');

        }else if (typeof window !== 'undefined'){
            appAlert('密码错误！');
            //当前是浏览器环境
            // 刷新页面
            window.location.reload();
        }
    },
    /**
     * 退出登录，关闭当前窗口
     */
    logout() {
        if (typeof nw !== 'undefined'){
            //当前是NW.js环境
            // 获取window对象
            let win = nw.Window.get();
            win.on('close', function() {
                this.hide(); // 阻止关闭窗口
                console.log("We're closing...");
                this.close(true); // 强制关闭窗口
            });

            win.close();
        }else if (isElectronEnvironment) {
            window.electronAPI.finish();
        }else if (isAndroidEnvironment){
            //当前是Android环境
            // 刷新页面
            Android.finish();
        }else if(isWindowsWebViewEnvironment ) {
            // 当前是Chrome WebView环境
            chrome.webview.hostObjects.AppInterface.finish();
        } else if (typeof window !== 'undefined'){
            //当前是浏览器环境
            // 退出登录
            window.location.reload();
        }
    },
    /**
     * 写入配置信息
     * @param configure 配置项名称
     * @param value 配置项值
     */
    writeConfig:(configure,value)=>{
        if (isAndroidEnvironment){
            //Android
            //TODO:
            Android.setConfig(configure, value);
        }else if (isWindowsWebViewEnvironment){
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            //Android
            //TODO:
            AppInterface.setConfig(configure, value);
        }else if (isElectronEnvironment) {
            window.electronAPI.setConfig(configure, value);
        }
        else if(typeof localStorage!== 'undefined') {
            localStorage.setItem(configure, value);
        }
    },
    /**
     * 读取配置信息
     * @param configure 配置项名称
     * @returns {string} 配置项值
     */
    readConfig:(configure)=>{
        let value='';
        if (isAndroidEnvironment){
            //Android
            //TODO:
            value= Android.getConfig(configure);
        }else if (isWindowsWebViewEnvironment){
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            //Android
            //TODO:
            value= AppInterface.getConfig(configure);
        }else if (isElectronEnvironment){
            value=  window.electronAPI.getConfig(configure);
        }else if(typeof localStorage!== 'undefined') {
            value=  localStorage.getItem(configure);
        }
        return value;
    },
    /**
     * 获取客户端信息
     * @returns {*} 客户端信息
     */
    obtainClientInfo(){
        let info='';
        if (isAndroidEnvironment){
            //Android
            //TODO:
            info= Android.clientInfo();
        }else if (isWindowsWebViewEnvironment){
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            //Android
            //TODO:
            info= AppInterface.clientInfo();
        }else if (isElectronEnvironment){
            info=  window.electronAPI.clientInfo();
        }else  {
            info=  '未知客户端，浏览器访问';
        }
        try {
            let infoObj=JSON.parse(info);
            return `客户端: ${infoObj.client}\n\r核心: ${infoObj.core}\n\r客户端版本: ${infoObj.version}\n\rUI版本: ${VERSION}`
        }catch (e) {
            console.log(e);
        }

        return info;
    },
    /**
     * 获取客户端UUID
     * @returns {string} 客户端版本
     */
    getUUID(){
        let uuid='';
        if (isAndroidEnvironment){
            //Android
            uuid= Android.getUUID();
        }else if (isWindowsWebViewEnvironment){
            const AppInterface = chrome.webview.hostObjects.sync.AppInterface;
            uuid= AppInterface.getUUID();
        }else if (isElectronEnvironment){
            uuid=  window.electronAPI.getUUID();
        }else {
            uuid= '';
        }
        return uuid;
    },
}
/**
 * 弹窗提示信息
 * @param message 提示信息
 */
export const appAlert=(message)=>{
    if (isElectronEnvironment) {
        window.electronAPI.alert(message);
    }else {
        window.alert(message);
    }
}