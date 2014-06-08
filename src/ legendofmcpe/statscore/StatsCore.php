<?php

namespace legendofmcpe\statscore;

use pocketmine\Server;
use pocketmine\plugin\PluginBase;

class StatsCore extends PluginBase{
	private static $name = "StatsCore";
	private $logger;
	public function onLoad(){
		self::$name = $this->getName();
		$this->logger = new Logger($this);
	}
	public function onEnable(){
		$this->logger->enable();
		$this->reqList = new RequestList($this);
	}
	public function onDisable(){
		$this->logger->disable();
		$this->reqList->finalize();
		unset($this->reqList);
	}
	public function getLogger(){
		return $this->logger;
	}
	public static function getInstance(){
		return Server::getInstance()->getPluginManager()->getPlugin(self::$name);
	}
}
