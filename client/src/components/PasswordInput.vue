<template>
  <div class="password-input-overlay">
    <div class="password-input-dialog">
      <div class="dialog-header">
        <h2>请输入密码</h2>
        <div v-if="showSettingsBtn" @click="settingsBtnClick" class="settings-button">
          <span class="material-symbols--settings-rounded"></span>
        </div>
      </div>
      <div class="dialog-body">
        <div class="form-group">
          <label>密码</label>
          <input 
            type="password" 
            v-model="password" 
            ref="passwordInput"
            class="form-input"
            @keyup.enter="confirmPassword"
            placeholder="请输入密码"
          />
          <div v-if="error" class="error-message">{{ error }}</div>
        </div>
      </div>
      <div class="dialog-footer">
        <button @click="confirmPassword" class="confirm-btn">确认</button>
        <div class="forgot-password" @click="showForgotPasswordDialog">忘记密码</div>
      </div>
    </div>
  </div>
  
  <!-- 第一个确认对话框 -->
  <div v-if="showFirstDialog" class="password-input-overlay">
    <div class="password-input-dialog">
      <div class="dialog-header">
        <h2>确认操作</h2>
      </div>
      <div class="dialog-body">
        <p>忘记密码将会删除所有密码项，是否继续？</p>
      </div>
      <div class="dialog-footer">
        <button @click="confirmFirstDialog" class="confirm-btn">确认</button>
        <button @click="cancelFirstDialog" class="cancel-btn">取消</button>
      </div>
    </div>
  </div>
  
  <!-- 第二个确认对话框 -->
  <div v-if="showSecondDialog" class="password-input-overlay">
    <div class="password-input-dialog">
      <div class="dialog-header">
        <h2>最终确认</h2>
      </div>
      <div class="dialog-body">
        <p>将清除所有密码记录，且不可恢复是否继续？</p>
      </div>
      <div class="dialog-footer">
        <button @click="confirmReset" class="confirm-btn">继续</button>
        <button @click="cancelSecondDialog" class="cancel-btn">取消</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {Icon} from '@iconify/vue'
import { ref, onMounted, nextTick } from 'vue'
import {GlobalObjVar} from "@/global.js";
import axios from "axios";
import AppPortService from "@/services/AppPortService.js";
import {appAlert} from "@/services/client_function.js";

const password = ref('')
const error = ref('')
const passwordInput = ref(null)
const showFirstDialog = ref(false)
const showSecondDialog = ref(false)
const showSettingsBtn = ref(false)

const emit = defineEmits(['password-confirmed','settingsBtnClick'])


const settingsBtnClick = () => {
  emit('settingsBtnClick')
}

const confirmPassword = () => {
  if (!password.value.trim()) {
    error.value = '密码不能为空'
    return
  }
  if (password.value.length< 6) {
    error.value = '密码不能小于6位字符'
    return
  }

  AppPortService.checkServer(GlobalObjVar.HOST).then((data)=>{
    if (data && data.success){
      GlobalObjVar.password=password.value;
      emit('password-confirmed', password.value)
    } else {
      throw new Error(data && data.error || "服务器连接失败，请检查网络设置或服务器状态");
    }
  }).catch((error) => {
    appAlert(error.message || "服务器连接失败，请检查网络设置或服务器状态");
    showSettingsBtn.value = true;
  });


}

// 显示忘记密码的第一个确认对话框
const showForgotPasswordDialog = () => {
  showFirstDialog.value = true
}

// 确认第一个对话框
const confirmFirstDialog = () => {
  showFirstDialog.value = false
  showSecondDialog.value = true
}

// 取消第一个对话框
const cancelFirstDialog = () => {
  showFirstDialog.value = false
}

// 取消第二个对话框
const cancelSecondDialog = () => {
  showSecondDialog.value = false
}

// 确认重置密码
const confirmReset = () => {
  showSecondDialog.value = false
  axios.get(GlobalObjVar.HOST+"/forget").then((data)=>{
    if (data.status===200){
      appAlert("重置密码成功！！！")
      // 重置密码逻辑
      if (typeof nw !== 'undefined') {
        // 在NW.js环境中，重新加载应用来实现重置
        const win = nw.Window.get()
        win.reload()
      } else {
        // 在浏览器环境中，刷新页面
        window.location.reload()
      }
    }else {
      appAlert("重置密码失败！！！")
    }
  });

}

