<template>
  <div class="password-manager">
    <div class="header">
      <h1>密码管理器</h1>
      <div class="header-buttons" v-show="!isMobileView">
        <button class="refresh-btn" @click="refreshList">刷新</button>
        <button class="toggle-password-btn" @click="togglePasswordVisibility">
          {{ passwordVisible ? '隐藏密码' : '显示密码' }}
        </button>
        <button class="add-btn" @click="showAddDialog = true">添加</button>
        <button class="settings-btn" @click="showSettingsDialog = true">设置</button>
        <button class="logout-btn" @click="logout">注销</button>
      </div>





      <div class="mobile-menu-area" v-show="isMobileView" ref="mobileMenu">
        <!-- 移动端菜单按钮 -->
        <div class="icon-button" @click="refreshList">
          <span class="material-symbols--refresh-rounded"></span>
        </div>
        <div class="icon-button" @click="togglePasswordVisibility">
          <span :class="{'material-symbols--visibility-outline ':passwordVisible,'material-symbols--visibility-off-outline':!passwordVisible }"></span>
        </div>
        <div class="icon-button" @click="showAddDialog = true">
          <span class="material-symbols--add-rounded"></span>
        </div>
        <div class="menu-button" v-show="isMobileView" @click="toggleMobileMenu">
          <span>☰</span>
        </div>
        <!-- 移动端弹出菜单 -->
        <div v-show="isMobileView && isMobileMenuOpen" class="mobile-menu-overlay"  >
          <div class="mobile-menu" @click.stop>
            <button class="mobile-menu-item refresh-btn" @click="()=>{refreshList(); isMobileMenuOpen = false}">刷新</button>
            <button class="mobile-menu-item toggle-password-btn" @click="()=>{togglePasswordVisibility(); isMobileMenuOpen = false}">
              {{ passwordVisible ? '隐藏密码' : '显示密码' }}
            </button>
            <button class="mobile-menu-item add-btn" @click="()=>{showAddDialog = true; isMobileMenuOpen = false}">添加</button>
            <button class="mobile-menu-item settings-btn" @click="()=>{showSettingsDialog = true; isMobileMenuOpen = false}">设置</button>
            <button class="mobile-menu-item logout-btn" @click="()=>{logout(); isMobileMenuOpen = false}">注销</button>
          </div>
        </div>
      </div>


    </div>
    

    <div class="search-box">
      <input
        type="text"
        v-model="searchQuery"
        placeholder="搜索应用..."
        class="search-input"
      />
    </div>

    <div class="password-list">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="password-item"
        @contextmenu.prevent="showContextMenu($event, item)"
      >
        <div class="item-header" @click="toggleItem(item)">
          <div class="app-icon">
            <img :src="getIconUrl(item.icon)" :alt="item.title" v-if="item.icon" />
            <span v-else>{{ item.title.charAt(0).toUpperCase() }}</span>
          </div>
          <div class="app-title">{{ item.title }}</div>
          <div class="expand-icon">
            {{ item.expanded ? '▲' : '▼' }}
          </div>
        </div>

        <div v-show="item.expanded" class="item-details">
          <div v-for="(account, index) in item.accounts" :key="index" class="account">
            <div class="account-title">账号{{ index + 1 }}</div>
            <div class="account-field">
              <input
                type="text"
                v-model="account.username"
                placeholder="账号"
                class="account-input"
                readonly
              />
              <button @click="copyToClipboard(account.username)" class="copy-btn">复制</button>
            </div>
            <div class="account-field">
              <input
                :type="passwordVisible ? 'text' : 'password'"
                v-model="account.password"
                placeholder="密码"
                class="account-input"
                readonly
              />
              <button @click="copyToClipboard(account.password)" class="copy-btn">复制</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 右键菜单 -->
    <div
      v-show="contextMenuVisible"
      class="context-menu"
      :style="{ top: contextMenuPosition.y + 'px', left: contextMenuPosition.x + 'px' }"
    >
      <div class="context-menu-item" @click="editItem(contextMenuItem)">编辑</div>
      <div class="context-menu-item" @click="confirmDeleteItem(contextMenuItem)">删除</div>
    </div>

    <!-- 添加应用对话框 -->
    <AddAppDialog
      :visible="showAddDialog"
      :host="host"
      @close="showAddDialog = false"
      @confirm="handleAddApp"
    />

    <!-- 编辑应用对话框 -->
    <EditAppDialog
      :visible="showEditDialog"
      :app-data="editingItem"
      :host="host"
      @close="showEditDialog = false"
      @confirm="handleEditApp"
    />

    <!-- 设置对话框 -->
    <SettingsDialog
      :visible="showSettingsDialog"
      @close="showSettingsDialog = false"
      @confirm="handleSettingsChange"
    />
    
    <!-- 删除确认对话框 -->
    <DeleteConfirmDialog
      :visible="showDeleteConfirm"
      :title="itemToDelete?.title"
      @close="showDeleteConfirm = false"
      @confirm="performDelete"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import passwordService from '../services/AppPortService.js'
