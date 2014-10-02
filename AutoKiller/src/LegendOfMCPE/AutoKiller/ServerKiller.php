<?php

namespace LegendOfMCPE\AutoKiller;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\PluginTask;

class ServerKiller extends PluginTask{
	public function onRun($ticks){
		$this->getOwner()->getLogger()->alert("Checking status...");
		if(count($this->getOwnee()->getServer()->getPluginManager()->getPlugins()) >= 2) exit(0); else exit(2);
	}
}
