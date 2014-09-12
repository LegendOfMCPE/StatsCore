<?php

namespace legendofmcpe\statscore\request;

use pocketmine\Player;
use pocketmine\Server;

class PlayerRequestable implements Requestable{
	private $name;
	/**
	 * @param Player|string $player
	 */
	public function __construct($player){
		if($player instanceof Player){
			$this->name = $player->getName();
		}
		else{
			$this->name = $player;
		}
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
	public function getName(){
		return $this->name;
	}
	public function getGetter(){
		return str_replace(["<name>"], [var_export($this->name, true)], <<<'EOC'
$requestable = $this->main->getServer()->getPlayerExact(<name>);
if(!($requestable instanceof \pocketmine\Player)){
	$requestable = null;
}
EOC
		);
	}
	public function getMetadata(){
		return $this->name;
	}
}