import AddAppDialog from './AddAppDialog.vue'
import EditAppDialog from './EditAppDialog.vue'
import SettingsDialog from './SettingsDialog.vue'
import DeleteConfirmDialog from './DeleteConfirmDialog.vue'
import {GlobalObjVar} from "@/global.js";
import axios from "axios";
import {appAlert, client_function} from "@/services/client_function.js";

// 数据定义
const passwordVisible = ref(false)
const searchQuery = ref('')
const showAddDialog = ref(false)
const showEditDialog = ref(false)
const showSettingsDialog = ref(false)
const contextMenuVisible = ref(false)
const contextMenuPosition = ref({ x: 0, y: 0 })
const contextMenuItem = ref(null)
const editingItem = ref({})
const host = ref(GlobalObjVar.HOST)
const isMobileView = ref(false)
const isMobileMenuOpen = ref(false)
const mobileMenu= ref(null)
const showDeleteConfirm = ref(false)
const itemToDelete = ref(null)

// 密码项列表
const passwordItems = ref([])

// 计算属性：过滤后的项目列表
const filteredItems = computed(() => {
  if (!searchQuery.value) {
    return passwordItems.value
  }
  return passwordItems.value.filter(item =>
    item.title.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      item.url.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    item.accounts.some(account =>
      account.username.toLowerCase().includes(searchQuery.value.toLowerCase())
    )
  )
})

// 定义emit事件，用于注销
const emit = defineEmits(['logout'])

// 检查是否为移动端视图
const checkMobileView = () => {
  isMobileView.value = window.innerWidth <= 768
}

// 切换移动端菜单
const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value
}

// 方法定义
const togglePasswordVisibility = () => {
  passwordVisible.value = !passwordVisible.value
}
// 刷新列表
const refreshList = async () => {
  try {
    let items=await passwordService.getPasswordItems(host.value);
   if (items == null){
     console.error('获取列表错误:null');
      return;
    }

    // 为每个项添加expanded属性，并确保accounts字段正确处理
    passwordItems.value = items.map(item => {
      // 确保accounts字段被正确解析
      let accounts = [];
      if (Array.isArray(item.accounts)) {
        accounts = item.accounts;
      }

      return {
        ...item,
        expanded: false,
        accounts: Array.isArray(accounts) ? accounts : []
      };
    });
  } catch (error) {
    console.error('获取列表错误:', error);
  }
  if (GlobalObjVar.errorFlag){
      client_function.pwdErrorHandle();
  }
}

const toggleItem = (item) => {
  item.expanded = !item.expanded
}

const showContextMenu = (event, item) => {
  contextMenuPosition.value = { x: event.clientX, y: event.clientY }
  contextMenuItem.value = item
  contextMenuVisible.value = true
}

const editItem = (item) => {
  // 实现编辑功能
  editingItem.value = { ...item }
  showEditDialog.value = true
  contextMenuVisible.value = false
}

const confirmDeleteItem = (item) => {
  itemToDelete.value = item
  showDeleteConfirm.value = true
  contextMenuVisible.value = false
}

const performDelete = async () => {
  if (!itemToDelete.value) return
  
  try {
    await passwordService.deletePasswordItem(itemToDelete.value.id, host.value)
    const index = passwordItems.value.findIndex(i => i.id === itemToDelete.value.id)
    if (index !== -1) {
      passwordItems.value.splice(index, 1)
    }
    showDeleteConfirm.value = false
    itemToDelete.value = null
  } catch (error) {
    console.error('删除密码项失败:', error)
    appAlert('删除失败: ' + error.message)
    showDeleteConfirm.value = false
    itemToDelete.value = null
  }
}

