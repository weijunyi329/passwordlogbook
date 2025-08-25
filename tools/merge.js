const fs = require('fs');

/**
 * 合并 EXE 和 TAR 文件，并在末尾写入 TAR 起始偏移量
 * @param {string} exePath - EXE 文件路径
 * @param {string} tarPath - TAR 文件路径
 * @param {string} outputPath - 输出文件路径
 */
function mergeExeAndTarWithOffset(exePath, tarPath, outputPath) {

const args=	 process.argv.slice(2);
console.log(args); 
const args1 = args[0];
const args2 =args[1];
const args3 =args[2];


if(typeof args1 !=='undefined'){
	console.log(args1); 
	exePath=args1;
}
if(typeof args2 !=='undefined'){
	console.log(args2); 
	tarPath=args2;
}
if(typeof args3 !=='undefined'){
	console.log(args3); 
	outputPath=args3;
}
    // 1. 读取 EXE 和 TAR 文件
    const exeData = fs.readFileSync(exePath);
    const tarData = fs.readFileSync(tarPath);

    console.log(`exeData: ${exeData.length} bytes`);
    console.log(`tarData: ${tarData.length} bytes`);

    // 2. 创建输出流
    const outputStream = fs.createWriteStream(outputPath);

    // 3. 写入 EXE
    outputStream.write(exeData);

    // 4. 对齐到 512 字节（TAR 要求）
    let padding = 512 - (exeData.length % 512);
    if (padding === 512) padding = 0;

    if (padding > 0) {
        outputStream.write(Buffer.alloc(padding));
    }

    // 5. 写入 TAR
    outputStream.write(tarData);

    // 6. 计算并写入 TAR 起始位置（8 字节）
    const tarStartPosition = exeData.length + padding;
    const offsetBuffer = Buffer.alloc(8);

	offsetBuffer.writeBigUInt64LE(BigInt(tarStartPosition));
    outputStream.write(offsetBuffer);
    outputStream.end();

    console.log(`合并完成，输出文件：${outputPath}`);
    console.log(`TAR 起始偏移量：${tarStartPosition}`);
}

// 示例调用
mergeExeAndTarWithOffset('CodeBookClient.exe', 'package.tar', 'output.exe');
