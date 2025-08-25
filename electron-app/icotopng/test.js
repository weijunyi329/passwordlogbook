const addon = require('./build/Release/icotopng.node');

const inputPath = './app.ico';
const outputPath = './test.png';

const result = addon.convertIcoToPng(inputPath, outputPath);
console.log('Conversion result:', result);

const result2 = addon.detectFileType(inputPath);
console.log(result2); // 0 (ICO)

//const result9 = addon.convertSvgToPng(inputPath, outputPath,128);
//console.log('Conversion result:', result9);