const deleteItem = async (item) => {
  try {
    await passwordService.deletePasswordItem(item.id, host.value)
    const index = passwordItems.value.findIndex(i => i.id === item.id)
    if (index !== -1) {
      passwordItems.value.splice(index, 1)
    }
    contextMenuVisible.value = false
  } catch (error) {
    console.error('删除密码项失败:', error)
    appAlert('删除失败: ' + error.message)
    contextMenuVisible.value = false
  }
}

const handleAddApp = async (appData) => {
  // 验证账号密码是否为空
  for (let i = 0; i < appData.accounts.length; i++) {
    const account = appData.accounts[i];
    if (!account.username && !account.password) {
      appAlert(`账号${i + 1}的用户名和密码不能都为空`)
      return
    }
  }
  
  try {
    const addedItem = await passwordService.addPasswordItem(appData, host.value)

    // 添加到列表中
    passwordItems.value.push({
      ...addedItem,
      expanded: false
    })

    showAddDialog.value = false
  } catch (error) {
    console.error('添加密码项失败:', error)
    appAlert('添加失败: ' + error.message)
  }
}

const handleEditApp = async (appData) => {
  // 验证账号密码是否为空
  for (let i = 0; i < appData.accounts.length; i++) {
    const account = appData.accounts[i];
    if (!account.username && !account.password) {
      appAlert(`账号${i + 1}的用户名和密码不能都为空`)
      return
    }
  }
  
  try {
    const updatedItem = await passwordService.updatePasswordItem(appData, host.value)
    
    // 更新列表中的项
    const index = passwordItems.value.findIndex(item => item.id === updatedItem.id)
    if (index !== -1) {
      passwordItems.value[index] = {
        ...updatedItem,
        expanded: passwordItems.value[index].expanded // 保持展开状态
      }
    }
    
    showEditDialog.value = false
  } catch (error) {
    console.error('更新密码项失败:', error)
    appAlert('更新失败: ' + error.message)
  }
}

const handleSettingsChange = (settings) => {
  // 处理设置变更
  console.log('设置已更新:', settings)
  host.value = settings.host || 'http://localhost'
  showSettingsDialog.value = false
}

const copyToClipboard = (text) => {
  // 实现复制到剪贴板功能
  if (typeof Android==='undefined'){
    // 非安卓环境，使用浏览器API复制
    navigator.clipboard.writeText(text).then(() => {
      console.log('复制成功:', text)
    })
  }else{
    // 安卓环境，使用Android API复制
    Android.copyToClipboard(text)
  }
}

const logout = () => {
  emit('logout')
}

// 获取图标完整URL
const getIconUrl = (iconName) => {
  if (!iconName) return ''
  // 如果已经是完整URL，则直接返回
  if (iconName.startsWith('http')) {
    return iconName
  }
  // 否则构造完整URL
  return `${host.value}/getResource?name=${iconName}`
}

// 点击其他地方隐藏右键菜单
const handleClickOutside = (event) => {
  if (contextMenuVisible.value) {
    contextMenuVisible.value = false
  }

  if (isMobileMenuOpen.value && !mobileMenu.value.contains(event.target)) {
    isMobileMenuOpen.value = false
  }
}

const checkServer=()=> {
  new Promise(async (resolve, reject) => {
    try {
      await axios.get(`${host.value}/check`).then((response) => {
        if (response === null || response.status !== 200) {
          appAlert("服务器异常，请重新设置服务器地址");
        }
      });
    } catch (e) {
      appAlert("服务器异常，请重新设置服务器地址");
    }
  });
}

// 生命周期钩子
onMounted(async () => {
  document.addEventListener('click', handleClickOutside)
  // 初始化加载数据
  await refreshList()
  
  // 从GlobalObjVar加载全局HOST
  host.value =GlobalObjVar.HOST;
  // 延迟1秒后检查服务器
  setTimeout(() => {
    checkServer();
  }, 1000);
  
  // 检查视图大小
  checkMobileView()
  window.addEventListener('resize', checkMobileView)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  window.removeEventListener('resize', checkMobileView)
})
</script>

