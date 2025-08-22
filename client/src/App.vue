<script setup>
import { ref } from 'vue'
import PasswordManager from './components/PasswordManager.vue'
import PasswordInput from './components/PasswordInput.vue'
import {GlobalObjVar} from "@/global.js";
import HostDialog from "@/components/HostDialog.vue";
import {client_function} from "@/services/client_function.js";

// 初始化全局变量对象，并读取配置文件中的Host信息
GlobalObjVar.init();

// Host配置对话框
const isShowHostDialog=ref(false);
// 密码输入对话框
const isPasswordDialog = ref(false);

let host=GlobalObjVar.readHOSTConfig();
if ( host!==null && host!==""){
    GlobalObjVar.HOST=host;
    isShowHostDialog.value = false;
    isPasswordDialog.value = true;
    //不弹出Host配置对话框
}else {
    isShowHostDialog.value=true;
}



const handleHostConfirmed = (host) => {
  // 保存Host
  GlobalObjVar.HOST=host;
  //
  isShowHostDialog.value=false;
  isPasswordDialog.value=true;
}
const handlePasswordConfirmed = (password) => {
  // 在实际应用中，这里应该验证密码是否正确
  // 目前我们假设任何非空密码都是有效的
  if (password) {
    isPasswordDialog.value = false;
  }
}
const settingsBtnClick = () => {
  isPasswordDialog.value = false;
  isShowHostDialog.value=true;
}
const handleLogout = () => {
 client_function.logout()
}
</script>

<template>
  <div class="app">
    <HostDialog 
      v-if="isShowHostDialog"
      @host-confirmed="handleHostConfirmed"
    />
    <PasswordInput 
      v-else-if="isPasswordDialog"
      @password-confirmed="handlePasswordConfirmed"
      @settingsBtnClick="settingsBtnClick"
    />
    <PasswordManager v-else-if="!isShowHostDialog && !isPasswordDialog" @logout="handleLogout" />
  </div>
</template>

<style scoped>
.app {
  flex: 1;
  padding: 0.1rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border-radius: 0.1rem;
  border-width: 1px;
  border-color: #666666;
}
</style>