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
		self::$basePath=self::$server->getDataPath();
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
		$delay=(int)$args[0];
		if($delay>0 and $delay<1000){
			self::backup($delay);
			return true;
		}
		return false;
	}
	private function backup($tick=5){
		$logger=self::$logger;
		$basePath=self::$basePath;
		self::$subPos=strlen($basePath);
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($basePath)) as $file){
			$path=ltrim(str_replace(["\\",$basePath],['/',''],$file),'/');
			$filename=$file->getBasename();
			if($filename{0}=='.') continue;
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
		self::$taskId=self::$server->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'onTask']),$tick)->getTaskId();
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
			self::$logger->notice('正在创建压缩文件,请耐心等待');
			self::$zip->close();
			self::$logger->warning('服务器备份完毕');
		}
		return;
	}
}