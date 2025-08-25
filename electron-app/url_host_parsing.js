const fs = require('fs');
const path = require('path');

const https = require('https');
const http = require('http');
const os = require('os');
// const sharp =require("sharp");
const UserAgent =require("./ua.js");
const icotopng =require("./icotopng.node");
const UUID = require("./UUID");
class  URLHostParsing{
    static downloadFile(url, path, maxBytes, timeout){
        return new Promise((resolve, reject) => {
            const req = (url.startsWith('https://')? https: http).get(url, (res) => {
                let data = [];
                let dataLength = 0;

                res.on('data', (chunk) => {
                    dataLength += chunk.length;
                    if (dataLength > maxBytes) {
                        req.abort();
                        reject(new Error('下载超过 10kB 限制'));
                    } else {
                        data.push(chunk);
                    }
                });

                res.on('end', () => {
                    const buffer = Buffer.concat(data);
                    fs.writeFileSync(path, buffer);
                    resolve(path);
                });
            });

            req.on('error', (err) => {
                reject(err);
            });

            req.setTimeout(timeout, () => {
                req.abort();
                reject(new Error('请求超时'));
            });
        });
    }

    static  downConvertPng(parseOriginUrl,parseUrlhost,uploadAddr) {
        return new Promise( async (resolve, reject) => {
            let webtitle = '';
            try {
                let maxBytes = 20480; // 20kB
                let timeout = 3000; // 3秒超时
                let userHome = os.homedir();
                parseUrlhost = (parseOriginUrl.startsWith('https://') ? "https://" : "http://") + parseUrlhost + '/favicon.ico';
                const dirPath = path.join(userHome, '.tmp');
                let icoPath = path.join(dirPath, 'favicon.ico');
                let pngPath = path.join(dirPath, 'favicon.png');

                let res = parseOriginUrl.match(/https?:\/\/([a-z]|[A-Z]|\d|-|_|\.)+\//g);
                if (res && res.length > 0) {
                    await this.getWebpageSource(res[0]).then((success) => {
                        let startIndex = success.indexOf('<title>');
                        let endIndex = success.indexOf('</title>', startIndex + 7);
                        if (startIndex > 0 && endIndex > 0 && startIndex + 7 < endIndex) {
                            webtitle = success.substring(startIndex + 7, endIndex);
                            console.log("subtitle", webtitle);
                        }
                    }, (error) => {
                        //网页取源失败
                        console.log("subtitle_error", error);
                    });
                }

                // 删除旧文件（如果存在）
                if (fs.existsSync(pngPath)) {
                    fs.unlinkSync(pngPath);
                }

                // 创建文件夹（如果不存在）
                fs.mkdirSync(dirPath, {recursive: true});

                // 下载文件
                await this.downloadFile(parseUrlhost, icoPath, maxBytes, timeout);
                console.log("下载成功");

                // const buffer = fs.readFileSync(icoPath);
                // const images = await parseICO(buffer); // 解析 .ico 文件

                console.log('图标解析成功');

                // 选择最大的图标（或指定尺寸）
                // const largestIcon = images.reduce((prev, curr) =>
                //     prev.width > curr.width ? prev : curr
                // );
                console.log('图标选择成功');
                // 使用 sharp 生成 PNG
                // await sharp(largestIcon.buffer)
                //     .png()
                //     .toFile(pngPath);

                let identify = icotopng.detectFileType(icoPath);
                if (identify == 0) {
                    let transformResult = icotopng.convertIcoToPng(icoPath, pngPath);
                    if (transformResult) {
                        console.log('转换成功');
                    } else {
                        throw new Error('转换失败');
                    }
                } else if (identify == 1) {
                    pngPath = icoPath;
                } else {
                    throw new Error('图标格式错误');
                }
                if (fs.existsSync(pngPath) === false) {
                    throw new Error('转换失败');
                }
                console.log('获取成功');
                await this.uploadFile({
                    filePath: pngPath, // 要上传的文件路径
                    url: uploadAddr, // 目标URL
                    formDataFieldName: 'file', // 表单字段名
                    additionalFields: { // 其他表单字段

                    }
                }).then((success) => {
                    //注入webtitle
                    console.log('上传成功' + success);
                    resolve({data: success, title: webtitle});
                }, (error) => {
                    //注入webtitle
                    resolve({error: error, title: webtitle});
                });


            } catch (error) {
                console.error('整个流程出错：', error);
                //注入webtitle
                reject({error: error, title: webtitle});
            }
        });
    }


    static uploadFile(options) {
        const { filePath, url, formDataFieldName = 'file', additionalFields = {} } = options;
        return new Promise((resolve, reject) => {
            // 读取文件内容
            const fileData = fs.readFileSync(filePath);
            const fileName = path.basename(filePath);
            const fileMimeType = 'application/octet-stream';

            // 生成随机 boundary
            const boundary = `----WebKitFormBoundary${Math.random().toString(16).substring(2)}`;

            // 构造 multipart/form-data 请求体使用 Buffer
            const buffers = [];

            // 添加文件字段
            buffers.push(Buffer.from(`--${boundary}\r\n`));
            buffers.push(Buffer.from(`Content-Disposition: form-data; name="${formDataFieldName}"; filename="${fileName}"\r\n`));
            buffers.push(Buffer.from(`Content-Type: ${fileMimeType}\r\n\r\n`));
            buffers.push(fileData);
            buffers.push(Buffer.from('\r\n'));

            // 添加额外表单字段
            for (const [key, value] of Object.entries(additionalFields)) {
                buffers.push(Buffer.from(`--${boundary}\r\n`));
                buffers.push(Buffer.from(`Content-Disposition: form-data; name="${key}"\r\n\r\n`));
                buffers.push(Buffer.from(value + '\r\n'));
            }

            // 结束 boundary
            buffers.push(Buffer.from(`--${boundary}--\r\n`));

            const postData = Buffer.concat(buffers);


            // 解析 URL
            const urlObj = new URL(url);
            const requestOptions = {
                hostname: urlObj.hostname,
                port: urlObj.port || (urlObj.protocol === 'https:' ? 443 : 80),
                path: urlObj.pathname + (urlObj.search || ''),
                method: 'POST',
                headers: {
                    'Uuid':UUID,
                    'Content-Type': `multipart/form-data; boundary=${boundary}`,
                    'Content-Length': Buffer.byteLength(postData),
                    'User-Agent': UserAgent()
                }
            };

            // 创建请求
            const req = http.request(requestOptions, (res) => {
                let responseData = '';
                res.on('data', (chunk) => {
                    responseData += chunk;
                });
                res.on('end', () => {
                    console.log('上传成功:', responseData);
                    resolve(responseData);
                });
            });
            req.setTimeout(5000, () => {
                req.abort();
                reject(new Error('请求超时'));
            });
            req.on('error', (error) => {
                console.error('上传失败:', error);
                reject(error);
            });

            // 写入请求体
            req.write(postData);
            req.end();
        });
    }
    static getWebpageSource(url) {
        return new Promise((resolve, reject) => {
            (url.startsWith('https://') ? https : http).get(url, (res) => {
                let data = '';
                res.on('data', (chunk) => {
                    data += chunk;
                });
                res.on('end', () => {
                    resolve(data);
                });
            }).on('error', (err) => {
                reject(err);
            });
        });
    }
}
module.exports = {
    URLHostParsing
};