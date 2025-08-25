package app.passwordlogbook;

import android.os.Build;

import java.nio.charset.StandardCharsets;
import java.security.SecureRandom;
import java.security.spec.KeySpec;
import java.util.Base64;
import java.util.Random;

import javax.crypto.Cipher;
import javax.crypto.SecretKey;
import javax.crypto.SecretKeyFactory;
import javax.crypto.spec.IvParameterSpec;
import javax.crypto.spec.PBEKeySpec;
import javax.crypto.spec.SecretKeySpec;

public class PwdEncryption {

    // 辅助方法：将16进制字符串转换为字节数组
    private static byte[] stringToByteArray(String hex) {
        if (hex == null || hex.length() % 2 != 0) {
            throw new IllegalArgumentException("Invalid hex string");
        }
        byte[] bytes = new byte[hex.length() / 2];
        for (int i = 0; i < hex.length(); i += 2) {
            bytes[i / 2] = (byte) Integer.parseInt(hex.substring(i, i + 2), 16);
        }
        return bytes;
    }

    /// <summary>
    /// 从密码派生密钥
    /// </summary>
    private static byte[] getKeyFromPassword(String password, byte[] salt) throws Exception {
        final int iterations = 65536;
        final int keyLength = 32; // AES-256 需要 32 字节的密钥
        SecretKeyFactory factory = SecretKeyFactory.getInstance("PBKDF2WithHmacSHA256");
        KeySpec spec = new PBEKeySpec(password.toCharArray(), salt, iterations, keyLength * 8); // keyLength in bits
        SecretKey tmp = factory.generateSecret(spec);
        return tmp.getEncoded();
    }

    /// <summary>
    /// 加密字符串（自动生成盐值并前置）
    /// </summary>
    public static String encrypt(String strToEncrypt, String password) {
        if (strToEncrypt == null || strToEncrypt.isEmpty()) {
            return "";
        }
        String salt = generateSecureSalt();
        return _encrypt(strToEncrypt, password, salt);
    }


