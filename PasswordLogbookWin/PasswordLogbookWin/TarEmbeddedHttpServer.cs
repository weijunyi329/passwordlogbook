using ICSharpCode.SharpZipLib.Tar;
using System;
using System.Collections.Generic;
using System.IO;
using System.Net;
using System.Text;

namespace PasswordLogbookWin
{
    public class TarEmbeddedHttpServer
    {
        private readonly HttpListener _listener;
        private readonly string _tarFilePath;
        private readonly Dictionary<string, TarEntry> _tarIndex;
        private string _prefix;
        private readonly int _port;
        private bool _isRunning;

        private long _tarStartPosition = 0;
        private long _tarLen = 0;
        public int Port { get; set; }
        public bool IsBindPort { get; set; }
        public TarEmbeddedHttpServer(string tarFilePath)
        {
            if (!File.Exists(tarFilePath))
            {
                throw new FileNotFoundException("TAR file not found", tarFilePath);
            }

            _tarFilePath = tarFilePath;
            _listener = new HttpListener();
            IsBindPort = false;

            _tarIndex = new Dictionary<string, TarEntry>();

            // 预加载TAR文件索引
            BuildTarIndex();
        }

        private void BuildTarIndex()
        {

            using (var stream = File.OpenRead(_tarFilePath))
            {

                if (_tarFilePath.EndsWith(".exe"))
                {
                    // 1. 从文件末尾读取偏移量 (最后8字节)
                    stream.Seek(-8, SeekOrigin.End);
                    byte[] offsetBytes = new byte[8];
                    stream.Read(offsetBytes, 0, 8);
                    long tarStartPosition = BitConverter.ToInt64(offsetBytes, 0);

                    // 2. (调试) 打印偏移量，确保它是一个合理的值
                    Console.WriteLine($"读取到的 TAR 起始位置: {tarStartPosition}");
                    if (tarStartPosition <= 0 || tarStartPosition >= stream.Length - 8)
                    {
                        throw new InvalidDataException($"从文件中读取的 TAR 偏移量无效: {tarStartPosition}");
                    }

                    // 3. (核心) 创建一个只包含 TAR 数据的子流
                    //    stream.Seek 将主流的指针移动到 TAR 开始位置
                    stream.Seek(tarStartPosition, SeekOrigin.Begin);

                    //    Substream 是一个“视图”，它从当前位置开始，到指定的长度结束。
                    //    它不会复制数据，非常高效。
                    long tarLength = stream.Length - 8 - tarStartPosition; // 总长度 - 偏移量 - 末尾的偏移量本身
                    Console.WriteLine($"真实的tarLength：{tarLength}");
                    _tarStartPosition = tarStartPosition;
                    _tarLen = tarLength;
                }
                else
                {
                    _tarStartPosition = 0;
                    _tarLen = new FileInfo(_tarFilePath).Length;
                }


                // 4. (关键) 将干净的子流传递给 TarArchive
                //    tarArchive 现在会认为 tarSubstream 就是一个完整的 TAR 文件

                using (var tarSubStream = new TarSubStream(stream, _tarStartPosition, _tarLen))
                using (var tarArchive = new TarInputStream(tarSubStream))
                {

                    TarEntry entry;
                    while ((entry = tarArchive.GetNextEntry()) != null)
                    {
                        if (entry.IsDirectory)
                        {
                            // 可以选择是否索引目录
                            continue;
                        }

                        // 规范化路径，确保以/开头
                        string normalizedPath = entry.Name.StartsWith("/") ? entry.Name : "/" + entry.Name;
                        _tarIndex[normalizedPath] = entry;
                    }
                }
            }
        }

        public void Start()
        {
            if (_isRunning)
            {
                Console.WriteLine("Server Not Running!!! Server is already running.");
                return;
            }
            for (int i = 2100; i < 9999; i++)
            {
                try
                {
                    _listener.Prefixes.Clear();
                    _prefix = $"http://127.0.0.1:{i}/";
                    _listener.Prefixes.Add(_prefix);
                    _listener.Start();
                    _isRunning = true;
                    Port = i;
                    IsBindPort = true;
                    break;
                }
                catch (Exception e)
                {
                    Console.WriteLine($"绑定到端口{i}异常：{_prefix}  {e.Message}");
                }
            }


            Console.WriteLine($"Server started at {_prefix}");

            // 开始监听请求
            _listener.BeginGetContext(ProcessRequest, null);
        }

