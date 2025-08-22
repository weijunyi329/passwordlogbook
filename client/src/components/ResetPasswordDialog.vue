<!--<template>-->
<!--  <div v-if="visible" class="dialog-overlay">-->
<!--    <div class="dialog">-->
<!--      <div class="dialog-header">-->
<!--        <h2>重置密码</h2>-->
<!--        <button class="close-btn" @click="close">×</button>-->
<!--      </div>-->
<!--      <div class="dialog-body">-->
<!--        <div class="form-group">-->
<!--          <label>请输入新密码</label>-->
<!--          <input -->
<!--            type="password" -->
<!--            v-model="newPassword" -->
<!--            class="form-input" -->
<!--            placeholder="请输入新密码"-->
<!--            ref="passwordInput"-->
<!--          />-->
<!--        </div>-->
<!--        <div class="form-group">-->
<!--          <label>请再次输入新密码</label>-->
<!--          <input -->
<!--            type="password" -->
<!--            v-model="confirmPassword" -->
<!--            class="form-input" -->
<!--            placeholder="请再次输入新密码"-->
<!--            @keyup.enter="confirm"-->
<!--          />-->
<!--        </div>-->
<!--        <div v-if="errorMessage" class="error-message">-->
<!--          {{ errorMessage }}-->
<!--        </div>-->
<!--        <div v-if="loading" class="loading-message">-->
<!--          正在等待....-->
<!--        </div>-->
<!--      </div>-->
<!--      <div class="dialog-footer">-->
<!--        <button @click="close" class="cancel-btn" :disabled="loading">取消</button>-->
<!--        <button @click="confirm" class="confirm-btn" :disabled="loading">-->
<!--          {{ loading ? '处理中...' : '确认' }}-->
<!--        </button>-->
<!--      </div>-->
<!--    </div>-->
<!--  </div>-->
<!--</template>-->

<!--<script setup>-->
<!--import { ref, watch, nextTick } from 'vue'-->

<!--// 定义组件属性-->
<!--const props = defineProps({-->
<!--  visible: {-->
<!--    type: Boolean,-->
<!--    default: false-->
<!--  },-->
<!--  host: {-->
<!--    type: String,-->
<!--    default: 'http://localhost'-->
<!--  }-->
<!--})-->

<!--// 定义组件事件-->
<!--const emit = defineEmits(['close', 'success'])-->

<!--// 表单相关响应式数据-->
<!--const newPassword = ref('')-->
<!--const confirmPassword = ref('')-->
<!--const errorMessage = ref('')-->
<!--const loading = ref(false)-->
<!--const passwordInput = ref(null)-->

<!--// 监听对话框显示状态，自动聚焦到密码输入框-->
<!--watch(() => props.visible, (newVal) => {-->
<!--  if (newVal) {-->
<!--    // 重置表单状态-->
<!--    errorMessage.value = ''-->
<!--    newPassword.value = ''-->
<!--    confirmPassword.value = ''-->
<!--    loading.value = false-->
<!--    -->
<!--    // 自动聚焦到第一个输入框-->
<!--    nextTick(() => {-->
<!--      if (passwordInput.value) {-->
<!--        passwordInput.value.focus()-->
<!--      }-->
<!--    })-->
<!--  }-->
<!--})-->

<!--// 关闭对话框-->
<!--const close = () => {-->
<!--  emit('close')-->
<!--}-->

