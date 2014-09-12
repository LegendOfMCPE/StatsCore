<?php

namespace legendofmcpe\statscore\log;

use pocketmine\Player;

class SQLite3Log extends Log{
	protected function init(array $args){
		// TODO: Implement init() method.
	}
	protected function startSession(Player $player){
		// TODO: Implement startSession() method.
	}
	protected function endSession(Player $player){
		// TODO: Implement endSession() method.
	}
	protected function addOfflineDays(Player $player, $days){
		// TODO: Implement addOfflineDays() method.
	}
	protected function setLastJoin(Player $player){
		// TODO: Implement setLastJoin() method.
	}
	protected function setLastOnline(Player $player){
		// TODO: Implement setLastOnline() method.
	}
	protected function addTotalOnline(Player $player, $time){
		// TODO: Implement addTotalOnline() method.
	}
	protected function incChatMsgCnt(Player $player){
		// TODO: Implement incChatMsgCnt() method.
	}
	protected function addChatMsgLen(Player $player, $len){
		// TODO: Implement addChatMsgLen() method.
	}
	protected function incChatMsgFreqRecCnt(Player $player){
		// TODO: Implement incChatMsgFreqRecCnt() method.
	}
	protected function addChatMsgFreq(Player $player, $secs){
		// TODO: Implement addChatMsgFreq() method.
	}
	protected function incMbChat(Player $player){
		// TODO: Implement incMbChat() method.
	}
	protected function addMbChat(Player $player, $delta){
		// TODO: Implement addMbChat() method.
	}
	protected function addDeath(Player $player, $reason){
		// TODO: Implement addDeath() method.
	}
	protected function addKill(Player $player, $victim){
		// TODO: Implement addKill() method.
	}
	protected function setLastIP(Player $player){
		// TODO: Implement setLastIP() method.
	}
	protected function setLocalCoords($coords){
		// TODO: Implement setLocalCoords() method.
	}
	protected function setCoords($ip, $coords){
		// TODO: Implement setCoords() method.
	}
	protected function setTimezoneDeltaFromUTC($coords, $delta){
		// TODO: Implement setTimezoneDeltaFromUTC() method.
	}
	protected function setCurrentTimezoneDeltaFromUTC($delta){
		// TODO: Implement setCurrentTimezoneDeltaFromUTC() method.
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
		// TODO: Implement getCoords() method.
	}
	public function getTimezoneDelta($coords){
		// TODO: Implement getTimezoneDelta() method.
	}
	public function getCurrentTimezoneDeltaFromUTC(){
		// TODO: Implement getCurrentTimezoneDeltaFromUTC() method.
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
	public function getOfflineDays($name){
		// TODO: Implement getOfflineDays() method.
	}
}