<style scoped>
.password-manager {
  padding: 20px;
  font-family: Arial, sans-serif;
  display: flex;      /* 开启Flex布局 */
  flex: 1; /* 撑满盒子2的剩余空间 */
  flex-direction: column; /* 子元素(div4,5,6)垂直排列 */
  overflow: hidden;   /* 确保盒子3本身也绝对不会滚动 */
  box-sizing: border-box;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 10px;
}

.header h1 {
  margin: 0;
}

.header-buttons {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;

}

.header-buttons button,
.mobile-menu-item {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.header-buttons button:hover,
.mobile-menu-item:hover {
  background: #f5f5f5;
}

.logout-btn {
  background: #dc3545 !important;
  color: white !important;
  border-color: #dc3545 !important;
}

.menu-button {
  padding: 8px 8px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.menu-button:hover {
  background: #f5f5f5;
}
.mobile-menu-area {
  display: flex;
  position: relative;
  align-items: center; /* 垂直居中 */
  column-gap: 0.4rem ;
}
.mobile-menu-overlay {
  position: absolute;
  right: 16px;
  top: 40px;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: flex-end;
  z-index: 1000;
}

.mobile-menu {
  background: white;
  width: 250px;
  height: 100%;
  display: flex;
  flex-direction: column;
  padding: 10px;
  box-shadow: -2px 0 5px rgba(0, 0, 0, 0.3);
}

.mobile-menu-item {
  width: 100%;
  text-align: left;
  margin-bottom: 10px;
}
.icon-button {
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 18px;
  display: flex;
  color: #666666;
}
.search-box {
  margin-bottom: 20px;
}

.search-input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 16px;
  box-sizing: border-box;
}

.password-list {
  border: 1px solid #ddd;
  border-radius: 4px;
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
  min-height: 0;
}

.password-item {
  border-bottom: 1px solid #eee;
}

.password-item:last-child {
  border-bottom: none;
}

.item-header {
  display: flex;
  align-items: center;
  padding: 15px;
  cursor: pointer;
  user-select: none;
}

.item-header:hover {
  background: #f9f9f9;
}

.app-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  font-weight: bold;
}

.app-icon img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.app-title {
  flex: 1;
  font-size: 18px;
  font-weight: bold;
}

.expand-icon {
  margin-left: 10px;
}

.item-details {
  padding: 0 10px 10px 10px;
}

.account {
  margin-bottom: 15px;
  padding: 10px;
  background: #f5f5f5;
  border-radius: 4px;
}

.account-title {
  font-weight: bold;
  margin-bottom: 10px;
}

.account-field {
  display: flex;
  margin-bottom: 10px;
  align-items: center;
}

.account-input {
  flex: 1;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  margin-right: 10px;
}

