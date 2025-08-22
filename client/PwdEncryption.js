// 检查当前环境是否为Node.js环境,不需要判断window对象
const isNodeEnvironment = typeof process !== 'undefined';

// 只在Node.js环境中导入crypto模块
let crypto;
if (isNodeEnvironment) {
    crypto = require('crypto');
}

class PwdEncryption {
    /**
     * 检查当前环境是否为Node.js环境
     * @returns {boolean}
     */
    static checkIsNodeEvn() {
        return isNodeEnvironment;
    }
    static generateSecureSalt(length = 16) {
        const array = new Uint8Array(length);
        window.crypto.getRandomValues(array);
        return Array.from(array)
            .map(b => b.toString(16).padStart(2, '0')) // 转为16进制字符串
            .join('')
            .slice(0, length); // 确保长度一致
    }


    // 固定的IV向量（与C#版本相同）
    static get IV() {
        if (isNodeEnvironment) {
            return Buffer.alloc(16); // 16字节全0的IV
        } else {
            // 浏览器环境下的兼容实现
            return new Uint8Array(16); // 16字节全0的IV
        }
    }

    // 从密码派生密钥
    static getKeyFromPassword(password,salt) {

        return crypto.pbkdf2Sync(
            password,
            salt,
            10000,
            32, // 32 bytes for AES-256
            'sha256'
        );
    }
    static encrypt(strToEncrypt, password){
        let salt = PwdEncryption.generateSecureSalt();
        let encrypted = PwdEncryption._encrypt(strToEncrypt, password, salt);
        if (encrypted==null)return '';
        return salt+encrypted;
    }
    // 加密方法
    static _encrypt(strToEncrypt, password, salt) {
        // 检查当前环境是否为Node.js环境
        // 保证跨平台兼容
        if (!this.checkIsNodeEvn()) {
            // 在非Node.js环境，返回未加密的字符串
            // 兼容浏览器环境
            return strToEncrypt;
        }
        try {

            const key = this.getKeyFromPassword(password,salt);
            const iv = PwdEncryption.IV;

            const cipher = crypto.createCipheriv('aes-256-cbc', key, iv);
            let encrypted = cipher.update(strToEncrypt, 'utf8', 'base64');
            encrypted += cipher.final('base64');

            return encrypted;
        } catch (ex) {
           console.log("加密失败:", ex.message);
           return null;
        }
    }

    static decrypt(strToDecrypt, password) {
        if (strToDecrypt.length < 16){
            throw new Error("解密失败: 输入字符串长度不足");
        }
        try {
            let salt = strToDecrypt.substring(0, 16);
            let decrypted = PwdEncryption._decrypt(strToDecrypt.substring(16), password, salt);
            return decrypted;
        }catch (e) {
            throw new Error("解密失败: " + e.message);
        }
    }
    // 解密方法
    static _decrypt(strToDecrypt, password,salt) {
        // 检查当前环境是否为Node.js环境
        // 保证跨平台兼容
        if (!this.checkIsNodeEvn()) {
            // 在非Node.js环境，返回未加密的字符串
            // 兼容浏览器环境
            return strToDecrypt;
        }
        try {
            const key = this.getKeyFromPassword(password, salt);
            const iv = PwdEncryption.IV;

            const decipher = crypto.createDecipheriv('aes-256-cbc', key, iv);
            let decrypted = decipher.update(strToDecrypt, 'base64', 'utf8');
            decrypted += decipher.final('utf8');

            return decrypted;
        } catch (ex) {
            throw new Error("解密失败: " + ex.message);
        }
    }

    // 测试方法
    static test() {
        const originalString = "这是一个需要加密的敏感信息";
        const userPassword = "mySecurePassword123";

        // 加密
        const encryptedString = PwdEncryption.encrypt(originalString, userPassword);
        console.log("加密后:", encryptedString);

        // 解密
        const decryptedString = PwdEncryption.decrypt(encryptedString, userPassword);
        console.log("解密后:", decryptedString);
    }
}

// 导出类（仅在Node.js环境中）
if (isNodeEnvironment) {
    module.exports = PwdEncryption;
}

// 测试代码
// PwdEncryption.test();