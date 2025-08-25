using System;
using System.IO;
using System.Runtime.InteropServices;
using System.Text.Json;
using System.Text.Json.Nodes;
using System.Windows.Forms;

namespace PasswordLogbookWin
{
    [ClassInterface(ClassInterfaceType.AutoDual)]
    [ComVisible(true)]
    public class WebAppInterface
    {
        private Form form;

        public WebAppInterface(Form form)
        {
            this.form = form;
        }

        public string Version { get; set; } = "1.0";
        public string decrypt(string strToDecrypt, string password)
        {
#if DEBUG

            Console.WriteLine(strToDecrypt);
            Console.WriteLine(password);
#endif
            string result = "";
            try
            {
                result = PwdEncryption.Decrypt(strToDecrypt, password);
                if (result == null) return "";
            }
            catch (Exception e)
            {
                Console.WriteLine(e.Message);
                return "";
            }

            return result;
        }
        public string encrypt(string strToDecrypt, string password)
        {
            try
            {
                string encrypted = PwdEncryption.Encrypt(strToDecrypt, password);
                if (encrypted == null) return "";
                return encrypted;
            }
            catch (Exception e)
            {
                Console.WriteLine(e.Message);
            }
            return "";

        }
        public string downConvertPng(string parseOriginUrl, string parseUrlhost, string uploadAddr)
        {
            try
            {
                var result = WebDownloadUpload.DownConvertPng(
                    parseOriginUrl,
                    parseUrlhost,
                    uploadAddr
                );
                using (JsonDocument jsonDocument = JsonDocument.Parse(result.data))
                {
                    string fileName = jsonDocument.RootElement.GetProperty("fileName").GetString();

                    // 构建新对象
                    var obj = new
                    {
                        title = result.title,
                        fileName = fileName,
                        data = result.data
                    };
                    string json = JsonSerializer.Serialize(obj);
                    Console.WriteLine($"上传结果: {result.data}");
                    Console.WriteLine($"网页标题: {result.title}");
                    return json;
                }

            }
            catch (Exception ex)
            {
                Console.WriteLine($"发生错误: {ex.Message}");
            }

            return JsonSerializer.Serialize(new
            {
                title = "",
                fileName = "",
                data = ""
            });
        }
        public void setHostConfig(string host)
        {
            // 1. 定义要写入的 host 值


            string AppDir = Path.Combine(
               Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "CodeBookClient", "User");

            // 2. 获取当前用户的家目录
            // Environment.GetFolderPath(Environment.SpecialFolder.UserProfile) 
            // 是 C# 中获取用户家目录的标准方法，它对应 Node.js 的 os.homedir()
            string userHome = Environment.GetFolderPath(Environment.SpecialFolder.UserProfile);

            // 3. 构建配置文件夹和文件的完整路径
            // Path.Combine 是 C# 中连接路径的最佳方式，它会自动处理不同操作系统的路径分隔符（\ 或 /）
            string configFile = Path.Combine(AppDir, "host.json");

            // 4. 检查目录是否存在，如果不存在则创建它
            // Directory.Exists() 对应 fs.existsSync()
            // Directory.CreateDirectory() 对应 fs.mkdirSync()
            // 一个非常好的特性是：如果目录已经存在，CreateDirectory 不会做任何事情，也不会报错。
            // 所以我们甚至可以不用先检查是否存在，直接调用它即可。但为了和 Node.js 代码逻辑保持一致，我们加上判断。
            if (!Directory.Exists(AppDir))
            {
                Console.WriteLine($"目录 '{AppDir}' 不存在，正在创建...");
                Directory.CreateDirectory(AppDir);
            }


            // 5. 准备要写入的 JSON 数据
            // 在 C# 中，我们通常先创建一个类或匿名对象来表示数据结构
            var configData = new { host = host }; // 使用匿名对象，属性名会默认为大写开头

            // 6. 将对象序列化为 JSON 字符串
            // JsonSerializer.Serialize 对应 JSON.stringify()
            // JsonWriterOptions.Indented 让输出的 JSON 文件格式化，更易读（可选）
            string jsonString = JsonSerializer.Serialize(configData, new JsonSerializerOptions { WriteIndented = true });
            Console.WriteLine($"准备写入的 JSON 内容:\n{jsonString}");

            // 7. 将 JSON 字符串写入文件
            // File.WriteAllText 对应 fs.writeFileSync()
            // 如果文件已存在，它会被覆盖。如果不存在，则会自动创建。
            Console.WriteLine($"正在将配置写入到 '{configFile}'...");
            File.WriteAllText(configFile, jsonString);

        }
        public string getHostConfig()
        {
            // 1. 获取App主目录
            // Environment.GetFolderPath 是 C# 中获取特殊文件夹路径的标准方法
            string AppDir = Path.Combine(
              Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "CodeBookClient", "User");

            // 2. 构建配置文件路径
            // Path.Combine 是跨平台构建路径的最佳实践，它会自动处理路径分隔符
            string configFile = Path.Combine(AppDir, "host.json");

            // 3. 检查并创建目录
            // Directory.Exists 和 Directory.CreateDirectory 是 C# 的文件系统操作方法
            if (!Directory.Exists(AppDir))
            {
                Directory.CreateDirectory(AppDir);
            }

            // 4. 检查文件是否存在
            if (!File.Exists(configFile))
            {
                Console.WriteLine("配置文件不存在");
                return ""; // 如果文件不存在，返回空字符串
            }

            // 5. 读取文件内容
            string jsonContent = File.ReadAllText(configFile);

            try
            {
                // 6. 直接解析 JSON
                // 使用 'using' 确保 JsonDocument 被正确释放
                using (JsonDocument doc = JsonDocument.Parse(jsonContent))
                {
                    Console.WriteLine("jsonContent:" + jsonContent);
                    // 获取 JSON 的根元素
                    JsonElement root = doc.RootElement;

                    // 尝试获取名为 "host" 的属性
                    // TryGetProperty 是一个安全的方法，如果属性不存在，它不会抛出异常
                    if (root.TryGetProperty("host", out JsonElement hostElement))
                    {
                        // 如果属性存在，获取它的字符串值
                        // GetString() 在元素为 null 或不是字符串时会返回 null
                        return hostElement.GetString() ?? ""; // 使用 null 合并运算符，如果为 null 则返回 ""
                    }
                }
            }
            catch (Exception e)
            {
                Console.WriteLine("json 错误：" + e.Message);
            }
            // 如果 JSON 中没有 "host" 属性，也返回空字符串
            return "";
        }
        public string getConfig(string configure)
        {
            // 1. 获取App主目录
            // Environment.GetFolderPath 是 C# 中获取特殊文件夹路径的标准方法
            string AppDir = Path.Combine(
              Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "PwdLogbookClient", "User");

            // 2. 构建配置文件路径
            // Path.Combine 是跨平台构建路径的最佳实践，它会自动处理路径分隔符
            string configFile = Path.Combine(AppDir, "configure.json");

            // 3. 检查并创建目录
            // Directory.Exists 和 Directory.CreateDirectory 是 C# 的文件系统操作方法
            if (!Directory.Exists(AppDir))
            {
                Directory.CreateDirectory(AppDir);
            }

            // 4. 检查文件是否存在
            if (!File.Exists(configFile))
            {
                Console.WriteLine("配置文件不存在");
                return ""; // 如果文件不存在，返回空字符串
            }

            // 5. 读取文件内容
            string jsonContent = File.ReadAllText(configFile);

            try
            {
                // 6. 直接解析 JSON
                // 使用 'using' 确保 JsonDocument 被正确释放
                using (JsonDocument doc = JsonDocument.Parse(jsonContent))
                {
                    Console.WriteLine("jsonContent:" + jsonContent);
                    // 获取 JSON 的根元素
                    JsonElement root = doc.RootElement;

                    // 尝试获取名为 "host" 的属性
                    // TryGetProperty 是一个安全的方法，如果属性不存在，它不会抛出异常
                    if (root.TryGetProperty(configure, out JsonElement hostElement))
                    {
                        // 如果属性存在，获取它的字符串值
                        // GetString() 在元素为 null 或不是字符串时会返回 null
                        return hostElement.GetString() ?? ""; // 使用 null 合并运算符，如果为 null 则返回 ""
                    }
                }
            }
            catch (Exception e)
            {
                Console.WriteLine("json 错误：" + e.Message);
            }
            // 如果 JSON 中没有 "host" 属性，也返回空字符串
            return "";
        }
        public string setConfig(string configure,string value)
        {
            // 1. 获取App主目录
            // Environment.GetFolderPath 是 C# 中获取特殊文件夹路径的标准方法
            string AppDir = Path.Combine(
              Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "PwdLogbookClient", "User");

            // 2. 构建配置文件路径
            // Path.Combine 是跨平台构建路径的最佳实践，它会自动处理路径分隔符
            string configFile = Path.Combine(AppDir, "configure.json");

            // 3. 检查并创建目录
            // Directory.Exists 和 Directory.CreateDirectory 是 C# 的文件系统操作方法
            if (!Directory.Exists(AppDir))
            {
                Directory.CreateDirectory(AppDir);
            }

            // 4. 检查文件是否存在
            if (!File.Exists(configFile))
            {
                try
                {
                    Console.WriteLine("配置文件不存在");
                    var jsonObject = JsonObject.Create(JsonDocument.Parse("{}").RootElement);
                    jsonObject[configure] = value;
                    File.WriteAllText(configFile, jsonObject.ToJsonString());
                    return value; // 如果文件不存在，返回空字符串
                }catch (Exception e)
                {
                    Console.WriteLine(e.Message);
                }
            }
            else
            {
                try { 
                    string jsonContent = File.ReadAllText(configFile);
                    JsonDocument doc = JsonDocument.Parse(jsonContent);
                    JsonElement root = doc.RootElement;
                    var jsonObject = JsonObject.Create(root.Clone());
                    jsonObject[configure] = value;
                    File.WriteAllText(configFile, jsonObject.ToJsonString());
                    return value;
                }
                catch(Exception e)
                {
                    Console.WriteLine(e.Message);
                }
            }

          
            return "";
        }
        /// <summary>
        /// 接口：结束程序
        /// </summary>
        public void finish()
        {
            this.form.Close();
        }
        /// <summary>
        /// 接口：获取客户端信息
        /// </summary>
        /// <returns></returns>
        public string clientInfo()
        {
            return Readme.getInfo();
        }
     
        /// <summary>
        /// 接口：获取应用uuid
        /// </summary>
        /// <returns></returns>
        public string getUUID()
        {
            return UUID.uuid;
        }
    

    }
}