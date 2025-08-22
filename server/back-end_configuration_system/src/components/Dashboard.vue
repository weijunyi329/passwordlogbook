<template>
  <div class="dashboard">
    <header class="header">
      <h1>后台管理系统配置</h1>
      <div class="header-actions">
        <button class="btn btn-primary" @click="saveSecurityConfig">保存配置</button>
        <button class="btn btn-secondary" @click="clearCache">清理缓存</button>
        <button class="btn btn-danger" @click="logout">退出登录</button>

      </div>
    </header>

    <div class="main-content">
      <!-- Tab导航 -->
      <div class="tabs">

        <button 
          :class="['tab', activeTab === 'system' ? 'active' : '']" 
          @click="activeTab = 'system'"
        >
          系统信息
        </button>
        <button 
          :class="['tab', activeTab === 'security' ? 'active' : '']" 
          @click="activeTab = 'security'"
        >
          安全配置
        </button>
        <button 
          :class="['tab', activeTab === 'records' ? 'active' : '']" 
          @click="activeTab = 'records'"
        >
          查看访问记录
        </button>
      </div>

      <!-- 内容区域容器，只在这个区域内滚动 -->
      <div class="content-container">
        <!-- 系统信息面板 -->
        <div v-show="activeTab === 'system'" class="tab-content">
          <div class="card">
            <h2>系统信息</h2>
            
            <div class="form-group">
              <label>当前运行状态:</label>
              <button 
                :class="['status-button', systemInfo.status ? 'running' : 'stopped']"
                @click="toggleSystemStatus"
              >
                {{ systemInfo.status ? '开启' : '关闭' }}
              </button>
            </div>

            <div class="form-group">
              <label>APP访问接口:</label>
              <input 
                type="text" 
                v-model="systemInfo.appApi" 
                readonly 
                class="form-control"
              />
            </div>

            <div class="form-group">
              <label>使用虚拟路径:</label>
              <span>{{ systemInfo.useVirtualPath ? '已开启' : '已关闭' }}</span>
            </div>
            <div class="form-group">
              <label>只允许https访问:</label>
              <span>{{ systemInfo.onlyHttpsAccess ? '已开启' : '已关闭' }}</span>
            </div>
            <div class="form-group">
              <label>是否限制客户端UA:</label>
              <span>{{ systemInfo.restrictUa ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>是否限制IP:</label>
              <span>{{ systemInfo.restrictIp ? '已开启' : '已关闭' }}</span>
            </div>

<!--            <div class="form-group">-->
<!--              <label>白名单IP:</label>-->
<!--              <span>{{ systemInfo.whitelistIpEnabled ? '已开启' : '已关闭' }}</span>-->
<!--            </div>-->

<!--            <div class="form-group">-->
<!--              <label>黑名单IP:</label>-->
<!--              <span>{{ systemInfo.blacklistIpEnabled ? '已开启' : '已关闭' }}</span>-->
<!--            </div>-->

            <div class="form-group">
              <label>限制客户端的UUID:</label>
              <span>{{ systemInfo.restrictUuid ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <button class="btn btn-primary" @click="backupDatabase">备份：下载数据库记录</button>
            </div>
          </div>
        </div>

        <!-- 安全配置面板 -->
        <div v-show="activeTab === 'security'" class="tab-content">
          <div class="card">
            <div class="card-header">
              <h2>安全配置</h2>
            </div>
            
            <div class="form-group">
              <label>使用口令访问后台:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.useToken"
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.useToken ? '已开启' : '已关闭' }}</span>
              <button class="btn btn-secondary" @click="showChangeToken = true">修改后台口令</button>
            </div>

            <div class="form-group">
              <label>客户端使用Cookie访问:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.useCookie" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.useCookie ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>客户端Cookie时间（秒）:</label>
              <input 
                type="number" 
                v-model="securityConfig.cookieTime" 
                class="form-control" 
              />
            </div>

            <div class="form-group">
              <label>记录客户端访问IP:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.logIp" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.logIp ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>使用虚拟路径:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.useVirtualPath" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.useVirtualPath ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>虚拟路径名:</label>
              <input 
                type="text" 
                v-model="securityConfig.virtualPath" 
                class="form-control" 
              />
            </div>
            <div class="form-group">
              <label>只允许HTTPS访问:</label>
              <input
                  type="checkbox"
                  v-model="securityConfig.onlyHttpsAccess"
                  class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.onlyHttpsAccess ? '已开启' : '已关闭' }}</span>
            </div>
            <div class="form-group">
              <label>是否限制客户端UA:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.restrictUa" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.restrictUa ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>允许的客户端UA:</label>
              <textarea 
                v-model="securityConfig.allowedUa" 
                class="form-control" 
                rows="4"
              ></textarea>
            </div>

            <div class="form-group">
              <label>是否限制IP:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.restrictIp" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.restrictIp ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>白名单IP:</label>
              <textarea
                v-model="securityConfig.whitelistIp"
                class="form-control"
                rows="4"
              ></textarea>
            </div>

            <div class="form-group">
              <label>黑名单IP:</label>
              <textarea
                v-model="securityConfig.blacklistIp"
                class="form-control"
                rows="4"
              ></textarea>
            </div>

            <div class="form-group">
              <label>限制客户端的UUID:</label>
              <input 
                type="checkbox" 
                v-model="securityConfig.restrictUuid" 
                class="form-checkbox"
              />
              <span class="checkbox-label">{{ securityConfig.restrictUuid ? '已开启' : '已关闭' }}</span>
            </div>

            <div class="form-group">
              <label>客户端UUID:</label>
              <textarea 
                v-model="securityConfig.allowedUuid" 
                class="form-control" 
                rows="4"
              ></textarea>
            </div>
          </div>
        </div>

        <!-- 访问记录面板 -->
        <div v-show="activeTab === 'records'" class="tab-content">
          <div class="card">
            <div class="records-header">
              <h2>访问记录</h2>
              <button class="btn btn-danger" @click="clearAccessRecords">清空记录</button>
            </div>
            <table class="table">
              <thead>
                <tr>
                  <th>时间</th>
                  <th>IP地址</th>
                  <th>用户代理</th>
                  <th>UUID</th>
                  <th>访问状态</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(record,index) in accessRecords" :key="index">
                  <td>{{ record.time }}</td>
                  <td>{{ record.ip }}</td>
                  <td>{{ record.ua }}</td>
                  <td>{{ record.uuid }}</td>
                  <td>{{ record.accept }}</td>
                </tr>
                <tr v-if="accessRecords.length === 0">
                  <td colspan="5" class="text-center">暂无访问记录</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- 自定义Alert组件 -->
    <Alert
      v-model="alertVisible"
      :title="alertTitle"
      :message="alertMessage"
      :type="alertType"
      @confirm="handleAlertClose"
    />

    <!-- 修改口令弹窗 -->
    <div v-if="showChangeToken" class="modal">
      <div class="modal-content">
        <span class="close" @click="showChangeToken = false">&times;</span>
        <h2>修改后台口令</h2>
        <div class="form-group">
          <label>新口令:</label>
          <input type="password" v-model="newToken" class="form-control" />
        </div>
        <div class="form-group">
          <label>确认新口令:</label>
          <input type="password" v-model="confirmToken" class="form-control" />
        </div>
        <div class="form-group">
          <button class="btn btn-primary" @click="changeToken">确认修改</button>
          <button class="btn btn-secondary" @click="showChangeToken = false">取消</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// 当前激活的Tab
