<?php

namespace legendofmcpe\statscore;

use pocketmine\Player;
use pocketmine\Server;

class PlayerRequestable implements Requestable{
	private $name;
	public function __construct(Player $player){
		$this->name = $player->getName();
	}
	public function isAvailable(){
		return Server::getInstance()->getPlayer($this->name) instanceof Player;
	}
	public function sendMessage($message){
		$p = Server::getInstance()->getPlayer($this->name);
		if($p instanceof Player){
			$p->sendMessage($message);
		}
	}
	public function getRequestableIdentifier(){
		return "PlayerRequestable ".strtolower($this->name);
	}
}
