::npm run build

set output=..\test
@echo off

if exist "%output%\" (
    echo 文件夹存在，正在清空内容...

    rem 删除文件夹内所有文件（不包括子目录）
    del /q "%output%*.*"

    rem 删除所有子目录及其内容（递归）
    for /d %%D in ("%output%*") do (
        rd /s /q "%%D"
    )

    echo 文件夹内容已清空。
) else (
    mkdir "%output%"
)

xcopy ".\*.php" "%output%\" /S /I /Y
xcopy .\admin "%output%\admin" /E /I /H /C /Y
xcopy .\security "%output%\security" /E /I /H /C /Y
xcopy .\install "%output%\install" /E /I /H /C /Y
del "%output%\config.php"
move /y "%output%\index.php" "%output%\install\index.php.template"
move /y "%output%\index_.php" "%output%\index.php"
move /y "%output%\admin\static\index.html" "%output%\admin\"
move /y "%output%\admin\static\assets" "%output%\admin\"

del "%output%\security\safe.settings.php"
rmdir /s /q "%output%\security\log"
rmdir "%output%\admin\static"

pause

