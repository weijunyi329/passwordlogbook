<template>
  <div v-if="visible" class="dialog-overlay">
    <div class="dialog">
      <div class="dialog-header">
        <h2>添加应用</h2>
        <button class="close-btn" @click="close">×</button>
      </div>
      <div class="dialog-body">
        <div class="form-group">
          <label>应用标题</label>
          <input type="text" v-model="formData.title" class="form-input" />
        </div>
        <div class="form-group">
          <label>应用图标</label>
          <div class="icon-preview">
            <img :src="getIconPreviewUrl()" :alt="formData.title" v-if="formData.icon" />
            <span v-else>{{ formData.title.charAt(0).toUpperCase() }}</span>
          </div>
          <input 
            type="file" 
            ref="fileInput" 
            accept=".png" 
            @change="handleFileSelect" 
            style="display: none;"
          />
          <div class="icon-buttons">
            <button @click="selectIcon" class="select-icon-btn">选择图标</button>
            <button @click="clearIcon" class="clear-icon-btn">清除图标</button>
          </div>
          <div v-if="uploading" class="upload-status">上传中...</div>
        </div>
        <div class="form-group">
          <label>应用URL</label>
          <div class="url-input-group">
            <input type="text" v-model="formData.url" class="form-input" />
            <button @click="formatUrl" class="format-btn">格式化</button>
          </div>
        </div>
        <div class="form-group">
          <label>应用包名</label>
          <div class="package-input-group">
            <input type="text" v-model="formData.packageName" class="form-input" />
            <button @click="formatPackageName"  class="format-btn">格式化</button>
          </div>
        </div>
        <div class="form-group">
          <label>备注</label>
          <input type="text" v-model="formData.remark" class="form-input" />
        </div>
        
        <div v-for="(account, index) in formData.accounts" :key="index" class="account-section">
          <div class="account-header">
            <div class="account-title">账号{{ index + 1 }}</div>
            <button 
              v-if="formData.accounts.length > 1" 
              @click="removeAccount(index)" 
              class="remove-account-btn"
            >
              删除
            </button>
          </div>
          <div class="form-group">
            <input type="text" v-model="account.username" placeholder="账号" class="form-input" />
          </div>
          <div class="form-group">
            <input type="text" v-model="account.password" placeholder="密码" class="form-input" />
          </div>
        </div>
        
        <div class="add-more-accounts">
          <p class="hint">提示：可以为同一应用添加多个账号</p>
          <button @click="addAccount" class="add-account-btn">添加更多账号</button>
        </div>
      </div>
      <div class="dialog-footer">
        <button @click="close" class="cancel-btn">取消</button>
        <button @click="confirm" class="confirm-btn">确认</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import {ref, reactive, watch} from 'vue'
import {GlobalObjVar} from "@/global.js";
import {appAlert, client_function} from "@/services/client_function.js";




const props = defineProps({
  visible: {
    type: Boolean,
    default: false
  },
  host: {
    type: String,
    default: GlobalObjVar.HOST
  }
})

const emit = defineEmits(['close', 'confirm'])

const fileInput = ref(null)
const uploading = ref(false)
const formData = reactive({
  title: '',
  icon: '',
  url: '',
  packageName: '',
  remark: '',
  accounts: [
    { username: '', password: '' }
  ]
})

const selectIcon = () => {
  fileInput.value.click()
}

const clearIcon = () => {
  formData.icon = ''
}

const handleFileSelect = async (event) => {
  const file = event.target.files[0]
  if (file && file.type === 'image/png') {
    // 上传文件到服务器
    uploading.value = true
    try {
      const fileName = await uploadFile(file)
      formData.icon = fileName
    } catch (error) {
      console.error('文件上传失败:', error)
      appAlert('文件上传失败: ' + error.message);
    } finally {
      uploading.value = false
    }
  } else if (file) {
    appAlert('请选择PNG格式的图片文件')
  }
}

const uploadFile = (file) => {
  return new Promise((resolve, reject) => {
    const formData = new FormData()
    formData.append('file', file)

    fetch(`${props.host}/upload`, {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        console.log('文件上传失败'+response.body)
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      console.log('文件上传成功'+response.body)
      return response.json()
    })
    .then(data => {
      if (data.success) {
        resolve(data.fileName)
      } else {
        reject(new Error(data.message || '上传失败'))
      }
    })
    .catch(error => {
      reject(error)
    })
  })
}

const getIconPreviewUrl = () => {
  if (!formData.icon) return ''
  // 如果已经是完整URL，则直接返回
  if (formData.icon.startsWith('http')) {
    return formData.icon
  }
  // 否则构造完整URL
  return `${props.host}/getResource?name=${formData.icon}`
}

