using System;
using System.Linq;
using System.Security.Cryptography;
using System.Text;

namespace PasswordLogbookWin
{
   public class PwdEncryption
    {

        // 辅助方法：将16进制字符串转换为字节数组
        private static byte[] StringToByteArray(string hex)
        {
            return Enumerable.Range(0, hex.Length)
                             .Where(x => x % 2 == 0)
                             .Select(x => Convert.ToByte(hex.Substring(x, 2), 16))
                             .ToArray();
        }

        /// <summary>
        /// 从密码派生密钥
        /// </summary>
        private static byte[] GetKeyFromPassword(string password, byte[] salt)
        {
            const int iterations = 65536;
            var keyGenerator = new Rfc2898DeriveBytes(password, salt, iterations, HashAlgorithmName.SHA256);
            return keyGenerator.GetBytes(32); // AES-256 需要 32 字节的密钥
        }
        /// <summary>
        /// 加密字符串（自动生成盐值并前置）
        /// </summary>
        public static string Encrypt(string strToEncrypt, string password)
        {
            if (string.IsNullOrEmpty(strToEncrypt))
                return string.Empty;

            string salt = GenerateSecureSalt();
            string encrypted = _Encrypt(strToEncrypt, password, salt);
            return encrypted;
        }
        /// <summary>
        /// 加密字符串
        /// </summary>
        /// <param name="strToEncrypt">要加密的原始字符串</param>
        /// <param name="password">用户密码</param>
        /// <param name="salt">16进制的盐字符串</param>
        /// <returns>按照 [干扰]+[IV]+[盐]+[密文] 格式组装的最终字符串</returns>
        private static string _Encrypt(string strToEncrypt, string password, string salt)
        {
            try
            {
                // 1. 准备密钥和原始数据
                byte[] saltBytes = StringToByteArray(salt);
                byte[] keyBytes = GetKeyFromPassword(password, saltBytes);
                byte[] plainBytes = Encoding.UTF8.GetBytes(strToEncrypt);

                // 2. 生成随机的 IV (16字节)
                byte[] ivBytes = new byte[16];
                using (var rng = RandomNumberGenerator.Create())
                {
                    rng.GetBytes(ivBytes);
                }

                // 3. 执行 AES 加密
                byte[] encryptedBytes;
                using (var aes = Aes.Create())
                {
                    aes.Key = keyBytes;
                    aes.IV = ivBytes;
                    aes.Mode = CipherMode.CBC;
                    aes.Padding = PaddingMode.PKCS7;

                    ICryptoTransform encryptor = aes.CreateEncryptor(aes.Key, aes.IV);
                    encryptedBytes = encryptor.TransformFinalBlock(plainBytes, 0, plainBytes.Length);
                }

                // 4. 按照自定义格式组装数据
                // a. 生成 [密码长度的随机干扰字符串]
                string prefix = GenerateSecureRandomPrefix(password.Length);

                // b. 将 IV 和盐转换为 32位的16进制字符串
                string ivHex = BitConverter.ToString(ivBytes).Replace("-", "");

                string saltHex = salt;

                ivHex = RandomizeCase(ivHex);//确保iv的字符串是随机大小写
                saltHex = RandomizeCase(saltHex);//确保盐的字符串是随机大小写

                // c. 将加密后的密文转换为Base64字符串
                string cipherTextBase64 = Convert.ToBase64String(encryptedBytes);

                // d. 拼接所有部分
                return $"{prefix}{ivHex}{saltHex}{cipherTextBase64}";
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Encryption Error: {ex.Message}");
                return null;
            }
        }
        /// <summary>
        /// 生成一个指定长度的、密码学安全的随机字符串前缀
        /// </summary>
        /// <param name="length">前缀的长度</param>
        /// <returns>随机字符串</returns>
        private static string GenerateSecureRandomPrefix(int length)
        {
            if (length <= 0)
            {
                return string.Empty;
            }

            const string chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
            var result = new StringBuilder(length);
            var randomBytes = new byte[length];

            // 使用密码学安全的随机数生成器
            using (var rng = RandomNumberGenerator.Create())
            {
                // 生成一串随机字节
                rng.GetBytes(randomBytes);
            }

            // 将每个随机字节映射到 chars 字符串中的一个字符
            for (int i = 0; i < length; i++)
            {
                // 使用 % 运算符确保索引在 chars 范围内
                result.Append(chars[randomBytes[i] % chars.Length]);
            }

            return result.ToString();
        }

