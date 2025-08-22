using Microsoft.Web.WebView2.Core;
using System;
using System.Collections.Generic;
using System.ComponentModel;
using System.Data;
using System.Drawing;
using System.IO;
using System.Linq;
using System.Reflection;
using System.Text;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace PasswordLogbookWin
{
    public partial class Form1 : Form
    {
        private TarEmbeddedHttpServer server;
        private int availablePort = 8082;
        public Form1()
        {
            // 在需要检查WebView2支持的地方调用该方法

            InitializeComponent();
            //  _fileServer = new FileServer(@"D:\nwjstest");
            // _fileServer.StatusChanged += OnStatusChanged;

        }
        private void OnStatusChanged(string message)
        {
            /*
            if (lblStatus.InvokeRequired)
            {
                lblStatus.Invoke(new Action(() => lblStatus.Text = message));
            }
            else
            {
                lblStatus.Text = message;
            }
            */
            Console.WriteLine(message);
        }
        private void Form1_Load(object sender, EventArgs e)
        {

            InitializeAsync();

        }
        private async Task InitializeAsync()
        {
            //缓存文件夹
            string cacheDir = Path.Combine(
                Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData), "PwdLogbookClient", "Webview");
            //缓存文件夹不存在则创建
            if (!Directory.Exists(cacheDir))
                Directory.CreateDirectory(cacheDir);
            Utils.CheckWebView2Support();
            var env = await CoreWebView2Environment.CreateAsync(userDataFolder: cacheDir);
            await webView21.EnsureCoreWebView2Async(env); // 确保WebView2核心组件已加载
            SetWebView2(); // 设置webview
        }
        private void SetWebView2()
        {

            webView21.CoreWebView2.Settings.UserAgent = UserAgent.user_agent;
            webView21.CoreWebView2.NavigationCompleted += CoreWebView2_NavigationCompleted;
            webView21.CoreWebView2.AddHostObjectToScript("AppInterface", new WebAppInterface(this));


#if DEBUG
            try
            {

                Console.WriteLine("Assembly.GetExecutingAssembly().Location:" + Assembly.GetExecutingAssembly().Location);
                server = new TarEmbeddedHttpServer(Assembly.GetExecutingAssembly().Location);
                server.Start();
                if (server.IsBindPort == false)
                {
                    Console.WriteLine("内部的服务器未能绑定到端口");
                    Close();
                    return;
                }
                availablePort = server.Port;
                Console.WriteLine($"绑定的端口：{availablePort}");


            }
            catch (Exception e)
            {
                Console.WriteLine("本地服务器无法开启或获取可用端口出错：" + e.Message);
                Close();
                return;
            }
            webView21.Source = new Uri($"http://127.0.0.1:{availablePort}/index.html");
            //webView21.Source = new Uri($"http://127.0.0.1:5173/index.html");
#else
    
            webView21.CoreWebView2.ContextMenuRequested += CoreWebView2_ContextMenuRequested; //禁用右键
            webView21.CoreWebView2.Settings.AreDevToolsEnabled = false;
            try {
            
                server = new TarEmbeddedHttpServer(Assembly.GetExecutingAssembly().Location);
                server.Start();
                 if (server.IsBindPort == false) {
                    Console.WriteLine("内部的服务器未能绑定到端口");
                    Close();
                    return;
                }
                availablePort = server.Port;
                Console.WriteLine($"绑定的端口：{availablePort}");
            }
            catch (Exception e)
            {
                Console.WriteLine("本地服务器无法开启或获取可用端口出错："+e.Message);
                Close();
                return;
            }
            webView21.Source = new Uri($"http://127.0.0.1:{availablePort}/index.html");
            
          
#endif

            // _fileServer.Start();


            // webView21.Source = new Uri($"http://127.0.0.1:{availablePort}/index.html");
            // webView21.Source = new Uri($"http://127.0.0.1:5173/index.html");
        }
        private void CoreWebView2_NavigationCompleted(object sender, CoreWebView2NavigationCompletedEventArgs e)
        {
            if (e.IsSuccess) return;

            // 导航失败，显示自定义错误页面
            string errorHtml = GetErrorHtmlPage(e.WebErrorStatus);
            webView21.CoreWebView2.NavigateToString(errorHtml);
        }

        private string GetErrorHtmlPage(CoreWebView2WebErrorStatus errorStatus)
        {
            string errorMessage = "内部错误";
            if (errorStatus == CoreWebView2WebErrorStatus.ConnectionAborted ||
                errorStatus == CoreWebView2WebErrorStatus.ConnectionReset ||
                errorStatus == CoreWebView2WebErrorStatus.HostNameNotResolved ||
                errorStatus == CoreWebView2WebErrorStatus.Timeout
                )
            {
                errorMessage = "内部错误";
            }

            return $@"<html>
                        <head>
                            <title>错误</title>
                            <style>
                                body {{ font-family: Arial, sans-serif; text-align: center; padding-top: 50px; }}
                                h1 {{ color: #d9534f; }}
                                p {{ font-size: 18px; }}
                            </style>
                        </head>
                        <body>
                            <h1>内部页面未找到</h1>
                            <p>{errorMessage}</p>
                        </body>
                        </html>";
        }
        private void Form1_FormClosing(object sender, FormClosingEventArgs e)
        {
            if (server != null) server.Stop();
        }
        private void CoreWebView2_ContextMenuRequested(object sender, Microsoft.Web.WebView2.Core.CoreWebView2ContextMenuRequestedEventArgs e)
        {

            // 检查右键是否发生在可编辑元素上（如 input、textarea）
            bool isEditable = e.ContextMenuTarget.HasSelection ||
                             e.ContextMenuTarget.IsEditable;

            // 如果不是编辑框，则阻止默认菜单
            if (!isEditable)
            {
                e.Handled = true; // 阻止默认菜单
            }
        }

        private void webView21_Click(object sender, EventArgs e)
        {

        }
    }
}
