<script setup>
import { ref, onMounted } from 'vue'
import Login from './components/Login.vue'
import Dashboard from './components/Dashboard.vue'

// 当前视图控制
const currentView = ref('login')

// 检查登录状态
const checkLoginStatus = () => {
  // 这里应该调用后端API检查登录状态
  // 暂时使用简单的检查
  fetch('./checkLogin.php')
    .then(response => response.json())
    .then(data => {
      if (data.loggedIn) {
        currentView.value = 'dashboard'
      } else {
        currentView.value = 'login'
      }
    })
    .catch(() => {
      currentView.value = 'login'
    })
}

onMounted(() => {
  checkLoginStatus()
})

// 处理登录成功事件
const handleLoginSuccess = () => {
  currentView.value = 'dashboard'
}

// 处理登出事件
const handleLogout = () => {
  currentView.value = 'login'
}
</script>

<template>
  <div id="app">
    <Login 
      v-if="currentView === 'login'" 
      @login-success="handleLoginSuccess"
    />
    <Dashboard 
      v-if="currentView === 'dashboard'" 
      @logout="handleLogout"
    />
  </div>
</template>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  line-height: 1.6;
  color: #333;
}

#app {
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow-y: hidden;
}
</style>