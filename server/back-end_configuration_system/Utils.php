<?php
/**
 * 检查数据是否为空
 * @param mixed $data 待检查的数据
 * @return bool 如果数据为空返回true，否则返回false
 */
function isEmpty($data): bool {
    return (!isset($data) || $data == null || trim($data) == '');
}

/**
 * 检查数据是否为数字
 * @param mixed $data 待检查的数据
 * @return bool 如果数据是数字返回true，否则返回false
 */
function isNum($data): bool {
    return !isEmpty($data) && is_numeric($data);
}


/**从变量中获取数字
 * @param $data
 * @return int
 */
function getNumber($data)
{
    if (isNum($data)) {
        return intval($data);
    }
    return 0;
}

/**
 * 检查是否是png
 * @param $filePath
 * @return bool
 */
function isPngFile($filePath) {
    // 检查文件是否存在
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return false;
    }

    // 打开文件（只读模式）
    $file = fopen($filePath, 'rb');
    if (!$file) {
        return false;
    }

    // 读取前 8 个字节（PNG 魔数）
    $header = fread($file, 8);
    fclose($file);

    // PNG 文件头（十六进制表示）
    $pngMagicNumbers = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";

    // 检查是否匹配
    return ($header === $pngMagicNumbers);
}
/**
 *
 * 检查数据是否以指定字符串开头
 * @param string $str 要检查的字符串
 * @param string $with 要匹配的开头字符串
 * @return bool 如果$str以$with开头返回true，否则返回false
 */
function str_startWith(string $str, string $with): bool
{
    // 检查参数是否设置且为字符串类型
    if (!isset($str) || !isset($with)) {
        return false;
    }

    $strLen = strlen($str);
    $withLen = strlen($with);

    // 如果源字符串比要检查的前缀短，则不可能匹配
    if ($strLen < $withLen) {
        return false;
    }

    // 使用substr_compare比较字符串开头部分是否匹配
    return substr_compare($str, $with, 0, $withLen) === 0;
}

/**
 * 检查数据是否以指定字符串结束
 * @param string $str 要检查的字符串
 * @param string $with 要匹配的结尾字符串
 * @return bool 如果$str以$with结尾返回true，否则返回false
 */
function str_endWith(string $str, string $with): bool
{
    // 检查参数是否设置且为字符串类型
    if (!isset($str) || !isset($with) ) {
        return false;
    }

    $strLen = strlen($str);
    $withLen = strlen($with);

    // 如果源字符串比要检查的后缀短，则不可能匹配
    if ($strLen < $withLen) {
        return false;
    }

    // 使用substr_compare比较字符串末尾部分是否匹配
    return substr_compare($str, $with, $strLen - $withLen) === 0;
}
/**
 * 获取13位时间戳
 * @return int
 */
function obtain13Timestamp(): int
{
    $micrometer = microtime(true);
    return $timestamp = round($micrometer * 1000);
}