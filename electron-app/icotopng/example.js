const { convert } = require('./index.js');
const path = require('path');

// 示例用法
const icoPath = path.join(__dirname, 'test.ico'); // 替换为实际ICO文件路径
const pngPath = path.join(__dirname, 'output.png'); // 替换为期望的输出路径

convert(icoPath, pngPath, (err, success) => {
  if (err) {
    console.error('转换出错:', err);
  } else if (success) {
    console.log('转换成功!');
  } else {
    console.log('转换失败');
  }
});