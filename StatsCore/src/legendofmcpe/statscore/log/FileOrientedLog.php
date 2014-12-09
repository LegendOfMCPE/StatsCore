<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;

abstract class FileOrientedLog extends Log{
	private $dir;
	protected function __construct(StatsCore $plugin, $dir){
		parent::__construct($plugin);
		$this->dir = $dir;
		if(is_file($this->dir)){
			throw new \RuntimeException("At StatsCore config.yml, the specified directory for " .
				(new \ReflectionClass($this))->getShortName() .
				" ($this->dir) is a log and cannot be used.");
		}
		elseif(!file_exists($this->dir)){
			mkdir($this->dir, 0777, true);
		}
	}
	protected function saveSession(LogSession $session){
		// TODO: Implement saveSession() method.
	}
	/**
	 * @param string $player
	 * @return bool
	 */
	public function hasRecords($player){
		// TODO: Implement hasRecords() method.
	}
	protected function _getChatMessageCount($lowName){
		// TODO: Implement _getChatMessageCount() method.
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected abstract function writeArray($file, array $array);
	/**
	 * @param string $file
	 * @return array
	 */
	protected abstract function readArray($file);
}
