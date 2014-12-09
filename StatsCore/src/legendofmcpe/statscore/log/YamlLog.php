<?php

namespace legendofmcpe\statscore\log;

use legendofmcpe\statscore\StatsCore;

class YamlLog extends FileOrientedLog{
	public function __construct(StatsCore $plugin, array $args){
		$dir = $plugin->parsePath($args["dir"]);
		parent::__construct($plugin, $dir);
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected function writeArray($file, array $array){
		yaml_emit_file($file, $array, YAML_UTF8_ENCODING);
	}
	/**
	 * @param string $file
	 * @return array
	 */
	protected function readArray($file){
		return yaml_parse_file($file);
	}
}
