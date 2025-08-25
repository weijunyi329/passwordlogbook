const crypto = require('crypto');

// Node.js 中没有内置的 StringBuilder，但对于这种操作，直接字符串拼接性能已经足够好。
// 如果追求极致性能，可以使用数组 join 的方式。

/**
 * 辅助方法：将16进制字符串转换为Buffer (等同于C#的byte[])
 * @param {string} hex - 16进制字符串
 * @returns {Buffer}
 */
function stringToBuffer(hex) {
    // 确保输入是有效的16进制字符串
    if (!/^[0-9A-Fa-f]+$/.test(hex)) {
        throw new Error("Invalid hex string.");
    }
    return Buffer.from(hex, 'hex');
}

/**
 * 从密码派生密钥
 * @param {string} password - 用户密码
 * @param {Buffer} salt - 盐值的Buffer
 * @returns {Buffer} - 32字节的AES密钥
 */
function getKeyFromPassword(password, salt) {
    const iterations = 65536;
    const keyLength = 32; // AES-256 需要 32 字节的密钥
    const digest = 'sha256'; // 对应 HashAlgorithmName.SHA256

    return crypto.pbkdf2Sync(password, salt, iterations, keyLength, digest);
}

/**
 * 生成安全的随机盐值（16进制字符串）
 * @param {number} [length=16] - 盐值的字节长度
 * @returns {string} - 大写的16进制字符串
 */
function generateSecureSalt(length = 16) {
    const saltBytes = crypto.randomBytes(length);
    return saltBytes.toString('hex').toUpperCase();
}

/**
 * 生成一个指定长度的、密码学安全的随机字符串前缀
 * @param {number} length - 前缀的长度
 * @returns {string} - 随机字符串
 */
function generateSecureRandomPrefix(length) {
    if (length <= 0) {
        return "";
    }
    const chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    const result = [];
    const randomBytes = crypto.randomBytes(length);

    for (let i = 0; i < length; i++) {
        // 将随机字节映射到 chars 字符串中的一个字符
        result.push(chars[randomBytes[i] % chars.length]);
    }

    return result.join('');
}

/**
 * 将输入字符串中的所有字母字符随机转换为大写或小写。
 * @param {string} input - 要处理的原始字符串。
 * @returns {string} - 一个新字符串，其中字母的大小写已被随机化。
 */
function randomizeCase(input) {
    if (!input) {
        return input;
    }
    let result = '';
    for (const c of input) {
        if (/[a-zA-Z]/.test(c)) {
            // Math.random() < 0.5 有50%的概率转为小写，否则大写
            // C#代码中 _random.Next(3) <= 1 概率是 2/3，这里我们调整为50%更直观
            result += Math.random() < 0.5 ? c.toLowerCase() : c.toUpperCase();
        } else {
            result += c;
        }
    }
    return result;
}

/**
 * 私有方法：执行核心加密逻辑
 * @param {string} strToEncrypt - 要加密的原始字符串
 * @param {string} password - 用户密码
 * @param {string} salt - 16进制的盐字符串
 * @returns {string} - 按照 [干扰]+[IV]+[盐]+[密文] 格式组装的最终字符串
 */
function _encrypt(strToEncrypt, password, salt) {
    try {
        // 1. 准备密钥和原始数据
        const saltBuffer = stringToBuffer(salt);
        const keyBuffer = getKeyFromPassword(password, saltBuffer);
        const plainBuffer = Buffer.from(strToEncrypt, 'utf8');

        // 2. 生成随机的 IV (16字节)
        const ivBuffer = crypto.randomBytes(16);

        // 3. 执行 AES 加密
        const cipher = crypto.createCipheriv('aes-256-cbc', keyBuffer, ivBuffer);
        let encryptedBuffer = cipher.update(plainBuffer);
        encryptedBuffer = Buffer.concat([encryptedBuffer, cipher.final()]);

        // 4. 按照自定义格式组装数据
        // a. 生成 [密码长度的随机干扰字符串]
        const prefix = generateSecureRandomPrefix(password.length);

        // b. 将 IV 和 盐转换为 32位的16进制字符串并随机化大小写
        let ivHex = ivBuffer.toString('hex');
        let saltHex = salt; // salt 本身就是大写的16进制

        ivHex = randomizeCase(ivHex);
        saltHex = randomizeCase(saltHex);

        // c. 将加密后的密文转换为Base64字符串
        const cipherTextBase64 = encryptedBuffer.toString('base64');

        // d. 拼接所有部分
        return `${prefix}${ivHex}${saltHex}${cipherTextBase64}`;
    } catch (ex) {
        console.error(`Encryption Error: ${ex.message}`);
        return null;
    }
}

