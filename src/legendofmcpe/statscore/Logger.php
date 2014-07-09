<?php

namespace legendofmcpe\statscore;

use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;

class Logger implements Listener{
	private $plugin;
	private $server;
	/** @var Session[] */
	private $sessions = [];
	public function __construct(StatsCore $core){
		$this->plugin = $core;
		$this->server = Server::getInstance();
		$this->server->getPluginManager()->registerEvents($this, $core);
	}
	public function onJoin(PlayerJoinEvent $e){
		$p = $e->getPlayer();
		$this->sessions[$this->CID($p)] = new Session($p);
	}
	public function onQuit(PlayerQuitEvent $e){
		$p = $e->getPlayer();
		if($this->getSession($p) !== false){
			$this->sessions[$this->CID($p)]->onQuit();
			unset($this->sessions[$this->CID($p)]);
		}
	}
	/**
	 * @param PlayerChatEvent $e
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onChat(PlayerChatEvent $e){
		if($this->getSession($e->getPlayer()) instanceof Session){
			$this->getSession($e->getPlayer())->onChat($e->getMessage());
		}
	}
	public function updateSession(Player $player){
		return $this->getSession($player)->update();
	}
	public function disable(){
	}
	/**
	 * @param Player|string $player
	 * @return bool|Session
	 */
	public function getSession($player){
		if(!($player instanceof Player)){
			$player = $this->server->getPlayer($player);
			if(!($player instanceof Player)){
				return false;
			}
		}
		return isset($this->sessions[$this->CID($player)]) ? $this->sessions[$this->CID($player)]:false;
	}
	/**
	 * @param $name
	 * @return int
	 */
	public function getTotalOnlineTime($name){
		if(($p = $this->server->getPlayer($name)) instanceof Player){
			return $this->updateSession($p);
		}
		$file = Session::getPath($name);
		if(!is_file($file)){
			return 0;
		}
		$data = json_decode(file_get_contents($file));
		return $data["online"];
	}
	public function getFullOfflineDays($player){
		if(($p = $this->server->getPlayer($player)) instanceof Player){
			return $this->getSession($p)->getData("full offline days");
		}
		$file = Session::getPath($player);
		if(!is_file($file)){
			return 0;
		}
		$data = json_decode(file_get_contents($file));
		return $data["full offline days"];
	}
	public function CID(Player $player){
		return $player->getAddress().":".$player->getPort();
	}
}
