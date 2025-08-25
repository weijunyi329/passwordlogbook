const { app, BrowserWindow,Menu ,protocol,ipcMain,dialog } = require('electron/main');
const  path = require('path');
const ipc_handler = require('./ipc_handler.js');
const UUID = require("./UUID");
const UserAgent = require("./ua");
const DEBUG = require("./utils.js").DEBUG;

ipcMain.on('show-alert-reload',ipc_handler.show_alert_reload );
ipcMain.on('show-alert', ipc_handler.show_alert );
ipcMain.on('finish', ipc_handler.finish);
ipcMain.on('setConfig', ipc_handler.setConfig);
ipcMain.on('getConfig', ipc_handler.getConfig);
ipcMain.on('decrypt', ipc_handler.decrypt);
ipcMain.on('encrypt', ipc_handler.encrypt);
ipcMain.on('clientInfo', ipc_handler.clientInfo);
ipcMain.on('getUUID', (event) => {event.returnValue = UUID; });
ipcMain.handle('download-and-convert-icon', ipc_handler.download_and_convert_icon);


app.whenReady().then(() => {
  createWindow()
  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

const createWindow = () => {

  const mainWin = new BrowserWindow({
    width: 800,
    height: 600,
    title: '我的密码本',
    icon: path.join(__dirname, './favicon.ico'),
    webPreferences: {
      preload: path.join(__dirname, 'preload.js')
    },

  });
  mainWin.onload = () => {
    Object.defineProperty(navigator, 'userAgent', {
      value: UserAgent(),
      writable: true
    });
  };
  mainWin.reload();
  Menu.setApplicationMenu(null);
  mainWin.loadFile('index.html');
  mainWin.webContents.userAgent = UserAgent();
  // DEBUG((()=>{
  //   // 打开主进程控制台
  //   mainWin.webContents.openDevTools();
  // }));

};

