using Microsoft.Web.WebView2.Core;
using System;

namespace PasswordLogbookWin
{
    public class Utils
    {
        public static void CheckWebView2Support()
        {
            try
            {
                string version = CoreWebView2Environment.GetAvailableBrowserVersionString(null);
                Console.WriteLine($"WebView2版本: {version}");
            }
            catch (Exception ex)
            {
                Console.WriteLine($"不支持WebView2: {ex.Message}");
                ShowUnsupportedMessageAndRedirect();
            }
        }

        private static void ShowUnsupportedMessageAndRedirect()
        {
            // 弹出提示信息
            Console.WriteLine("您的系统不支持WebView2，将跳转到下载页面以获取最新运行时。");

            // 跳转到官方下载页面
            System.Diagnostics.Process.Start(new System.Diagnostics.ProcessStartInfo
            {
                FileName = "https://developer.microsoft.com/microsoft-edge/webview2/",
                UseShellExecute = true
            });
        }
    }
}