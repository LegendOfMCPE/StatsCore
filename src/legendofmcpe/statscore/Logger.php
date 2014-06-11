<?php

namespace legendofmcpe\statscore;

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
	public function updateSession(Player $player){
		$this->getSession($player)->update();
	}
	public function disable(){
	}
	/**
	 * @param Player $player
	 * @return bool|Session
	 */
	public function getSession(Player $player){
		return isset($this->sessions[$this->CID($player)]) ? $this->sessions[$this->CID($player)]:false;
	}
	public function CID(Player $player){
		return $player->getAddress().":".$player->getPort();
	}
}
