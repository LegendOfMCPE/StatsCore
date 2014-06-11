<?php

namespace legendofmcpe\statscore;

use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;

class StatsCore extends PluginBase{
	private static $name = "StatsCore";
	/** @var Logger */
	private $mlogger;
	private $reqList;
	public function onLoad(){
		self::$name = $this->getName();
	}
	public function onEnable(){
		$this->mlogger = new Logger($this);
		$this->reqList = new RequestList($this);
		@mkdir($this->getPlayersFolder());
		$cmd = new CustomPluginCommand("online", $this, array($this, "onOnlineCmd"));
	}
	public function onDisable(){
		$this->mlogger->disable();
		$this->reqList->finalize();
	}
	public function getMLogger(){
		return $this->mlogger;
	}
	public function getRequestList(){
		return $this->reqList;
	}
	public function getPlayersFolder(){
		return $this->getDataFolder()."players/";
	}
	public function onOnlineCmd($cmd, array $args, CommandSender $issuer){
		if(!isset($args[0])){
			return false;
		}
		$player = $this->getServer()->getPlayer(array_shift($args));
		if(!($player instanceof Player)){
			return CustomPluginCommand::NO_PLAYER;
		}
		$time = $this->getMLogger()->getSession($player)->update();
		return $player->getDisplayName()." has been online for ".round($time, 2)." seconds totally.";
	}
	/**
	 * @return static|null
	 */
	public static function getInstance(){
		return Server::getInstance()->getPluginManager()->getPlugin(self::$name);
	}
}
