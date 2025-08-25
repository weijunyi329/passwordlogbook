using System;
using System.Collections.Generic;
using System.IO;
using System.Linq;
using System.Threading.Tasks;
using System.Windows.Forms;

namespace PasswordLogbookWin
{
    static class Program
    {
        /// <summary>
        /// 应用程序的主入口点。
        /// </summary>
        [STAThread]
        static void Main()
        {
            Application.EnableVisualStyles();
            Application.SetCompatibleTextRenderingDefault(false);
            Application.Run(new Form1());
        }
        public static void MergeExeAndTarWithOffset(string exePath, string tarPath, string outputPath)
        {
            byte[] exeData = File.ReadAllBytes(exePath);
            byte[] tarData = File.ReadAllBytes(tarPath);
            Console.WriteLine($"exeData:{exeData.Length}");
            Console.WriteLine($"tarData:{tarData.Length}");
            using (var outputStream = new FileStream(outputPath, FileMode.Create))
            {
                // 1. 写入 EXE
                outputStream.Write(exeData, 0, exeData.Length);

                // 2. 对齐到 512 字节（TAR 要求）
                long padding = 512 - (exeData.Length % 512);
                if (padding != 512)
                {
                    outputStream.Write(new byte[padding], 0, (int)padding);
                }
                else
                {
                    padding = 0;
                }

                // 3. 写入 TAR
                outputStream.Write(tarData, 0, tarData.Length);

                // 4. 在文件末尾写入 TAR 起始位置（8 字节）
                long tarStartPosition = exeData.Length + padding;
                byte[] offsetBytes = BitConverter.GetBytes(tarStartPosition);
                outputStream.Write(offsetBytes, 0, offsetBytes.Length);
            }
        }
    }
}
