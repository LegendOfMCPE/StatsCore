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
		];
		$day = 60 * 60 * 24;
		if(($diff = $micro - $this->data["last-quit"]) >= $day/* * 1.5*/){
			$this->data["full offline days"] += ((int) ($diff / $day));
		}
		$this->session = $micro;
	}
	public function onQuit(){
		$micro = $this->update();
		$this->data["last-quit"] = $micro;
		$this->save();
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