    /**
     * 加密字符串
     * @param strToEncrypt 要加密的原始字符串
     * @param password 用户密码
     * @param salt 16进制的盐字符串
     * @return 按照 [干扰]+[IV]+[盐]+[密文]
     */
    private static String _encrypt(String strToEncrypt, String password, String salt) {
        try {
            // 1. 准备密钥和原始数据
            byte[] saltBytes = stringToByteArray(salt);
            byte[] keyBytes = getKeyFromPassword(password, saltBytes);
            byte[] plainBytes = strToEncrypt.getBytes(StandardCharsets.UTF_8);

            // 2. 生成随机的 IV (16字节)
            byte[] ivBytes = new byte[16];
            SecureRandom secureRandom = new SecureRandom();
            secureRandom.nextBytes(ivBytes);

            // 3. 执行 AES 加密
            Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
            SecretKeySpec secretKey = new SecretKeySpec(keyBytes, "AES");
            IvParameterSpec ivSpec = new IvParameterSpec(ivBytes);
            cipher.init(Cipher.ENCRYPT_MODE, secretKey, ivSpec);
            byte[] encryptedBytes = cipher.doFinal(plainBytes);

            // 4. 按照自定义格式组装数据
            // a. 生成 [密码长度的随机干扰字符串]
            String prefix = generateSecureRandomPrefix(password.length());

            // b. 将 IV 和盐转换为 32位的16进制字符串
            String ivHex = printHexBinary(ivBytes);
            String saltHex = salt;

            ivHex = randomizeCase(ivHex); // 确保iv的字符串是随机大小写
            saltHex = randomizeCase(saltHex); // 确保盐的字符串是随机大小写

            // c. 将加密后的密文转换为Base64字符串
            String cipherTextBase64 = null;
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                cipherTextBase64 = Base64.getEncoder().encodeToString(encryptedBytes);
            }else {
                cipherTextBase64 = android.util.Base64.encodeToString(encryptedBytes, android.util.Base64.DEFAULT);
            }

            // d. 拼接所有部分
            return prefix + ivHex + saltHex + cipherTextBase64;
        } catch (Exception ex) {
            System.err.println("Encryption Error: " + ex.getMessage());
            ex.printStackTrace();
            return null;
        }
    }

    /// <summary>
    /// 生成一个指定长度的、密码学安全的随机字符串前缀
    /// </summary>
    /// <param name="length">前缀的长度</param>
    /// <returns>随机字符串</returns>
    private static String generateSecureRandomPrefix(int length) {
        if (length <= 0) {
            return "";
        }
        final String chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        StringBuilder result = new StringBuilder(length);
        SecureRandom rng = new SecureRandom();
        for (int i = 0; i < length; i++) {
            result.append(chars.charAt(rng.nextInt(chars.length())));
        }
        return result.toString();
    }

    /// <summary>
    /// 解密字符串
    /// </summary>
    /// <param name="strToDecrypt">待解密的完整字符串</param>
    /// <param name="password">用户密码</param>
    /// <returns>解密后的原始字符串</returns>
    public static String decrypt(String strToDecrypt, String password) {
        try {
            // 1. 拆分自定义格式的数据
            // a. 去除 [密码长度的随机干扰字符串]
            if (strToDecrypt == null || strToDecrypt.isEmpty() || strToDecrypt.length() <= password.length()) {
                throw new IllegalArgumentException("Invalid encrypted string format or length.");
            }
            String dataStr = strToDecrypt.substring(password.length());

            // b. 提取 [32位的IV] 和 [32位的盐]
            if (dataStr.length() < 64) { // IV(32) + Salt(32) = 64
                throw new IllegalArgumentException("Invalid encrypted string format. Missing IV or Salt.");
            }
            // 必须都转换为大写
            String ivHex = dataStr.substring(0, 32).toUpperCase();
            String saltHex = dataStr.substring(32, 64).toUpperCase();

            System.out.println("解析的盐："+saltHex);
            System.out.println("解析的IV："+ivHex);

            // c. 提取 [加密的密文]
            String cipherTextBase64 = dataStr.substring(64);

            // 2. 将各部分转换回字节数组
            byte[] ivBytes = stringToByteArray(ivHex);
            byte[] saltBytes = stringToByteArray(saltHex);
            byte[] encryptedBytes = null;
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                encryptedBytes = Base64.getDecoder().decode(cipherTextBase64);
            }else {
                encryptedBytes = android.util.Base64.decode(cipherTextBase64,android.util.Base64.DEFAULT);
            }

            // 3. 从密码派生密钥
            byte[] keyBytes = getKeyFromPassword(password, saltBytes);

            // 4. 执行 AES 解密
            Cipher cipher = Cipher.getInstance("AES/CBC/PKCS5Padding");
            SecretKeySpec secretKey = new SecretKeySpec(keyBytes, "AES");
            IvParameterSpec ivSpec = new IvParameterSpec(ivBytes);
            cipher.init(Cipher.DECRYPT_MODE, secretKey, ivSpec);
            byte[] decryptedBytes = cipher.doFinal(encryptedBytes);

            return new String(decryptedBytes, StandardCharsets.UTF_8);
        } catch (IllegalArgumentException ex) {
            System.err.println("Decryption Format Error: " + ex.getMessage() + " (可能是Base64或16进制转换失败)");
            return null;
        } catch (Exception ex) {
            System.err.println("Decryption Crypto Error: " + ex.getMessage() + " (可能是密码错误、IV或Salt不匹配)");
            return null;
        }
    }

    /// <summary>
    /// 生成安全的随机盐值（16进制字符串）
    /// </summary>
    public static String generateSecureSalt(int length) {
        SecureRandom secureRandom = new SecureRandom();
        byte[] saltBytes = new byte[length];
        secureRandom.nextBytes(saltBytes);
        // 确保输出为大写的16进制字符串
        return printHexBinary(saltBytes);
    }

    public static String generateSecureSalt() {
        return generateSecureSalt(16); // 默认16字节
    }

    private static final Random _random = new Random();

    /// <summary>
    /// 将输入字符串中的所有字母字符随机转换为大写或小写。
    /// 非字母字符（如数字、标点符号、空格）将保持不变。
    /// </summary>
    /// <param name="input">要处理的原始字符串。</param>
    /// <returns>一个新字符串，其中字母的大小写已被随机化。</returns>
    public static String randomizeCase(String input) {
        if (input == null || input.isEmpty()) {
            return input;
        }

        StringBuilder sb = new StringBuilder(input.length());

        for (char c : input.toCharArray()) {
            if (Character.isLetter(c)) {
                // 模拟 C# 的 _random.Next(3) <= 1 逻辑
                if (_random.nextInt(3) <= 1) {
                    sb.append(Character.toLowerCase(c));
                } else {
                    sb.append(Character.toUpperCase(c));
                }
            } else {
                sb.append(c);
            }
        }
        return sb.toString();
    }
    /**
     * 手动实现 DatatypeConverter.printHexBinary 的功能。
     * 将字节数组转换为大写的16进制字符串。
     *
     * @param bytes 要转换的字节数组
     * @return 大写的16进制字符串
     */
    public static String printHexBinary(byte[] bytes) {
        if (bytes == null) {
            return null;
        }

        // 1. 创建一个 StringBuilder，预分配足够的空间，以提高性能。
        //    每个字节会变成2个16进制字符，所以总长度是 bytes.length * 2。
        final StringBuilder hexString = new StringBuilder(bytes.length * 2);

        // 2. 定义一个包含16进制所有字符的字符串。
        //    使用大写字母 A-F，以匹配 DatatypeConverter 的行为。
        final String hexDigits = "0123456789ABCDEF";

        // 3. 遍历输入字节数组中的每一个字节。
        for (byte b : bytes) {
            // 4. 获取字节的高4位。
            //    Java 的 byte 是有符号的（-128 到 127）。
            //    (b >> 4) 将字节向右移动4位，得到高4位。
            //    & 0x0F (即二进制 00001111) 的作用是清除掉可能存在的符号位扩展，
            //    确保我们得到的是一个 0-15 之间的无符号整数。
            int highNibble = (b >> 4) & 0x0F;
            // 5. 从 hexDigits 字符串中查找对应的字符，并追加到 StringBuilder。
            hexString.append(hexDigits.charAt(highNibble));

            // 6. 获取字节的低4位。
            //    b & 0x0F 直接保留低4位，清除高4位。
            int lowNibble = b & 0x0F;
            // 7. 从 hexDigits 字符串中查找对应的字符，并追加到 StringBuilder。
            hexString.append(hexDigits.charAt(lowNibble));
        }

        // 8. 返回最终的16进制字符串。
        return hexString.toString();
    }

    /// <summary>
    /// 测试加密与解密
    /// </summary>
    public static void test() {
        String originalString = "This is a secret message to be encrypted with a custom format.";
        String userPassword = "mySuperSecretPassword123";

        // 1. 生成一个盐
        String salt = generateSecureSalt();
        System.out.println("生成的盐 (Salt): " + salt);

        // 2. 加密
        System.out.println("\n--- 加密过程 ---");
        String encryptedString = _encrypt(originalString, userPassword, salt);
        System.out.println("原始字符串: " + originalString);
        System.out.println("加密后 (完整格式): " + encryptedString);

        // 3. 解密
        System.out.println("\n--- 解密过程 ---");
        String decryptedString = decrypt(encryptedString, userPassword);
        System.out.println("解密后: " + decryptedString);

        // 验证
        System.out.println("\n解密是否成功: " + originalString.equals(decryptedString));

        // --- 测试错误情况 ---
        System.out.println("\n--- 测试错误密码 ---");
        String wrongPassword = "thisIsAWrongPassword";
        String failedDecryption = decrypt(encryptedString, wrongPassword);
        System.out.println("使用错误密码解密结果: " + (failedDecryption != null ? failedDecryption : "NULL (解密失败，符合预期)"));

        System.out.println("C#解密后: " + decrypt("RrJnbaUSCH4Wwa5DEGaQJuXZ3deb4baFDa07b169a9ef634146a9C50f1FC2dd72ad5617ad40bb7627511560feVWYE93JbbwcYkkog0KXEhrVW52S1cTTDxcfTfoW8RPSmnEObdWOtl8iXCWnvEdjSjFp5jVD3O1V0oq4637XNIw==","mySuperSecretPassword123"));
    }

    public static void main(String[] args) {
        test();
    }
}
