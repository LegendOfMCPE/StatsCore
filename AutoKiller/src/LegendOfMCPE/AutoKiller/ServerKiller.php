<?php

namespace LegendOfMCPE\AutoKiller;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\PluginTask;

class ServerKiller extends PluginTask{
	public function onRun($ticks){
		$this->getOwner()->getLogger()->alert("Checking status...");
		if(count($this->getOwner()->getServer()->getPluginManager()->getPlugins()) >= 2){ echo "success"; echo PHP_EOL; exit(0); } else{ echo "failure"; echo PHP_EOL; exit(2); }
	}
}
