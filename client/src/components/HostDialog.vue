<template>
  <div class="host-dialog-overlay">
    <div class="host-dialog">
      <div class="dialog-header">
        <h2>设置服务器地址</h2>
      </div>
      <div class="dialog-body">
        <div class="form-group">
          <label>服务器地址</label>
          <input 
            type="text" 
            v-model="host" 
            ref="hostInput"
            class="form-input"
            @keyup.enter="confirmHost"
            placeholder="例如: http://localhost:8080"
          />
          <div v-if="error" class="error-message">{{ error }}</div>
          <div v-if="loading" class="loading-message">正在验证服务器连接...</div>
        </div>
      </div>
      <div class="dialog-footer">
        <button @click="confirmHost" class="confirm-btn" :disabled="loading">确认</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import axios from 'axios'
import { GlobalObjVar } from '@/global.js'
import AppPortService from "@/services/AppPortService.js";

const host = ref('')
const error = ref('')
const loading = ref(false)
const hostInput = ref(null)

const emit = defineEmits(['host-confirmed'])

const confirmHost = async () => {
  if (!host.value.trim()) {
    error.value = '服务器地址不能为空'
    return
  }
  if (!AppPortService.isValidURL(host.value)) {
    error.value = '请输入有效的URL地址'
    return
  }
  // 确保URL格式正确
  let hostUrl = host.value.trim()
  if (!hostUrl.startsWith('http://') && !hostUrl.startsWith('https://')) {
    hostUrl = 'http://' + hostUrl
  }
  if (hostUrl.endsWith('/')){
    hostUrl = hostUrl.slice(0, -1);
  }
  // 验证服务器连接
  loading.value = true
  error.value = ''

  AppPortService.checkServer(hostUrl).then((data)=>{
    if (data && data.success){
      // 保存HOST配置
      GlobalObjVar.writeHOSTConfig(hostUrl)
      emit('host-confirmed', hostUrl)
    } else {
      throw new Error(data && data.error || "服务器连接失败，请检查网络设置或服务器状态");
    }
  }).catch((err) => {
    console.error('服务器连接错误:', err)
    error.value = '无法连接到服务器，请检查地址是否正确'
  }).finally(() => {
    loading.value = false;
  });


  // try {
  //   const response = await axios.get(`${hostUrl}/heartbeat`, { timeout: 5000 })
  //   if (response.status === 200) {
  //     // 保存HOST配置
  //     GlobalObjVar.writeHOSTConfig(hostUrl)
  //     emit('host-confirmed', hostUrl)
  //   } else {
  //     error.value = '服务器连接失败，请检查地址是否正确'
  //   }
  // } catch (err) {
  //   console.error('服务器连接错误:', err)
  //   error.value = '无法连接到服务器，请检查地址是否正确'
  // } finally {
  //   loading.value = false
  // }
}

onMounted(() => {
  host.value = GlobalObjVar.HOST;
  // 自动聚焦到输入框
  nextTick(() => {
    if (hostInput.value) {
      hostInput.value.focus()
    }
  })
})
</script>

<style scoped>
.host-dialog-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 3000;
}

.host-dialog {
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
}

.dialog-header h2 {
  margin: 0;
  text-align: center;
}

.dialog-body {
  padding: 20px;
  flex: 1;
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

.form-input:disabled {
  background-color: #f5f5f5;
}

.error-message {
  color: #dc3545;
  font-size: 14px;
  margin-top: 5px;
}

.loading-message {
  color: #007bff;
  font-size: 14px;
  margin-top: 5px;
}

.dialog-footer {
  display: flex;
  justify-content: center;
  padding: 20px;
  border-top: 1px solid #eee;
}

.confirm-btn {
  padding: 10px 30px;
  background: #007bff;
  color: white;
  border: 1px solid #007bff;
  border-radius: 4px;
  cursor: pointer;
  font-size: 16px;
}

.confirm-btn:hover {
  opacity: 0.8;
}

.confirm-btn:disabled {
  background: #6c757d;
  border-color: #6c757d;
  cursor: not-allowed;
  opacity: 1;
}
</style>