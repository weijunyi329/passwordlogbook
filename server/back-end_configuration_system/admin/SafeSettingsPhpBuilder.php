<?php

class SafeSettingsPhpBuilder
{
   private $settings;
   private $updatedItem;

   private $relativePath;
   public function __construct($relativePath)
   {
       $this->relativePath=$relativePath;
       $this->settings=require $relativePath.'security/safe.settings.php';
       $this->updatedItem=[];
   }
   public function putValue($configName,$value)
   {
       if(!isset($this->settings[$configName])){
           return;
       }
       if ($this->settings[$configName]!=$value){
           $this->updatedItem[]=$configName;
           $this->settings[$configName]=$value;
       }
   }

   public function getValue($configName)
   {
       return $this->settings[$configName];
   }
    /**
     * 是否有更新
     * @return bool
     */
   public function isUpdated()
   {
       return (count($this->updatedItem)>0);
   }

   public function getUpdatedItem()
   {
       return $this->updatedItem;
   }
    /**
     * 保存settings
     * @return void
     */
   public function save()
   {
       // 生成配置文件内容
       $configContent = "<?php\n\nreturn " . var_export($this->settings, true) . ";".PHP_EOL;
       // 写入配置文件
       file_put_contents($this->relativePath.'security/safe.settings.php', $configContent);
   }
}