<!--// 确认重置密码-->
<!--const confirm = async () => {-->
<!--  // 重置错误信息-->
<!--  errorMessage.value = ''-->
<!--  -->
<!--  // 验证密码输入-->
<!--  if (!newPassword.value) {-->
<!--    errorMessage.value = '请输入新密码'-->
<!--    return-->
<!--  }-->
<!--  -->
<!--  if (newPassword.value !== confirmPassword.value) {-->
<!--    errorMessage.value = '两次输入的密码不一致'-->
<!--    return-->
<!--  }-->
<!--  -->
<!--  // 开始重置密码流程-->
<!--  loading.value = true-->
<!--  -->
<!--  try {-->
<!--    // 获取所有密码项-->
<!--    const response = await fetch(`${props.host}/getAll`, {-->
<!--      method: 'GET',-->
<!--      headers: {-->
<!--        'Content-Type': 'application/json'-->
<!--      }-->
<!--    })-->
<!--    -->
<!--    if (!response.ok) {-->
<!--      throw new Error(`获取数据失败: ${response.status}`)-->
<!--    }-->
<!--    -->
<!--    const items = await response.json()-->
<!--    -->
<!--    // 准备重置数据-->
<!--    const resetData = items.map(item => {-->
<!--      // 加密accounts字段-->
<!--      let encryptedAccounts = ''-->
<!--      if (item.accounts && item.accounts.length > 0) {-->
<!--        try {-->
<!--          // 这里需要加密accounts字段，但由于加密逻辑在Node.js环境中，我们发送空字符串-->
<!--          encryptedAccounts = ''-->
<!--        } catch (e) {-->
<!--          console.error('加密失败:', e)-->
<!--        }-->
<!--      }-->
<!--      -->
<!--      return {-->
<!--        id: item.id,-->
<!--        accounts: encryptedAccounts-->
<!--      }-->
<!--    })-->
<!--    -->
<!--    // 发送到重置密码接口-->
<!--    const resetResponse = await fetch(`${props.host}/resetpwd`, {-->
<!--      method: 'POST',-->
<!--      headers: {-->
<!--        'Content-Type': 'application/json'-->
<!--      },-->
<!--      body: JSON.stringify(resetData)-->
<!--    })-->
<!--    -->
<!--    if (!resetResponse.ok) {-->
<!--      throw new Error(`重置密码失败: ${resetResponse.status}`)-->
<!--    }-->
<!--    -->
<!--    const result = await resetResponse.json()-->
<!--    if (result.success) {-->
<!--      emit('success')-->
<!--    } else {-->
<!--      throw new Error(result.error || '重置密码失败')-->
<!--    }-->
<!--  } catch (error) {-->
<!--    console.error('重置密码出错:', error)-->
<!--    errorMessage.value = error.message || '重置密码失败，请稍后重试'-->
<!--  } finally {-->
<!--    loading.value = false-->
<!--  }-->
<!--}-->
<!--</script>-->

<!--<style scoped>-->
<!--.dialog-overlay {-->
<!--  position: fixed;-->
<!--  top: 0;-->
<!--  left: 0;-->
<!--  right: 0;-->
<!--  bottom: 0;-->
<!--  background: rgba(0,0,0,0.5);-->
<!--  display: flex;-->
<!--  align-items: center;-->
<!--  justify-content: center;-->
<!--  z-index: 1000;-->
<!--}-->

<!--.dialog {-->
<!--  background: white;-->
<!--  border-radius: 8px;-->
<!--  width: 90%;-->
<!--  max-width: 500px;-->
<!--  max-height: 90vh;-->
<!--  overflow-y: auto;-->
<!--  display: flex;-->
<!--  flex-direction: column;-->
<!--}-->

<!--.dialog-header {-->
<!--  display: flex;-->
<!--  justify-content: space-between;-->
<!--  align-items: center;-->
<!--  padding: 20px;-->
<!--  border-bottom: 1px solid #eee;-->
<!--}-->

<!--.dialog-header h2 {-->
<!--  margin: 0;-->
<!--}-->

<!--.close-btn {-->
<!--  background: none;-->
<!--  border: none;-->
<!--  font-size: 24px;-->
<!--  cursor: pointer;-->
<!--  padding: 0;-->
<!--  width: 30px;-->
<!--  height: 30px;-->
<!--  display: flex;-->
<!--  align-items: center;-->
<!--  justify-content: center;-->
<!--}-->

<!--.close-btn:hover {-->
<!--  background: #f5f5f5;-->
<!--  border-radius: 50%;-->
<!--}-->

<!--.dialog-body {-->
<!--  padding: 20px;-->
<!--  flex: 1;-->
<!--}-->

<!--.form-group {-->
<!--  margin-bottom: 15px;-->
<!--}-->

<!--.form-group label {-->
<!--  display: block;-->
<!--  margin-bottom: 5px;-->
<!--  font-weight: bold;-->
<!--}-->

<!--.form-input {-->
<!--  width: 100%;-->
<!--  padding: 10px;-->
<!--  border: 1px solid #ddd;-->
<!--  border-radius: 4px;-->
<!--  font-size: 16px;-->
<!--  box-sizing: border-box;-->
<!--}-->

<!--.error-message {-->
<!--  color: #dc3545;-->
<!--  margin-top: 10px;-->
<!--  padding: 10px;-->
<!--  background-color: #f8d7da;-->
<!--  border: 1px solid #f5c6cb;-->
<!--  border-radius: 4px;-->
<!--}-->

<!--.loading-message {-->
<!--  text-align: center;-->
<!--  margin-top: 15px;-->
<!--  padding: 15px;-->
<!--  background-color: #d1ecf1;-->
<!--  border: 1px solid #bee5eb;-->
<!--  border-radius: 4px;-->
<!--  color: #0c5460;-->
<!--}-->

<!--.dialog-footer {-->
<!--  display: flex;-->
<!--  justify-content: flex-end;-->
<!--  gap: 10px;-->
<!--  padding: 20px;-->
<!--  border-top: 1px solid #eee;-->
<!--}-->

