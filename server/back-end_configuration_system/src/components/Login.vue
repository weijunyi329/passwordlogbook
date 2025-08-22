<template>
  <div class="login-container">
    <div class="login-form">
      <h2>后台管理系统登录</h2>
      <form @submit.prevent="handleLogin">
        <div class="form-group">
          <label for="password">口令:</label>
          <input 
            type="password" 
            id="password" 
            v-model="token"
            required 
            placeholder="请输入后台访问口令"
          />
        </div>
        <div class="form-group" v-if="showCaptcha">
          <label for="captcha">验证码:</label>
          <div class="captcha-container">
            <input 
              type="text" 
              id="captcha" 
              v-model="captcha"
              required 
              placeholder="请输入验证码"
              class="captcha-input"
            />
            <img 
              :src="captchaUrl" 
              alt="验证码" 
              class="captcha-image"
              @click="refreshCaptcha"
            />
          </div>
        </div>
        <button type="submit" class="login-button">登录</button>
      </form>
      <div v-if="errorMessage" class="error-message">
        {{ errorMessage }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const token = ref('')
const captcha = ref('')
const errorMessage = ref('')
const showCaptcha = ref(false)
const captchaUrl = ref('./captcha.php?' + Date.now())
const emit = defineEmits(['logout','login-success'])

const refreshCaptcha = () => {
  // 清除之前的验证码会话并获取新的验证码
  fetch('./captcha.php?refresh=' + Date.now())
    .then(() => {
      captchaUrl.value = './captcha.php?' + Date.now()
    })
}

const handleLogin = () => {
  // 准备登录数据
  const loginData = { token: token.value }
  if (showCaptcha.value) {
    loginData.captcha = captcha.value
  }
  
  // 发送登录请求到后端
  fetch('./login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(loginData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // 登录成功，跳转到主页面
      window.location.href = '#/dashboard';
      emit('login-success');
    } else {
      token.value = '';
      captcha.value = '';
      
      // 如果后端要求验证码，则显示验证码输入框
      if (data.needCaptcha) {
        showCaptcha.value = true
        refreshCaptcha()
      }
      
      errorMessage.value = data.message || '登录失败';
    }
  })
  .catch(error => {
    console.error('Error:', error);
    token.value = '';
    captcha.value = '';
    errorMessage.value = '登录请求失败';
  })
}
</script>

<style scoped>
.login-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
  background-color: #f5f5f5;
}

.login-form {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  width: 100%;
  max-width: 400px;
}

.login-form h2 {
  text-align: center;
  margin-bottom: 1.5rem;
  color: #333;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #555;
}

.form-group input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-sizing: border-box;
}

.captcha-container {
  display: flex;
  gap: 10px;
  align-items: center;
}

.captcha-input {
  flex: 1;
}

.captcha-image {
  height: 38px;
  cursor: pointer;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.login-button {
  width: 100%;
  padding: 0.75rem;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

.login-button:hover {
  background-color: #0056b3;
}

.error-message {
  color: #dc3545;
  margin-top: 1rem;
  text-align: center;
}
</style>