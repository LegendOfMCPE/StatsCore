<?php

namespace legendofmcpe\statscore;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
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
				"explosion" => 0,
				"drown" => 0,
				"burn" => 0,
				"lava" => 0,
				"fire" => 0,
				"shot" => 0,
				"suffocation" => 0,
				"command" => 0,
				"void" => 0,
				"misc" => 0,
				"entity" => [],
			],
		];
		$day = 60 * 60 * 24;
		if(($diff = $micro - $this->data["last-quit"]) >= $day /* * 1.5 */){ // any comments on `* 1.5`? anyone can change it into a timezone API?
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
			$this->data["chat"]["multibyte"]["count"]++;
			$this->data["chat"]["multibyte"]["total length"] += mb_strlen($message);
			$this->data["chat"]["multibyte"]["characters length"] += (strlen($message) - mb_strlen($message));
		}
	}
	public function onAttack(EntityDamageByEntityEvent $event){
		// TODO
	}
	public function onDamage(EntityDamageEvent $event){
		if($this->player->getHealth() - $event->getFinalDamage() <= 0){ // death
			$cause = $event->getCause();
			switch($cause){
				case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
					$key = "explosion";
					break;
				case EntityDamageEvent::CAUSE_CUSTOM:
					$key = "misc";
					break;
				case EntityDamageEvent::CAUSE_DROWNING:
					$key = "drown";
					break;
				case EntityDamageEvent::CAUSE_FALL:
					$key = "fall";
					break;
				case EntityDamageEvent::CAUSE_FIRE:
					$key = "fire";
					break;
				case EntityDamageEvent::CAUSE_FIRE_TICK:
					$key = "burn";
					break;
				case EntityDamageEvent::CAUSE_LAVA:
					$key = "lava";
					break;
				case EntityDamageEvent::CAUSE_MAGIC:
					$key = "misc";
					break;
				case EntityDamageEvent::CAUSE_PROJECTILE:
					$key = "shot";
					break;
				case EntityDamageEvent::CAUSE_SUFFOCATION:
					$key = "suffocation";
					break;
				case EntityDamageEvent::CAUSE_SUICIDE:
					$key = "command";
					break;
				case EntityDamageEvent::CAUSE_VOID:
					$key = "void";
					break;
			}
			if(isset($key)){
				$this->data["deaths"][$key]++;
			}
			elseif($event instanceof EntityDamageByEntityEvent){
				$cause = $event->getDamager();
				$class = strtolower(get_class($cause));
				$class = array_slice(explode("\\", $class), -1)[0];
				if(!isset($this->data["deaths"]["entity"][$class])){
					$this->data["deaths"]["entity"][$class] = 0;
				}
				$this->data["deaths"]["entity"][$class] ++;
			}
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
