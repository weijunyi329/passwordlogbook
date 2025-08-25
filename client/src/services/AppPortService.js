// 密码管理API服务

import {GlobalObjVar} from "@/global.js";
import {client_function} from "@/services/client_function.js";


class AppPortService {
  /**
   * 获取密码项
   * @param host
   * @returns {Promise<unknown>}
   */
  async getPasswordItems(host) {

    return new Promise((resolve, reject) => {
      fetch(`${host}/getAll`, {
        method: 'GET',
        headers: {
          'UUID': GlobalObjVar.UUID,
          'Content-Type': 'application/json',
        }
      })
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then( data => {

        // 确保数据格式正确
        if (Array.isArray(data)) {
          // 处理每个项目，确保accounts字段正确
          for (let i = 0; i < data.length; i++) {

            if (data[i].title === '') {
              if (data[i].url !== ''){
                data[i].title = data[i].url;
              }else if (data[i].packageName !== ''){
                data[i].title = data[i].packageName;
              }
            }
            //item.accounts是字符串
            if (typeof data[i].accounts === 'string' && data[i].accounts !== '') {
              // 检查是否支持加密，如果是则尝试解密，否则直接解析JSON字符串
              if (client_function.checkEncryptSupport()) {
                try {
                  let decryptedAccounts = '';
                  decryptedAccounts = client_function.decrypt(data[i].accounts, GlobalObjVar.password);
                  data[i].accounts = JSON.parse(decryptedAccounts);
                } catch (decryptError) {
                  console.error('Decryption or parsing of JSON failed:', decryptError);
                  GlobalObjVar.errorFlag = true;
                  data[i].accounts = [];
                }
              } else {
                //尝试解析accounts字段，如果它是字符串则尝试解析为JSON
                try {
                  data[i].accounts = JSON.parse(data[i].accounts);
                } catch (parseError) {
                  console.log('Failed to parse accounts JSON:', parseError);
                  data[i].accounts = [];
                }
              }
            }
          }

          const processedData = data;
          console.log(processedData);
          resolve(processedData);
        } else {
          // 如果返回的数据不是数组，直接返回空数组
          resolve([]);
        }
      })
      .catch(error => {
        console.error('Error fetching password items:', error);
        reject(error);
      });
    });
  }


