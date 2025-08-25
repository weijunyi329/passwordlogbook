{
  "targets": [
    {
      "target_name": "icotopng",
      "product_name": "icotopng",  # 动态生成文件名
      "sources": [
        "src/ico_to_png.cc",
        "src/lodepng.cpp",
        "src/nanosvg.c",
        "src/nanosvgrast.c"
      ],
      "include_dirs": [
        "<!@(node -p \"require('node-addon-api').include\")",
        "src"
      ],
      "dependencies": [
        "<!(node -p \"require('node-addon-api').gyp\")"
      ],
      "cflags!": ["-fno-exceptions"],
      "cflags_cc!": ["-fno-exceptions"],
      "defines": ["NAPI_DISABLE_CPP_EXCEPTIONS"],
      "conditions": [
        ["OS==\"win\"", {
          "msvs_settings": {
            "VCCLCompilerTool": {
              "ExceptionHandling": 1
            }
          },
          "product_name": "icotopng-win"  # Windows 专用名称
        }],
        ["OS==\"linux\"", {
          "cflags": ["-fPIC", "-O3"],
          "cflags_cc": ["-fPIC", "-O3"],
          "libraries": ["-lpng"],
          "product_name": "icotopng-linux"  # Linux 专用名称
        }],
        ["OS==\"mac\"", {
          "xcode_settings": {
            "GCC_ENABLE_CPP_EXCEPTIONS": "YES"
          },
          "product_name": "icotopng-darwin"  # macOS 专用名称
        }]
      ]
    }
  ]
}