.copy-btn {
  padding: 8px 12px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.copy-btn:hover {
  background: #f5f5f5;
}

.context-menu {
  position: fixed;
  background: white;
  border: 1px solid #ddd;
  border-radius: 4px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  z-index: 1000;
}

.context-menu-item {
  padding: 10px 20px;
  cursor: pointer;
}

.context-menu-item:hover {
  background: #f5f5f5;
}


@media (min-width: 900px) {
  .password-manager {
    max-width: 800px;
    margin: 0 auto;
  }
}

@media (max-width: 768px) {
  .header {
    flex-direction: row;
    align-items: center;
  }
  
  .header-buttons {
    justify-content: center;
    visibility: hidden;
  }
  
  .password-manager {
    padding: 20px 10px 10px 10px;
  }

}

@media (max-width: 600px) {
  .header {
    flex-direction: row;
    align-items: center;
  }
  
  .header-buttons {
    justify-content: center;
    visibility: hidden;
  }

  .password-manager {
    padding: 24px 10px 10px 10px;
  }
  
  .item-details {
    padding: 0 10px 10px 10px;
  }
  h1 {
    font-size: 23px;
  }
  .menu-button {
    padding: 2px 6px  2px 6px;
  }
}
/*图标-刷新*/
.material-symbols--refresh-rounded {
  display: inline-block;
  width: 24px;
  height: 24px;
  --svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M12 20q-3.35 0-5.675-2.325T4 12t2.325-5.675T12 4q1.725 0 3.3.712T18 6.75V5q0-.425.288-.712T19 4t.713.288T20 5v5q0 .425-.288.713T19 11h-5q-.425 0-.712-.288T13 10t.288-.712T14 9h3.2q-.8-1.4-2.187-2.2T12 6Q9.5 6 7.75 7.75T6 12t1.75 4.25T12 18q1.7 0 3.113-.862t2.187-2.313q.2-.35.563-.487t.737-.013q.4.125.575.525t-.025.75q-1.025 2-2.925 3.2T12 20'/%3E%3C/svg%3E");
  background-color: currentColor;
  -webkit-mask-image: var(--svg);
  mask-image: var(--svg);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
}
/*图标-添加*/
.material-symbols--add-rounded {
  display: inline-block;
  width: 24px;
  height: 24px;
  --svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M11 13H6q-.425 0-.712-.288T5 12t.288-.712T6 11h5V6q0-.425.288-.712T12 5t.713.288T13 6v5h5q.425 0 .713.288T19 12t-.288.713T18 13h-5v5q0 .425-.288.713T12 19t-.712-.288T11 18z'/%3E%3C/svg%3E");
  background-color: currentColor;
  -webkit-mask-image: var(--svg);
  mask-image: var(--svg);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
}
/*图标-密码可见*/
.material-symbols--visibility-outline {
  display: inline-block;
  width: 24px;
  height: 24px;
  --svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='M12 16q1.875 0 3.188-1.312T16.5 11.5t-1.312-3.187T12 7T8.813 8.313T7.5 11.5t.788 1.912T12 16m0-1.8q-1.125 0-1.912-.788T9.3 11.5t.788-1.912T12 8.8t1.913.788t.787 1.913t-.787 1.913T12 14.2m0 4.8q-3.65 0-6.65-2.037T1 11.5q1.35-3.425 4.35-5.462T12 4t6.65 2.038T23 11.5q-1.35 3.425-4.35 5.463T12 19m0-2q2.825 0 5.188-1.487T20.8 11.5q-1.25-2.525-3.613-4.012T1 11.5q.575 1.475 1.325 2.463T4.15 7L1.4 4.2l1.4-1.4l18.4 18.4zM5.55 8.4q-.725.65-1.325 1.425T3.2 11.5q1.25 2.525 3.588 4.013T12 17q.5 0 .975-.062t.975-.138l-.9-.95q-.275.075-.525.113T12 16q-1.875 0-3.188-1.312T7.5 11.5q0-.275.038-.525t.112-.525zm4.2 4.2'/%3E%3C/svg%3E");
  background-color: currentColor;
  -webkit-mask-image: var(--svg);
  mask-image: var(--svg);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
}
.material-symbols--visibility-off-outline {
  display: inline-block;
  width: 24px;
  height: 24px;
  --svg: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23000' d='m16.1 13.3l-1.45-1.45q.225-1.175-.675-2.2t-2.325-.8L10.2 7.4q.425-.2.863-.3T12 7q1.875 0 3.188 1.313T16.5 11.5q0 .5-.1.938t-.3.862m3.2 3.15l-1.45-1.4q.95-.725 1.688-1.587T20.8 11.5q-1.25-2.525-3.588-4.012T1 11.5q.575 1.475 1.325 2.463T4.15 7L1.4 4.2l1.4-1.4l18.4 18.4zM5.55 8.4q-.725.65-1.325 1.425T3.2 11.5q1.25 2.525 3.588 4.013T12 17q.5 0 .975-.062t.975-.138l-.9-.95q-.275.075-.525.113T12 16q-1.875 0-3.188-1.312T7.5 11.5q0-.275.038-.525t.112-.525zm4.2 4.2'/%3E%3C/svg%3E");
  background-color: currentColor;
  -webkit-mask-image: var(--svg);
  mask-image: var(--svg);
  -webkit-mask-repeat: no-repeat;
  mask-repeat: no-repeat;
  -webkit-mask-size: 100% 100%;
  mask-size: 100% 100%;
}
/*阻止用户复制控件*/
h1,.app-title,.context-menu,.account-title,.copy-btn,.logout-btn,.header-buttons,.refresh-btn,.menu-button{
  -webkit-user-select: none; /* Chrome, Opera, Safari */
  -moz-user-select: none;    /* Firefox */
  -ms-user-select: none;     /* Internet Explorer/Edge */
  user-select: none;         /* Standard */
}
</style>