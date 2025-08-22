package app.passwordlogbook;

import android.app.Activity;
import android.content.Context;
import android.content.DialogInterface;
import android.content.Intent;
import android.content.res.AssetManager;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.webkit.JsResult;
import android.webkit.ValueCallback;
import android.webkit.WebChromeClient;
import android.webkit.WebResourceRequest;
import android.webkit.WebResourceResponse;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.Button;

import androidx.activity.EdgeToEdge;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.graphics.Insets;
import androidx.core.view.ViewCompat;
import androidx.core.view.WindowInsetsCompat;

import org.json.JSONException;
import org.json.JSONObject;

import java.io.IOException;
import java.io.InputStream;

public class MainActivity extends AppCompatActivity {
    private WebView webView;
    private Button refresh;
    private Button selectAppButton;
    private static final int REQUEST_SELECT_APP = 1001;
    private static final int FILECHOOSER_RESULT_CODE_ABOVE_LOLLIPOP = 1002;

    private ValueCallback<Uri[]> mUploadMessageAboveL;
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        EdgeToEdge.enable(this);
        setContentView(R.layout.activity_main);
        refresh=findViewById(R.id.refresh);
        selectAppButton = findViewById(R.id.select_app);

        refresh.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                webView.reload();
            }
        });

        selectAppButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                Intent intent = new Intent(MainActivity.this, AppPickerActivity.class);
                startActivityForResult(intent, REQUEST_SELECT_APP);
            }
        });


        webView=findViewById(R.id.webView);
        //打开调试
        webView.setWebContentsDebuggingEnabled(true);
        //设置WebView的属性
        WebSettings webSettings = webView.getSettings();
        //
        webSettings.setJavaScriptEnabled(true);
        webSettings.setAllowContentAccess(true);
        webSettings.setAllowFileAccess(true);
        webSettings.setAllowUniversalAccessFromFileURLs(true);
        webSettings.setUserAgentString(UserAgent.USER_AGENT);
        webView.setWebChromeClient(new WebChromeClient(){

            @Override
            public boolean onJsAlert(WebView view, String url, String message, JsResult result) {
                showDialog(MainActivity.this,"",message,result);
                return true;
            }
            // For Android > 5.0
            public boolean onShowFileChooser(WebView webView, ValueCallback<Uri[]> filePathCallback, FileChooserParams fileChooserParams) {
                mUploadMessageAboveL = filePathCallback;
                Intent takePictureIntent = new Intent(Intent.ACTION_GET_CONTENT);
                takePictureIntent.setType("*/*");
                Intent chooserIntent = new Intent(Intent.ACTION_CHOOSER);
                chooserIntent.putExtra(Intent.EXTRA_INTENT, takePictureIntent);
                chooserIntent.putExtra(Intent.EXTRA_TITLE, "Image Chooser");
                startActivityForResult(chooserIntent, FILECHOOSER_RESULT_CODE_ABOVE_LOLLIPOP);
                return true;
            }
        });
        webView.setWebViewClient(new WebViewClient(){



            @Override
            public void onLoadResource(WebView view, String url) {
                if ( url .startsWith("file:///")  && !url .startsWith("file:///android_asset/")){
                    super.onLoadResource(view, url.replace("file:///","file:///android_asset/"));
                }
                super.onLoadResource(view, url);
            }

            @Nullable
            @Override
            public WebResourceResponse shouldInterceptRequest(WebView view, WebResourceRequest request) {
                String url = request.getUrl().toString();
                System.out.println("拦截请求 - 原始URL: " + url);

                // 处理所有资源类型
                String assetPath = null;
                if (url.startsWith("file:///android_asset/")) {
                    // 已经是指向assets的请求
                    assetPath = url.substring("file:///android_asset/".length());
                } else if (url.startsWith("file:///" ) && !url.startsWith("file:///android_asset/" )) {
                    assetPath = url.substring("file:///".length());
                }else {
                    return super.shouldInterceptRequest(view, request);
                }

                if (assetPath != null) {
                    System.out.println("尝试加载asset资源: " + assetPath);
                    try {
                        AssetManager assetManager = MainActivity.this.getAssets();
                        InputStream inputStream = assetManager.open(assetPath);

                        // 根据文件扩展名设置MIME类型
                        String mimeType = "text/plain";
                        if (assetPath.endsWith(".js")) mimeType = "application/javascript";
                        else if (assetPath.endsWith(".css")) mimeType = "text/css";
                        else if (assetPath.endsWith(".png")) mimeType = "image/png";
                        else if (assetPath.endsWith(".html")) mimeType = "text/html";
                        else if (assetPath.endsWith(".ico")) mimeType = "image/x-icon";

                        System.out.println("成功加载asset资源: " + assetPath + ", MIME类型: " + mimeType);
                        return new WebResourceResponse(mimeType, "UTF-8", inputStream);
                    } catch (IOException e) {
                        System.out.println("加载asset资源失败: " + assetPath + ", 错误: " + e.getMessage());
                        return null;
                    }
                }
                return null;
            }
        });
        // webView.getSettings().setCacheMode(WebSettings.LOAD_CACHE_ELSE_NETWORK);
        webView.addJavascriptInterface(new WebAppInterface(this,webView), "Android");
        //webView.loadUrl("http://192.168.0.103:5174");
        webView.loadUrl("file:///android_asset/index.html");
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == REQUEST_SELECT_APP && resultCode == RESULT_OK && data != null) {
            String packageName = data.getStringExtra(AppPickerActivity.RESULT_PACKAGE_NAME);
            String appName = data.getStringExtra(AppPickerActivity.RESULT_APP_NAME);
            String iconName = data.getStringExtra(AppPickerActivity.RESULT_APP_ICON);
            if (packageName==null)packageName="";
            if (appName==null)appName="";
            String icon="";
            if (iconName!=null){
                JSONObject jsonObject= null;
                try {
                    jsonObject = new JSONObject(iconName);
                    icon=jsonObject.getString("fileName");
                } catch (JSONException e) {
                    e.printStackTrace();
                }
            }
            webView.evaluateJavascript(String.format(" AndroidCallback.resultCallback({fileName:'%s',title:'%s','pkgName':'%s'});", icon, appName,packageName),  (value)->{});
            // 显示选中的应用信息
            //Toast.makeText(this, "应用名称: " + appName + "\n包名: " + packageName, Toast.LENGTH_LONG).show();

            // 这里可以添加你想要的处理逻辑
            // 比如保存到SharedPreferences或者传递给其他组件
        }else if(requestCode == FILECHOOSER_RESULT_CODE_ABOVE_LOLLIPOP ){
            if (null == mUploadMessageAboveL) return;
            Uri[] results = null;
            // Check on the response
            if (resultCode == Activity.RESULT_OK) {

                if ( data == null) {
                    // Capture Photo Intent Cancelled
                    mUploadMessageAboveL.onReceiveValue(new Uri[]{});
                    mUploadMessageAboveL = null;
                    return;
                }
                String dataString = data.getDataString();
                if (dataString != null) {
                    results = new Uri[]{Uri.parse(dataString)};
                }
            }
            mUploadMessageAboveL.onReceiveValue(results);
            mUploadMessageAboveL = null;
            return;
        }
    }

    public static void showDialog(Context context, String title, String message, JsResult result){
        AlertDialog dialog = new AlertDialog.Builder(context)
                .setTitle(title)//设置对话框的标题
                .setMessage(message)//设置对话框的内容

                .setPositiveButton("确定", new DialogInterface.OnClickListener() {

                    @Override
                    public void onClick(DialogInterface dialog, int which) {

                        result.cancel();
                        dialog.dismiss();
                    }
                }).create();
        dialog.setCancelable(true);
        dialog.setCanceledOnTouchOutside(true);
        dialog.show();
    }
    public void startAppPickerActivity(String uploadUrl) {
        Intent intent = new Intent(this, AppPickerActivity.class);
        intent.putExtra("uploadUrl", uploadUrl); // 替换为你的实际URL
        startActivityForResult(intent, REQUEST_SELECT_APP);
    }
    @Override
    protected void onPause() {
        super.onPause();
        webView.onPause();
    }

    @Override
    protected void onResume() {
        super.onResume();
        webView.onResume();
    }
}