<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;

class JSONLog extends NestedArrayLog{
	private $flags;
	public function __construct(StatsCore $main, $dir, $pretty){
		parent::__construct($main, $dir);
		$this->flags = JSON_BIGINT_AS_STRING;
		if($pretty){
			$this->flags |= JSON_PRETTY_PRINT;
		}
	}
	/**
	 * @param string $file
	 * @return array a nested array from a log file
	 */
	protected function readFromFile($file){
		return json_decode(file_get_contents($file));
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected function writeToFile($file, array $array){
		file_put_contents($file, json_encode($array, $this->flags));
	}
	protected function getPlayerFile($name){
		return realpath($this->dir.DIRECTORY_SEPARATOR.strtolower($name).".json");
	}
}
