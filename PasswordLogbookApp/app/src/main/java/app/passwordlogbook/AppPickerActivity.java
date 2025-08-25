package app.passwordlogbook;

import android.app.Activity;
import android.content.Intent;
import android.content.pm.ApplicationInfo;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.Canvas;
import android.graphics.drawable.Drawable;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;
import android.view.ViewGroup;
import android.widget.AdapterView;
import android.widget.BaseAdapter;
import android.widget.Button;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.ListView;
import android.widget.TextView;
import android.widget.Toast;

import java.io.File;
import java.io.FileOutputStream;
import java.util.ArrayList;
import java.util.List;

public class AppPickerActivity extends Activity {
    public static final String RESULT_PACKAGE_NAME = "package_name";
    public static final String RESULT_APP_NAME = "app_name";
    public static final String RESULT_APP_ICON = "app_icon";
    private ListView appListView;
    private AppListAdapter adapter;
    private List<AppInfo> appList = new ArrayList<>();
    private List<AppInfo> filteredAppList = new ArrayList<>();
    private List<AppInfo> allAppList = new ArrayList<>(); // 缓存所有应用列表
    private String uploadUrl;
    private boolean showSystemApps = false; // 控制是否显示系统应用，默认不显示系统应用
    private EditText filterEditText;
    private Button toggleButton;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_app_picker);
        uploadUrl=getIntent().getStringExtra("uploadUrl");
        initViews();
    }

    @Override
    public void onWindowFocusChanged(boolean hasFocus) {
        super.onWindowFocusChanged(hasFocus);
        // 当Activity获得焦点并显示后开始加载应用列表
        if (hasFocus) {
            // 只在第一次获得焦点时加载应用列表
            if (allAppList.isEmpty()) {
                loadInstalledAppsAsync();
            }
        }
    }

    private void initViews() {
        appListView = findViewById(R.id.app_list_view);
        filterEditText = findViewById(R.id.filter_edit_text);
        toggleButton = findViewById(R.id.toggle_button);

        // 设置初始按钮文本
        toggleButton.setText(showSystemApps ? "隐藏系统应用" : "显示系统应用");

        adapter = new AppListAdapter();
        appListView.setAdapter(adapter);

        // 设置过滤输入框监听器
        filterEditText.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {
            }

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                filterApps(s.toString());
            }

            @Override
            public void afterTextChanged(Editable s) {
            }
        });

        // 设置显示/隐藏系统应用按钮监听器
        toggleButton.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                showSystemApps = !showSystemApps;
                if (showSystemApps) {
                    toggleButton.setText("隐藏系统应用");
                } else {
                    toggleButton.setText("显示系统应用");
                }
                // 重新过滤应用列表
                filterApps(filterEditText.getText().toString());
            }
        });

        appListView.setOnItemClickListener(new AdapterView.OnItemClickListener() {
            @Override
            public void onItemClick(AdapterView<?> parent, View view, int position, long id) {
                AppInfo appInfo = filteredAppList.get(position);

                // 保存图标到私有目录并上传
                String iconPath = saveAppIconToPrivateDir(appInfo);
                if (iconPath != null) {
                    new Thread(() -> {
                        String result = OkHttpFaviconDownloader.uploadFileToLocalhost(
                                iconPath, uploadUrl);
                        runOnUiThread(() -> {
                            if (result != null) {
                                Toast.makeText(AppPickerActivity.this,
                                        "图标上传成功", Toast.LENGTH_SHORT).show();
                            } else {
                                Toast.makeText(AppPickerActivity.this,
                                        "图标上传失败", Toast.LENGTH_SHORT).show();
                            }

                            // 返回应用信息
                            Intent resultIntent = new Intent();
                            resultIntent.putExtra(RESULT_PACKAGE_NAME, appInfo.packageName);
                            resultIntent.putExtra(RESULT_APP_NAME, appInfo.appName);
                            resultIntent.putExtra(RESULT_APP_ICON, result);
                            setResult(RESULT_OK, resultIntent);
                            finish();
                        });
                    }).start();
                } else {
                    // 保存图标失败，仍然返回应用信息
                    Intent resultIntent = new Intent();
                    resultIntent.putExtra(RESULT_PACKAGE_NAME, appInfo.packageName);
                    resultIntent.putExtra(RESULT_APP_NAME, appInfo.appName);
                    setResult(RESULT_OK, resultIntent);
                    finish();
                }
            }
        });
    }

    private void loadInstalledAppsAsync() {
        // 在后台线程加载应用列表
        new Thread(() -> {
            // 如果已经缓存了所有应用列表，则直接使用缓存
            if (allAppList.isEmpty()) {
                PackageManager pm = getPackageManager();
                List<ApplicationInfo> installedApps = pm.getInstalledApplications(PackageManager.GET_META_DATA);

                for (ApplicationInfo appInfo : installedApps) {
                    AppInfo info = new AppInfo();
                    info.appName = appInfo.loadLabel(pm).toString();
                    info.packageName = appInfo.packageName;
                    info.icon = appInfo.loadIcon(pm);
                    info.isSystemApp = (appInfo.flags & ApplicationInfo.FLAG_SYSTEM) != 0; // 判断是否为系统应用
                    allAppList.add(info);
                }

                // 按应用名称排序
                allAppList.sort((a, b) -> a.appName.compareToIgnoreCase(b.appName));
            }

            // 在UI线程更新UI
            runOnUiThread(() -> {
                // 初始化应用列表
                appList.clear();
                appList.addAll(allAppList);
                filterApps(""); // 应用当前过滤条件
            });
        }).start();
    }


    // 根据关键字过滤应用列表
    private void filterApps(String keyword) {
        filteredAppList.clear();

        for (AppInfo appInfo : appList) {
            boolean matchesKeyword = keyword.isEmpty() ||
                    appInfo.appName.toLowerCase().contains(keyword.toLowerCase()) ||
                    appInfo.packageName.toLowerCase().contains(keyword.toLowerCase());

            boolean shouldShow = showSystemApps || !appInfo.isSystemApp;

            if (matchesKeyword && shouldShow) {
                filteredAppList.add(appInfo);
            }
        }

        adapter.notifyDataSetChanged();
    }

    private String saveAppIconToPrivateDir(AppInfo appInfo) {
        try {
            // 将Drawable转换为Bitmap
            Drawable drawable = appInfo.icon;
            Bitmap bitmap = drawableToBitmap(drawable);

            // 检查图标尺寸，最好是64*64，但不大于96*96
            int width = bitmap.getWidth();
            int height = bitmap.getHeight();

            // 如果图标尺寸大于96*96，则缩放至96*96
            if (width > 96 || height > 96) {
                bitmap = Bitmap.createScaledBitmap(bitmap, 96, 96, true);
            }
            // 如果图标尺寸小于64*64，则缩放至64*64
            else if (width < 64 || height < 64) {
                bitmap = Bitmap.createScaledBitmap(bitmap, 64, 64, true);
            }

            // 创建文件名
            String fileName = appInfo.packageName + ".png";

            // 获取应用私有目录
            File privateDir = getFilesDir();
            File iconFile = new File(privateDir, fileName);

            // 保存Bitmap到文件
            FileOutputStream out = new FileOutputStream(iconFile);
            bitmap.compress(Bitmap.CompressFormat.PNG, 100, out);
            out.flush();
            out.close();

            return iconFile.getAbsolutePath();
        } catch (Exception e) {
            e.printStackTrace();
            return null;
        }
    }

    private Bitmap drawableToBitmap(Drawable drawable) {
        if (drawable instanceof android.graphics.drawable.BitmapDrawable) {
            return ((android.graphics.drawable.BitmapDrawable) drawable).getBitmap();
        }

        Bitmap bitmap = Bitmap.createBitmap(
                drawable.getIntrinsicWidth(),
                drawable.getIntrinsicHeight(),
                Bitmap.Config.ARGB_8888);
        Canvas canvas = new Canvas(bitmap);
        drawable.setBounds(0, 0, canvas.getWidth(), canvas.getHeight());
        drawable.draw(canvas);
        return bitmap;
    }

    private static class AppInfo {
        String appName;
        String packageName;
        Drawable icon;
        boolean isSystemApp; // 添加是否为系统应用的标识
    }

    private class AppListAdapter extends BaseAdapter {

        @Override
        public int getCount() {
            return filteredAppList.size();
        }

        @Override
        public Object getItem(int position) {
            return filteredAppList.get(position);
        }

        @Override
        public long getItemId(int position) {
            return position;
        }

        @Override
        public View getView(int position, View convertView, ViewGroup parent) {
            ViewHolder holder;
            if (convertView == null) {
                convertView = getLayoutInflater().inflate(R.layout.item_app, parent, false);
                holder = new ViewHolder();
                holder.iconImageView = convertView.findViewById(R.id.app_icon);
                holder.nameTextView = convertView.findViewById(R.id.app_name);
                holder.packageTextView = convertView.findViewById(R.id.app_package);
                convertView.setTag(holder);
            } else {
                holder = (ViewHolder) convertView.getTag();
            }

            AppInfo appInfo = filteredAppList.get(position);
            holder.iconImageView.setImageDrawable(appInfo.icon);
            holder.nameTextView.setText(appInfo.appName);
            holder.packageTextView.setText(appInfo.packageName);

            return convertView;
        }

        private class ViewHolder {
            ImageView iconImageView;
            TextView nameTextView;
            TextView packageTextView;
        }
    }
}