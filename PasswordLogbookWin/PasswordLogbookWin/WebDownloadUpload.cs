using System;
using System.Drawing;
using System.Drawing.Imaging;
using System.IO;
using System.Net.Http;
using System.Net.Http.Headers;
using System.Text.RegularExpressions;

namespace PasswordLogbookWin
{
    class WebDownloadUpload
    {
        private static readonly HttpClient httpClient = new HttpClient();
        private static readonly string BrowserUserAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36";

        public static string DownloadFile(string url, string path, int maxBytes, int timeout)
        {
            try
            {
                using (var client = new HttpClient())
                {
                    client.Timeout = TimeSpan.FromMilliseconds(timeout);
                    client.DefaultRequestHeaders.Add("User-Agent", BrowserUserAgent);

                    using (var response = client.GetAsync(url).Result)
                    {
                        response.EnsureSuccessStatusCode();

                        using (var stream = response.Content.ReadAsStreamAsync().Result)
                        using (var memoryStream = new MemoryStream())
                        {
                            byte[] buffer = new byte[8192];
                            int bytesRead;
                            long totalBytes = 0;

                            while ((bytesRead = stream.Read(buffer, 0, buffer.Length)) > 0)
                            {
                                totalBytes += bytesRead;
                                if (totalBytes > maxBytes)
                                {
                                    throw new Exception($"下载超过 {maxBytes / 1024}kB 限制");
                                }
                                memoryStream.Write(buffer, 0, bytesRead);
                            }

                            File.WriteAllBytes(path, memoryStream.ToArray());
                            return path;
                        }
                    }
                }
            }
            catch (Exception ex)
            {
                throw new Exception($"下载文件失败: {ex.Message}", ex);
            }
        }

