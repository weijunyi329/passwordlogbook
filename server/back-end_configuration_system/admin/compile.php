<?php
class Compile
{
   public static function settingsStrToArrayList($content)
    {
        $arrayList=array();
        $content=trim($content);

        $list=preg_split('/(\\n|\\r)+/',$content);
        if ($list===false && $content!=='')
            $arrayList[]=$content;
        for ($i = 0; $i < count($list); $i++) {
            $item=trim($list[$i]);
            if (
                $item!='' &&
                !(substr($item,0,1)==='#')&&
                !(substr($item,0,2)==='//')
            ){
                $arrayList[]=$item;
            }
        }
        return $arrayList;
    }
    public static function compilePhpCheckFunction($checkContentList, $functionName='check',$defaultReturn=true,$savePhpFile=null,$hasCompareRetOppo= false)
    {
        $phpStr='<?php '.PHP_EOL.'function '.$functionName.'($value){'.PHP_EOL;
        $valueArrayList=array();
        if ($checkContentList===null || count($checkContentList)==0){
            $phpStr.= '    return '.($defaultReturn?'true':'false').';'.PHP_EOL;
        }else {
            if ($hasCompareRetOppo){
                $defaultReturn=!$defaultReturn;
            }
            for ($i = 0; $i < count($checkContentList); $i++) {
                $contentItem = $checkContentList[$i];
                if (substr($contentItem, 0, 1) == '/' && substr($contentItem, -1) == '/') {
                    $valueArrayList[] = "preg_match('$contentItem',\$value)";
                } else {
                    $valueArrayList[] = "'$contentItem'";
                }
            }
            $phpStr.= '    return '.($defaultReturn?'':'!').'in_array($value,['.PHP_EOL;
            $phpStr.=implode(',',$valueArrayList).PHP_EOL;
            $phpStr.=']);';
        }

        $phpStr.=PHP_EOL.'}'.PHP_EOL;

        if ($savePhpFile!==null)
            file_put_contents($savePhpFile,$phpStr);

        return $phpStr;
    }

}
