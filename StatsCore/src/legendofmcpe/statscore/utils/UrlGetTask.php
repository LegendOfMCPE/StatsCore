<?php

namespace legendofmcpe\statscore\utils;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Utils;

class UrlGetTask extends AsyncTask{
	/** @var string */
	private $url;
	/** @var callable */
	private $callback;
	/** @var int */
	private $timeout;
	private $args;
	public function __construct($url, callable $callback, $timeout, ...$args){
		$this->url = $url;
		$this->callback = $callback;
		$this->timeout = $timeout;
		$this->args = $args;
	}
	public function onRun(){
		$this->setResult(Utils::getURL($this->url, $this->timeout));
	}
	public function onCompletion(Server $server){
		call_user_func($this->callback, $this->getResult(), ...$this->args);
	}
}
