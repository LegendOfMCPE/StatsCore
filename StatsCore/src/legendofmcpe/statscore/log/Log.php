<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;
use legendofmcpe\statscore\utils\UrlGetTask;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\utils\Utils;

abstract class Log implements Listener{
	//////////////////////
	// internal section //
	//////////////////////
	/**
	 * @var StatsCore
	 */
	private $core;
	private $googleApiKey;
	/** @var float[] */
	private $lastChat = [];
	public function __construct(StatsCore $core){
		$this->core = $core;
		$this->googleApiKey = $core->getConfig()->get("google timezone api")["api key"]; // TODO validate
		$this->init(array_slice(func_get_args(), 1));
	}
	/**
	 * @return mixed
	 */
	public function getGoogleApiKey(){
		return $this->googleApiKey;
	}
	/**
	 * @return StatsCore
	 */
	public function getCore(){
		return $this->core;
	}
	protected function fetchCurrentDelta(){ //
		$data = Utils::getURL("http://ipinfo.io/json", 3);
		$data = json_decode($data);
		if(!is_array($data)){
			throw new \Exception("Please check internet connection. Unable to retrieve coordinates from http://ipinfo.io");
		}
		$this->setLocalCoords($loc = $data["loc"]);
		$this->setCoords(Utils::getIP(), $loc);
		$data = Utils::getURL("https://maps.googleapis.com/maps/api/timezone/json?key=" .
				$this->getGoogleApiKey()."&location=$loc&timestamp=" .
				time());
		$data = json_decode($data);
		$this->setCurrentTimezoneDeltaFromUTC($data["rawOffset"] + $data["dstOffset"]);
	}
	protected function scheduleIPFetch($ip){
		$scheduler = $this->getCore()->getServer()->getScheduler();
		$me = $this;
		$scheduler->scheduleAsyncTask(
			new UrlGetTask("http://ipinfo.io/$ip/json", function($result) use($scheduler, $me, $ip){
				$result = json_decode($result);
				$me->setCoords($ip, $loc = $result["loc"]);
				$scheduler->scheduleAsyncTask(new UrlGetTask(
					"https://maps.googleapis.com/maps/api/timezone/json?key=".$me->getGoogleApiKey() .
					"&location=$loc&timestamp=".time(),
					function($result) use($me, $loc){
						$result = json_decode($result);
						$me->setTimezoneDeltaFromUTC($loc, $result["rawOffset"] + $result["dstOffset"]);
					}
				));
			}));
	}
	////////////////////////////
	// event handling section //
	////////////////////////////
	/**
	 * @param PlayerLoginEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onLogin(PlayerLoginEvent $event){
		$p = $event->getPlayer();
		$this->startSession($p);
		$this->setLastIP($p);
	}
	/**
	 * @param PlayerJoinEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onJoin(PlayerJoinEvent $event){
		$p = $event->getPlayer();
		$lastJoin = $this->getLastJoin($p->getName());
		$lastJoin += ($delta = $this->getTimezoneDelta($this->getCoords($p->getAddress())));
		$now = time() + $delta;
		$lastJoin = ceil($lastJoin / 60 / 60 / 24);
		$now = floor($now / 60 / 60 / 24);
		$diff = (int) ($now - $lastJoin);
		if($diff >= 1){
			$this->addOfflineDays($p, $diff);
		}
		$this->setLastJoin($p);
	}
	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		$this->incChatMsgCnt($player);
		if(extension_loaded("mbstring")){
			$len = mb_strlen($msg);
			$rawlen = strlen($msg);
			$delta = $rawlen - $len;
			$this->incMbChat($player);
			$this->addMbChat($player, $delta);
		}
		else{
			$len = strlen($msg); // hope this never gets called.
		}
		$this->addChatMsgLen($player, $len);
		$micro = microtime(true);
		$id = $player->getID();
		if(isset($this->lastChat[$id])){
			if($micro - $this->lastChat < 9999){ // chat-freq-timeout
				$this->incChatMsgFreqRecCnt($player);
				$this->addChatMsgFreq($player, $micro - $this->lastChat[$id]);
			}
		}
		$this->lastChat[$id] = $micro;
	}
	/**
	 * @param EntityDeathEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onDeath(EntityDeathEvent $event){
		$ent = $event->getEntity();
		if($ent instanceof Player){
			$reason = $ent->getLastDamageCause();
			if($reason instanceof EntityDamageEvent){
				if($reason instanceof EntityDamageByEntityEvent){
					$reason = "entity ".strtolower((new \ReflectionClass($reason->getDamager()))->getShortName());
				}
				else{
					$reason = $reason->getCause();
				}
			}
			if(is_null($reason)){
				$reason = "unknown";
			}
			$this->addDeath($ent, $reason);
		}
		$cause = $ent->getLastDamageCause();
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){
				$victim = strtolower((new \ReflectionClass($ent))->getShortName());
				$this->addKill($damager, $victim);
			}
		}
	}
	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onQuit(PlayerQuitEvent $event){
		$p = $event->getPlayer();
		$this->setLastOnline($p);
		$this->addTotalOnline($p, time() - $this->getLastJoin($p->getName()));
		$this->endSession($p);
	}
	/////////////////////////////
	// abstract saving section //
	/////////////////////////////
	protected abstract function init(array $args);
	// sessioning
	protected abstract function startSession(Player $player);
	protected abstract function endSession(Player $player);
	// online/offline
	protected abstract function addOfflineDays(Player $player, $days);
	protected abstract function setLastJoin(Player $player);
	protected abstract function setLastOnline(Player $player);
	protected abstract function addTotalOnline(Player $player, $sessionTime);
	// chat
	protected abstract function incChatMsgCnt(Player $player);
	protected abstract function addChatMsgLen(Player $player, $len);
	protected abstract function incChatMsgFreqRecCnt(Player $player);
	protected abstract function addChatMsgFreq(Player $player, $secs);
	protected abstract function incMbChat(Player $player);
	protected abstract function addMbChat(Player $player, $delta);
	// kills
	protected abstract function addDeath(Player $player, $reason);
	protected abstract function addKill(Player $player, $victim);
	// coords & timezones
	protected abstract function setLastIP(Player $player);
	protected abstract function setLocalCoords($coords);
	protected abstract function setCoords($ip, $coords);
	protected abstract function setTimezoneDeltaFromUTC($coords, $delta);
	protected abstract function setCurrentTimezoneDeltaFromUTC($delta);
	/**
	 * @return bool
	 */
	public function isAvailable(){
		return true;
	}
	public function close(){

	}
	/////////////////////////////////
	// abstract API getter section //
	/////////////////////////////////
	public abstract function getOfflineDays($name);
	public abstract function getTotalOnline($name);
	public abstract function getLastOnline($name);
	public abstract function getLastJoin($name);
	public abstract function getLastIP($name);
	public abstract function getDeaths($name);
	public abstract function getKills($name);
	public function getChatMsgAvgLen($name){
		return $this->getChatMsgTotalLen($name) - $this->getChatMsgCnt($name);
	}
	public abstract function getChatMsgTotalLen($name);
	public abstract function getChatMsgCnt($name);
	public function getMbChatAvgLen($name){
		return $this->getMbChatTotalLen($name) - $this->getMbChatCnt($name);
	}
	public abstract function getMbChatTotalLen($name);
	public abstract function getMbChatCnt($name);
	public abstract function getChatFreq($name);
	public abstract function getCoords($ip);
	public abstract function getTimezoneDelta($coords);
	public abstract function getCurrentTimezoneDeltaFromUTC();
	/////////////////////////////////////
	// non-abstract API getter section //
	/////////////////////////////////////
	public function getCurrentTimestampAt($name){
		$ip = $this->getLastIP($name);
		if($ip === null){
			return null;
		}
		$coords = $this->getCoords($ip);
		$delta = $this->getTimezoneDelta($coords);
		return time() + $delta;
	}
	protected function formatIP($ip){
		return implode("", array_map(function($str){
			return chr(intval($str));
		}, explode(".", $ip)));
	}
	protected function unformatIP($bin){
		$values = str_split($bin);
		$numbers = [];
		foreach($values as $value){
			$numbers[] = "$value";
		}
		return implode(".", $numbers);
	}
}
