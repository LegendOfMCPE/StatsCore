<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;

class JsonLog extends FileOrientedLog{
	private $flags = JSON_BIGINT_AS_STRING;
	public function __construct(StatsCore $plugin, array $args){
		parent::__construct($plugin, $plugin->parsePath($args["dir"]));
		$this->flags |= $args["pretty print"] ? JSON_PRETTY_PRINT:0;
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected function writeArray($file, array $array){
		file_put_contents($file, json_encode($array, $this->flags));
	}
	/**
	 * @param string $file
	 * @return array
	 */
	protected function readArray($file){
		return json_decode(file_get_contents($file), true);
	}
}
