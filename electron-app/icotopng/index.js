const icotopng = require('./build/Release/icotopng');

/**
 * 将ICO文件转换为PNG文件
 * @param {string} icoPath - ICO文件路径
 * @param {string} pngPath - PNG文件保存路径
 * @param {function} callback - 回调函数 (err, outputPath)
 */
function convert(icoPath, pngPath, callback) {
  if (typeof callback !== 'function') {
    throw new TypeError('Callback must be a function');
  }
  
  if (typeof icoPath !== 'string' || typeof pngPath !== 'string') {
    callback(new TypeError('Paths must be strings'), null);
    return;
  }
  
  icotopng.convert(icoPath, pngPath, callback);
}

module.exports = {
  convert
};