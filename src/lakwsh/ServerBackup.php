<?php
namespace lakwsh;
use pocketmine\command\{Command,CommandSender,ConsoleCommandSender};
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\CallbackTask;
use pocketmine\utils\TextFormat;

class ServerBackup extends PluginBase{
	/** @var $zip \ZipArchive */
	private static $zip;
	/** @var $server \pocketmine\Server */
	private static $server;
	/** @var $server \pocketmine\plugin\PluginLogger */
	private static $logger;
	private static $count;
	private static $taskId;
	private static $subPos;
	private static $basePath;
	private static $now=0;
	private static $tip=false;
	private static $fileList=array();
	public function onEnable(){
		self::$server=$this->getServer();
		self::$logger=$this->getLogger();
	}
	public function onCommand(CommandSender $sender,Command $command,$label,array $args){
		if(!($sender instanceof ConsoleCommandSender)){
			$sender->sendMessage(TextFormat::RED.'此命令只能在控制台使用');
			return true;
		}
		if(!isset($args[0])){
			self::backup();
			return true;
		}
		if(isset($args[1])) self::$tip=true;
		else self::$tip=false;
		if(is_int($args[0]) and $args[0]>0){
			self::backup($args[0]);
			return true;
		}
		return false;
	}
	private function backup($tick=5){
		$server=self::$server;
		$logger=self::$logger;
		self::$basePath=$server->getDataPath();
		$basePath=self::$basePath;
		self::$subPos=strlen($basePath);
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath)) as $file){
			$path=ltrim(str_replace(["\\",$basePath],['/',''],$file),'/');
			if($path{0}==='.' or stripos($path,'/.')!==false) continue;
			self::$fileList[]=$path;
		}
		self::$count=count(self::$fileList);
		$filename=$basePath.'backup_'.time().'.zip';
		if(file_exists($filename) and !@unlink($filename)){
			$logger->warning('无目录写入权限');
			return false;
		}
		self::$zip=new \ZipArchive();
		if(self::$zip->open($filename,\ZIPARCHIVE::CREATE)!==true){
			$logger->warning('无法创建压缩文件');
			return false;
		}
		$logger->warning('正在备份服务器');
		self::$taskId=$server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'onTask']),$tick)->getTaskId();
		return true;
	}
	public function onTask(){
		$file=self::$fileList[self::$now];
		if(file_exists($file)){
			if(self::$tip) self::$logger->notice('正在压缩: '.$file);
			self::$zip->addFile($file,substr($file,self::$subPos));
		}
		self::$now++;
		if(self::$now>=self::$count){
			self::$server->getScheduler()->cancelTask(self::$taskId);
			self::$zip->close();
			self::$logger->warning('服务器备份完毕');
		}
		return;
	}
}