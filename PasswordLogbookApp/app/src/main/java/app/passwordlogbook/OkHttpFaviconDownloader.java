package app.passwordlogbook;

// 首先添加依赖：implementation 'com.squareup.okhttp3:okhttp:4.9.3'

import android.content.Context;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;

import androidx.annotation.Nullable;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.concurrent.TimeUnit;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.RequestBody;
import okhttp3.Response;

public class OkHttpFaviconDownloader {
    private static final OkHttpClient client = new OkHttpClient.Builder()
            .connectTimeout(3, TimeUnit.SECONDS)
            .readTimeout(3, TimeUnit.SECONDS)
            .writeTimeout(3, TimeUnit.SECONDS)
            .build();

    /**
     * 下载百度 favicon 并保存为 PNG 格式到私有目录
     * @param context 上下文
     * @return 成功返回文件路径，失败返回 null
     */
    @Nullable
    public static String downloadFaviconAsPng(Context context,String iconUrl) {
        // 下载原始 favicon 数据
        byte[] iconData = downloadFaviconData(iconUrl);
        if (iconData == null) {
            return null;
        }

        // 将数据转换为 Bitmap
        Bitmap bitmap = bytesToBitmap(iconData);
        if (bitmap == null) {
            return null;
        }

        // 确保保存为 PNG 格式
        return saveBitmapAsPng(context, bitmap);
    }

    /**
     * 下载 favicon 的原始字节数据
     */
    @Nullable
    private static byte[] downloadFaviconData(String iconUrl) {
        Request request = new Request.Builder()
                .url(iconUrl)
                .build();

        try (Response response = client.newCall(request).execute()) {
            if (response.isSuccessful() && response.body() != null) {
                return response.body().bytes();
            }
        } catch (Exception ignored) {
        }
        return null;
    }

    /**
     * 将字节数据转换为 Bitmap（处理各种可能的 favicon 格式）
     */
    @Nullable
    private static Bitmap bytesToBitmap(byte[] data) {
        // 尝试直接解码
        Bitmap bitmap = BitmapFactory.decodeByteArray(data, 0, data.length);
        if (bitmap != null) {
            return bitmap;
        }

        // 如果直接解码失败，尝试作为 Drawable 加载后转换
        try {
            // 注意：这里需要 Android 上下文，所以这个方法应该在 Android 环境中调用
            // 由于是静态工具类，我们无法直接获取 Context，所以这种转换方式不适合静态方法
            // 替代方案：保持使用 BitmapFactory 的多种解码方式

            // 尝试不同的解码选项
            BitmapFactory.Options options = new BitmapFactory.Options();
            options.inJustDecodeBounds = true;
            BitmapFactory.decodeByteArray(data, 0, data.length, options);
            options.inJustDecodeBounds = false;
            options.inPreferredConfig = Bitmap.Config.ARGB_8888;

            return BitmapFactory.decodeByteArray(data, 0, data.length, options);
        } catch (Exception ignored) {
            return null;
        }
    }

