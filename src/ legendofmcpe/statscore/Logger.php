<?php

namespace legendofmcpe\statscore;

use pocketmine\event\Listener;

class Logger implements Listener{
	private $plugin;
	private $server;
	public function __construct(StatsCore $core){
		$this->plugin = $core;
		$this->server = Server::getInstance();
		$this->server->getPluginManager()->registerEvents($this, $core);
	}
	public function enable(){
		$this->load();
	}
	public function disable(){
		$this->save();
	}
}
