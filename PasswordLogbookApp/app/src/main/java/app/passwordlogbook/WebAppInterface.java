package app.passwordlogbook;

import static android.content.Context.MODE_PRIVATE;

import android.content.ClipData;
import android.content.ClipboardManager;
import android.content.Context;
import android.content.SharedPreferences;
import android.os.Build;
import android.os.Handler;
import android.webkit.JavascriptInterface;
import android.webkit.WebView;
import android.widget.Toast;

import androidx.annotation.RequiresApi;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;

public class WebAppInterface {
    Context mContext;
    WebView webView;
    Handler handler;

    /** Instantiate the interface and set the context */
    public WebAppInterface(Context c) {
        mContext = c;
    }

    public WebAppInterface(Context mContext, WebView webView) {
        this.mContext = mContext;
        this.webView = webView;
        this.handler=new Handler();
    }

    /** Show a toast from the web page */
    @JavascriptInterface
    public String encrypt(String content,String password) {
       return PwdEncryption.encrypt(content,password);
    }
    @JavascriptInterface
    public String decrypt(String content,String password) {
        return PwdEncryption.decrypt(content,password);
    }

    @JavascriptInterface
    public void showToast(String toast) {
        Toast.makeText(mContext, toast, Toast.LENGTH_SHORT).show();
    }
    @JavascriptInterface
    public String getHostConfig(){
        SharedPreferences sharedPreferences=mContext.getSharedPreferences("settings",MODE_PRIVATE);
        if (!sharedPreferences.contains("host")){return "";}
        return sharedPreferences.getString("host","");
    }

    /**
     *
     * @param host
     */
    @JavascriptInterface
    public void setHostConfig(String host){
        SharedPreferences sharedPreferences=mContext.getSharedPreferences("settings",MODE_PRIVATE);
        SharedPreferences.Editor editor= sharedPreferences.edit();
        editor.putString("host",host);
        editor.apply();
    }
    @JavascriptInterface
    public void finish(){
        ((MainActivity)mContext).finish();
    }

    /**
     * 下载png并上传
     * @param parseOriginUrl
     * @param parsedUrlhost
     * @param userHost
     */
    @JavascriptInterface
    public void downConvertPng(String parseOriginUrl,String parsedUrlhost,String userHost){
        System.out.println("开始下载png:"+parseOriginUrl);
       Thread thread= new Thread(() -> {
           String title="";
           try {
              String html= OkHttpFaviconDownloader.fetchWebIndexHtmlSync(OkHttpFaviconDownloader.urlParseHostUrl(parseOriginUrl));
              System.out.println("获取html成功:"+html);
              int startindex=html.indexOf("<title>");
              int endindex=html.indexOf("</title>");

              if (startindex!=-1 && endindex!=-1 && startindex<endindex)
              {
                  title = html.substring(startindex + 7, endindex);
                  System.out.println("获取title成功:" + title);
              }
           } catch (IOException e) {
               e.printStackTrace();
           }
           String pngPath = OkHttpFaviconDownloader.downloadFaviconAsPng(mContext,OkHttpFaviconDownloader.urlParseHostUrl(parseOriginUrl)+"/favicon.ico");
            if (pngPath != null) {
                System.out.println("下载png成功:" + pngPath);
                // 成功，可以在主线程更新UI
               String uploadres= OkHttpFaviconDownloader.uploadFileToLocalhost(pngPath, userHost);
               if (uploadres!=null){
                   System.out.println("上传png成功:" + uploadres);
                   String finalTitle = title;
                   handler.post(()->{
                           try {
                               JSONObject jsonObject=new JSONObject(uploadres);
                               webView.evaluateJavascript(String.format(" AndroidCallback.succCallback({fileName:'%s',title:'%s'});", jsonObject.getString("fileName"), finalTitle),  (value)->{});
                           } catch (JSONException e) {
                               e.printStackTrace();
                           }
                       });
               }else {
                   System.out.println("上传png失败:");
               }
            } else {
                // 失败处理
                System.out.println("下载png失败:");
            }
           String finalTitle1 = title;
           handler.post(()->{
               webView.evaluateJavascript(String.format(" AndroidCallback.failCallback({fileName:'%s',title:'%s'});", "", finalTitle1),  (value)->{});
           });

        });

        thread.start();

    }
    @JavascriptInterface
    public String Version(){
        return "1.0";
    }

    /**
     * js接口：选择app或者格式化获取URL
     * @param uploadUrl
     */
    @JavascriptInterface
    public void reformatPkgName(String uploadUrl){
        System.out.println("开始选择app:"+uploadUrl);
        ((MainActivity)mContext).startAppPickerActivity(uploadUrl);
    }
    @JavascriptInterface
    public void copyToClipboard(String text) {
        ClipboardManager clipboard = (ClipboardManager) mContext.getSystemService(Context.CLIPBOARD_SERVICE);
        ClipData clip = ClipData.newPlainText("text", text);
        clipboard.setPrimaryClip(clip);
        Toast.makeText(mContext, "已复制到剪切板", Toast.LENGTH_SHORT).show();
    }
    @JavascriptInterface
    public void setConfig(String config,String value) {
        SharedPreferences sharedPreferences=mContext.getSharedPreferences("settings",MODE_PRIVATE);
        SharedPreferences.Editor editor= sharedPreferences.edit();
        editor.putString(config,value);
        editor.apply();
    }
    @JavascriptInterface
    public String getConfig(String config) {
        SharedPreferences sharedPreferences=mContext.getSharedPreferences("settings",MODE_PRIVATE);
        if (!sharedPreferences.contains(config)){return "";}
        return sharedPreferences.getString(config,"");
    }

    @JavascriptInterface
    public String clientInfo() {
        JSONObject jsonObject=new JSONObject();
        try {
            jsonObject.put("client","Android");
            jsonObject.put("core","Android WebView "+((Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) ? webView.getCurrentWebViewPackage().versionName:""));
            jsonObject.put("version",Version.VERSION);
        } catch (JSONException e) {
            e.printStackTrace();
        }
        return jsonObject.toString();
    }
    @JavascriptInterface
    public String getUUID() {
        return UUID.UUID;
    }
}