/**
 * 加密字符串（自动生成盐值并前置）
 * @param {string} strToEncrypt - 要加密的原始字符串
 * @param {string} password - 用户密码
 * @returns {string} - 加密后的完整字符串
 */
function encrypt(strToEncrypt, password) {
    if (!strToEncrypt) {
        return "";
    }
    const salt = generateSecureSalt();
    return _encrypt(strToEncrypt, password, salt);
}

/**
 * 解密字符串
 * @param {string} strToDecrypt - 待解密的完整字符串
 * @param {string} password - 用户密码
 * @returns {string} - 解密后的原始字符串
 */
function decrypt(strToDecrypt, password) {
    try {
        // 1. 拆分自定义格式的数据
        if (!strToDecrypt || strToDecrypt.length <= password.length) {
            throw new Error("Invalid encrypted string format or length.");
        }
        const dataStr = strToDecrypt.substring(password.length);

        if (dataStr.length < 64) { // IV(32) + Salt(32) = 64
            throw new Error("Invalid encrypted string format. Missing IV or Salt.");
        }

        // b. 提取 [32位的IV] 和 [32位的盐] 并转为大写
        const ivHex = dataStr.substring(0, 32).toUpperCase();
        const saltHex = dataStr.substring(32, 64).toUpperCase();

        // c. 提取 [加密的密文]
        const cipherTextBase64 = dataStr.substring(64);

        // 2. 将各部分转换回 Buffer
        const ivBuffer = stringToBuffer(ivHex);
        const saltBuffer = stringToBuffer(saltHex);
        const encryptedBuffer = Buffer.from(cipherTextBase64, 'base64');

        // 3. 从密码派生密钥
        const keyBuffer = getKeyFromPassword(password, saltBuffer);

        // 4. 执行 AES 解密
        const decipher = crypto.createDecipheriv('aes-256-cbc', keyBuffer, ivBuffer);
        let decryptedBuffer = decipher.update(encryptedBuffer);
        decryptedBuffer = Buffer.concat([decryptedBuffer, decipher.final()]);

        return decryptedBuffer.toString('utf8');
    } catch (ex) {
        // Node.js 的 crypto 错误通常是 OpenSSLError, 我们可以统一捕获
        console.error(`Decryption Error: ${ex.message} (可能是密码错误、IV或Salt不匹配或格式错误)`);
        return null;
    }
}
function test() {
    const originalString = "This is a secret message to be encrypted with a custom format.";
    const userPassword = "mySuperSecretPassword123";

    console.log("--- Node.js Encryption/Decryption Test ---");

    // 1. 加密
    console.log("\n--- 加密过程 ---");
    const encryptedString = encrypt(originalString, userPassword);
    console.log("原始字符串: " + originalString);
    console.log("加密后 (完整格式): " + encryptedString);

    // 2. 解密
    console.log("\n--- 解密过程 ---");
    const decryptedString = decrypt(encryptedString, userPassword);
    console.log("解密后: " + decryptedString);

    // 验证
    console.log(`\n解密是否成功: ${originalString === decryptedString}`);

    // --- 测试错误情况 ---
    console.log("\n--- 测试错误密码 ---");
    const wrongPassword = "thisIsAWrongPassword";
    const failedDecryption = decrypt(encryptedString, wrongPassword);
    console.log("使用错误密码解密结果: " + (failedDecryption ?? "NULL (解密失败，符合预期)"));
}

// --- 导出模块 ---
// 为了方便使用，我们将所有方法封装在一个对象中导出
module.exports = {
    encrypt,
    decrypt,
    // 如果需要测试，也可以导出测试函数
    _test: test,
};
