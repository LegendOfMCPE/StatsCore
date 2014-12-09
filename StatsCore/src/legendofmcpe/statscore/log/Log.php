<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

abstract class Log implements Listener{
	//////////////////////
	// internal section //
	//////////////////////
	/**
	 * @var StatsCore
	 */
	private $plugin;
	/** @var LogSession[] */
	protected $sessions = [];
	public function __construct(StatsCore $plugin){
		$this->plugin = $plugin;
		if(!function_exists("mb_strlen")){
			$plugin->getLogger()->warning("Multibyte String extension not found on this server (function mb_strlen() does not exist). The multibyte-character-statistics feature will be disabled");
			$plugin->getLogger()->warning("This is " . TextFormat::BOLD . TextFormat::UNDERLINE . "NOT" . TextFormat::RESET . TextFormat::YELLOW . " a bug. It is an issue led by your server setup. Do " . TextFormat::BOLD . TextFormat::UNDERLINE . "NOT" . TextFormat::RESET . TextFormat::YELLOW . " report this as a bug.");
		}
	}
	/**
	 * @return StatsCore
	 */
	public function getPlugin(){
		return $this->plugin;
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
	}
	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onChat(PlayerChatEvent $event){
		$player = $event->getPlayer();
		/** @var LogSession $session */
		$session = $this->getSession($player);
		$msg = $event->getMessage();
		$session->incrementChatMsgCnt();
		if(function_exists("mb_strlen")){
			$len = mb_strlen($msg);
			$rawlen = strlen($msg);
			$delta = $rawlen - $len;
			$session->incrementMultibyteChat();
			$session->increaseMultibyteChars($delta / 2);
		}
		else{
			$len = strlen($msg); // hope this never gets called.
		}
		$session->increaseChatMsgGT($player, $len);
		$freq = $session->newChatFreq();
		if($freq !== null){
			$session->incChatMsgFreqRecCnt();
			$session->addChatMsgFreq($freq);
		}
	}
	/** This function forwards the PlayerDeathEvent to EntityDeathEvent. Since PlayerDeathEvent has its own static $handlerList, it is necessary that PlayerDeathEvent has its own listener.
	 * @param PlayerDeathEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function forward_onPlayerDeath(PlayerDeathEvent $event){
		$this->onDeath($event);
	}
	/**
	 * @param EntityDeathEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onDeath(EntityDeathEvent $event){
		$victim = $event->getEntity();
		$cause = $victim->getLastDamageCause(); // @shoghicp I HATE MULTI RETURN TYPE FUNCTIONS!
		if($victim instanceof Player){ // when a player dies
			$reasonId = $cause;
			$victimSession = $this->getSession($victim);
			if($reasonId instanceof EntityDamageEvent){
				if($reasonId instanceof EntityDamageByEntityEvent){
					$resultKey = "entity:" . strtolower((new \ReflectionClass($reasonId->getDamager()))->getShortName());
					unset($reasonId);
				}
				else{
					$reasonId = $reasonId->getCause();
				}
			}
			if(isset($reasonId) and is_int($reasonId)){
				foreach((new \ReflectionClass("pocketmine\\event\\entity\\EntityDamageEvent"))->getConstants() as $name => $value){
					if($value === $reasonId){
						$resultKey = strtolower(substr($name, 6));
					}
				}
				if(!isset($resultKey)){
					$this->plugin->getLogger()->warning("Unable to find cause for entity damage cause ID " .
						"$reasonId. This might be caused by another plugin or a bug. " .
						"Warning triggered from " . __FILE__ . " at line " . __LINE__ . ".");
					// TODO : Find a reason. Possibly bug, possibly other plugins' causes
					$resultKey = "unknown:$reasonId";
				}
			}
			if(!isset($resultKey)){
				$resultKey = "unknown";
			}
			$victimSession->addDeath($resultKey);
		}
		if($cause instanceof EntityDamageByEntityEvent){
			$damager = $cause->getDamager();
			if($damager instanceof Player){ // when a player kills
				$killerSession = $this->getSession($damager);
				$victimType = strtolower((new \ReflectionClass($victim))->getShortName());
				$killerSession->addKill($damager, $victimType);
			}
		}
	}
	/**
	 * @param PlayerQuitEvent $event
	 *
	 * @priority MONITOR
	 */
	public function onQuit(PlayerQuitEvent $event){
		$session =& $this->getSession($event->getPlayer());
		if($session instanceof LogSession){
			$this->saveSession($session);
			unset($session);
		}
	}
	/**
	 * @return bool
	 */
	public function isAvailable(){
		return true;
	}
	public function close(){}
	public function startSession(Player $player){
		$this->sessions[$player->getId()] = new LogSession($player, $this);
	}
	/** Returns the {@link LogSession} object for the specified player, or <code>null</code> if session not started.
	 * @param Player $player
	 * @return null|LogSession
	 */
	public function &getSession(Player $player){
		if(isset($this->sessions[$player->getId()])){
			return $this->sessions[$player->getId()];
		}
		$null = null;
		return $null;
	}
	protected abstract function saveSession(LogSession $session);
	/**
	 * @param string $player
	 * @return bool
	 */
	public abstract function hasRecords($player);
	/**
	 * @param Player|string $player
	 * @return int
	 */
	public function getChatMessageCount($player){
		if($player instanceof Player){
			if(isset($this->sessions[$player->getId()])){
				return $this->sessions[$player->getId()]->chatMsgs;
			}
			$player = $player->getName();
		}
		$player = strtolower($player);
		return $this->_getChatMessageCount($player);
	}
	protected abstract function _getChatMessageCount($lowName);
	// TODO: more getters and abstract _getters
}
