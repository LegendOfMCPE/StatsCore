<?php

namespace legendofmcpe\statscore;

use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;

class StatsCore extends PluginBase implements Listener{
	private static $name = "StatsCore";
	/** @var Logger */
	private $mlogger;
	/** @var RequestList */
	private $reqList;
	/** @var OfflineMessageList */
	private $offlineInbox;
	public function onLoad(){
		self::$name = $this->getName();
	}
	public function onEnable(){
		$this->mlogger = new Logger($this);
		$this->reqList = new RequestList($this);
		$this->offlineInbox = new OfflineMessageList($this);
		@mkdir($this->getPlayersFolder());
		$cmd = new CustomPluginCommand("online", $this, array($this, "onOnlineCmd"));
		$cmd->setDescription("View a player's total online time.");
		$cmd->setUsage("/online <player>");
		$cmd->setPermission("statscore.cmd.online");
		$cmd->reg(true);
		$cmd = new CustomPluginCommand("request", $this, array($this, "onReqCmd"));
		$cmd->setDescription("Manage your pending requests.");
		$cmd->setUsage("/req list|accept|reject [id]");
		$cmd->setPermission("statscore.cmd.request");
		$cmd->reg(true);
		$cmd = new CustomPluginCommand("inbox", $this, array($this->offlineInbox, "onCommand"));
		$cmd->setDescription("Read your inbox");
		$cmd->setUsage("/inbox");
		$cmd->setPermission("statscore.cmd.inbox");
		$cmd->reg(true);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}
	public function onDisable(){
		$this->mlogger->disable();
		$this->reqList->finalize();
	}
	public function getMLogger(){
		return $this->mlogger;
	}
	public function getRequestList(){
		return $this->reqList;
	}
	public function getPlayersFolder(){
		return $this->getDataFolder()."players/";
	}
	public function onOnlineCmd($cmd, array $args, CommandSender $issuer){
		if(!isset($args[0])){
			return false;
		}
		$player = $this->getServer()->getPlayer(array_shift($args));
		if(!($player instanceof Player)){
			return CustomPluginCommand::NO_PLAYER;
		}
		$time = $this->getMLogger()->getSession($player)->update();
		switch(true){
			case $time >= 60 * 60 * 24 * 2:
				$str = round($time / 60 / 60 / 24, 2)." days";
				break;
			case $time >= 60 * 60 * 2:
				$str = round($time / 60 / 60, 2)." hours";
				break;
			case $time >= 60 * 2:
				$str = round($time / 60, 2)." minutes";
				break;
			default:
				$str = round($time, 2)." seconds";
				break;
		}
		return $player->getDisplayName()." has been online for a total of $str.";
	}
	public function onReqCmd($cmd, array $args, Player $issuer){
		if(!isset($args[0])){
			return false;
		}
		switch($subcmd = array_shift($args)){
			case "list":
				if(!$issuer->hasPermission("statscore.cmd.request.list")){
					return CustomPluginCommand::NO_PERM;
				}
				if(isset($args[0])){
					$id = $args[0];
					// vvv what the vvv //
					/** @var int[]|Request[] $req */
					$req = $this->getRequestList()->getRequest($id, new PlayerRequestable($issuer));
					if(is_array($req)){
						return "#$req[0]: {$req[1]->getContent()}";
					}
				}
				$this->getRequestList()->notifyStrReq(new PlayerRequestable($issuer)); // can be instantiated because it identifies using $requestable->getRequestableIdentifier() not spl_object_hash()
				return true;
			case "accept":
				if(!$issuer->hasPermission("statscore.cmd.request.accept")){
					return CustomPluginCommand::NO_PERM;
				}

				return true;
			case "reject":
				if(!$issuer->hasPermission("statscore.cmd.request.reject")){
					return CustomPluginCommand::NO_PERM;
				}
				break;
		}
		return false;
	}
	public function onJoin(PlayerJoinEvent $event){
		$p = $event->getPlayer();
		$this->getRequestList()->onRequestable(new PlayerRequestable($p));
	}
	/**
	 * @return static|null
	 */
	public static function getInstance(){
		return Server::getInstance()->getPluginManager()->getPlugin(self::$name);
	}
}
