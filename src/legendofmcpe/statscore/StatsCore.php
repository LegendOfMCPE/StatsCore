<?php

namespace legendofmcpe\statscore;

use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Utils;

class StatsCore extends PluginBase implements Listener{
	private static $name = "StatsCore";
	/** @var Logger */
	private $mlogger;
	/** @var RequestList */
	private $reqList;
	/** @var OfflineMessageList */
	private $offlineInbox;
	/** @var \SQLite3 */
	private $time_db;
	public function onLoad(){
		self::$name = $this->getName();
	}
	public function onEnable(){
		$this->saveDefaultConfig();
		$this->time_db = new \SQLite3($this->getDataFolder()."ip_timezones_cache.sq3");
		$this->time_db->exec("CREATE TABLE IF NOT EXISTS ips (ip TEXT PRIMARY KEY, coords TEXT);");
		$this->time_db->exec("CREATE TABLE IF NOT EXISTS times (coords TEXT PRIMARY KEY, secs_delta INT);");
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
	public function getCoordsFromIP($ip, callable $callback){
		$result = $this->time_db->query("SELECT coords FROM ips WHERE ip = '$ip';")->fetchArray(SQLITE3_ASSOC);
		if(is_array($result) and isset($result["coords"])){
			call_user_func($callback, $result["coords"]);
			return;
		}
		if(strpos($ip, "192.168.") === 0 or $ip === "0.0.0.0" or $ip === "127.0.0.1"){
			$ip = Utils::getIP();
		}
		$this->getServer()->getScheduler()->scheduleAsyncTask(new UrlGetTask("http://ipinfo.io/$ip/json",
			function($result) use($callback){
				$data = @json_decode($result);
				if(is_array($data) and isset($data["loc"])){
					call_user_func($callback, $data["loc"]);
				}
				else{
					call_user_func($callback, false);
				}
			}, 5));
	}
	public function getTimezoneDeltaFromCoords($coords, callable $callback){
		$result = $this->time_db->query("SELECT secs_delta FROM times WHERE coords = '$coords';")->fetchArray(SQLITE3_ASSOC);
		if(is_array($result) and isset($result["secs_delta"])){
			call_user_func($callback, $result["secs_delta"]);
			return;
		}
		$time = time();
		$key = $this->getConfig()->get("google timezone api")["api key"];
		if($key === false){
			call_user_func($callback, "NO_GOOGLE_API_KEY"); // maps for business not supported yet
			return;
		}
		$this->getServer()->getScheduler()->scheduleAsyncTask(new UrlGetTask(
			"https://maps.googleapis.com/maps/api/timezone/json?location=$coords&timestamp=$time&key=$key",
			function($json) use($callback){
				if($json === false){
					call_user_func($callback, "OFFLINE");
					return;
				}
				$data = json_decode($json);
				if(strtoupper($data["status"]) === "OK"){
					call_user_func($callback, $data["rawOffset"]); // don't count daylight saving time
				}
				else{
					call_user_func($callback, $data["status"]);
				}
			}
		));
	}
	public function getTimezoneDeltaFromIP($ip, callable $callback){
		$instance = $this;
		$this->getCoordsFromIP($ip, function($coords) use($callback, $instance){
			if($coords === false){
				call_user_func($callback, 0);
				return;
			}
			$instance->getTimezoneDeltaFromCoords($coords, function($result) use($callback, $instance){
				if(!is_int($result)){
					$instance->getLogger()->alert("The following error occurred when querying from Google Timezone API: $result. Players will be assumed at timezone GMT.");
					call_user_func($callback, 0);
					return;
				}
				call_user_func($callback, $result);
			});
		});
	}
	/**
	 * @return static|null
	 */
	public static function getInstance(){
		return Server::getInstance()->getPluginManager()->getPlugin(self::$name);
	}
	// thread-blocking static API
	// do <b>not</b> call these functions from the main thread
}