        public static (string data, string title) DownConvertPng(string parseOriginUrl, string parseUrlhost, string uploadAddr)
        {
            string webtitle = "";
            try
            {
                int maxBytes = 20480; // 20kB
                int timeout = 3000; // 3秒超时
                string userHome = Environment.GetFolderPath(Environment.SpecialFolder.UserProfile);
                parseUrlhost = (parseOriginUrl.StartsWith("https://") ? "https://" : "http://") + parseUrlhost + "/favicon.ico";
                string dirPath = Path.Combine(userHome, ".tmp");
                string icoPath = Path.Combine(dirPath, "favicon.ico");
                string pngPath = Path.Combine(dirPath, "favicon.png");

                // 提取网页标题
                Match match = Regex.Match(parseOriginUrl, "https?:\\/\\/([a-z]|[A-Z]|\\d|-|_|\\.)+\\/");
                if (match.Success)
                {
                    try
                    {
                        Console.WriteLine("提取的网页url为：" + match.Value);
                        string webpageSource = GetWebpageSource(match.Value);
                        int startIndex = webpageSource.IndexOf("<title>");
                        int endIndex = webpageSource.IndexOf("</title>", startIndex + 7);
                        if (startIndex > 0 && endIndex > 0 && startIndex + 7 < endIndex)
                        {
                            webtitle = webpageSource.Substring(startIndex + 7, endIndex - (startIndex + 7));
                            if (Regex.IsMatch(webtitle, @"[<>/=?]"))
                            {
                                webtitle = "";
                            }
                            Console.WriteLine("subtitle: " + webtitle);
                        }
                    }
                    catch (Exception ex)
                    {
                        Console.WriteLine("subtitle_error: " + ex.Message);
                    }
                }

                // 删除旧文件（如果存在）
                if (File.Exists(pngPath))
                {
                    File.Delete(pngPath);
                }

                // 创建文件夹（如果不存在）
                Directory.CreateDirectory(dirPath);

                // 下载文件
                DownloadFile(parseUrlhost, icoPath, maxBytes, timeout);
                Console.WriteLine("下载成功");

                // 转换ICO为PNG
                if (IsIcoFile(icoPath))
                {
                    if (!ConvertIcoToPng(icoPath, pngPath))
                    {
                        throw new Exception("转换失败");
                    }
                    Console.WriteLine("转换成功");
                }
                else if (IsPngFile(icoPath))
                {
                    pngPath = icoPath;
                }
                else
                {
                    throw new Exception("图标格式错误");
                }

                if (!File.Exists(pngPath))
                {
                    throw new Exception("转换失败");
                }
                Console.WriteLine("获取成功");

                // 上传文件
                try
                {
                    string uploadResult = UploadFile(new UploadOptions
                    {
                        FilePath = pngPath,
                        Url = uploadAddr,
                        FormDataFieldName = "file"
                    });
                    Console.WriteLine("上传成功: " + uploadResult);
                    return (uploadResult, webtitle);
                }
                catch (Exception ex)
                {
                    return (ex.Message, webtitle);
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine("整个流程出错: " + ex.Message);
                throw new Exception($"整个流程出错: {ex.Message}", ex);
            }
        }

        /// <summary>
        /// 使用 C# 的 MultipartFormDataContent API 上传文件和额外表单数据。
        /// 这是推荐的方式，比手动拼接请求体更安全、更简洁。
        /// </summary>
        /// <param name="options">上传选项</param>
        /// <returns>服务器响应内容</returns>
        public static string UploadFile(UploadOptions options)
        {
            try
            {
                // 1. 创建 MultipartFormDataContent 对象，它会自动生成 boundary
                using (var formData = new MultipartFormDataContent())
                {
                    // 2. 添加文件内容
                    // 从文件路径读取文件字节流
                    byte[] fileData = File.ReadAllBytes(options.FilePath);
                    string fileName = Path.GetFileName(options.FilePath);

                    // 创建文件内容的 StreamContent
                    var fileContent = new ByteArrayContent(fileData);

                    // 设置文件内容的 Content-Type 头 (可选，但推荐)
                    // MediaTypeNames.Application.Octet 是 "application/octet-stream" 的常量
                    fileContent.Headers.ContentType = new MediaTypeHeaderValue("application/octet-stream");

                    // 将文件内容添加到表单中
                    // Add 方法会自动设置正确的 Content-Disposition 头，例如：
                    // Content-Disposition: form-data; name="yourFieldName"; filename="yourFile.txt"
                    formData.Add(fileContent, options.FormDataFieldName, fileName);

                    // 3. 添加额外的表单字段
                    if (options.AdditionalFields != null)
                    {
                        foreach (var field in options.AdditionalFields)
                        {
                            // 为每个字符串字段创建 StringContent
                            var stringContent = new StringContent(field.Value);
                            // Add 方法会自动设置 Content-Disposition 头，例如：
                            // Content-Disposition: form-data; name="yourFieldKey"
                            formData.Add(stringContent, field.Key);
                        }
                    }

                    // 4. 创建 HttpRequestMessage 并设置内容
                    var request = new HttpRequestMessage(HttpMethod.Post, options.Url)
                    {
                        Content = formData
                    };

                    // 5. 添加请求头
                    request.Headers.Add("User-Agent", UserAgent.user_agent);
                    request.Headers.Add("Uuid", UUID.uuid);
                    // 注意：Content-Type 头由 MultipartFormDataContent 自动设置，无需手动添加！

                    // 6. 发送请求并获取响应
                    // 使用 .Result 是为了保持与原代码同步调用方式一致。
                    // 在实际应用中，更推荐使用 async/await
                    using (var response = httpClient.SendAsync(request).Result)
                    {
                        response.EnsureSuccessStatusCode(); // 如果状态码不是 2xx，则抛出异常
                        return response.Content.ReadAsStringAsync().Result;
                    }
                }
            }
            catch (Exception ex)
            {
                // 捕获所有异常并包装后抛出，提供更友好的错误信息
                throw new Exception($"上传文件失败: {ex.Message}", ex);
            }
        }
        public static string GetWebpageSource(string url)
        {
            try
            {
                using (var client = new HttpClient())
                {

                    client.DefaultRequestHeaders.Add("User-Agent", BrowserUserAgent);
                    client.Timeout = TimeSpan.FromSeconds(8);
                    return client.GetStringAsync(url).Result;
                }
            }
            catch (Exception ex)
            {
                throw new Exception($"获取网页源码失败: {ex.Message}", ex);
            }
        }




        private static bool IsIcoFile(string filePath)
        {
            try
            {
                using (var image = Image.FromFile(filePath))
                {
                    return image.RawFormat.Equals(ImageFormat.Icon);
                }
            }
            catch
            {
                return false;
            }
        }

        private static bool IsPngFile(string filePath)
        {
            try
            {
                using (var image = Image.FromFile(filePath))
                {
                    return image.RawFormat.Equals(ImageFormat.Png);
                }
            }
            catch
            {
                return false;
            }
        }

        private static bool ConvertIcoToPng(string icoPath, string pngPath)
        {
            try
            {
                using (var icoImage = Image.FromFile(icoPath))
                {
                    icoImage.Save(pngPath, ImageFormat.Png);
                }
                return true;
            }
            catch
            {
                return false;
            }
        }
    }

    public class UploadOptions
    {
        public string FilePath { get; set; }
        public string Url { get; set; }
        public string FormDataFieldName { get; set; } = "file";
        public System.Collections.Generic.Dictionary<string, string> AdditionalFields { get; set; } = new System.Collections.Generic.Dictionary<string, string>();
    }
}