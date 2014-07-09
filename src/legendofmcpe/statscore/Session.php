<?php

namespace legendofmcpe\statscore;

use pocketmine\Player;

class Session{
	/** @var float */
	private $session;
	/** @var Player */
	private $player;
	/** @var string */
	private $path;
	/** @var array */
	private $data;
	public function __construct(Player $player){
		$this->player = $player;
		$this->path = self::getPath($player->getName());
		$micro = microtime(true);
		$this->data = is_file($this->path) ? json_decode(file_get_contents($this->path)):[
			"online" => 0,
			"first-join" => $micro,
			"last-quit" => $micro,
			"full offline days" => 0,
			"chat" => [
				"count" => 0,
				"length" => 0,
				"multibyte" => [
					"count" => 0,
					"total length" => 0,
					"characters length" => 0,
				],
			],
			"deaths" => [
				# $reason => $deaths, initialized by keys
				// TODO add this
			],
		];
		$day = 60 * 60 * 24;
		if(($diff = $micro - $this->data["last-quit"]) >= $day/* * 1.5 */){
			$this->data["full offline days"] += ((int) ($diff / $day));
		}
		$this->session = $micro;
	}
	public function onQuit(){
		$micro = $this->update();
		$this->data["last-quit"] = $micro;
		$this->save();
	}
	public function onChat($message){
		$this->data["chat"]["count"]++;
		$this->data["chat"]["length"] += mb_strlen($message); // multibyte characters count as one
		if(strlen($message) !== mb_strlen($message)){ // with multibyte characters!
			$this->data["chat"]["count"]++;
			$this->data["chat"]["total length"] += mb_strlen($message);
			$this->data["chat"]["characters length"] += (strlen($message) - mb_strlen($message));
		}
	}
	public function update(){
		$micro = microtime(true); // this can avoid one or two microsecond's difference xD
		$this->data["online"] += ($micro - $this->session);
		$this->session = $micro;
		return $micro;
	}
	public function save(){
		file_put_contents($this->path, json_encode($this->data));
	}
	public function getData($key){
		return $this->data[$key];
	}
	/**
	 * @param string $name
	 * @return string
	 */
	public final static function getPath($name){
		return StatsCore::getInstance()->getPlayersFolder().strtolower(trim($name)).".json";
	}
}
