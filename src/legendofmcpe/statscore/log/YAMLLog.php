<?php

namespace legendofmcpe\statscore\log;

class YAMLLog extends NestedArrayLog{
	/**
	 * @param string $file
	 * @return array a nested array from a log file
	 */
	protected function readFromFile($file){
		return yaml_parse(file_get_contents($file));
	}
	/**
	 * @param string $file
	 * @param array $array
	 */
	protected function writeToFile($file, array $array){
		file_put_contents($file, yaml_emit($array, YAML_UTF8_ENCODING));
	}
	protected function getPlayerFile($name){
		return realpath($this->dir.DIRECTORY_SEPARATOR.$name.".json");
	}
}