<!--.dialog-footer button {-->
<!--  padding: 10px 20px;-->
<!--  border: 1px solid #ddd;-->
<!--  border-radius: 4px;-->
<!--  cursor: pointer;-->
<!--}-->

<!--.cancel-btn {-->
<!--  background: white;-->
<!--}-->

<!--.confirm-btn {-->
<!--  background: #007bff;-->
<!--  color: white;-->
<!--  border-color: #007bff;-->
<!--}-->

<!--.cancel-btn:hover:not(:disabled), .confirm-btn:hover:not(:disabled) {-->
<!--  opacity: 0.8;-->
<!--}-->

<!--.cancel-btn:disabled, .confirm-btn:disabled {-->
<!--  cursor: not-allowed;-->
<!--  opacity: 0.6;-->
<!--}-->
<!--</style>-->
<template>
  <div v-if="visible" class="dialog-overlay">
    <div class="dialog">
      <div class="dialog-header">
        <h2>重置密码</h2>
        <button class="close-btn" @click="close">×</button>
      </div>
      <div class="dialog-body">
        <div class="form-group">
          <label>请输入新密码</label>
          <input 
            type="password" 
            v-model="newPassword" 
            class="form-input" 
            placeholder="请输入新密码"
            ref="passwordInput"
          />
        </div>
        <div class="form-group">
          <label>请再次输入新密码</label>
          <input 
            type="password" 
            v-model="confirmPassword" 
            class="form-input" 
            placeholder="请再次输入新密码"
            @keyup.enter="confirm"
          />
        </div>
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>
        <div v-if="loading" class="loading-message">
          正在等待....
        </div>
      </div>
      <div class="dialog-footer">
        <button @click="close" class="cancel-btn" :disabled="loading">取消</button>
        <button @click="confirm" class="confirm-btn" :disabled="loading">
          {{ loading ? '处理中...' : '确认' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import {GlobalObjVar} from "@/global.js";
import AppPortService from "@/services/AppPortService.js";

// 定义组件属性
const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  host: {
    type: String,
    default: 'http://localhost'
  }
})

// 定义组件事件
const emit = defineEmits(['close', 'success'])

// 表单相关响应式数据
const newPassword = ref('')
const confirmPassword = ref('')
const errorMessage = ref('')
const loading = ref(false)
const passwordInput = ref(null)

// 监听对话框显示状态，自动聚焦到密码输入框
watch(() => props.visible, (newVal) => {
  if (newVal) {
    // 重置表单状态
    errorMessage.value = ''
    newPassword.value = ''
    confirmPassword.value = ''
    loading.value = false
    
    // 自动聚焦到第一个输入框
    nextTick(() => {
      if (passwordInput.value) {
        passwordInput.value.focus()
      }
    })
  }
})

// 关闭对话框
const close = () => {
  emit('close')
}

// 确认重置密码
const confirm = async () => {
  // 防止重复点击
  if (loading.value ){
    return;
  }
  // 重置错误信息
  errorMessage.value = ''
  
  // 验证密码输入
  if (!newPassword.value) {
    errorMessage.value = '请输入新密码'
    return
  }
  if (confirmPassword.value.length<6){
    errorMessage.value = '密码长度不能小于6位'
    return
  }
  if (newPassword.value !== confirmPassword.value) {
    errorMessage.value = '两次输入的密码不一致'
    return
  }

  // 开始重置密码流程
  loading.value = true
    AppPortService.resetPassword(props.host,newPassword.value).then(function (res) {
      GlobalObjVar.password=newPassword.value;
      emit('success')
    }).catch(function (error) {
      console.error('重置密码出错:', error)
      errorMessage.value = error.message || '重置密码失败，请稍后重试'
    }).finally(function () {
      loading.value = false
    });

    
  //   const result = await resetResponse.json()
  //   if (result.success) {
  //
  //   } else {
  //     throw new Error(result.error || '重置密码失败')
  //   }
  // } catch (error) {
  //
  // } finally {
  //   loading.value = false
  // }
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
  max-width: 500px;
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
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  box-sizing: border-box;
}

.error-message {
  color: #dc3545;
  margin-top: 10px;
  padding: 10px;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  border-radius: 4px;
}

.loading-message {
  text-align: center;
  margin-top: 15px;
  padding: 15px;
  background-color: #d1ecf1;
  border: 1px solid #bee5eb;
  border-radius: 4px;
  color: #0c5460;
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

.cancel-btn:hover:not(:disabled), .confirm-btn:hover:not(:disabled) {
  opacity: 0.8;
}

.cancel-btn:disabled, .confirm-btn:disabled {
  cursor: not-allowed;
  opacity: 0.6;
}
</style>
