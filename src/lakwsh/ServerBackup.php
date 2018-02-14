<?php
namespace lakwsh;
use pocketmine\plugin\PluginBase;

class ServerBackup extends PluginBase{
	public function onEnable(){
		self::onRun();
	}
	private function onRun()	{
		$files=array();
		$filePath=$this->getServer()->getDataPath();
		$subPos=strlen($filePath);
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($filePath)) as $file){
			$path=ltrim(str_replace(["\\",$filePath],['/',''],$file),'/');
			if($path{0}==='.' or stripos($path,'/.')!==false) continue;
			$files[]=$path;
		}
		$filename=$filePath.'bak.zip';
		if(file_exists($filename) and !@unlink($filename)) return false;
		$zip=new \ZipArchive();
		if($zip->open($filename,\ZIPARCHIVE::CREATE)!==true) return false;
		foreach($files as $val){if(file_exists($val)){$zip->addFile($val,substr($val,$subPos));}}
		$zip->close();
		return true;
	}
}