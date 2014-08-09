<?php

namespace legendofmcpe\statscore\log;

use pocketmine\Player;

class MysqliLog extends Log{
	/** @var \mysqli */
	private $db;
	/** @var string */
	private $table;
	protected function init(array $args){
		$this->table = $args["table"];
		$this->db = $args[0];
		$this->db->query("CREATE TABLE IF NOT EXISTS players (
				name VARCHAR(31) PRIMARY KEY,
				last_ip VARBINARY(4) DEFAULT '{$this->db->escape_string("\x00\x00\x00\x00")}',
				last_join INT UNSIGNED, # 4 bytes are very enough. I don't think this plugin can last until year 2106, which is when 0xFFFFFFFF is reached.
				last_online INT UNSIGNED,
				total_online INT UNSIGNED DEFAULT 0,
				offline_days SMALLINT UNSIGNED DEFAULT 0, # expiries after 200 years
				chat_msg_cnt INT UNSIGNED DEFAULT 0, # expiries when a player continuously spams one message per second for 136 years :P
				chat_msg_total_len BIGINT UNSIGNED DEFAULT 0, # too large that calculators refuse to calculate the expiry time
				chat_msg_freq_avg DOUBLE(4,2) UNSIGNED DEFAULT 0, # limited with chat-freq-timeout to reset after 9999 seconds
				chat_msg_freq_cnt INT UNSIGNED DEFAULT 0, # more than chat_msg_cnt
				chat_msg_mb_cnt INT UNSIGNED DEFAULT 0, # assumes all his 136 years of spam chat are multibyte messages
				chat_msg_mb_len BIGINT UNSIGNED DEFAULT 0 # same as above
				);");
		$this->db->query("CREATE TABLE IF NOT EXISTS coords (ip VARBINARY(4) PRIMARY KEY, coords VARCHAR(15));");
		$this->db->query("CREATE TABLE IF NOT EXISTS timezones (coords VARCHAR(15) PRIMARY KEY, delta MEDIUMINT);");
		$this->db->query("CREATE TABLE IF NOT EXISTS deaths (player VARCHAR(31), reason TEXT(255), times MEDIUMINT);");
		$this->db->query("CREATE TABLE IF NOT EXISTS kills (player VARCHAR(31), victim TEXT(63), times MEDIUMINT);");
		$result = $this->db->query("SELECT coords FROM coords WHERE ip = '".$this->db->escape_string($this->formatIP("0.0.0.0"))."';");
		$ret = $result->fetch_assoc();
		$result->close();
		if(!is_array($ret)){
			$this->fetchCurrentDelta();
		}
	}
	protected function startSession(Player $player){
		$now = time();
		$result = $this->db->query("SELECT last_ip FROM players WHERE name = '{$this->db->escape_string($player->getName())}';");
		$array = $result->fetch_assoc();
		$result->close();
		if(!is_array($array)){
			$this->db->query("INSERT INTO players (name, last_join, last_online) VALUES (
				'{$this->db->escape_string($player->getName())}', $now, $now);");
			$result = $this->db->query("SELECT coords FROM coords WHERE ip = '".
					$this->db->escape_string($this->formatIP($player->getAddress()))."';");
			$array = $result->fetch_assoc();
			$result->close();
			if(!is_array($array)){
				$this->scheduleIPFetch($player->getAddress());
			}
		}
		$result->close();
	}
	protected function endSession(Player $player){

	}
	protected function addOfflineDays(Player $player, $days){
		$this->updateColumn($player, "offline_days", "offline_days + $days");
	}
	protected function setLastJoin(Player $player){
		$this->updateColumn($player, "last_join", time());
	}
	protected function setLastOnline(Player $player){
		$this->updateColumn($player, "last_online", time());
	}
	protected function addTotalOnline(Player $player, $time){
		$this->updateColumn($player, "total_online", $time);
	}
	protected function incChatMsgCnt(Player $player){
		$this->updateColumn($player, "chat_msg_cnt", "chat_msg_cnt + 1");
	}
	protected function addChatMsgLen(Player $player, $len){
		$this->updateColumn($player, "chat_msg_total_len", "chat_msg_total_len + $len");
	}
	protected function incChatMsgFreqRecCnt(Player $player){
		$this->updateColumn($player, "chat_msg_freq_cnt", "chat_msg_freq_cnt + 1");
	}
	protected function addChatMsgFreq(Player $player, $secs){
		$this->updateColumn($player, "chat_msg_freq_avg", "(chat_msg_freq_avg * chat_msg_freq_cnt + $secs) / (chat_msg_freq_cnt + 1)");
	}
	protected function incMbChat(Player $player){
		$this->updateColumn($player, "chat_msg_mb_cnt", "chat_msg_mb_cnt + 1");
	}
	protected function addMbChat(Player $player, $delta){
		$this->updateColumn($player, "chat_msg_mb_len", "chat_msg_mb_len + $delta");
	}
	protected function addDeath(Player $player, $reason){
		$name = $this->db->escape_string($player->getName());
		$cause = $this->db->escape_string($reason);
		$result = $this->db->query("SELECT times FROM deaths WHERE name = '$name' AND reason = '$cause';");
		$data = $result->fetch_assoc();
		$result->close();
		if(is_array($data)){
			$this->db->query("UPDATE deaths SET times = times + 1 WHERE name = '$name' AND reason = '$cause';");
		}
		else{
			$this->db->query("INSERT INTO deaths VALUES ('$name', '$cause', 1);");
		}
	}
	protected function addKill(Player $player, $victim){
		$name = $this->db->escape_string($player->getName());
		$victim = $this->db->escape_string($victim);
		$result = $this->db->query("SELECT times FROM kills WHERE name = '$name' AND victim = '$victim';");
		$data = $result->fetch_assoc();
		$result->close();
		if(is_array($data)){
			$this->db->query("UPDATE kills SET times = times + 1 WHERE name = '$name' AND victim = '$victim';");
		}
		else{
			$this->db->query("INSERT INTO kills VALUES ('$name', '$victim', 1);");
		}
	}
	protected function setLastIP(Player $player){
		$this->updateColumn($player, "last_ip", $this->formatIP($player->getAddress()));
	}
	protected function setLocalCoords($coords){
		$this->setCoords("0.0.0.0", $coords);
	}
	protected function setCoords($ip, $coords){
		$ip = $this->db->escape_string($this->formatIP($ip));
		$coords = $this->db->escape_string($coords);
		$this->db->query("REPLACE INTO coords VALUES ('$ip', '$coords');");
	}
	protected function setTimezoneDeltaFromUTC($coords, $delta){
		$coords = $this->db->escape_string($coords);
		$this->db->query("REPLACE INTO timezones VALUES ('$coords', $delta);");
	}
	protected function setCurrentTimezoneDeltaFromUTC($delta){
		$this->setTimezoneDeltaFromUTC($this->getCoords("0.0.0.0"), $delta);
	}
	public function getTotalOnline($name){
		// TODO: Implement getTotalOnline() method.
	}
	public function getLastOnline($name){
		// TODO: Implement getLastOnline() method.
	}
	public function getLastJoin($name){
		// TODO: Implement getLastJoin() method.
	}
	public function getLastIP($name){
		// TODO: Implement getLastIP() method.
	}
	public function getCoords($ip){
		if($ip === "127.0.0.1" or strpos($ip, "10.") === 0 or strpos($ip, "192.168.") === 0){
			$ip = "0.0.0.0";
		}
		else{
			$tokens = explode(".", $ip);
			if($tokens[0] === "172" and 16 <= ((int) $tokens[1]) and ((int) $tokens[1]) <= 31){
				$ip = "0.0.0.0";
			}
		}
		$result = $this->db->query("SELECT coords FROM coords WHERE ip = '{$this->db->escape_string($ip)}';");
		$array = $result->fetch_assoc();
		$result->close();
		if(is_array($array)){
			return $array["coords"];
		}
		$this->scheduleIPFetch($ip);
		return null;
	}
	public function getTimezoneDelta($coords){
		$result = $this->db->query("SELECT delta FROM timezones WHERE coords = '{$this->db->escape_string($coords)}';");
		$array = $result->fetch_assoc();
		$result->close();
		if(is_array($array)){
			return $array["delta"];
		}
		return null;
	}
	public function getCurrentTimezoneDeltaFromUTC(){
		return $this->getTimezoneDelta($this->getCoords("0.0.0.0"));
	}
	private function updateColumn(Player $player, $column, $value){
		$this->db->query("UPDATE players SET $column = $value WHERE name = '{$this->db->escape_string($player->getName())}';");
	}
	public function getDeaths($name){
		// TODO: Implement getDeaths() method.
	}
	public function getKills($name){
		// TODO: Implement getKills() method.
	}
	public function getChatMsgTotalLen($name){
		// TODO: Implement getChatMsgTotalLen() method.
	}
	public function getChatMsgCnt($name){
		// TODO: Implement getChatMsgCnt() method.
	}
	public function getMbChatTotalLen($name){
		// TODO: Implement getMbChatTotalLen() method.
	}
	public function getMbChatCnt($name){
		// TODO: Implement getMbChatCnt() method.
	}
	public function getChatFreq($name){
		// TODO: Implement getChatFreq() method.
	}
}
