<?php

namespace legendofmcpe\statscore;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class OfflineMessageList implements Listener{
	protected $messages = [];
	protected $main;
	protected $canReset = [];
	// let the unloaded plugins be garbaged.
//	/** @var Plugin[] */
//	protected $plugins = [];
	public function __construct(StatsCore $plugin){
		$this->main = $plugin;
		$this->main->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	/**
	 * @param Plugin $plugin
	 * @param Player|string $player
	 * @param $message
	 */
	public function addMessage(Plugin $plugin, $player, $message){
		if($player instanceof Player){
			$player = $player->getName();
		}
		$player = strtolower($player);
		// let the unloaded plugins be garbaged.
//		$hash = spl_object_hash($plugin);
//		$this->plugins[$hash] = $plugin;
		$hash = $this->hashPlugin($plugin);
		if(!isset($this->messages[$player])){
			$this->messages[$player] = [];
		}
		if(!isset($this->messages[$player][$hash])){
			$this->messages[$player][$hash] = [];
		}
		$this->messages[$player][$hash][] = $message;
	}
	public function onJoin(PlayerJoinEvent $event){
		$msgs = [];
		$player = strtolower($event->getPlayer()->getName());
		if(isset($this->messages[$player])){
			foreach($this->messages[$player] as $plugin => $messages){
				$object = $this->getPluginFromHash($plugin);
				if(($object instanceof Plugin) and $object->isEnabled()){
					$prefix = $object->getDescription()->getPrefix();
					foreach($messages as $msg){
						$msgs[] = "[$prefix] $msg";
					}
				}
			}
		}
		if(($cnt = count($msgs)) > 0){
			$player = $event->getPlayer();
			$player->sendMessage("[StatsCore] You have $cnt unread messages".($cnt > 1 ? "s":"").":");
			foreach(array_values($msgs) as $id => $msg){
				$player->sendMessage(($id + 1).") $msg");
			}
			$player->sendMessage("You can review ".($cnt > 1 ? "these messages":"this message")." using the command /inbox.");
			$player->sendMessage("However, note that your inbox will be cleared once you quit the game or the plugin StatsCore is disabled.");
		}
		$this->canReset[strtolower($event->getPlayer()->getName())] = true;
	}
	public function onQuit(PlayerQuitEvent $event){
		if(isset($this->canReset[$name = strtolower($event->getPlayer()->getName())])){
			$this->messages[$name] = [];
		}
		// reset the field on game leave
		// players who don't have a field will also get initialized; this doesn't affect
	}
	/**
	 * @param Plugin $plugin
	 * @return string
	 */
	protected function hashPlugin(Plugin $plugin){
		$desc = $plugin->getDescription();
		return "[".$desc->getPrefix()."]=> ".$desc->getName()."~ by ~".implode(",", $desc->getAuthors()); // how to make it as unique as possible?
	}
	/**
	 * @param string $hash
	 * @return Plugin|false
	 */
	protected function getPluginFromHash($hash){
		foreach($this->main->getServer()->getPluginManager()->getPlugins() as $plugin){
			if($this->hashPlugin($plugin) === $hash){
				return $plugin;
			}
		}
		return false;
	}
	public function onCommand(){

	}
}
