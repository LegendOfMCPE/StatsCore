<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\utils\UrlGetTask;
use pocketmine\Player;

class LogSession{
	private $player;
	public $joinTime;
	public $ip;
	// the above are set before the init() method
	// the following are set by the init() method
	/** The time for the last time joining. If there is no last join, this is null.
	 * @var float|null
	 */
	public $lastJoin;
	public $chatMsgs;
	public $multibyteChat;
	public $multibyteChars;
	public $chatMsgLengthGT;
	public $chatMsgFreqRecCnt;
	public $chatMsgFreqSum;
	/**
	 * @var int[]
	 */
	public $deathReasons;
	/**
	 * @var int[]
	 */
	public $killTargets;
	// the following are set after the init() method
	public $offlineDays;
	// the following are set at an undefined time (depends on AsyncTask completion)
	/** @var \DateTimeZone|null */
	public $timezone = null; // delta from UTC
	private $lastChatMicro = null;
	public function __construct(Player $player, Log $log){
		$this->player = $player;
		$this->joinTime = microtime(true);
		$this->ip = $player->getAddress();
		$this->init($log);
		if($this->timezone !== null){
			$this->offlineDays += $this->calculateRecentOfflineDays();
		}
	}
	private function init(Log $log){
		$player = $this->player;
		if($log->hasRecords(strtolower($player->getName()))){
			$this->chatMsgs = $log->getChatMessageCount($player);
			// TODO: more
		}
		else{
			$this->initAsDefault($log);
		}
	}
	private function initAsDefault(Log $log){
		$this->lastJoin = null;
		$this->chatMsgs = 0;
		$this->multibyteChat = 0;
		$this->multibyteChars = 0;
		$this->chatMsgLengthGT = 0;
		$this->chatMsgFreqRecCnt = 0;
		$this->chatMsgFreqSum = 0;
		$this->deathReasons = [];
		$this->killTargets = [];
		$log->getPlugin()->getServer()->getScheduler()->scheduleAsyncTask(new UrlGetTask(
			"http://ip-api.com/json/$this->ip", array($this, "updateTimezoneCallback"), 10));
	}
	private function calculateRecentofflineDays(){
		if($this->timezone === null){
			throw new \BadMethodCallException(__FUNCTION__ . " must not be called until LogSession->timezone is defined");
		}
		$joinTime = new \DateTime($this->joinTime, $this->timezone);
		$lastJoin = new \DateTime($this->lastJoin, $this->timezone);
		$joinTimeUnixDays = (int) ($joinTime->getTimestamp() / (3600 * 24));
		$lastJoinUnixDays = (int) ($lastJoin->getTimestamp() / (3600 * 24));
		return $joinTimeUnixDays - $lastJoinUnixDays - 1;
	}
	public function updateTimezoneCallback($json){
		$data = json_decode($json, true);
		$this->timezone = new \DateTimeZone($data["timezone"]);
	}
	public function incrementChatMsgCnt(){
		$this->chatMsgs++;
	}
	public function incrementMultibyteChat(){
		$this->multibyteChat++;
	}
	public function increaseMultibyteChars($chars){
		$this->multibyteChars += $chars;
	}
	public function increaseChatMsgGT($length){
		$this->chatMsgLengthGT += $length;
	}
	public function incChatMsgFreqRecCnt(){
		$this->chatMsgFreqRecCnt++;
	}
	public function addChatMsgFreq($freq){
		$this->chatMsgFreqSum += $freq;
	}
	/**
	 * @param string $reason
	 */
	public function addDeath($reason){
		if(!isset($this->deathReasons[$reason])){
			$this->deathReasons[$reason] = 1;
		}
		else{
			$this->deathReasons[$reason]++;
		}
	}
	public function addKill($type){
		if(!isset($this->killTargets[$type])){
			$this->killTargets[$type] = 1;
		}
		else{
			$this->killTargets[$type]++;
		}
	}
	public function newChatFreq(){
		$micro = microtime(true);
		if($this->lastChatMicro !== null){
			$delta = $micro - $this->lastChatMicro;
			if($delta > 7200){ // TODO config
				$delta = null;
			}
		}
		else{
			$delta = null;
		}
		$this->lastChatMicro = $micro;
		return $delta;
	}
}
