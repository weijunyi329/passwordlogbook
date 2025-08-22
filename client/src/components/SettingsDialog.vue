<template>
  <div v-if="visible" class="dialog-overlay">
    <div class="dialog">
      <div class="dialog-header">
        <h2>设置</h2>
        <button class="close-btn" @click="close">×</button>
      </div>
      <div class="dialog-body">
        <div class="settings-section">
          <h3>API设置</h3>
          <div class="setting-item">
            <label class="setting-label">API主机地址</label>
            <input type="text" v-model="settings.host" class="setting-input" placeholder="例如: http://localhost" />
          </div>
        </div>
        
        <div class="settings-section">
          <h3>密码管理</h3>
          <div class="setting-item">
            <label class="setting-label">重置密码</label>
            <button @click="showResetPasswordDialog = true" class="reset-password-btn">重置密码</button>
          </div>
        </div>
        
        <div class="settings-section">
          <h3>客户端信息</h3>
          <div class="setting-item">
            <label class="setting-label">查看客户端信息</label>
            <button @click="showClientInfo" class="client-info-btn">客户端信息</button>
          </div>
        </div>
      </div>
      <div class="dialog-footer">
        <button @click="close" class="cancel-btn">取消</button>
        <button @click="confirm" class="confirm-btn">保存</button>
      </div>
    </div>
  </div>
  
  <!-- 重置密码对话框 -->
  <ResetPasswordDialog 
    :visible="showResetPasswordDialog"
    :host="settings.host"
    @close="showResetPasswordDialog = false"
    @success="onResetPasswordSuccess"
  />
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import {GlobalObjVar} from "@/global.js";
import ResetPasswordDialog from './ResetPasswordDialog.vue'
import AppPortService from "@/services/AppPortService.js";
import {appAlert, client_function} from "@/services/client_function.js";

const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['close', 'confirm'])

// 默认设置
const settings = reactive({
  host: 'http://localhost'
})

// 重置密码对话框显示状态
const showResetPasswordDialog = ref(false)

// 监听设置变化，如果需要可以从localStorage加载
watch(() => props.visible, (newVal) => {
  if (newVal) {
    settings.host=GlobalObjVar.HOST ;
  }
})

const close = () => {
  emit('close')
}

const confirm = () => {
  if (!settings.host || !settings.host.trim().length === 0 ){
    appAlert('请输入API主机地址');
    return
  }
  if (!AppPortService.isValidURL(settings.host.trim())) {
    appAlert('请输入有效的API主机地址');
    return
  }
  // 确保URL格式正确
  let hostUrl = settings.host.trim()
  if (!hostUrl.startsWith('http://') && !hostUrl.startsWith('https://')) {
    hostUrl = 'http://' + hostUrl
  }
  if (hostUrl.endsWith('/')){
    hostUrl = hostUrl.slice(0, -1);
    settings.host = hostUrl;
  }
  AppPortService.checkServer(hostUrl).then(res=>{
    if (res && res.success){
      GlobalObjVar.HOST=settings.host;
      //保存设置
      GlobalObjVar.writeHOSTConfig(settings.host);
      emit('confirm', { host: settings.host })
    }else {
      throw new Error(res && res.error || "服务器连接失败，请检查网络设置或服务器状态");
    }
  }).catch(err=>{
    appAlert('API主机地址无效'+err.message);
  })
}

const onResetPasswordSuccess = () => {
  showResetPasswordDialog.value = false
  appAlert('密码重置成功');
  if (typeof nw !== 'undefined'){
    //当前是NW.js环境
    // 获取window对象
    let win = nw.Window.get();
    // 重新加载页面
    win.reload();
  }else if (typeof window !== 'undefined'){
    //当前是浏览器环境
    // 刷新页面
    window.location.reload();
  }
}

// 显示客户端信息
const showClientInfo = () => {
  const clientInfo = client_function.obtainClientInfo();
  appAlert(clientInfo);
}
</script>

<style scoped>
.dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.dialog {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #eee;
}

.dialog-header h2 {
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: #f5f5f5;
  border-radius: 50%;
}

.dialog-body {
  padding: 20px;
  flex: 1;
}

.settings-section {
  margin-bottom: 25px;
}

.settings-section h3 {
  margin-top: 0;
  border-bottom: 1px solid #eee;
  padding-bottom: 8px;
}

.setting-item {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  padding: 10px;
  background: #f9f9f9;
  border-radius: 4px;
}

.setting-label {
  flex: 1;
  font-weight: bold;
  margin: 0;
}

.setting-select, .setting-input {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.setting-checkbox {
  width: 18px;
  height: 18px;
}

.reset-password-btn, .client-info-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: #dc3545;
  color: white;
  border-radius: 4px;
  cursor: pointer;
}

.reset-password-btn:hover, .client-info-btn:hover {
  background: #c82333;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 20px;
  border-top: 1px solid #eee;
}

.dialog-footer button {
  padding: 10px 20px;
  border: 1px solid #ddd;
  border-radius: 4px;
  cursor: pointer;
}

.cancel-btn {
  background: white;
}

.confirm-btn {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.cancel-btn:hover, .confirm-btn:hover {
  opacity: 0.8;
}
</style>