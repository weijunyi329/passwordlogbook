using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;
using System.Text.Json;
using System.Text.Json.Nodes;
using System.Threading.Tasks;

namespace PasswordLogbookWin
{
    public class Readme
    {
        public static string getInfo ()
        {
            var jsonObject = JsonObject.Create(JsonDocument.Parse("{}").RootElement);
            jsonObject["client"] = "Windows";
            jsonObject["core"] = "C# Webview2";
            jsonObject["version"] = "1.0.0";
            jsonObject["other"] = "";
            return jsonObject.ToJsonString();
        }
    }
}