const activeTab = ref('system')

// 系统信息数据
const systemInfo = ref({
  status: true,
  appApi: '/api/app',
  useVirtualPath: true,
  restrictUa: false,
  restrictIp: true,
  restrictUuid: false,
  onlyHttpsAccess:false
})

// 安全配置数据
const securityConfig = ref({
  useToken: true,
  useCookie: true,
  cookieTime: 3600,
  logIp: true,
  useVirtualPath: true,
  virtualPath: 'secure',
  onlyHttpsAccess:false,
  restrictUa: false,
  allowedUa: '',
  restrictIp: true,
  whitelistIp: '',
  blacklistIp: '',
  restrictUuid: false,
  allowedUuid: ''
})

// 访问记录数据
const accessRecords = ref([])

// 修改口令相关
const showChangeToken = ref(false)
const newToken = ref('')
const confirmToken = ref('')

// 切换系统状态
const toggleSystemStatus = () => {

  systemInfo.value.status = !systemInfo.value.status

  fetch('./toggleSystemStatus.php', {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    }
  }).then(response => response.json())
  .then(data => {
    if (data.success) {
      systemInfo.value.status = !((typeof data.status==='string') && (data.status==='running'));
      alert('系统状态已更新为: ' + systemInfo.value.status)
    } else {
      alert('更新失败: ' + data.message)
    }
  })

  // 这里应该调用后端API保存状态
  console.log('系统状态已更新为:', systemInfo.value.status)
}

// 清理缓存
const clearCache = () => {
  alert('缓存已清理')
  // 这里应该调用后端API清理缓存
}

// 退出登录
const logout = () => {
  if (confirm('确定要退出登录吗？')) {
    // 调用后端退出登录接口
    fetch('./logout.php')
      .then(() => {
        window.location.href = '#/login'
        window.location.reload();
      })
  }
}

// 备份数据库
const backupDatabase = () => {
  // 调用后端备份接口
  window.open('./backup.php', '_blank')
}

// 修改口令
const changeToken = () => {
  if (newToken.value !== confirmToken.value) {
    alert('两次输入的口令不一致')
    return
  }
  
  // 调用后端修改口令接口
  fetch('./changeToken.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      newToken: newToken.value
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('口令修改成功')
      showChangeToken.value = false
      newToken.value = ''
      confirmToken.value = ''
    } else {
      alert('口令修改失败: ' + data.message)
    }
  });
}

// 保存安全配置
const saveSecurityConfig = () => {
  // 调用后端保存配置接口
  fetch('./saveConfig.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(securityConfig.value)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('配置保存成功')
    } else {
      alert('配置保存失败: ' + data.message)
    }
  })
}

