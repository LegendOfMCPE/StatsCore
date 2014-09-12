<?php

namespace LegendOfMCPE\AutoKiller;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\scheduler\PluginTask;

class ServerKiller extends PluginTask{
	public function onRun($ticks){
		$this->getOwner()->getServer()->dispatchCommand(new ConsoleCommandSender(), "stop");
	}
}
