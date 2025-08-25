import {client_function} from "@/services/client_function";
export const GlobalObjVar={
    password:"",
    errorFlag:false,
    NodeEnv:false,
    AndroidEnv:false,
    WindowsWebViewEnv:false,
    ElectronEnv:false,
    UUID:"",
    HOST:"http://localhost",  //全局HOST
    init:function(){

        this.password="";
        this.errorFlag=false;
        /*检查环境*/
        if (typeof window!=="undefined"){
            console.log("Evn:window √");
        }else {
            console.log("Evn:window ×");
        }
        if (typeof process!=="undefined"){
            console.log("Evn:Node.js √");
            this.NodeEnv=true;
        }else {
            console.log("Evn:Node.js ×");
        }
        if (typeof process !== 'undefined' && process.versions && process.versions.nw){
            console.log("Evn:NW.js √");
        }else {
            console.log("Evn:NW.js ×");
        }
        if (typeof chrome!=='undefined' && typeof chrome.webview!== 'undefined' && typeof chrome.webview.hostObjects !== 'undefined'){
            console.log("Evn:Windows Webview2 √");
            this.WindowsWebViewEnv=true;
        }else {
            console.log("Evn:Windows Webview2 ×");
        }

        if (typeof Android !== 'undefined' ){
            this.AndroidEnv=true;
            console.log("Evn:Android √");

        }else {
            console.log("Evn:Android ×");
        }
        if (typeof window.electronAPI !== 'undefined' ){
            this.ElectronEnv=true;
            console.log("Evn:Electron √");

        }else {
            console.log("Evn:Electron ×");
        }
        this.UUID=client_function.getUUID();

    },
    writeHOSTConfig:function(host){
        try {
            client_function.writeConfig("host",host);
        }catch (error){
            console.log("写入HOST配置失败"+error);
        }
    },
    readHOSTConfig:function(){
        let host='';
            try {
                host= client_function.readConfig("host");
                console.log("读取HOST配置成功"+host);
                return host;
            }catch (error){
                console.log("读取HOST配置失败"+error);
            }

        return "http://localhost";
    }
};