        /// <summary>
        /// 解密字符串
        /// </summary>
        /// <param name="strToDecrypt">待解密的完整字符串</param>
        /// <param name="password">用户密码</param>
        /// <returns>解密后的原始字符串</returns>
        public static string Decrypt(string strToDecrypt, string password)
        {
            try
            {
                // 1. 拆分自定义格式的数据
                // a. 去除 [密码长度的随机干扰字符串]
                if (string.IsNullOrEmpty(strToDecrypt) || strToDecrypt.Length <= password.Length)
                {
                    throw new ArgumentException("Invalid encrypted string format or length.");
                }
                string dataStr = strToDecrypt.Substring(password.Length);

                // b. 提取 [32位的IV] 和 [32位的盐]
                if (dataStr.Length < 64) // IV(32) + Salt(32) = 64
                {
                    throw new ArgumentException("Invalid encrypted string format. Missing IV or Salt.");
                }
                //必须都转换为大写
                string ivHex = dataStr.Substring(0, 32).ToUpper();
                string saltHex = dataStr.Substring(32, 32).ToUpper();

                // c. 提取 [加密的密文]
                string cipherTextBase64 = dataStr.Substring(64);

                // 2. 将各部分转换回字节数组
                byte[] ivBytes = StringToByteArray(ivHex);
                byte[] saltBytes = StringToByteArray(saltHex);
                byte[] encryptedBytes = Convert.FromBase64String(cipherTextBase64);

                // 3. 从密码派生密钥
                byte[] keyBytes = GetKeyFromPassword(password, saltBytes);

                // 4. 执行 AES 解密
                byte[] decryptedBytes;
                using (var aes = Aes.Create())
                {
                    aes.Key = keyBytes;
                    aes.IV = ivBytes;
                    aes.Mode = CipherMode.CBC;
                    aes.Padding = PaddingMode.PKCS7;

                    ICryptoTransform decryptor = aes.CreateDecryptor(aes.Key, aes.IV);
                    decryptedBytes = decryptor.TransformFinalBlock(encryptedBytes, 0, encryptedBytes.Length);
                }

                return Encoding.UTF8.GetString(decryptedBytes);
            }
            catch (FormatException ex)
            {
                Console.WriteLine($"Decryption Format Error: {ex.Message} (可能是Base64或16进制转换失败)");
                return null;
            }
            catch (CryptographicException ex)
            {
                Console.WriteLine($"Decryption Crypto Error: {ex.Message} (可能是密码错误、IV或Salt不匹配)");
                return null;
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Decryption Error: {ex.Message}");
                return null;
            }
        }

        /// <summary>
        /// 生成安全的随机盐值（16进制字符串）
        /// </summary>
        public static string GenerateSecureSalt(int length = 16)
        {
            byte[] saltBytes = new byte[length];
            using (var rng = RandomNumberGenerator.Create())
            {
                rng.GetBytes(saltBytes);
            }
            // 确保输出为32位大写的16进制字符串
            return BitConverter.ToString(saltBytes).Replace("-", "");
        }
        private static readonly Random _random = new Random();

        /// <summary>
        /// 将输入字符串中的所有字母字符随机转换为大写或小写。
        /// 非字母字符（如数字、标点符号、空格）将保持不变。
        /// </summary>
        /// <param name="input">要处理的原始字符串。</param>
        /// <returns>一个新字符串，其中字母的大小写已被随机化。</returns>
        public static string RandomizeCase(string input)
        {
            // 1. 处理 null 或空字符串的边界情况
            if (string.IsNullOrEmpty(input))
            {
                return input;
            }

            // 2. 使用 StringBuilder 高效地构建新字符串
            //    在循环中修改字符串，StringBuilder 的性能远优于直接使用字符串拼接 (+)
            var sb = new StringBuilder(input.Length);

            // 3. 遍历输入字符串中的每一个字符
            foreach (char c in input)
            {
                // 4. 检查当前字符是否为字母
                if (char.IsLetter(c))
                {
                    // 5. 如果是字母，则随机决定其大小写
                    //    _random.Next(3) 会返回 0 或 1，3
                    //    如果结果 <= 1，我们将其转为小写；如果为 其他，则转为大写
                    // 这样做能提高小写的概率
                    if (_random.Next(3) <= 1)
                    {
                        sb.Append(char.ToLower(c));
                    }
                    else
                    {
                        sb.Append(char.ToUpper(c));
                    }
                }
                else
                {
                    // 6. 如果不是字母，则直接追加到结果中，不做任何改变
                    sb.Append(c);
                }
            }

            // 7. 返回最终构建好的字符串
            return sb.ToString();
        }

        /// <summary>
        /// 测试加密与解密
        /// </summary>
        // --- 测试代码 ---
        public static void Test()
        {
            string originalString = "This is a secret message to be encrypted with a custom format.";
            string userPassword = "mySuperSecretPassword123";

            // 1. 生成一个盐 (在实际应用中，这个盐需要和用户账户一起存储)
            string salt = GenerateSecureSalt();
            Console.WriteLine("生成的盐 (Salt): " + salt);

            // 2. 加密
            Console.WriteLine("\n--- 加密过程 ---");
            string encryptedString = _Encrypt(originalString, userPassword, salt);
            Console.WriteLine("原始字符串: " + originalString);
            Console.WriteLine("加密后 (完整格式): " + encryptedString);

            // 3. 解密
            Console.WriteLine("\n--- 解密过程 ---");
            // 注意：解密函数现在只需要完整的加密字符串和密码
            string decryptedString = Decrypt(encryptedString, userPassword);
            Console.WriteLine("解密后: " + decryptedString);

            // 验证
            Console.WriteLine($"\n解密是否成功: {originalString == decryptedString}");

            // --- 测试错误情况 ---
            Console.WriteLine("\n--- 测试错误密码 ---");
            string wrongPassword = "thisIsAWrongPassword";
            string failedDecryption = Decrypt(encryptedString, wrongPassword);
            Console.WriteLine("使用错误密码解密结果: " + (failedDecryption ?? "NULL (解密失败，符合预期)"));
        }
    }
}