        public void Stop()
        {
            if (!_isRunning)
            {
                Console.WriteLine("Server not Stop !!!Server is not running.");
                return;
            }

            _listener.Stop();
            _isRunning = false;
            Console.WriteLine("Server stopped.");
        }

        private void ProcessRequest(IAsyncResult ar)
        {
            if (!_listener.IsListening)
                return;

            var context = _listener.EndGetContext(ar);
            _listener.BeginGetContext(ProcessRequest, null);

            try
            {
                HandleRequest(context);
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error processing request: {ex.Message}");
                SendErrorResponse(context.Response, HttpStatusCode.InternalServerError, "Internal server error");
            }
        }

        private void HandleRequest(HttpListenerContext context)
        {
            var request = context.Request;
            var response = context.Response;

            string path = request.Url.AbsolutePath;
            Console.WriteLine($"Request received for: {path}");

            // 尝试从TAR索引中找到匹配的条目
            if (_tarIndex.TryGetValue(path, out var tarEntry))
            {
                ServeFileFromTar(response, tarEntry);
            }
            else
            {
                // 如果没有找到，尝试查找默认文件（如index.html）
                if (path.EndsWith("/"))
                {
                    string indexPath = path + "index.html";
                    if (_tarIndex.TryGetValue(indexPath, out var indexEntry))
                    {
                        ServeFileFromTar(response, indexEntry);
                        return;
                    }
                }

                // 资源未找到
                SendErrorResponse(response, HttpStatusCode.NotFound, "Resource not found");
            }
        }

        private void ServeFileFromTar(HttpListenerResponse response, TarEntry tarEntry)
        {
            // 设置内容类型
            string contentType = GetContentType(tarEntry.Name);
            response.ContentType = contentType;

            // 设置内容长度
            response.ContentLength64 = tarEntry.Size;

            // 从TAR文件中读取内容并写入响应
            using (var tarStream = File.OpenRead(_tarFilePath))
            using (var tarSubStream = new TarSubStream(tarStream, _tarStartPosition, _tarLen))
            using (var tarInputStream = new TarInputStream(tarSubStream))
            {
                // 定位到正确的条目
                TarEntry entry;
                while ((entry = tarInputStream.GetNextEntry()) != null)
                {
                    if (entry.Name == tarEntry.Name)
                    {
                        // 复制数据到响应流
                        var buffer = new byte[4096];
                        int bytesRead;
                        while ((bytesRead = tarInputStream.Read(buffer, 0, buffer.Length)) > 0)
                        {
                            response.OutputStream.Write(buffer, 0, bytesRead);
                        }
                        break;
                    }
                }
            }

            response.Close();
        }

        private string GetContentType(string fileName)
        {
            string extension = Path.GetExtension(fileName).ToLowerInvariant();

            switch (extension)
            {
                case ".html":
                case ".htm":
                    return "text/html";
                case ".css":
                    return "text/css";
                case ".js":
                    return "application/javascript";
                case ".png":
                    return "image/png";
                case ".jpg":
                case ".jpeg":
                    return "image/jpeg";
                case ".gif":
                    return "image/gif";
                case ".svg":
                    return "image/svg+xml";
                case ".json":
                    return "application/json";
                case ".xml":
                    return "application/xml";
                case ".txt":
                    return "text/plain";
                case ".pdf":
                    return "application/pdf";
                case ".zip":
                    return "application/zip";
                default:
                    return "application/octet-stream";
            }
        }

        private void SendErrorResponse(HttpListenerResponse response, HttpStatusCode statusCode, string message)
        {
            response.StatusCode = (int)statusCode;
            response.StatusDescription = message;

            byte[] buffer = Encoding.UTF8.GetBytes($"<html><body><h1>{(int)statusCode} {statusCode}</h1><p>{message}</p></body></html>");
            response.ContentLength64 = buffer.Length;
            response.ContentType = "text/html";

            response.OutputStream.Write(buffer, 0, buffer.Length);
            response.Close();
        }
    }
}