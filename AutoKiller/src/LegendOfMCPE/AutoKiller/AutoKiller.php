<?php

namespace LegendOfMCPE\AutoKiller;

use pocketmine\plugin\PluginBase;

class AutoKiller extends PluginBase{
	public function onEnable(){
		$this->getServer()->getScheduler()->scheduleDelayedTask(new ServerKiller($this), 20 * 15);
	}
}