  /**
   * 添加密码记录项到数据库
   * @param item
   * @param host
   * @returns {Promise<unknown>}
   */
  async addPasswordItem(item, host) {
    // 使用新的HOST/addNew接口
    return new Promise((resolve, reject) => {
      // 准备发送到新接口的数据
      const formData = new FormData();
      
      // 根据PHP代码要求设置字段名
      formData.append('title', item.title || '');
      formData.append('url', item.url || '');
      formData.append('packageName', item.packageName || '');
      formData.append('icon', item.icon || '');
      formData.append('remark', item.remark || '');
      formData.append('modification', ''); // 这个字段在当前界面中未使用
      //在每个密码项插入当前时间戳
      item.accounts.forEach(account => {
          account.t=Date.now();
      });
      // 将accounts字段转换为JSON字符串
      let accounts = JSON.stringify(item.accounts || []);
      // 检查是否支持加密，如果是则加密
      if (client_function.checkEncryptSupport()){
        accounts=client_function.encrypt(accounts, GlobalObjVar.password);
      }
      formData.append('accounts', accounts);
      
      fetch(`${host}/addNew`, {
        method: 'POST',
        headers: {
          'UUID': GlobalObjVar.UUID,
        },
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(errorData => {
            throw new Error(errorData.error || '添加失败');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // 添加成功，返回完整对象
          const newItem = {
            ...item,
            id: Date.now() // 在实际实现中，应该由服务器返回ID
          };
          resolve(newItem);
        } else {
          reject(new Error(data.error || '添加失败'));
        }
      })
      .catch(error => {
        reject(error);
      });
    });
  }

  /**
   * 更新密码记录项到数据库
   * @param item
   * @param host
   * @returns {Promise<unknown>}
   */
  async updatePasswordItem(item, host) {
    return new Promise((resolve, reject) => {
      // 准备发送到新接口的数据
      const formData = new FormData();
      
      // 根据PHP代码要求设置字段名
      formData.append('id', item.id || '');
      formData.append('title', item.title || '');
      formData.append('url', item.url || '');
      formData.append('packageName', item.packageName || '');
      formData.append('icon', item.icon || '');
      formData.append('remark', item.remark || '');
      formData.append('modification', Date.now()); // 使用当前时间戳
      //在每个密码项插入当前时间戳
      item.accounts.forEach(account => {
        account.t=Date.now();
      });
      // 将accounts字段转换为JSON字符串
      let accounts = JSON.stringify(item.accounts || []);
      // 检查是否支持加密，如果是则加密
      if (client_function.checkEncryptSupport()){
        accounts=client_function.encrypt(accounts, GlobalObjVar.password);
      }
      formData.append('accounts', accounts);
      
      fetch(`${host}/update`, {
        method: 'POST',
        headers: {
          'UUID': GlobalObjVar.UUID,
        },
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(errorData => {
            throw new Error(errorData.error || '更新失败');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          // 更新成功，返回完整对象
          resolve(item);
        } else {
          reject(new Error(data.error || '更新失败'));
        }
      })
      .catch(error => {
        reject(error);
      });
    });
  }

  /**
   * 删除密码记录项
   * @param id
   * @param host
   * @returns {Promise<unknown>}
   */
  async deletePasswordItem(id, host) {
    return new Promise((resolve, reject) => {
      fetch(`${host}/delete?id=${id}`, {
        method: 'GET', // 根据PHP代码，这里使用GET方法
        headers: {
        }
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(errorData => {
            throw new Error(errorData.error || '删除失败');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          resolve({ id });
        } else {
          reject(new Error(data.error || '删除失败'));
        }
      })
      .catch(error => {
        reject(error);
      });
    });
  }

  /**
   * 重置密码，重新加密所有记录项的accounts字段，并上传
   * @param host
   * @param newPassword
   * @returns {Promise<unknown>}
   */
  async resetPassword(host,newPassword) {
    return new Promise(async (resolve, reject) => {
      try {
        // 获取所有密码项
        const response = await fetch(`${host}/getAll`, {
          method: 'GET',
          headers: {
            'UUID': GlobalObjVar.UUID,
            'Content-Type': 'application/json'
          }
        });

        if (!response.ok) {
          throw new Error(`获取数据失败: ${response.status}`)
        }
        const items = await response.json()
        // 准备重置数据
        const resetData = items.map(item => {
          // 加密accounts字段
          let encryptedAccounts = ''
          if (item.accounts && item.accounts.length > 0) {
            try {
              // 检查是否支持加密，如果是则加密
              if (client_function.checkEncryptSupport()){
                encryptedAccounts = client_function.decrypt(item.accounts,GlobalObjVar.password);
                encryptedAccounts = client_function.encrypt(encryptedAccounts, newPassword);
              }
            } catch (e) {
              console.error('加密失败:', e)
              throw new Error('加密失败');
            }
          }
          return {
            id: item.id,
            accounts: encryptedAccounts
          }
        })
        // 发送到重置接口
        const resetResponse = await fetch(`${host}/resetpwd`, {
          method: 'POST',
          headers: {
            'UUID': GlobalObjVar.UUID,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(resetData)
        });
        if (!resetResponse.ok) {
          throw new Error(`重置密码失败: ${resetResponse.status}`)
        }
        const result = await resetResponse.json();
        if (result.success) {
          resolve(result);
        }else {
          reject(new Error(result.error || '重置密码失败'));
        }
      }catch (error) {
        reject(error);
      }
    });
  }

  /**
   * 检查服务器是否启动,是否允许访问
   * @param host
   * @returns {Promise<unknown>}
   */
   checkServer(host) {
    return new Promise((resolve, reject) => {
      fetch(`${host}/check`, {
        method: 'GET',
        headers: {
          'UUID': GlobalObjVar.UUID
        },
        timeout: 5000 // 设置超时时间
      })
      .then(response => {
        if (!response.ok) {
          return response.json().then(errorData => {
            throw new Error(errorData.error || '服务器未启动');
          }).catch(() => {
            throw new Error('服务器未启动');
          });
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          resolve(data);
        } else {
          reject(new Error(data.error || '服务器未启动'));
        }
      })
      .catch(error => {
        reject(error);
      });
    });
  }

  static isValidURL(url) {
    try {
      new URL(url);
      return true;
    } catch (error) {
      return false;
    }
  }

  isValidURL(url) {
    try {
      new URL(url);
      return true;
    } catch (error) {
      return false;
    }
  }


}

export default new AppPortService();