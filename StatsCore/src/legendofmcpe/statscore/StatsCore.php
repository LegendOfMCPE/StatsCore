<?php

namespace legendofmcpe\statscore;

use legendofmcpe\statscore\log\JSONLog;
use legendofmcpe\statscore\log\YAMLLog;
use legendofmcpe\statscore\log\SQLite3Log;
use legendofmcpe\statscore\log\MysqliLog;
use legendofmcpe\statscore\request\PlayerRequestable;
use legendofmcpe\statscore\request\Request;
use legendofmcpe\statscore\request\RequestList;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;

class StatsCore extends PluginBase implements Listener{
	private static $name = "StatsCore";
	/** @var RequestList */
	private $reqList;
	/** @var OfflineMessageList */
	private $offlineInbox;
	/** @var log\Log */
	private $log;
	public function onLoad(){
		self::$name = $this->getName();
	}
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getServer()->getPluginManager()->registerEvents($this->offlineInbox = new OfflineMessageList($this), $this);
		$config = $this->getConfig();
		$dbc = $config->get("log database");
		switch($dbc["type"]){
			case "mysql":
				$conn = $dbc["mysql"];
				$db = new \mysqli($conn["host"], $conn["username"], $conn["password"], $conn["database"], $conn["port"]);
				if($db->connect_error){
					$this->getLogger()->warning("Error connecting to MySQL database: {$db->connect_error}! JSON database will be used instead.");
				}
				else{
					$this->log = new MysqliLog($this, $db);
					break;
				}
			default:
				$this->getLogger()->warning("Unknown database type: ".$dbc["type"].". JSON database will be used.");
			case "json":
				$this->log = new JSONLog($this, $dbc["json"]["dir"], $dbc["json"]["pretty print"]);
				break;
			case "yaml":
				$this->log = new YAMLLog($this, $dbc["yaml"]["dir"]);
				break;
			case "sqlite3":
				$this->log = new SQLite3Log($this, $dbc["sqlite3"]["file"]);
				break;
		}
		$this->getServer()->getPluginManager()->registerEvents($this->log, $this);
		$this->reqList = new RequestList($this);
		$cmd = new CustomPluginCommand("request", $this, array($this, "onReqCmd"));
		$cmd->setDescription("Manage requests");
		$cmd->setUsage("/req <list [id]|accept <id>|reject <id>");
		$cmd->setAliases(["req"]);
		$cmd->setPermission("statscore.cmd.request");
		$cmd->reg(true);
	}
	public function onDisable(){
		$this->reqList->finalize();
	}
	public function getRequestList(){
		return $this->reqList;
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
					$id = intval($args[0]);
					$request = $this->reqList->getRequest($id, new PlayerRequestable($issuer));
					if($request instanceof Request){
						return "Request [{$request->getStrStatus()}] #$id: {$request->getContent()}";
					}
					return "Request #$id doesn't exist/was not sent to you!";
				}
				$this->listRequests($issuer);
				return true;
			case "accept":
				if(!$issuer->hasPermission("statscore.cmd.request.accept")){
					return CustomPluginCommand::NO_PERM;
				}
				if(!isset($args[0])){
					return false;
				}
				$id = intval($args[0]);
				$request = $this->reqList->getRequest($id, new PlayerRequestable($issuer));
				if($request instanceof Request){
					if($request->getStatus() !== Request::PENDING){
						return "You can only accept a pending request!";
					}
					$request->accept();
					return "You have accepted request #$id.";
				}
				return "Request #$id doesn't exist/was not sent to you!";
			case "reject":
				if(!$issuer->hasPermission("statscore.cmd.request.reject")){
					return CustomPluginCommand::NO_PERM;
				}
				if(!isset($args[0])){
					return false;
				}
				$id = intval($args[0]);
				$request = $this->reqList->getRequest($id, new PlayerRequestable($issuer));
				if($request instanceof Request){
					if($request->getStatus() !== Request::PENDING){
						return "You can only reject a pending request!";
					}
					$request->reject();
					return "You have rejected request #$id.";
				}
				return "Request #$id doesn't exist/was not sent to you!";
		}
		return false;
	}
	public function onJoin(PlayerJoinEvent $event){
		$this->listRequests($event->getPlayer());
	}
	public function listRequests(Player $player){
		$requests = $this->getRequestList()->getRequests(new PlayerRequestable($player));
		/** @var Request[] $pending */
		$pending = [];
		foreach($requests as $request){
			if($request->getStatus() === Request::PENDING){
				$pending[] = $request;
			}
		}
		if($cnt = count($pending)){
			$player->sendMessage("You have $cnt pending request(s).");
			foreach($pending as $req){
				$id = $req->getID();
				$content = $req->getContent();
				$player->sendMessage("#$id: $content");
			}
			$player->sendMessage("End of $cnt pending request(s).\n(IDs: ".implode(", ", array_map(function($req){
					/** @var Request $req */
					return $req->getID();
				}, $pending)));
		}
	}
	/**
	 * @return static|null
	 */
	public static function getInstance(){
		return Server::getInstance()->getPluginManager()->getPlugin(self::$name);
	}
	/**
	 * @return \legendofmcpe\statscore\log\Log
	 */
	public function getLog(){
		return $this->log;
	}
}
