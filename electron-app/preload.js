const { contextBridge, ipcRenderer } = require('electron/renderer')

contextBridge.exposeInMainWorld('electronAPI', {
    alertReload: (message) => ipcRenderer.send('show-alert-reload', message),
    onRefreshPage: (callback) => ipcRenderer.on('refresh-page', callback),
    finish: (message) => ipcRenderer.send('finish', message),
    alert: (message) => ipcRenderer.send('show-alert', message),
    getConfig: (config) => ipcRenderer.sendSync('getConfig', config),
    setConfig: (config,value) => ipcRenderer.send('setConfig', config,value),
    decrypt: (strToDecrypt,pwd) => ipcRenderer.sendSync('decrypt', strToDecrypt,pwd),
    encrypt: (strToEncrypt,pwd) => ipcRenderer.sendSync('encrypt', strToEncrypt,pwd),
    downloadAndConvertIcon: (parseOriginUrl, parseUrlhost, uploadAddr) =>
        ipcRenderer.invoke('download-and-convert-icon', parseOriginUrl, parseUrlhost, uploadAddr),
    clientInfo: ()=>ipcRenderer.sendSync('clientInfo'),
    getUUID: ()=>ipcRenderer.sendSync('getUUID')
})