onMounted(() => {
  // 自动聚焦到密码输入框
  nextTick(() => {
    if (passwordInput.value) {
      passwordInput.value.focus()
    }
  })
})
</script>

<style scoped>
.password-input-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.password-input-dialog {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 400px;
  display: flex;
  flex-direction: column;
}

.dialog-header {
  padding: 20px;
  border-bottom: 1px solid #eee;
  position: relative;
}

.dialog-header h2 {
  margin: 0;
  text-align: center;
}

.dialog-body {
  padding: 20px;
  flex: 1;
}

.dialog-body p {
  margin: 0;
  text-align: center;
  font-size: 16px;
  line-height: 1.5;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.form-input {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  box-sizing: border-box;
}

.error-message {
  color: #dc3545;
  font-size: 14px;
  margin-top: 5px;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  padding: 20px;
  border-top: 1px solid #eee;
  position: relative;
}

.confirm-btn {
  padding: 10px 30px;
  background: #007bff;
  color: white;
  border: 1px solid #007bff;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  margin: 0 5px;
}

.cancel-btn {
  padding: 10px 30px;
  background: #6c757d;
  color: white;
  border: 1px solid #6c757d;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
  margin: 0 5px;
}

.confirm-btn:hover {
  opacity: 0.8;
}

.cancel-btn:hover {
  opacity: 0.8;
}

.forgot-password {
  position: absolute;
  right: 20px;
  bottom: 20px;
  color: #007bff;
  cursor: pointer;
  font-size: 12px;
  text-decoration: underline;
}

.settings-button {
  position: absolute;
  top: 0.16rem;
  right: 0.16rem;
  cursor: pointer;
  font-size: 24px;
  color: #6c757d;
}

.settings-button:hover{
  color: #007bff;
}
/*图标-设置*/
.material-symbols--settings-rounded {
  display: inline-block;
  width: 24px;
  height: 24px;
  --svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M10.825 22q-.675 0-1.162-.45t-.588-1.1L8.85 18.8q-.325-.125-.612-.3t-.563-.375l-1.55.65q-.625.275-1.25.05t-.975-.8l-1.175-2.05q-.35-.575-.2-1.225t.675-1.075l1.325-1Q4.5 12.5 4.5 12.337v-.675q0-.162.025-.337l-1.325-1Q2.675 9.9 2.525 9.25t.2-1.225L3.9 5.975q.35-.575.975-.8t1.25.05l1.55.65q.275-.2.575-.375t.6-.3l.225-1.65q.1-.65.588-1.1T10.825 2h2.35q.675 0 1.163.45t.587 1.1l.225 1.65q.325.125.613.3t.562.375l1.55-.65q.625-.275 1.25-.05t.975.8l1.175 2.05q.35.575.2 1.225t-.675 1.075l-1.325 1q.025.175.025.338v.674q0 .163-.05.338l1.325 1q.525.425.675 1.075t-.2 1.225l-1.2 2.05q-.35.575-.975.8t-1.25-.05l-1.5-.65q-.275.2-.575.375t-.6.3l-.225 1.65q-.1.65-.587 1.1t-1.163.45zm1.225-6.5q1.45 0 2.475-1.025T15.55 12t-1.025-2.475T12.05 8.5q-1.475 0-2.488 1.025T8.55 12t1.013 2.475T12.05 15.5'/%3E%3C/svg%3E");
  background-color: currentColor;
  -webkit-mask-image: var(--svg);
  mask-image: var(--svg);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
}
/*阻止用户复制控件*/
.confirm-btn, .cancel-btn, .forgot-password, h2, label {
  -webkit-user-select: none; /* Chrome, Opera, Safari */
  -moz-user-select: none;    /* Firefox */
  -ms-user-select: none;     /* Internet Explorer/Edge */
  user-select: none;         /* Standard */
}
</style>