const formatUrl = () => {
  if (formData.url===''){
    appAlert('请输入URL');
    return;
  }
  if (/^(-|\d|[a-z]|[A-Z])+(\.(-|\d|[a-z]|[A-Z])+)+(:\d+)?$/.test(formData.url)){
    // 正确的地址
    return;
  }
  if (!(formData.url && /^https?:\/\/([a-z]|[A-Z]|\d|-|_|\.)+\/?\S*/.test(formData.url))) {
    appAlert('请输入正确的URL');
     return;
  }

  let oUrl = formData.url;

  // 去掉http://和https://开头
  if (formData.url.startsWith('https://')){
    formData.url=formData.url.substring(8);
  }else if (formData.url.startsWith('http://')){
    formData.url=formData.url.substring(7);
  }
  //去掉末尾的斜杠
  if(formData.url.endsWith('/')){
    formData.url=formData.url.substring(0,formData.url.length-1);
  }

  //取HOST部分
  let res=formData.url.match(new RegExp(/(-|\d|[a-z]|[A-Z])+(\.(-|\d|[a-z]|[A-Z])+)+(:\d+)?/g));
  if ( res && res.length>0){
     formData.url=res[0];
  }else {
    return;
  }

    if (formData.icon==='') {
      // 上传文件到服务器
      uploading.value = true;
      // 下载图标并保存到服务器
       client_function.downloadIconHostUpload(GlobalObjVar.HOST,oUrl,formData.url, function (data)
       {
         if (typeof data==='string'){
           try {
             data=JSON.parse(data);
           }catch (e){
             console.log("文件上传失败:"+ data);
             data={fileName:"",title:""}
           }
         }
         console.log("文件上传成功:"+ data.data);
         if (typeof data.data === "string"){
           let d=JSON.parse(data.data);
           formData.icon=d.fileName;
           console.log("文件上传成功:"+ formData.icon);
         }else if (typeof data.fileName === "string"){
           formData.icon=data.fileName;
           console.log("文件上传成功:"+ formData.icon);
         }
         uploading.value = false;
         if (formData.title=== '' && data.title)formData.title=data.title;
       },function (error){
         console.log("文件上传失败:"+ error);
         uploading.value = false;
         if (formData.title=== '' && error.title)formData.title=error.title;
       });

    }
}

const formatPackageName = () => {
  console.log("handlePackageName")
  client_function.reformatPkgName(GlobalObjVar.HOST,(data)=>{
    if(typeof data.title!=="undefined" && data.title!=="" && data.title!==null && formData.title=== ''){
      formData.title=data.title;
    }
    if(typeof data.fileName!=="undefined" && data.fileName!=="" && data.fileName!==null && formData.icon=== ''){
      formData.icon=data.fileName;
    }
    if(typeof data.pkgName!=="undefined" && data.pkgName!=="" && data.pkgName!==''){
      formData.packageName=data.pkgName;
    }

  });
}

const addAccount = () => {
  formData.accounts.push({ username: '', password: '' })
}

const removeAccount = (index) => {
  if (formData.accounts.length > 1) {
    formData.accounts.splice(index, 1)
  }
}

const resetForm = () => {
  // 重置表单
  Object.assign(formData, {
    title: '',
    icon: '',
    url: '',
    packageName: '',
    remark: '',
    accounts: [
      { username: '', password: '' }
    ]
  })
}

// 使用 getter 函数来监听 props.visible 的变化
// 如果关闭的时候，清空窗口
watch(() => props.visible, (newValue, oldValue) => {
  if (oldValue === true && newValue === false) {
    //console.log('close变量从true变为了false');
    resetForm();
  }
});

const close = () => {
 // resetForm()
  emit('close');
}

const confirm = () => {
  // 检查是否正在上传
  if (uploading.value) {
    appAlert('请等待文件上传完成')
    return
  }
  
  // 检查必填字段
  if (!formData.title && !formData.packageName && !formData.url) {
    appAlert('请至少填写应用标题、包名或URL中的一个')
    return
  }
  
  // 检查账号密码是否为空
  for (let i = 0; i < formData.accounts.length; i++) {
    const account = formData.accounts[i];
    if (!account.username && !account.password) {
      appAlert(`账号${i + 1}的用户名和密码不能都为空`)
      return
    }
  }
  
  emit('confirm', { ...formData })
  //resetForm()
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

.url-input-group {
  display: flex;
  gap: 10px;
}

.url-input-group .form-input {
  flex: 1;
}

.format-btn {
  padding: 10px 15px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.icon-preview {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 10px 0;
  font-weight: bold;
}

.icon-preview img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.icon-buttons {
  display: flex;
  gap: 10px;
  margin-top: 10px;
}

.select-icon-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.clear-icon-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.account-section {
  background: #f9f9f9;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 15px;
}

.account-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.account-title {
  font-weight: bold;
}

.remove-account-btn {
  padding: 5px 10px;
  border: 1px solid #dc3545;
  background: #dc3545;
  color: white;
  border-radius: 4px;
  cursor: pointer;
}

.remove-account-btn:hover {
  opacity: 0.8;
}

.add-more-accounts {
  text-align: center;
  margin: 20px 0;
}

.hint {
  color: #666;
  font-size: 14px;
  margin-bottom: 10px;
}

.add-account-btn {
  padding: 10px 20px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
}

.upload-status {
  margin-top: 5px;
  font-size: 14px;
  color: #007bff;
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

.package-input-group {
  display: flex;
  gap: 10px;
}

.package-input-group .form-input {
  flex: 1;
}

@media (max-width: 600px) {
  .url-input-group,
  .package-input-group,
  .icon-buttons {
    flex-direction: column;
  }
}

/*阻止用户复制控件*/
label,img,h2,button{
  -webkit-user-select: none; /* Chrome, Opera, Safari */
  -moz-user-select: none;    /* Firefox */
  -ms-user-select: none;     /* Internet Explorer/Edge */
  user-select: none;         /* Standard */
}
</style>