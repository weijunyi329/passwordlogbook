using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Net;
using System.Text;
using System.Threading;
using System.Threading.Tasks;

namespace PasswordLogbookWin
{
    public class FileServer
    {
        private HttpListener listener;
        private Thread serverThread;
        private bool isRunning = false;
        private readonly string rootPath;

        // 使用事件来通知UI层状态变化
        public event Action<string> StatusChanged;

        public FileServer(string path)
        {
            rootPath = path;
        }

        public void Start()
        {
            if (isRunning) return;

            // 确保监听地址以/结尾，以便正确处理相对路径
            string prefix = "http://localhost:8080/";
            listener = new HttpListener();
            listener.Prefixes.Add(prefix);

            listener.Start();
            isRunning = true;
            StatusChanged?.Invoke($"服务器已启动，监听: {prefix}");
            StatusChanged?.Invoke($"共享根目录: {rootPath}");

            serverThread = new Thread(() =>
            {
                while (isRunning)
                {
                    try
                    {
                        HttpListenerContext context = listener.GetContext();
                        // 为每个请求创建一个新线程处理，以支持并发
                        ThreadPool.QueueUserWorkItem(HandleRequest, context);
                    }
                    catch (HttpListenerException) when (!isRunning)
                    {
                        // 服务器关闭时，GetContext()会抛出异常，这是正常的，忽略即可
                    }
                    catch (Exception ex)
                    {
                        StatusChanged?.Invoke($"发生错误: {ex.Message}");
                    }
                }
            });
            serverThread.IsBackground = true;
            serverThread.Start();
        }

        public void Stop()
        {
            if (!isRunning) return;

            isRunning = false;
            listener.Stop();
            listener.Close();
            StatusChanged?.Invoke("服务器已停止。");
        }

        private void HandleRequest(object state)
        {
            HttpListenerContext context = (HttpListenerContext)state;
            HttpListenerRequest request = context.Request;
            HttpListenerResponse response = context.Response;

            try
            {
                // 将URL路径映射到本地文件系统路径
                string urlPath = request.Url.LocalPath;
                string localPath = Path.Combine(rootPath, urlPath.TrimStart('/'));

                // 检查路径是否在根目录内，防止目录遍历攻击
                if (!localPath.StartsWith(rootPath, StringComparison.OrdinalIgnoreCase))
                {
                    ServeNotFoundPage(response, "访问被拒绝：路径不合法。");
                    return;
                }

                // 核心逻辑：判断是文件还是目录
                if (File.Exists(localPath))
                {
                    // 如果是文件，直接提供
                    ServeFile(response, localPath);
                }
                else if (Directory.Exists(localPath))
                {
                    // 如果是目录，尝试查找 index.html
                    string indexPath = Path.Combine(localPath, "index.html");
                    if (File.Exists(indexPath))
                    {
                        // 找到 index.html，作为网页返回
                        ServeFile(response, indexPath);
                    }
                    else
                    {
                        // 没有找到 index.html，返回 404
                        ServeNotFoundPage(response, $"目录 '{urlPath}' 下未找到默认页面 (index.html)。");
                    }
                }
                else
                {
                    // 既不是文件也不是目录，返回 404
                    ServeNotFoundPage(response, $"您请求的资源 '{urlPath}' 不存在。");
                }
            }
            catch (Exception ex)
            {
                StatusChanged?.Invoke($"处理请求时出错: {ex.Message}");
                ServeNotFoundPage(response, "服务器内部错误。");
            }
            finally
            {
                response.Close();
            }
        }

        /// <summary>
        /// 提供文件内容，并根据文件扩展名设置正确的MIME类型
        /// </summary>
        private void ServeFile(HttpListenerResponse response, string filePath)
        {
            byte[] buffer = File.ReadAllBytes(filePath);
            response.ContentLength64 = buffer.Length;

            // 根据文件扩展名设置 Content-Type，让浏览器知道如何渲染
            string extension = Path.GetExtension(filePath).ToLowerInvariant();
            switch (extension)
            {
                case ".html":
                case ".htm":
                    response.ContentType = "text/html; charset=utf-8";
                    break;
                case ".css":
                    response.ContentType = "text/css";
                    break;
                case ".js":
                    response.ContentType = "application/javascript";
                    break;
                case ".png":
                    response.ContentType = "image/png";
                    break;
                case ".jpg":
                case ".jpeg":
                    response.ContentType = "image/jpeg";
                    break;
                case ".gif":
                    response.ContentType = "image/gif";
                    break;
                case ".ico":
                    response.ContentType = "image/x-icon";
                    break;
                case ".txt":
                    response.ContentType = "text/plain; charset=utf-8";
                    break;
                // 默认为二进制流，浏览器会尝试下载或根据内容猜测
                default:
                    response.ContentType = "application/octet-stream";
                    break;
            }

            response.OutputStream.Write(buffer, 0, buffer.Length);
        }

        /// <summary>
        /// 返回一个自定义的404 Not Found HTML页面
        /// </summary>
        private void ServeNotFoundPage(HttpListenerResponse response, string message)
        {
            response.StatusCode = (int)HttpStatusCode.NotFound;
            response.StatusDescription = "Not Found";

            string htmlContent = $@"
<!DOCTYPE html>
<html>
<head>
    <title>404 - 页面未找到</title>
    <style>
        body {{ font-family: Arial, sans-serif; text-align: center; padding: 50px; }}
        h1 {{ color: #d9534f; }}
        p {{ color: #777; }}
    </style>
</head>
<body>
    <h1>404 - 页面未找到</h1>
    <p>抱歉，您请求的资源无法找到。</p>
    <p><strong>详细信息:</strong> </p>
    <hr>
    <p><small>简易文件服务器</small></p>
</body>
</html>";

            byte[] buffer = Encoding.UTF8.GetBytes(htmlContent);
            response.ContentType = "text/html; charset=utf-8";
            response.ContentLength64 = buffer.Length;
            response.OutputStream.Write(buffer, 0, buffer.Length);
        }
    }
}
