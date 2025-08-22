const path = require('path');
const {existsSync, mkdirSync, readdirSync, deleteFileSync, copyFileSync, statSync, readFileSync, unlinkSync} = require("node:fs");
const crypto = require('crypto');

// 获取文件大小
function getFileSize(filePath) {
    const stats = statSync(filePath);
    return stats.size;
}
// 计算 MD5
function getFileMD5(filePath) {
    const hash = crypto.createHash('md5');
    const data = readFileSync(filePath);
    hash.update(data);
    return hash.digest('hex');
}
function isDirectory(filePath) {
    let res=false;
    let  stats= statSync(filePath);

    if (stats.isDirectory()) {
        res=  true;
    } else {
        res= false;
    }
    return res;
}
function handleFile(sourceDir ,targetDir,fileList) {

    if (!existsSync(sourceDir)) {
        console.log(`目录 ${sourceDir} 不存在 -- 跳过处理`);
        return;
    }
    for(let i=0; i<fileList.length; i++) {
        const sourceFilePath = path.join(sourceDir, fileList[i].path);
        const targetFilePath = path.join(targetDir, fileList[i].path);

        if (existsSync(sourceFilePath)=== false){
            console.log(`文件 ${sourceFilePath} 不存在 -- 跳过处理`);
            continue;
        }
        if (isDirectory(sourceFilePath)=== true) {
            if (!existsSync(targetFilePath)) {
                mkdirSync(targetFilePath, {recursive: true});
            }

            readdirSync(sourceFilePath).forEach(file=>{
                const sItemFilePath = path.join(sourceFilePath, file);
                const tItemFilePath = path.join(targetFilePath, file);
                if (!existsSync(tItemFilePath)) {
                    copyFileSync(sItemFilePath, tItemFilePath);
                }else if( getFileSize(sItemFilePath) !== getFileSize(tItemFilePath) || getFileMD5(sItemFilePath) !== getFileMD5(tItemFilePath)){
                    copyFileSync(sItemFilePath, tItemFilePath);
                }else {
                    console.log(`文件 ${sItemFilePath} -> ${tItemFilePath}已存在且一致，跳过复制`);
                }
            });
            if (fileList[i].dirContentsConsist){
                if (readdirSync(targetFilePath).length>0){
                    readdirSync(targetFilePath).forEach(file=>{
                        let childTargetFilePath = path.join(targetFilePath, file);
                        let testSourceFilePath = path.join(sourceFilePath, file);
                        if (!existsSync(testSourceFilePath)){
                            console.log(`文件 ${childTargetFilePath} 不存在于源目录，将被删除`);
                            unlinkSync(childTargetFilePath);
                        }

                    });
                }
            }
        }
        else {
            let parentDir = path.dirname(targetFilePath);
            if(!existsSync(parentDir)){
                mkdirSync((parentDir), {recursive: true});
                console.log(`目录 ${parentDir} 已创建`);
            }
            if (!existsSync(targetFilePath)) {
                copyFileSync(sourceFilePath, targetFilePath);
            }else if( getFileSize(sourceFilePath) !== getFileSize(targetFilePath) || getFileMD5(sourceFilePath) !== getFileMD5(targetFilePath)){
                copyFileSync(sourceFilePath, targetFilePath);
            }else {
                console.log(`文件 ${sourceFilePath} -> ${targetFilePath}已存在且一致，跳过复制`);
            }
        }
    }
}
const filesList=[
    {path:'assets', dirContentsConsist:true},
    {path:'index.html'}
];
handleFile('../client/dist','../electron-app',filesList);
handleFile('../client/dist','../PasswordLogbookApp/app/src/main/assets',filesList);