    /**
     * 将 Bitmap 保存为 PNG 到私有目录
     */
    @Nullable
    private static String saveBitmapAsPng(Context context, Bitmap bitmap) {
        FileOutputStream output = null;
        try {
            File privateDir = context.getFilesDir();
            File faviconFile = new File(privateDir, "baidu_favicon.png"); // 明确使用 .png 扩展名

            output = new FileOutputStream(faviconFile);
            // 强制使用 PNG 格式保存
            bitmap.compress(Bitmap.CompressFormat.PNG, 100, output);
            output.flush();

            return faviconFile.getAbsolutePath();
        } catch (Exception e) {
            return null;
        } finally {
            try {
                if (output != null) {
                    output.close();
                }
            } catch (Exception ignored) {
            }
        }
    }
    /**
     * 上传文件到 localhost/upload
     */
    public static String uploadFileToLocalhost(String filePath,String uploadUrl) {
        File file = new File(filePath);
        if (!file.exists()) {
            return null;
        }

        // 创建 Multipart 表单请求体
        RequestBody requestBody = new MultipartBody.Builder()
                .setType(MultipartBody.FORM)
                .addFormDataPart(
                        "file",  // 表单字段名（服务器根据这个字段获取文件）
                        file.getName(),  // 文件名
                        RequestBody.create(file, MediaType.parse("image/png"))  // 文件内容和 MIME 类型
                )
                .build();

        // 构建请求
        Request request = new Request.Builder()
                .url(uploadUrl)  // 替换为你的服务器地址
                .addHeader("User-Agent", UserAgent.USER_AGENT)
                .addHeader("Uuid", UUID.UUID)
                .post(requestBody)
                .build();

        // 执行请求
        try (Response response = client.newCall(request).execute()) {
            if (response.isSuccessful()) {
                // 处理响应
               return response.body().string();
            }else {
                System.out.println("上传失败："+response.body().string());
            }
        } catch (IOException e) {
            e.printStackTrace();
            return null;
        }
        return null;
    }
    /**
     * 同步获取 网站 的 HTML 源码（模拟浏览器请求）
     * @return HTML 字符串
     * @throws IOException 如果请求失败
     */
    public static String fetchWebIndexHtmlSync(String url) throws IOException {
        String regex="https?://([a-z]|[A-Z]|\\d|-|_|\\.)+/?";

        Pattern r = Pattern.compile(regex);
        Matcher m = r.matcher(url);
        if (m.find()){
            url=m.group();
        }else {
            return null;
        }
        // 1. 构建请求，添加 User-Agent 模拟浏览器
        Request request = new Request.Builder()
                .url(url)
                .addHeader("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36")
                .build();

        // 2. 同步执行请求（会阻塞当前线程）
        try (Response response = client.newCall(request).execute()) {
            if (!response.isSuccessful()) {
                throw new IOException("服务器返回错误: " + response.code());
            }
            if (response.body() == null) {
                throw new IOException("响应体为空");
            }
            String body = response.body().string();
            if (response.code()==200){

                System.out.println(body);
                return body;
            }else {
                throw new IOException("上传错误："+body);
            }

        }
    }

    public static  String urlParseHostUrl(String ourl){
        String regex="https?://([a-z]|[A-Z]|\\d|-|_|\\.)+/?";

        Pattern r = Pattern.compile(regex);
        Matcher m = r.matcher(ourl);
        if (m.find()){
            ourl=m.group();
            if (ourl.endsWith("/")){
                ourl=ourl.substring(0,ourl.length()-1);
            }
            return ourl;
        }else {
            return null;
        }
    }
    public static void main(Context context) {
        new Thread(() -> {
            // 示例用法
                System.out.println("解析后的URL："+urlParseHostUrl("https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=2&tn=75144485_5_dg&wd=android%20Thread%20Join&oq=android%2520webview%2520js%25E5%25AF%25B9%25E8%25B1%25A1java%25E6%2589%25A7%25E8%25A1%258C%25E5%259B%259E%25E8%25B0%2583js&rsv_pq=f1c379fd01427591&rsv_t=82e5h%2BaacWkla7oRlCPDcJhp9fMsOYBXvMc%2B8f487Avkn67NFc6K%2B32lIgxobqnfec%2FagA&rqlang=cn&rsv_enter=1&rsv_dl=tb&rsv_btype=t&inputT=11515&rsv_sug3=150&rsv_sug1=133&rsv_sug7=100&rsv_sug2=0&rsv_sug4=12366"));
               String res= OkHttpFaviconDownloader.downloadFaviconAsPng(context,"https://www.baidu.com/favicon.ico");
               if (res!=null){
                  String endres= uploadFileToLocalhost(res,"http://192.168.0.102/upload");
                  if (endres!=null){
                      System.out.println("上传成功:"+endres);
                  }else {
                      System.out.println("上传失败");
                  }
               }
                System.out.println(res);

        }).start();

    }
}