// 获取访问记录
const loadAccessRecords = () => {
  // 调用后端获取访问记录接口
  fetch('./getAccessRecords.php')
    .then(response => response.json())
    .then(data => {
      let recordsList = [];
      if (Array.isArray(data)) {
        data.forEach(record => {
          recordsList.push({
            time: record.time,
            access: record.access,
            accept: (record.accept?'接受':'拒绝'),
            ip: (record.ip?record.ip: 'N/A'),
            ua: (record.ua?record.ua: 'N/A'),
            uuid: (record.uuid?record.uuid: 'N/A')
          });
        });
      }

      accessRecords.value.push(...recordsList);
      console.log(accessRecords.value);
    });
}
// 获取当前配置
const fetchCurrentConfig = () => {
  fetch('./currentConfig.php')
  .then(response => response.json())
  .then(data => {
    console.log(data);
    Object.keys(data).forEach(key => {
      if (typeof securityConfig.value[key] !== 'undefined') {
        securityConfig.value[key] = data[key];
      }
      if (typeof systemInfo.value[key] !== 'undefined') {
        systemInfo.value[key] = data[key];
      }
    });
    if (typeof data.serverStatus !== 'undefined' && data.serverStatus !== null && typeof data.serverStatus === 'boolean') {
      systemInfo.value.status = data.serverStatus;
    }
  });
}

// 清空访问记录
const clearAccessRecords = () => {
  if (confirm('确定要清空所有访问记录吗？')) {
    // 调用后端清空记录接口
    fetch('./deleteAccessRecords.php', {
      method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        accessRecords.value = [];
        alert('访问记录已清空');
      } else {
        alert('清空记录失败: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('清空记录失败');
    });
  }
}

// 处理Alert关闭事件
const handleAlertClose = () => {
  alertVisible.value = false
  if (alertCallback.value) {
    alertCallback.value()
    alertCallback.value = null
  }
}

// 组件挂载时加载数据
onMounted(() => {
  fetchCurrentConfig();
  loadAccessRecords();
})
</script>

<style scoped>
.dashboard {
  flex: 1;
  background-color: #f5f5f5;
  display: flex;
  flex-direction: column;
  overflow-y: hidden;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 2rem;
  background-color: #fff;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  z-index: 100;
}

.header h1 {
  margin: 0;
  color: #333;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
}

.btn-primary {
  background-color: #007bff;
  color: white;
}

.btn-primary:hover {
  background-color: #0056b3;
}

.btn-secondary {
  background-color: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background-color: #545b62;
}

.btn-danger {
  background-color: #dc3545;
  color: white;
}

.btn-danger:hover {
  background-color: #bd2130;
}

.main-content {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow-y: hidden;
}

.tabs {
  display: flex;
  background-color: #fff;
  z-index: 90;
  border-bottom: 1px solid #dee2e6;
}

.tab {
  padding: 0.75rem 1.5rem;
  background-color: #e9ecef;
  border: none;
  border-bottom: 3px solid transparent;
  cursor: pointer;
  font-size: 1rem;
}

.tab.active {
  background-color: #fff;
  border-bottom-color: #007bff;
  color: #007bff;
}

.content-container {
  flex: 1;
  overflow-y: auto;
  padding: 2rem;

}

.tab-content {
  background-color: #fff;
  padding: 1.5rem;
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card {
  margin-bottom: 1rem;
}

.card h2 {
  margin-top: 0;
  margin-bottom: 1.5rem;
  color: #333;
  border-bottom: 1px solid #eee;
  padding-bottom: 0.5rem;
}

.form-group {
  margin-bottom: 1rem;
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #555;
}

.form-control {
  padding: 0.5rem 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 1rem;
}

.form-control:focus {
  border-color: #80bdff;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.form-checkbox {
  width: 20px;
  height: 20px;
  margin-right: 10px;
  align-self: flex-start;
}

.checkbox-label {
  display: inline-block;
  margin-top: 5px;
}

.status-button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
  width: fit-content;
}

.status-button.running {
  background-color: #28a745;
  color: white;
}

.status-button.stopped {
  background-color: #dc3545;
  color: white;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th,
.table td {
  padding: 0.75rem;
  vertical-align: top;
  border-top: 1px solid #dee2e6;
}

.table thead th {
  vertical-align: bottom;
  border-bottom: 2px solid #dee2e6;
  background-color: #f8f9fa;
}

.text-center {
  text-align: center;
}

.records-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

/* 弹窗样式 */
.modal {
  display: block;
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.4);
}

.modal-content {
  background-color: #fefefe;
  margin: 15% auto;
  padding: 2rem;
  border: 1px solid #888;
  width: 80%;
  max-width: 500px;
  border-radius: 4px;
  position: relative;
}

.close {
  color: #aaa;
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: 2rem;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: black;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .header {
    padding: 1rem;
  }
  
  .content-container {
    padding: 1rem;
  }
  
  .tab {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
  }
}
</style>